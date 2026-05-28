<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CheckSystemMaintenanceMode::class,
            \App\Http\Middleware\SetExamYearContext::class,
            \App\Http\Middleware\PreventAuthenticatedResponseCache::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\CheckSystemMaintenanceMode::class,
        ]);

        // Trust all proxies (needed for irms.ac.tz HTTPS behind nginx/load balancer)
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (Request $request) {
            // If guest hits a mock-portal protected route → send to mock-portal login
            if (str_starts_with($request->path(), 'mock-portal/')) {
                return route('mock-portal.login');
            }
            return route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if (auth()->check()) {
                if ($request->is('login') && $request->isMethod('post') && $request->filled('role')) {
                    session(['active_role' => $request->input('role')]);
                }

                return \App\Http\Controllers\AuthController::redirectPathForUser(auth()->user());
            }

            return '/dashboard';
        });
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'exam-admin-access' => \App\Http\Middleware\RestrictExamAdminAccess::class,
            'main-system' => \App\Http\Middleware\RestrictMainSystemAccess::class,
            'user' => \App\Http\Middleware\UserMiddleware::class,
            'single-device' => \App\Http\Middleware\EnsureSingleMarkEntryDevice::class,
        ]);
    })
    ->withProviders([
        \App\Providers\ScheduleServiceProvider::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                    'error' => 'CSRF_EXPIRED'
                ], 419);
            }

            if ($request->isMethod('POST')) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'email' => 'Your session expired. Please refresh the page and try again.',
                    ])
                    ->withInput();
            }

            return null;
        });
    })->create();
