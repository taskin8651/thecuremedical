<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('admin.profile.edit', ['user' => $request->user()->load('roles')]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $user->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($data['password'])]);
        // Signs out every other browser/device that is using this account.
        Auth::logoutOtherDevices($data['password']);
        ActivityLog::record('password_changed', $user, $user->name.' changed their password');

        return back()->with('success', 'Password changed. Other devices were signed out.');
    }

    public function logoutOthers(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        Auth::logoutOtherDevices($request->input('password'));
        ActivityLog::record('sessions_revoked', $request->user(), 'Signed out of other devices');

        return back()->with('success', 'Signed out of all other devices.');
    }
}
