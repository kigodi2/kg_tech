<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\GovernanceAuditLog;
use App\Models\MarkEntryActiveSession;
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

        // 3. Bypass for logout routes to ensure the user can actually log out
        $path = $request->path();
        if (str_contains($path, 'logout') || str_contains($path, 'login') || str_starts_with($path, 'admin/')) {
            return $next($request);
        }

        // 4. Strictly restrict to mark-entry routes to avoid blocking users elsewhere
        $isMarkEntryRoute = str_starts_with($path, 'mark-entry') || 
                            str_starts_with($path, 'api/mark-entry') || 
                            str_contains($path, 'mark-entry') ||
                            $request->routeIs('mark-entry.*');

        if (!$isMarkEntryRoute) {
            return $next($request);
        }

        // 5. Retrieve or generate the device token secure forever cookie
        $deviceToken = $request->cookie('meo_device_token');
        if (!$deviceToken) {
            $deviceToken = Str::random(40);
            cookie()->queue(cookie()->forever('meo_device_token', $deviceToken, null, null, true, true));
        }

        // 6. Compute stable device hash and current hashed session ID
        $userAgent = $request->userAgent() ?? 'unknown_browser';
        $deviceHash = hash('sha256', $userAgent . '|' . $deviceToken);
        $currentSessionId = session()->getId();
        $currentSessionHash = hash('sha256', $currentSessionId);

        // Retrieve active session record for this user from database
        $activeSession = MarkEntryActiveSession::where('user_id', $user->id)->first();

        // 7. If no active session record exists, register the current session
        if (!$activeSession) {
            $activeSession = MarkEntryActiveSession::create([
                'user_id' => $user->id,
                'session_id' => $currentSessionHash,
                'device_hash' => $deviceHash,
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'last_seen_at' => now(),
                'locked_at' => now(),
            ]);

            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                userId: $user->id,
                adminId: null,
                data: [
                    'event' => 'mark_entry_session_created',
                    'session_hash' => $currentSessionHash,
                    'ip_address' => $request->ip(),
                    'user_agent_hash' => hash('sha256', $userAgent),
                ]
            );

            return $next($request);
        }

        $storedSessionId = $activeSession->session_id;
        $lastSeenAt = $activeSession->last_seen_at;

        // 8. Check if the current session ID matches the stored session ID
        if ($storedSessionId !== $currentSessionHash) {
            // Mismatch: A different session is active in the database.
            
            // Check if the request is coming from the SAME device
            $isSameDevice = $activeSession->device_hash === $deviceHash;

            // Check for Inactivity Timeout expiration (stale session takeover)
            $timeoutMinutes = config('mark_entry.single_device_timeout_minutes', 30);
            $isStale = $lastSeenAt && \Carbon\Carbon::parse($lastSeenAt)->addMinutes($timeoutMinutes)->isPast();

            if ($isSameDevice || $isStale) {
                // Same-device or Stale takeover allowed: replace previous session
                $activeSession->delete();

                MarkEntryActiveSession::create([
                    'user_id' => $user->id,
                    'session_id' => $currentSessionHash,
                    'device_hash' => $deviceHash,
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                    'last_seen_at' => now(),
                    'locked_at' => now(),
                ]);

                GovernanceAuditLog::log(
                    GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL,
                    userId: $user->id,
                    adminId: null,
                    data: [
                        'event' => $isSameDevice ? 'mark_entry_session_recreated_same_device' : 'mark_entry_session_stale_replaced',
                        'previous_active_ip' => $activeSession->ip_address,
                        'new_ip' => $request->ip(),
                        'session_hash' => $currentSessionHash,
                        'user_agent_hash' => hash('sha256', $userAgent),
                    ]
                );

                return $next($request);
            }

            // Otherwise: This second session/device is STRICTLY BLOCKED!
            // Terminate the new/mismatched session immediately to prevent access.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_FAILED,
                userId: $user->id,
                adminId: null,
                data: [
                    'event' => 'mark_entry_session_blocked',
                    'active_ip' => $activeSession->ip_address,
                    'attempted_ip' => $request->ip(),
                    'session_hash' => $currentSessionHash,
                    'user_agent_hash' => hash('sha256', $userAgent),
                ]
            );

            $blockMessage = "This Mark Entry Officer account is already active on another device. Active IP: {$activeSession->ip_address}. To protect mark entry speed and data consistency, only one active device is allowed per account. Please log out from the active device or contact the administrator.";

            // Handle AJAX/JSON requests
            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'code' => 'MARK_ENTRY_ACCOUNT_ALREADY_ACTIVE',
                    'active_ip' => $activeSession->ip_address,
                    'message' => $blockMessage
                ], 423);
            }

            // Handle normal HTML page requests
            return redirect()->route('login')->withErrors([
                'email' => $blockMessage
            ]);
        }

        // 9. Session IDs match: Refresh last seen timestamp (rate-limited to every 60 seconds to optimize DB speed)
        $now = now();
        if (!$lastSeenAt || $now->diffInSeconds($lastSeenAt) > 60) {
            $activeSession->update(['last_seen_at' => $now]);
        }

        return $next($request);
    }
}
