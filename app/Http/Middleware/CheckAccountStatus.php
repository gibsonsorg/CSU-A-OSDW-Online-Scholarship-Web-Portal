<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Pending accounts must complete email verification first.
            if ($user->account_status === 'pending') {
                auth()->logout();
                return redirect(route('login'))->with('error', 'Please verify your email before logging in.');
            }

            // If user account is rejected, logout and redirect to login
            if ($user->account_status === 'rejected') {
                auth()->logout();
                return redirect(route('login'))->with('error', 'Your account application was rejected. Reason: ' . ($user->rejection_reason ?? 'Not specified'));
            }
        }

        return $next($request);
    }
}
