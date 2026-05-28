<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MarkEntryGeofenceOverride;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarkEntryGeoFence
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Bypass if geofencing is disabled in config
        if (!config('mark_entry.geofence_enabled', true)) {
            return $next($request);
        }

        // 2. Bypass if guest or not a Mark Entry Officer
        if (!$user || !$user->isMarkEntryOfficer()) {
            return $next($request);
        }

        // 3. Bypass logout, verification itself, and non-mark-entry routes
        $path = $request->path();
        if (str_contains($path, 'logout') || str_contains($path, 'location/verify') || str_starts_with($path, 'admin/')) {
            return $next($request);
        }

        $isMarkEntryRoute = str_starts_with($path, 'mark-entry') || 
                            str_starts_with($path, 'api/mark-entry') || 
                            str_contains($path, 'mark-entry') ||
                            $request->routeIs('mark-entry.*');

        if (!$isMarkEntryRoute) {
            return $next($request);
        }

        // 4. Check active admin geofence overrides
        $override = MarkEntryGeofenceOverride::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->exists();

        if ($override) {
            return $next($request);
        }

        // 5. Check session for recent verification
        $verifiedAt = session('mark_entry_location_verified_at');
        $recheckMinutes = config('mark_entry.location_recheck_minutes', 10);

        $isStale = !$verifiedAt || Carbon::parse($verifiedAt)->addMinutes($recheckMinutes)->isPast();

        if ($isStale) {
            // Force re-verification
            $blockMessage = "Location verification is required or has expired. Please verify your device location to continue mark entry.";

            if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'code' => 'MARK_ENTRY_LOCATION_REQUIRED',
                    'message' => $blockMessage
                ], 423);
            }

            return redirect()->route('mark-entry.location.verify.page');
        }

        return $next($request);
    }
}
