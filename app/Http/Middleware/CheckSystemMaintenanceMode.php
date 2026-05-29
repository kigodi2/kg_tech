<?php

namespace App\Http\Middleware;

use App\Helpers\SystemSettingsHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSystemMaintenanceMode
{
    private const ADMIN_EMAIL = 'agreykigodi@gmail.com';

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Exclude assets so CSS, JS, images, and fonts load correctly on the maintenance page
        if ($this->isAssetRequest($request)) {
            return $next($request);
        }

        // 2. If maintenance mode is OFF, continue normally
        if (! $this->maintenanceModeIsEnabled()) {
            return $next($request);
        }

        // 3. If current user is the whitelisted allowed admin, bypass the block
        if ($this->isAllowedAdmin($request)) {
            return $next($request);
        }

        // 4. Redirect guest admins trying to access the admin base path to login
        if (! $request->user() && $request->is('admin')) {
            return redirect('/admin/login');
        }

        // 5. Allow essential authentication, login/logout routes
        if ($this->isAllowedAuthRoute($request)) {
            return $next($request);
        }

        // 6. Return professional JSON for API, AJAX, or requests expecting JSON
        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'message' => 'System is under maintenance',
                'reason' => 'Server migration and performance upgrade after high traffic challenge on 28th May, 2026'
            ], 503);
        }

        // 7. For normal web routes, render the premium maintenance Blade view
        return response()
            ->view('errors.maintenance', [], 503)
            ->header('Retry-After', '10800');
    }

    private function maintenanceModeIsEnabled(): bool
    {
        try {
            return filter_var(
                SystemSettingsHelper::getSetting('maintenance_mode', false),
                FILTER_VALIDATE_BOOLEAN
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private function isAllowedAdmin(Request $request): bool
    {
        $email = strtolower((string) $request->user()?->email);

        return $email === self::ADMIN_EMAIL;
    }

    private function isAssetRequest(Request $request): bool
    {
        $path = $request->path();

        // Check common asset directories
        if ($request->is('build/*', 'assets/*', 'storage/*', 'images/*', 'favicon.ico')) {
            return true;
        }

        // Check common asset file extensions
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/i', $path)) {
            return true;
        }

        return false;
    }

    private function isAllowedAuthRoute(Request $request): bool
    {
        if (! $request->user() && $request->is('/', 'home', 'admin')) {
            return true;
        }

        if ($request->is('livewire/*')) {
            return ! $request->user() || $this->isAllowedAdmin($request);
        }

        if ($request->routeIs(
            'login',
            'logout',
            'auth.check-admin-email',
            'auth.github.redirect',
            'auth.github.callback',
            'filament.admin.auth.login',
            'filament.admin.auth.logout'
        )) {
            return true;
        }

        return $request->is(
            'login',
            'logout',
            'check-admin-email',
            'auth/github',
            'auth/github/callback',
            'admin/login',
            'admin/logout',
            'session-heartbeat'
        );
    }
}
