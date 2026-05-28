<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MarkEntryGeoFenceService;
use Carbon\Carbon;

class MarkEntryLocationController extends Controller
{
    protected MarkEntryGeoFenceService $geoService;

    public function __construct(MarkEntryGeoFenceService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * Show the geofencing location capture page.
     */
    public function showVerificationPage()
    {
        return view('mark-entry.psle.location-verify');
    }

    /**
     * Verify posted location coordinates against the user's geofence.
     */
    public function verifyLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $result = $this->geoService->verifyLocation(
            $user,
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('accuracy'),
            $request->ip(),
            $request->userAgent()
        );

        if (!$result['allowed']) {
            $payload = [
                'ok' => false,
                'code' => $result['code'] ?? 'LOCATION_BLOCKED',
                'message' => $result['message'],
            ];

            if (isset($result['distance'])) {
                $payload['distance'] = $result['distance'];
            }

            if (isset($result['centre_name'])) {
                $payload['centre_name'] = $result['centre_name'];
            }

            return response()->json($payload, 423);
        }

        // Store verification details in session
        session([
            'mark_entry_location_verified_at' => Carbon::now()->toIso8601String(),
            'mark_entry_location_latitude' => $request->input('latitude'),
            'mark_entry_location_longitude' => $request->input('longitude'),
            'mark_entry_location_accuracy' => $request->input('accuracy'),
            'mark_entry_location_distance_meters' => $result['distance'] ?? null,
            'mark_entry_centre_id' => $result['centre_id'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'redirect' => \App\Http\Controllers\AuthController::redirectPathForUser($user),
        ]);
    }
}
