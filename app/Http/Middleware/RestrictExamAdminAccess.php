<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictExamAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $email = strtolower((string) $user->email);
        $roleCode = strtolower((string) ($user->role?->code ?? ''));
        $roleName = strtolower((string) ($user->role?->name ?? ''));
        $portalRole = strtolower((string) $user->portal_role);

        $allowed = $email === 'agreykigodi@gmail.com'
            || (bool) $user->is_admin
            || $user->isAdmin()
            || in_array($portalRole, ['admin', 'super_admin', 'system_admin'], true)
            || in_array($roleCode, ['admin', 'super_admin', 'system_admin'], true)
            || in_array($roleName, ['admin', 'administrator', 'super admin', 'system admin'], true);

        if (! $allowed) {
            abort(403, 'Access restricted to system administrators.');
        }

        return $next($request);
    }
}
