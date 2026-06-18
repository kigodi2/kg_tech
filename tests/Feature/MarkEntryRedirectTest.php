<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkEntryRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles required for tests
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);
        Role::firstOrCreate(['code' => 'centre_verifier'], ['name' => 'Marking Centre Verifier']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
    }

    /**
     * Test Mark Entry Officer logs in and is redirected to /mark-entry/psle
     */
    public function test_mark_entry_officer_redirects_to_mark_entry_portal(): void
    {
        $role = Role::where('code', 'mark_officer')->first();
        $user = User::create([
            'name' => 'Mark Officer User',
            'email' => 'officer@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'officer@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/mark-entry/psle');
    }

    /**
     * Test Marking Centre Verifier logs in and is redirected to /mark-entry/psle
     */
    public function test_marking_centre_verifier_redirects_to_mark_entry_portal(): void
    {
        $role = Role::where('code', 'centre_verifier')->first();
        $user = User::create([
            'name' => 'Verifier User',
            'email' => 'verifier@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'verifier@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/mark-entry/psle');
    }

    /**
     * Test Regional Education Officer logs in and is redirected to /mark-entry/psle
     */
    public function test_reo_redirects_to_mark_entry_portal(): void
    {
        $role = Role::where('code', 'reo')->first();
        $user = User::create([
            'name' => 'REO User',
            'email' => 'reo@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'reo@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/mark-entry/psle');
    }

    /**
     * Test Admin logs in and redirects to admin dashboard
     */
    public function test_admin_redirects_to_admin_dashboard(): void
    {
        $role = Role::where('code', 'admin')->first();
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'portal_role' => 'admin',
            'password_reset_required' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Test role simulator or explicit role selection redirect
     */
    public function test_role_selection_redirects_correctly(): void
    {
        $role = Role::where('code', 'admin')->first();
        $user = User::create([
            'name' => 'Multi Role User',
            'email' => 'multi@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'portal_role' => 'admin',
            'password_reset_required' => false,
        ]);

        // Login selecting mark_officer
        $response = $this->post('/login', [
            'email' => 'multi@example.com',
            'password' => 'password123',
            'role' => 'mark_officer',
        ]);

        $response->assertRedirect('/mark-entry/psle');
        $this->assertEquals('mark_officer', session('active_role'));

        // Reset session and login selecting admin
        session()->forget('active_role');

        $response2 = $this->post('/login', [
            'email' => 'multi@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response2->assertRedirect('/admin/dashboard');
        $this->assertEquals('admin', session('active_role'));
    }

    /**
     * Test middleware redirects mark entry user trying to visit /dashboard
     */
    public function test_middleware_protects_main_dashboard_for_mark_entry_users(): void
    {
        $role = Role::where('code', 'mark_officer')->first();
        $user = User::create([
            'name' => 'Mark Officer User',
            'email' => 'officer@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/mark-entry/psle?view=overview');
    }

    /**
     * Test already-logged-in mark entry user is redirected to /mark-entry/psle when hitting guest route
     */
    public function test_already_logged_in_guest_redirect_rules(): void
    {
        $role = Role::where('code', 'mark_officer')->first();
        $user = User::create([
            'name' => 'Mark Officer User',
            'email' => 'officer@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/mark-entry/psle');
    }
}
