<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictMainSystemAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        if (in_array($user->portal_role, ['mock_headteacher', 'mock_dao', 'mock_rao', 'mock_secretariat'], true)) {
            // Check if it's already a mock portal route or mark entry route
            if (!$request->is('mock-portal*') && !$request->is('mark-entry*') && !$request->is('api/mark-entry*')) {
                if ($this->expectsJsonResponse($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not authorized to access this area.',
                    ], 403);
                }

                return redirect('/mock-portal');
            }
        }

        // Subject Panel Leaders are restricted to their own portal only
        if ($user->portal_role === 'subject_panel_leader') {
            if (!$request->is('subject-panel*') && !$request->is('logout')) {
                if ($this->expectsJsonResponse($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not authorized to access this area.',
                    ], 403);
                }

                return redirect()->route('subject-panel.verification.index');
            }
        }

        if (\App\Http\Controllers\AuthController::shouldRedirectToMarkEntry($user)) {
            if (
                !$request->is('mark-entry*')
                && !$request->is('api/mark-entry*')
                && !$request->is('api/candidates/import*')
                && !$request->is('logout')
            ) {
                if ($this->expectsJsonResponse($request)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not authorized to access this area.',
                    ], 403);
                }

                return redirect('/mark-entry/psle?view=overview');
            }
        }

        return $next($request);
    }

    private function expectsJsonResponse(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->is('api/*');
    }
}
