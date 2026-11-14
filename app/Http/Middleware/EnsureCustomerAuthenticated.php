<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCustomerAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('customer');
        if (! $guard->check()) {
            return redirect()->guest(route('customer.login'));
        }
        if ($guard->user()->status !== 'active') {
            $guard->logout();

            return redirect()->route('customer.login')->withErrors(['email' => 'This account is unavailable. Contact the store.']);
        }

        return $next($request);
    }
}
