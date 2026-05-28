<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\MarkingCentre;
use App\Models\MarkEntryLocationLog;
use App\Models\MarkEntryGeofenceOverride;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class MarkEntryGeoFenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private User $reo;
    private Region $region;
    private MarkingCentre $centre;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure roles exist
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);

        // 2. Create Region and Marking Centre with coords in Iringa (latitude: -7.77, longitude: 35.69)
        $this->region = Region::factory()->create(['name' => 'IRINGA']);
        
        $this->centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'MC-IR-01',
            'name' => 'IFUNDA GIRLS SECONDARY SCHOOL',
            'latitude' => -7.7731234,
            'longitude' => 35.6943210,
            'allowed_radius_meters' => 50,
            'status' => 'active',
        ]);

        // 3. Create Users
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->region->id,
            'marking_centre_id' => $this->centre->id,
            'status' => 'active',
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->region->id,
            'status' => 'active',
        ]);

        // Ensure geofencing is enabled in config
        config(['mark_entry.geofence_enabled' => true]);
        config(['mark_entry.location_recheck_minutes' => 10]);
        config(['mark_entry.max_location_accuracy_meters' => 100]);
    }

    /**
     * Test A: Non-MEO Bypassed
     * Verify Admins and REOs are fully exempt from geofence restrictions and redirect logic.
     */
    public function test_admin_is_exempt_from_geofence(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        
        $response->assertStatus(200);
        $this->assertNull(session('mark_entry_location_verified_at'));
    }

    public function test_reo_is_exempt_from_geofence(): void
    {
        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=marking-centres');
        
        $response->assertStatus(200);
        $this->assertNull(session('mark_entry_location_verified_at'));
    }

    /**
     * Test B: MEO with no assigned centre is blocked.
     */
    public function test_meo_without_centre_assignment_is_blocked(): void
    {
        $unassignedOfficer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'marking_centre_id' => null,
            'status' => 'active',
        ]);

        // Accessing mark-entry page redirects to location verify page
        $response = $this->actingAs($unassignedOfficer)->get('/mark-entry/psle');
        $response->assertRedirect(route('mark-entry.location.verify.page'));

        // Post verification fails
        $responsePost = $this->actingAs($unassignedOfficer)->postJson(route('mark-entry.location.verify.submit'), [
            'latitude' => -7.7731234,
            'longitude' => 35.6943210,
            'accuracy' => 15,
        ]);

        $responsePost->assertStatus(423);
        $responsePost->assertJsonPath('ok', false);
        $responsePost->assertJsonPath('code', 'NO_CENTRE_ASSIGNMENT');

        $this->assertDatabaseHas('mark_entry_location_logs', [
            'user_id' => $unassignedOfficer->id,
            'allowed' => false,
            'reason' => 'No marking centre assignment found.',
        ]);
    }

    /**
     * Test C: MEO within 50m of assigned coordinates is allowed.
     */
    public function test_meo_within_radius_is_allowed(): void
    {
        // Coordinates exactly matching the centre
        $response = $this->actingAs($this->officer)->postJson(route('mark-entry.location.verify.submit'), [
            'latitude' => -7.7731234,
            'longitude' => 35.6943210,
            'accuracy' => 10,
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        
        // Session should be populated
        $this->assertNotNull(session('mark_entry_location_verified_at'));
        $this->assertEquals($this->centre->id, session('mark_entry_centre_id'));

        // Log should be successful
        $this->assertDatabaseHas('mark_entry_location_logs', [
            'user_id' => $this->officer->id,
            'marking_centre_id' => $this->centre->id,
            'allowed' => true,
            'reason' => 'Location verified successfully.',
        ]);
    }

    /**
     * Test D: MEO outside 50m gets denied with distance details.
     */
    public function test_meo_outside_radius_is_blocked(): void
    {
        // Distance from -7.7731234, 35.6943210 to -7.78, 35.70 is ~1000+ meters
        $response = $this->actingAs($this->officer)->postJson(route('mark-entry.location.verify.submit'), [
            'latitude' => -7.7800000,
            'longitude' => 35.7000000,
            'accuracy' => 10,
        ]);

        $response->assertStatus(423);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'OUTSIDE_RADIUS');
        $response->assertJsonStructure(['message', 'distance', 'centre_name']);

        // Log must record the rejection
        $this->assertDatabaseHas('mark_entry_location_logs', [
            'user_id' => $this->officer->id,
            'marking_centre_id' => $this->centre->id,
            'allowed' => false,
        ]);
    }

    /**
     * Test E: Poor GPS accuracy signals are blocked.
     */
    public function test_meo_with_poor_gps_accuracy_is_blocked(): void
    {
        // High accuracy meters (e.g. 150m is larger than max limit of 100m)
        $response = $this->actingAs($this->officer)->postJson(route('mark-entry.location.verify.submit'), [
            'latitude' => -7.7731234,
            'longitude' => 35.6943210,
            'accuracy' => 150,
        ]);

        $response->assertStatus(423);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'POOR_ACCURACY');

        $this->assertDatabaseHas('mark_entry_location_logs', [
            'user_id' => $this->officer->id,
            'marking_centre_id' => $this->centre->id,
            'allowed' => false,
            'accuracy_meters' => 150.0,
        ]);
    }

    /**
     * Test F: Session expiration stale check (recheck interval)
     */
    public function test_verification_expires_after_configured_minutes(): void
    {
        // Set mock initial verification 15 minutes ago (interval is 10 mins)
        session([
            'mark_entry_location_verified_at' => Carbon::now()->subMinutes(15)->toIso8601String(),
            'mark_entry_location_latitude' => -7.7731234,
            'mark_entry_location_longitude' => 35.6943210,
        ]);

        // Attempting to visit mark entry page should redirect to location verify page
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle');
        $response->assertRedirect(route('mark-entry.location.verify.page'));

        // AJAX request should return 423 Json
        $responseAjax = $this->actingAs($this->officer)->getJson('/mark-entry/psle');
        $responseAjax->assertStatus(423);
        $responseAjax->assertJsonPath('code', 'MARK_ENTRY_LOCATION_REQUIRED');
    }

    /**
     * Test G: Admin Override Bypass
     * Active override successfully permits access.
     */
    public function test_active_override_allows_bypass(): void
    {
        // Create active override
        MarkEntryGeofenceOverride::create([
            'user_id' => $this->officer->id,
            'override_by' => $this->admin->id,
            'reason' => 'Emergency GPS issue',
            'expires_at' => Carbon::now()->addHour(),
        ]);

        // Visit without any coordinates or session verification
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=start-entry');
        $response->assertStatus(200);

        // Verification service returns allowed true with override reason
        $responsePost = $this->actingAs($this->officer)->postJson(route('mark-entry.location.verify.submit'), [
            'latitude' => null,
            'longitude' => null,
            'accuracy' => null,
        ]);

        $responsePost->assertOk();
        $responsePost->assertJsonPath('ok', true);
    }

    /**
     * Test H: Expired Override Blocked
     * Override that has passed its expiration time is blocked.
     */
    public function test_expired_override_is_denied(): void
    {
        // Create expired override
        MarkEntryGeofenceOverride::create([
            'user_id' => $this->officer->id,
            'override_by' => $this->admin->id,
            'reason' => 'Emergency GPS issue expired',
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->actingAs($this->officer)->get('/mark-entry/psle');
        $response->assertRedirect(route('mark-entry.location.verify.page'));
    }

    /**
     * Test I: Coordinate Change Audited
     * Modifying centre coordinates via admin endpoints generates location logs.
     */
    public function test_admin_modifying_centre_coords_generates_audit_logs(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/' . $this->centre->id . '/update', [
            'name' => 'UPDATED IFUNDA GIRLS',
            'code' => 'MC-IR-01',
            'region_id' => $this->region->id,
            'location' => 'Iringa Town',
            'status' => 'active',
            'latitude' => -7.7850000,
            'longitude' => 35.7120000,
            'allowed_radius_meters' => 60,
        ]);

        $response->assertRedirect();

        // Database should update
        $this->assertDatabaseHas('marking_centres', [
            'id' => $this->centre->id,
            'latitude' => -7.7850000,
            'longitude' => 35.7120000,
            'allowed_radius_meters' => 60,
        ]);

        // Audit log must exist
        $this->assertDatabaseHas('mark_entry_location_logs', [
            'user_id' => $this->admin->id,
            'marking_centre_id' => $this->centre->id,
            'centre_latitude' => -7.7850000,
            'centre_longitude' => 35.7120000,
            'reason' => 'Centre coordinates/radius modified by admin',
        ]);
    }

    /**
     * Test J: Emergency override artisan command
     */
    public function test_emergency_override_artisan_command(): void
    {
        $this->artisan('mark-entry:geo-override', [
            'user' => $this->officer->email,
            '--minutes' => 45,
            '--reason' => 'Artisan override test',
        ])->assertExitCode(0);

        // Assert database override record exists
        $this->assertDatabaseHas('mark_entry_geofence_overrides', [
            'user_id' => $this->officer->id,
            'reason' => 'Artisan override test',
        ]);

        $override = MarkEntryGeofenceOverride::where('user_id', $this->officer->id)->first();
        $this->assertTrue(Carbon::parse($override->expires_at)->isFuture());
    }
}
