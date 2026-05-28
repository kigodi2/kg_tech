<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\MarkingCentre;
use App\Models\MarkEntryActiveSession;
use App\Models\GovernanceAuditLog;
use App\Helpers\MarkEntrySettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MarkEntryGeoFenceToggleTest extends TestCase
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

        // 1. Seed Roles
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);

        // 2. Create Region and Marking Centre in Iringa
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
            'email' => 'agreykigodi@gmail.com', // Admin override email
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->region->id,
            'marking_centre_id' => $this->centre->id,
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->region->id,
            'status' => 'active',
            'password' => Hash::make('password123'),
        ]);

        // Ensure geofencing is enabled by default in config
        config(['mark_entry.geofence_enabled' => true]);
        config(['mark_entry.enable_single_device_restriction' => true]);
    }

    /**
     * Test A: Admin can view geofence toggle.
     */
    public function test_admin_can_view_geofence_toggle(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=marking-centres');
        
        $response->assertStatus(200);
        $response->assertSee('Location Restriction Control');
        $response->assertSee('Disable Location Restriction');
    }

    /**
     * Test B: Mark Entry Officer cannot view geofence toggle.
     */
    public function test_meo_cannot_view_geofence_toggle(): void
    {
        // Officer view of marking-centres is blocked by role permission middleware
        $response = $this->actingAs($this->officer)
            ->withSession(['mark_entry_location_verified_at' => Carbon::now()->toIso8601String()])
            ->get('/mark-entry/psle?view=marking-centres');
        
        $response->assertRedirect('/mark-entry/psle');
        $response->assertSessionHas('warning');
    }

    /**
     * Test C: Admin can disable geofence.
     */
    public function test_admin_can_disable_geofence(): void
    {
        // 1. Initially enabled
        $this->assertTrue(MarkEntrySettings::geofenceEnabled());

        // 2. Disable via API POST route
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
            'enabled' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('enabled', false);

        // 3. Setting should be disabled in helper/DB
        $this->assertFalse(MarkEntrySettings::geofenceEnabled());
    }

    /**
     * Test D: Admin can enable geofence.
     */
    public function test_admin_can_enable_geofence(): void
    {
        // 1. Manually disable first
        MarkEntrySettings::setGeofenceEnabled(false);
        $this->assertFalse(MarkEntrySettings::geofenceEnabled());

        // 2. Enable via API POST route
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
            'enabled' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('enabled', true);

        // 3. Setting should be enabled in helper/DB
        $this->assertTrue(MarkEntrySettings::geofenceEnabled());
    }

    /**
     * Test E: Non-admin cannot toggle geofence.
     */
    public function test_non_admin_cannot_toggle_geofence(): void
    {
        // 1. Officer attempt blocked
        $responseOfficer = $this->actingAs($this->officer)
            ->withSession(['mark_entry_location_verified_at' => Carbon::now()->toIso8601String()])
            ->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
                'enabled' => false,
            ]);
        $responseOfficer->assertStatus(403);

        // 2. REO attempt blocked
        $responseReo = $this->actingAs($this->reo)->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
            'enabled' => false,
        ]);
        $responseReo->assertStatus(403);

        // 3. Verify it is still enabled
        $this->assertTrue(MarkEntrySettings::geofenceEnabled());
    }

    /**
     * Test F: When geofence is enabled, MEO outside radius is blocked.
     */
    public function test_meo_outside_radius_is_blocked_when_enabled(): void
    {
        // 1. Ensure enabled
        MarkEntrySettings::setGeofenceEnabled(true);

        // 2. MEO has no cached session coordinates, attempting to access mark-entry redirects to verify page
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle');
        $response->assertRedirect(route('mark-entry.location.verify.page'));

        // 3. Re-verification fails for coordinates outside 50m
        $responsePost = $this->actingAs($this->officer)->postJson(route('mark-entry.location.verify.submit'), [
            'latitude' => -7.7800000,
            'longitude' => 35.7000000,
            'accuracy' => 10,
        ]);

        $responsePost->assertStatus(423);
        $responsePost->assertJsonPath('ok', false);
        $responsePost->assertJsonPath('code', 'OUTSIDE_RADIUS');
    }

    /**
     * Test G: When geofence is disabled, MEO can access mark entry without GPS verification.
     */
    public function test_meo_exempt_from_gps_when_disabled(): void
    {
        // 1. Disable geofence restriction
        MarkEntrySettings::setGeofenceEnabled(false);

        // 2. MEO has no cached coordinates, but can access mark entry page successfully
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=start-entry');
        $response->assertStatus(200);

        // 3. Verification screen shows disabled notice
        $responseVerify = $this->actingAs($this->officer)->get(route('mark-entry.location.verify.page'));
        $responseVerify->assertStatus(200);
        $responseVerify->assertSee('Location Verification Disabled');
    }

    /**
     * Test H: When geofence is disabled, one-device restriction still blocks second device.
     */
    public function test_one_device_restriction_remains_active_when_geofence_disabled(): void
    {
        // 1. Disable geofence restriction
        MarkEntrySettings::setGeofenceEnabled(false);

        // 2. Device 1 logs in
        $responseLogin1 = $this->post('/login', [
            'email' => $this->officer->email,
            'password' => 'password123',
        ]);
        $responseLogin1->assertRedirect();
        
        $session = MarkEntryActiveSession::where('user_id', $this->officer->id)->first();
        $this->assertNotNull($session);

        Auth::logout();
        session()->invalidate();

        // 3. Device 2 attempts login with the same account -> gets blocked
        $responseLogin2 = $this->post('/login', [
            'email' => $this->officer->email,
            'password' => 'password123',
        ], [
            'REMOTE_ADDR' => '192.168.1.100',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
        ]);
        
        $responseLogin2->assertRedirect();
        $responseLogin2->assertSessionHasErrors(['email']);
        $this->assertStringContainsString('already active on another device', session()->get('errors')->first('email'));
    }

    /**
     * Test I: Toggle action is audited in GovernanceAuditLog.
     */
    public function test_toggle_action_is_audited(): void
    {
        // Disable geofence
        $this->actingAs($this->admin)->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
            'enabled' => false,
        ]);

        $this->assertDatabaseHas('governance_audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'mark_entry_geofence_disabled',
        ]);

        // Enable geofence
        $this->actingAs($this->admin)->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
            'enabled' => true,
        ]);

        $this->assertDatabaseHas('governance_audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'mark_entry_geofence_enabled',
        ]);
    }

    /**
     * Test J: Disabling geofence does not delete centre coordinates or assignments.
     */
    public function test_disabling_geofence_preserves_coordinates_and_assignments(): void
    {
        // 1. Confirm coordinates exist in DB
        $this->assertEquals(-7.7731234, (float)$this->centre->latitude);
        $this->assertEquals(35.6943210, (float)$this->centre->longitude);

        // 2. Disable geofence
        $this->actingAs($this->admin)->postJson('/mark-entry/psle/marking-centres/geofence-toggle', [
            'enabled' => false,
        ]);

        // 3. Refresh and verify coordinates are still completely intact
        $freshCentre = $this->centre->fresh();
        $this->assertEquals(-7.7731234, (float)$freshCentre->latitude);
        $this->assertEquals(35.6943210, (float)$freshCentre->longitude);
        $this->assertEquals(50, $freshCentre->allowed_radius_meters);
        $this->assertEquals($this->centre->id, $this->officer->fresh()->marking_centre_id);
    }
}
