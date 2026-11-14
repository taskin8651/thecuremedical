<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerAccountController extends Controller
{
    public function registerForm()
    {
        return Auth::guard('customer')->check()
            ? redirect()->route('customer.orders.index')
            : view('site.customer.register');
    }

    public function register(Request $request, CartService $carts)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::query()->where('email', Str::lower($data['email']))->lockForUpdate()->first();
            if ($customer && $customer->password) {
                throw ValidationException::withMessages(['email' => 'An account already exists for this email. Please sign in.']);
            }
            if ($customer && $customer->status !== 'active') {
                throw ValidationException::withMessages(['email' => 'This account cannot be registered. Contact the store.']);
            }

            $customer ??= new Customer;
            $customer->forceFill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => Str::lower($data['email']),
                'password' => Hash::make($data['password']),
                'email_verified_at' => null,
                'status' => 'active',
            ])->save();

            return $customer;
        });

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $carts->current($request);
        try {
            $customer->sendEmailVerificationNotification();
            $status = 'We sent an email verification link.';
        } catch (Throwable $e) {
            report($e);
            $status = null;
        }

        return redirect()->route('customer.verification.notice')
            ->with('status', $status)
            ->with('mail_error', $status ? null : 'We could not send email right now. The account is saved; configure MAIL_* settings and request a new link.');
    }

    public function loginForm()
    {
        return Auth::guard('customer')->check()
            ? redirect()->route('customer.orders.index')
            : view('site.customer.login');
    }

    public function login(Request $request, CartService $carts)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = Str::lower($data['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many sign-in attempts. Please try again shortly.']);
        }
        if (! Auth::guard('customer')->attempt($data + ['status' => 'active'], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active account.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $carts->current($request);

        return redirect()->intended(route('customer.orders.index'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('site.home')->with('status', 'You have been signed out.');
    }

    public function verificationNotice()
    {
        return view('site.customer.verify-email', ['customer' => Auth::guard('customer')->user()]);
    }

    public function resendVerification(Request $request)
    {
        $customer = $request->user('customer');
        if (! $customer->hasVerifiedEmail()) {
            try {
                $customer->sendEmailVerificationNotification();
            } catch (Throwable $e) {
                report($e);

                return back()->with('mail_error', 'We could not send email right now. Please try again after mail settings are configured.');
            }
        }

        return back()->with('status', 'If your account needs verification, a new link has been sent.');
    }

    public function verify(Request $request, Customer $customer, string $hash)
    {
        abort_unless($request->hasValidSignature() && hash_equals(sha1($customer->getEmailForVerification()), $hash), 403);
        abort_unless($customer->status === 'active', 403);

        if (! $customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
            event(new \Illuminate\Auth\Events\Verified($customer));
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.orders.index')->with('status', 'Your email address is verified.');
    }

    public function profile(Request $request)
    {
        return view('site.customer.profile', ['customer' => $request->user('customer')]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:12'],
        ]);
        $request->user('customer')->update($data);

        return back()->with('status', 'Your account details have been updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);
        $request->user('customer')->forceFill(['password' => Hash::make($data['password'])])->save();

        return back()->with('status', 'Your password has been changed.');
    }

    public function orders(Request $request)
    {
        return view('site.customer.orders', [
            'orders' => Order::query()->where('customer_id', $request->user('customer')->id)
                ->withCount('items')->latest('id')->paginate(12),
        ]);
    }

    public function order(Request $request, string $order)
    {
        $order = Order::query()->with(['items.product', 'shippingZone'])
            ->where('customer_id', $request->user('customer')->id)->where('order_number', $order)->firstOrFail();

        return view('site.customer.order', ['order' => $order]);
    }

    public function cancelOrder(Request $request, string $order)
    {
        $order = Order::query()->where('customer_id', $request->user('customer')->id)
            ->where('order_number', $order)->firstOrFail();
        abort_unless($order->status === 'pending' && $order->payments()->doesntExist(), 403, 'This order can no longer be cancelled online. Contact the store for help.');
        $order->update(['status' => 'cancelled', 'notes' => trim(($order->notes ? $order->notes."\n" : '').'Cancelled by customer on '.now()->toDateTimeString())]);

        return redirect()->route('customer.orders.show', $order->order_number)->with('status', 'Your pending order has been cancelled.');
    }

    public function forgotForm()
    {
        return view('site.customer.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:150']]);
        try {
            Password::broker('customers')->sendResetLink($request->only('email'));
        } catch (Throwable $e) {
            report($e);

            return back()->with('mail_error', 'We could not send email right now. Please try again after mail settings are configured.');
        }

        return back()->with('status', 'If an account uses that email, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('site.customer.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $status = Password::broker('customers')->reset($data, function (Customer $customer, string $password) {
            $customer->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($customer));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        return redirect()->route('customer.login')->with('status', 'Your password has been reset. Please sign in.');
    }
}
