<?php

namespace App\Http\Middleware;

use App\Models\GovernanceAuditLog;
use Closure;
use Illuminate\Http\Request;

class LogAuthenticationEvents
{
    /**
     * Log successful login and failed login attempts
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if this is a login attempt
        if ($request->is('login') && $request->isMethod('post')) {
            // After handling, check if auth succeeded
            $response = $next($request);

            // Auth check is done in authenticate middleware
            // We'll log in the SessionGuard instead
            return $response;
        }

        // Check if logout
        if ($request->is('logout') && $request->isMethod('post')) {
            if (auth()->check()) {
                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_LOGIN_FAILED, // Using LOGOUT as logout
                    userId: auth()->id(),
                    adminId: null,
                    data: [
                        'event' => 'logout',
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'timestamp' => now()->toIso8601String(),
                    ]
                );
            }
        }

        return $next($request);
    }
}
