<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCustomerEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $customer = $request->user('customer');
        if ($customer && ! $customer->hasVerifiedEmail()) {
            return redirect()->route('customer.verification.notice');
        }

        return $next($request);
    }
}
