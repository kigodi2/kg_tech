<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\District;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MarkEntrySingleDeviceTest extends TestCase
{
    use RefreshDatabase;

    private User $meo;
    private User $admin;
    private User $reo;
    private Role $meoRole;
    private Role $adminRole;
    private Role $reoRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->meoRole = Role::firstOrCreate(['code' => 'mark_entry_officer'], ['name' => 'Mark Entry Officer']);
        $this->adminRole = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        $this->reoRole = Role::firstOrCreate(['code' => 'regional_officer'], ['name' => 'Regional Education Officer']);

        // Create MEO User
        $this->meo = User::factory()->create([
            'email' => 'meo@example.com',
            'password' => Hash::make('password123'),
            'portal_role' => 'mark_entry_officer',
            'role_id' => $this->meoRole->id,
            'status' => 'active',
        ]);

        // Create Admin User
        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'portal_role' => 'admin',
            'role_id' => $this->adminRole->id,
            'is_admin' => true,
            'status' => 'active',
        ]);

        // Create REO User
        $this->reo = User::factory()->create([
            'email' => 'reo@example.com',
            'password' => Hash::make('password123'),
            'portal_role' => 'reo',
            'role_id' => $this->reoRole->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test A: MEO can log in on a first device and session/device hash are registered.
     */
    public function test_meo_can_log_in_on_first_device_and_registers_session(): void
    {
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        
        $this->meo->refresh();
        $this->assertNotNull($this->meo->mark_entry_session_id);
        $this->assertNotNull($this->meo->mark_entry_device_hash);
        $this->assertNotNull($this->meo->mark_entry_last_seen_at);
    }

    /**
     * Test B: Same MEO logging in on a second device invalidates the first.
     */
    public function test_same_meo_logging_in_on_second_device_overwrites_active_session(): void
    {
        // 1. First Device Logs In
        $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);
        
        $this->meo->refresh();
        $firstSessionId = $this->meo->mark_entry_session_id;
        $firstDeviceHash = $this->meo->mark_entry_device_hash;

        Auth::logout();
        session()->invalidate();

        // 2. Second Device Logs In
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        
        $this->meo->refresh();
        $secondSessionId = $this->meo->mark_entry_session_id;
        $secondDeviceHash = $this->meo->mark_entry_device_hash;

        $this->assertNotEquals($firstSessionId, $secondSessionId);
        $this->assertNotNull($secondSessionId);
    }

    /**
     * Test C: MEO request from Device 1 is blocked/logged out after Device 2 logs in.
     */
    public function test_meo_session_on_device_one_is_terminated_after_device_two_login(): void
    {
        // 1. Device 1 logs in
        $this->actingAs($this->meo);
        $firstSessionId = session()->getId();
        
        // Populate tracking fields for Device 1
        $this->meo->update([
            'mark_entry_session_id' => $firstSessionId,
            'mark_entry_device_hash' => 'hash_device_1',
            'mark_entry_last_seen_at' => now(),
        ]);

        // 2. Simulate Device 2 logging in and overwriting tracking fields
        $this->meo->update([
            'mark_entry_session_id' => 'different_session_id_2',
            'mark_entry_device_hash' => 'hash_device_2',
            'mark_entry_last_seen_at' => now(),
        ]);

        // 3. Device 1 tries to access a protected mark entry route
        $response = $this->get('/mark-entry/psle?view=overview');

        // Should be logged out and redirected to login with flash warning
        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
        
        // Assert the session redirect message is set
        $response->assertSessionHasErrors(['email']);
    }

    /**
     * Test D: Autosave AJAX endpoint returns JSON 423 when session is replaced.
     */
    public function test_autosave_endpoint_returns_json_423_when_session_replaced(): void
    {
        $this->actingAs($this->meo);
        $firstSessionId = session()->getId();
        
        // Device 1
        $this->meo->update([
            'mark_entry_session_id' => $firstSessionId,
            'mark_entry_device_hash' => 'hash_device_1',
            'mark_entry_last_seen_at' => now(),
        ]);

        // Device 2 takes over
        $this->meo->update([
            'mark_entry_session_id' => 'different_session_id_2',
            'mark_entry_device_hash' => 'hash_device_2',
            'mark_entry_last_seen_at' => now(),
        ]);

        // Device 1 attempts JSON autosave request
        $response = $this->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => 1,
            'subject_id' => 1,
            'mark' => 45,
        ]);

        // Expect HTTP 423 (Locked)
        $response->assertStatus(423);
        $response->assertJson([
            'ok' => false,
            'code' => 'MARK_ENTRY_SESSION_REPLACED',
        ]);
        $this->assertFalse(Auth::check());
    }

    /**
     * Test E: Admins can use multiple sessions without device lockout.
     */
    public function test_admins_are_exempt_from_single_device_restriction(): void
    {
        $this->actingAs($this->admin);
        $adminSessionId = session()->getId();

        // Manually trigger MEO simulation tracking fields on admin user (should be ignored)
        $this->admin->update([
            'mark_entry_session_id' => 'different_session_id',
            'mark_entry_device_hash' => 'different_hash',
            'mark_entry_last_seen_at' => now(),
        ]);

        // Admin accesses main system route
        $response = $this->get('/dashboard');
        $response->assertOk();
        $this->assertTrue(Auth::check()); // remains authenticated!
    }

    /**
     * Test F: REO/DAO are not restricted unless acting as MEO.
     */
    public function test_reo_is_exempt_from_single_device_restriction(): void
    {
        $this->actingAs($this->reo);

        $this->reo->update([
            'mark_entry_session_id' => 'different_session_id',
            'mark_entry_device_hash' => 'different_hash',
            'mark_entry_last_seen_at' => now(),
        ]);

        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();
        $this->assertTrue(Auth::check());
    }

    public function test_logout_clears_only_matching_meo_session(): void
    {
        // 1. Log in via standard login post request
        $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);
        
        $this->meo->refresh();
        $this->assertNotNull($this->meo->mark_entry_session_id);

        // 2. Perform logout post request
        $response = $this->post('/logout');
        $response->assertRedirect();

        $this->meo->refresh();
        $this->assertNull($this->meo->mark_entry_session_id);
        $this->assertNull($this->meo->mark_entry_device_hash);
    }

    /**
     * Test H: Expired/stale MEO session can be replaced.
     */
    public function test_expired_meo_session_is_takeover_allowed(): void
    {
        $this->actingAs($this->meo);

        // Make a request first to initialize the session in PHPUnit
        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();

        // Expired last seen (older than 30 mins, e.g. 40 mins ago)
        $this->meo->update([
            'mark_entry_session_id' => 'old_inactive_session',
            'mark_entry_device_hash' => 'old_hash',
            'mark_entry_last_seen_at' => now()->subMinutes(40),
        ]);

        // MEO accesses route; since the previous session was stale, it will takeover
        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();

        $this->meo->refresh();
        $this->assertEquals(session()->getId(), $this->meo->mark_entry_session_id);
    }

    /**
     * Test I: Admin Artisan command clears stuck MEO session.
     */
    public function test_admin_command_clears_stuck_meo_session(): void
    {
        $this->meo->update([
            'mark_entry_session_id' => 'stuck_session',
            'mark_entry_device_hash' => 'stuck_hash',
            'mark_entry_last_seen_at' => now(),
        ]);

        // Clear via Artisan command by Email
        $exitCode = Artisan::call('mark-entry:clear-session', [
            'user' => 'meo@example.com'
        ]);

        $this->assertEquals(0, $exitCode);
        $this->meo->refresh();
        $this->assertNull($this->meo->mark_entry_session_id);
        $this->assertNull($this->meo->mark_entry_device_hash);
        $this->assertNull($this->meo->mark_entry_last_seen_at);
    }
}
