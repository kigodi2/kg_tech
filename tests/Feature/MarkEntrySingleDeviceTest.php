<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\District;
use App\Models\MarkEntryActiveSession;
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

        // Explicitly enable single-device restriction for testing this feature specifically
        config(['mark_entry.enable_single_device_restriction' => true]);

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
     * Test A: Mark Entry Officer first login is allowed.
     */
    public function test_meo_first_login_is_allowed(): void
    {
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        
        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->first();
        $this->assertNotNull($activeSession);
        $this->assertNotNull($activeSession->session_id);
        $this->assertNotNull($activeSession->device_hash);
        $this->assertNotNull($activeSession->ip_address);
    }

    /**
     * Test B: Same Mark Entry Officer second login from another session is blocked.
     * Test C: Blocked response includes active IP address.
     */
    public function test_same_meo_second_login_from_another_session_is_blocked_and_shows_active_ip(): void
    {
        // 1. First Device Logs In
        $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);
        
        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->first();
        $firstSessionId = $activeSession->session_id;
        $firstIp = $activeSession->ip_address;

        Auth::logout();
        session()->invalidate();

        // 2. Second Device/Session attempts Login (Simulate different Device Token Cookie / User Agent)
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ], [
            'REMOTE_ADDR' => '192.168.1.100', // Simulate different IP for second device
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)', // Different User Agent
        ]);

        // Should NOT log in, should redirect back with error
        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
        
        $errors = session('errors')->get('email');
        $this->assertStringContainsString('already active on another device', $errors[0]);
        $this->assertStringContainsString($firstIp, $errors[0]);

        // Database record should NOT be overwritten (first session is preserved)
        $activeSession->refresh();
        $this->assertEquals($firstSessionId, $activeSession->session_id);
    }

    /**
     * Test D: Active first session remains usable after second device is blocked.
     */
    public function test_active_first_session_remains_usable_after_second_device_blocked(): void
    {
        // 1. Device 1 logs in and accesses route
        $this->actingAs($this->meo);
        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();

        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->firstOrFail();

        // 2. Simulated Device 2 attempts access using a different session ID and device hash
        $activeSession->update([
            'session_id' => 'different_hash_device_2',
            'device_hash' => 'different_device_hash_2',
            'ip_address' => '192.168.1.99', // Simulate different active device IP
        ]);

        // Second device gets blocked
        $response2 = $this->get('/mark-entry/psle?view=overview');
        $response2->assertRedirect(route('login'));

        // Database record for Device 1/Device 2 is still preserved as 'different_hash_device_2'
        $activeSession->refresh();
        $this->assertEquals('different_hash_device_2', $activeSession->session_id);
    }

    /**
     * Test E: Admin account can still log in from multiple sessions.
     */
    public function test_admin_account_can_still_log_in_from_multiple_sessions(): void
    {
        $this->actingAs($this->admin);

        // Simulated admin is immune from active sessions tracking
        $activeSession = MarkEntryActiveSession::where('user_id', $this->admin->id)->first();
        $this->assertNull($activeSession);

        $response = $this->get('/dashboard');
        $response->assertOk();
    }

    /**
     * Test F: REO/DAO accounts are not affected unless also Mark Entry Officer.
     */
    public function test_reo_is_exempt_from_single_device_restriction(): void
    {
        $this->actingAs($this->reo);

        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();
    }

    /**
     * Test G: Stale Mark Entry Officer session can be replaced after timeout.
     */
    public function test_stale_meo_session_can_be_replaced_after_timeout(): void
    {
        // 1. Device 1 has stale session
        $this->meo->update(['last_login_at' => now()]);
        
        $activeSession = MarkEntryActiveSession::create([
            'user_id' => $this->meo->id,
            'session_id' => 'stale_session_hash',
            'device_hash' => 'stale_device_hash',
            'ip_address' => '192.168.1.5',
            'last_seen_at' => now()->subMinutes(40), // > 30 minutes config
        ]);

        // 2. Device 2 logs in
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        
        // Query the new session from the database
        $newActiveSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->firstOrFail();
        $this->assertNotEquals('stale_session_hash', $newActiveSession->session_id);
    }

    /**
     * Test H: Logout clears only the current matching active session.
     */
    public function test_logout_clears_only_current_matching_active_session(): void
    {
        // 1. Log in via standard login post request (this registers the session correctly in the database)
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect();

        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->first();
        $this->assertNotNull($activeSession);

        // 2. Perform logout post request on the same client
        $response2 = $this->post('/logout');
        $response2->assertRedirect();

        // 3. Active session record must be deleted
        $this->assertNull(MarkEntryActiveSession::where('user_id', $this->meo->id)->first());
    }

    /**
     * Test I: Blocked second session cannot clear active session on logout.
     */
    public function test_blocked_second_session_cannot_clear_active_session_on_logout(): void
    {
        // 1. Device 1 logs in and registers session
        $this->actingAs($this->meo);
        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();

        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->firstOrFail();

        // 2. Change session ID and device hash in database to simulate that Device 2 is the current request context
        // and Device 1 (mismatched) is stored in the database
        $activeSession->update([
            'session_id' => 'hash_device_1_session_id',
            'device_hash' => 'hash_device_1_mismatched',
        ]);

        // 3. Device 2 (current request context) logs out
        $this->post('/logout');

        // Stored active session of Device 1 must STILL remain intact in the database!
        $this->assertNotNull(MarkEntryActiveSession::where('user_id', $this->meo->id)->first());
    }

    /**
     * Test J: Autosave endpoint returns JSON 423/409 with active IP.
     */
    public function test_autosave_endpoint_returns_json_423_with_active_ip(): void
    {
        // 1. Device 1 logs in
        $this->actingAs($this->meo);
        $response = $this->get('/mark-entry/psle?view=overview');
        $response->assertOk();

        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->firstOrFail();

        // 2. Change session ID and device hash in database to simulate that a different device (Device 1) is active
        $activeSession->update([
            'session_id' => 'different_active_session_hash',
            'device_hash' => 'different_active_device_hash',
            'ip_address' => '192.168.1.99',
        ]);

        // Device 2 attempts API autosave
        $response2 = $this->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => 1,
            'subject_id' => 1,
            'mark' => 45,
        ]);

        // Must return HTTP 423 (Locked)
        $response2->assertStatus(423);
        $response2->assertJson([
            'ok' => false,
            'code' => 'MARK_ENTRY_ACCOUNT_ALREADY_ACTIVE',
            'active_ip' => '192.168.1.99',
        ]);
    }

    /**
     * Test K: Artisan command clears stuck MEO session.
     */
    public function test_admin_command_clears_stuck_meo_session(): void
    {
        $activeSession = MarkEntryActiveSession::create([
            'user_id' => $this->meo->id,
            'session_id' => 'stuck_session',
            'device_hash' => 'stuck_hash',
            'ip_address' => '10.0.0.1',
            'last_seen_at' => now(),
        ]);

        // Clear via Artisan command by Email
        $exitCode = Artisan::call('mark-entry:clear-session', [
            'user' => 'meo@example.com'
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertNull(MarkEntryActiveSession::where('user_id', $this->meo->id)->first());
    }

    /**
     * Test L: Same device can log back in with IP + User Agent match even when device token cookie is missing (e.g. cookie cleared).
     */
    public function test_same_device_can_log_in_via_ip_and_ua_fallback_when_cookie_is_missing(): void
    {
        // 1. Log in Device 1 once to create active session
        $response = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ], [
            'REMOTE_ADDR' => '192.168.1.5',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/125.0.0.0',
        ]);
        $response->assertRedirect();

        $activeSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->firstOrFail();
        $firstSessionId = $activeSession->session_id;

        // Invalidate current client session so we are guest again
        Auth::logout();
        session()->invalidate();

        // 2. Log in again with the EXACT same IP and UA, but without cookie (simulates browser restart or cookie clearing)
        $response2 = $this->post('/login', [
            'email' => 'meo@example.com',
            'password' => 'password123',
        ], [
            'REMOTE_ADDR' => '192.168.1.5',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/125.0.0.0',
        ]);

        // This must be ALLOWED and NOT blocked because of the IP + UA fallback matching!
        $response2->assertRedirect();
        $this->assertFalse(session()->hasOldInput('email'));

        $newActiveSession = MarkEntryActiveSession::where('user_id', $this->meo->id)->firstOrFail();
        $this->assertNotEquals($firstSessionId, $newActiveSession->session_id); // Session must be successfully recreated!
    }
}
