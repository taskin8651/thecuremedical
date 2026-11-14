<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Forgot / reset password for staff. The reply never reveals whether an email exists.
 * Reset links are sent with the configured mailer (MAIL_MAILER=log writes them to storage/logs).
 */
class PasswordController extends Controller
{
    public function request()
    {
        return view('admin.auth.forgot');
    }

    public function email(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);

        Password::sendResetLink($data);

        return back()->with('status', 'If that email belongs to an active account, a reset link has been sent.');
    }

    public function reset(Request $request, string $token)
    {
        return view('admin.auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
            event(new PasswordReset($user));
            ActivityLog::record('password_reset', $user, $user->name.' reset their password');
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Your password has been reset. Please sign in.')
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
