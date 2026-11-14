<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show()
    {
        return view('admin.auth.login');
    }

    public function attempt(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many sign-in attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            ActivityLog::record('login_failed', null, 'Failed sign-in for '.Str::limit($credentials['email'], 100, ''));

            // One message for every failure so accounts cannot be probed.
            throw ValidationException::withMessages(['email' => 'These credentials do not match an active account.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
        ActivityLog::record('login', $user, $user->name.' signed in');

        return redirect()->intended($this->homeFor($user));
    }

    public function logout(Request $request)
    {
        ActivityLog::record('logout', $request->user(), $request->user()->name.' signed out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }

    /** First screen the member of staff is allowed to open. */
    private function homeFor($user): string
    {
        if ($user->can('dashboard.view')) {
            return route('admin.dashboard');
        }
        foreach (config('cure.nav') as $group) {
            foreach ($group['items'] as [, , $route, $permission]) {
                if ($user->can($permission)) {
                    return route($route);
                }
            }
        }

        return route('admin.profile');
    }
}
