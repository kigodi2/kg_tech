<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePasswordChange
{
    /**
     * Enforce password change on first login
     *
     * If user has password_reset_required = true, redirect to password change page
     * UNLESS they are already on that page
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // If password reset is required and they're not on the change-password page
        if ($user->password_reset_required && !$this->isPasswordChangePage($request)) {
            return redirect()->route('password.change-required');
        }

        return $next($request);
    }

    /**
     * Check if current route is the password change page
     */
    protected function isPasswordChangePage(Request $request): bool
    {
        return $request->routeIs('password.change-required') 
            || $request->routeIs('password.update-required');
    }
}
