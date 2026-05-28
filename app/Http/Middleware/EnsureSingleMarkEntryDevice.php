<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\GovernanceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureSingleMarkEntryDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Bypass if single-device restriction is disabled in config
        if (!config('mark_entry.enable_single_device_restriction', true)) {
            return $next($request);
        }

        // 2. Bypass if guest or not a Mark Entry Officer
        if (!$user || !$user->isMarkEntryOfficer()) {
            return $next($request);
        }

        // 2. Bypass for logout routes to ensure the user can actually log out
        $path = $request->path();
        if (str_contains($path, 'logout') || str_contains($path, 'login') || str_starts_with($path, 'admin/')) {
            return $next($request);
        }

        // 3. Strictly restrict to mark-entry routes to avoid blocking users elsewhere
        $isMarkEntryRoute = str_starts_with($path, 'mark-entry') || 
                            str_starts_with($path, 'api/mark-entry') || 
                            str_contains($path, 'mark-entry') ||
                            $request->routeIs('mark-entry.*');

        if (!$isMarkEntryRoute) {
            return $next($request);
        }

        // 4. Retrieve or generate the device token secure forever cookie
        $deviceToken = $request->cookie('meo_device_token');
        $newCookieQueued = false;
        
        if (!$deviceToken) {
            $deviceToken = Str::random(40);
            cookie()->queue(cookie()->forever('meo_device_token', $deviceToken, null, null, true, true));
            $newCookieQueued = true;
        }

        // 5. Compute stable device hash
        $userAgent = $request->userAgent() ?? 'unknown_browser';
        $deviceHash = hash('sha256', $userAgent . '|' . $deviceToken);
        $currentSessionId = session()->getId();

        // Refresh reference in DB to avoid stale object properties
        $freshUser = User::find($user->id);
        if (!$freshUser) {
            return $next($request);
        }

        $storedSessionId = $freshUser->mark_entry_session_id;
        $storedDeviceHash = $freshUser->mark_entry_device_hash;
        $lastSeenAt = $freshUser->mark_entry_last_seen_at;

        // 6. If no active session fields are stored, auto-register the current session
        if (!$storedSessionId) {
            $freshUser->update([
                'mark_entry_session_id' => $currentSessionId,
                'mark_entry_device_hash' => $deviceHash,
                'mark_entry_last_seen_at' => now(),
            ]);

            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                userId: $user->id,
                adminId: null,
                data: [
                    'event' => 'meo_device_session_registered',
                    'session_id_hash' => hash('sha256', $currentSessionId),
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                ]
            );

            return $next($request);
        }

        // 7. Check if the current session ID matches the stored session ID
        if ($storedSessionId !== $currentSessionId) {
            // Mismatch: A different session is active in the database.
            
            // Check for Inactivity Timeout expiration (stale session takeover)
            $timeoutMinutes = config('mark_entry.single_device_timeout_minutes', 30);
            $isStale = $lastSeenAt && \Carbon\Carbon::parse($lastSeenAt)->addMinutes($timeoutMinutes)->isPast();

            if ($isStale) {
                // Stale takeover allowed: overwriting previous inactive session
                $freshUser->update([
                    'mark_entry_session_id' => $currentSessionId,
                    'mark_entry_device_hash' => $deviceHash,
                    'mark_entry_last_seen_at' => now(),
                ]);

                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                    userId: $user->id,
                    adminId: null,
                    data: [
                        'event' => 'meo_session_stale_takeover',
                        'old_session_id_hash' => hash('sha256', $storedSessionId),
                        'new_session_id_hash' => hash('sha256', $currentSessionId),
                        'ip_address' => $request->ip(),
                        'user_agent' => $userAgent,
                    ]
                );

                return $next($request);
            }

            // Otherwise, this current session has been replaced by a newer login on another device!
            // Terminate current mismatched session immediately
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_FAILED,
                userId: $user->id,
                adminId: null,
                data: [
                    'event' => 'meo_session_replaced_lockout',
                    'mismatched_session_id_hash' => hash('sha256', $currentSessionId),
                    'stored_active_session_id_hash' => hash('sha256', $storedSessionId),
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                ]
            );

            // Handle AJAX/JSON requests
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'code' => 'MARK_ENTRY_SESSION_REPLACED',
                    'message' => 'This Mark Entry Officer account is active on another device. Please log in again on this device if you want to continue.'
                ], 423);
            }

            // Handle normal HTML page requests
            return redirect()->route('login')->withErrors([
                'email' => 'Your mark entry session has ended because this account was opened on another device.'
            ]);
        }

        // 8. Session IDs match: Refresh last seen timestamp (rate-limited to every 60 seconds to optimize DB speed)
        $now = now();
        if (!$lastSeenAt || $now->diffInSeconds($lastSeenAt) > 60) {
            $freshUser->update(['mark_entry_last_seen_at' => $now]);
        }

        return $next($request);
    }
}
