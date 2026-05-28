<?php

namespace App\Services;

use App\Models\User;
use App\Models\MarkingCentre;
use App\Models\MarkEntryLocationLog;
use App\Models\MarkEntryGeofenceOverride;
use Carbon\Carbon;

class MarkEntryGeoFenceService
{
    /**
     * Verify if the Mark Entry Officer is allowed access from these coordinates.
     *
     * @param User $user
     * @param float|null $lat
     * @param float|null $lon
     * @param float|null $accuracy
     * @param string $ip
     * @param string $userAgent
     * @return array
     */
    public function verifyLocation(User $user, ?float $lat, ?float $lon, ?float $accuracy, string $ip, string $userAgent): array
    {
        // 1. Check for active admin overrides
        $override = MarkEntryGeofenceOverride::where('user_id', $user->id)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($override) {
            $this->logAttempt($user, null, $lat, $lon, null, $accuracy, $ip, $userAgent, true, 'Admin override active until: ' . $override->expires_at);
            return ['allowed' => true, 'reason' => 'override', 'expires_at' => $override->expires_at->toIso8601String()];
        }

        // 2. Resolve user's marking centre
        $centre = $this->resolveUserCentre($user);
        if (!$centre) {
            $this->logAttempt($user, null, $lat, $lon, null, $accuracy, $ip, $userAgent, false, 'No marking centre assignment found.');
            return ['allowed' => false, 'code' => 'NO_CENTRE_ASSIGNMENT', 'message' => 'No approved marking centre assignment found. Please contact the administrator.'];
        }

        // 3. Ensure coordinates are supplied
        if ($lat === null || $lon === null) {
            $this->logAttempt($user, $centre, $lat, $lon, null, $accuracy, $ip, $userAgent, false, 'Missing coordinates.');
            return ['allowed' => false, 'code' => 'MISSING_GPS', 'message' => 'GPS coordinates are missing. Please allow location permissions.'];
        }

        // 4. Ensure centre coordinates exist
        if ($centre->latitude === null || $centre->longitude === null) {
            $this->logAttempt($user, $centre, $lat, $lon, null, $accuracy, $ip, $userAgent, false, 'Marking centre lacks GPS configuration.');
            return ['allowed' => false, 'code' => 'CENTRE_GPS_MISSING', 'message' => "The marking centre '{$centre->name}' coordinates are not yet set by an administrator."];
        }

        // 5. Validate location accuracy
        $maxAccuracy = config('mark_entry.max_location_accuracy_meters', 100);
        if ($accuracy !== null && $accuracy > $maxAccuracy) {
            $this->logAttempt($user, $centre, $lat, $lon, null, $accuracy, $ip, $userAgent, false, "Accuracy poor: {$accuracy}m.");
            return ['allowed' => false, 'code' => 'POOR_ACCURACY', 'message' => 'Your device location accuracy is too low for secure mark entry. Please enable GPS/high accuracy location and try again.'];
        }

        // 6. Compute distance using Haversine formula
        $distance = $this->distanceInMeters($lat, $lon, (float)$centre->latitude, (float)$centre->longitude);
        $radius = $centre->allowed_radius_meters ?? config('mark_entry.default_radius_meters', 50);

        if ($distance > $radius) {
            $this->logAttempt($user, $centre, $lat, $lon, $distance, $accuracy, $ip, $userAgent, false, "Outside radius: {$distance}m from '{$centre->name}'.");
            return [
                'allowed' => false,
                'code' => 'OUTSIDE_RADIUS',
                'distance' => round($distance, 1),
                'centre_name' => $centre->name,
                'message' => "Access denied. This Mark Entry Officer account can only be used within {$radius} metres of the assigned marking centre. Your current distance is approximately " . round($distance, 0) . " metres from '{$centre->name}'. Please report to the approved marking centre or contact the administrator."
            ];
        }

        // 7. Successful check!
        $this->logAttempt($user, $centre, $lat, $lon, $distance, $accuracy, $ip, $userAgent, true, 'Location verified successfully.');
        return ['allowed' => true, 'centre_id' => $centre->id, 'distance' => $distance];
    }

    private function resolveUserCentre(User $user): ?MarkingCentre
    {
        if ($user->marking_centre_id) {
            return MarkingCentre::find($user->marking_centre_id);
        }
        $activeAssignment = $user->markEntryAssignments()->where('status', 'active')->first();
        return $activeAssignment ? $activeAssignment->markingCentre : null;
    }

    private function distanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1) * cos($lat2)
            * sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function logAttempt(User $user, ?MarkingCentre $centre, ?float $attemptLat, ?float $attemptLon, ?float $distance, ?float $accuracy, string $ip, string $userAgent, bool $allowed, string $reason): void
    {
        MarkEntryLocationLog::create([
            'user_id' => $user->id,
            'marking_centre_id' => $centre?->id,
            'attempted_latitude' => $attemptLat,
            'attempted_longitude' => $attemptLon,
            'centre_latitude' => $centre?->latitude,
            'centre_longitude' => $centre?->longitude,
            'distance_meters' => $distance,
            'accuracy_meters' => $accuracy,
            'ip_address' => $ip,
            'user_agent_hash' => hash('sha256', $userAgent),
            'allowed' => $allowed,
            'reason' => $reason
        ]);
    }
}
