<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZonalSecretariatAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_when_opening_zonal_control_centre(): void
    {
        $response = $this->get('/admin/zonal-control-centre');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_when_opening_mock_secretariat_dashboard(): void
    {
        $response = $this->get('/mock-portal/secretariat');

        $response->assertRedirect('/mock-portal/login');
    }

    public function test_admin_can_log_in_and_open_zonal_control_centre(): void
    {
        $admin = User::create([
            'name' => 'Secretariat Admin',
            'email' => 'secretariat-admin@example.com',
            'password' => bcrypt('password123'),
            'portal_role' => 'admin',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $loginResponse = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);

        $zonalResponse = $this->get('/admin/zonal-control-centre');

        $zonalResponse->assertOk();
        $zonalResponse->assertSee('ZONAL CONTROL CENTRE');
        $zonalResponse->assertSee('Secretariat Priority Alerts');
    }

    public function test_mock_secretariat_can_register_and_open_dashboard_via_mock_portal(): void
    {
        $response = $this->from('/mock-portal/register')->post('/mock-portal/register', [
            'name' => 'Zonal Secretariat',
            'email' => 'zonal-secretariat@example.com',
            'password' => 'password123',
            'portal_role' => 'mock_secretariat',
        ]);

        $response->assertRedirect('/mock-portal/secretariat');

        $this->assertDatabaseHas('users', [
            'email' => 'zonal-secretariat@example.com',
            'portal_role' => 'mock_secretariat',
        ]);

        $user = User::where('email', 'zonal-secretariat@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);

        $dashboardResponse = $this->get('/mock-portal/secretariat');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('ZONAL CONTROL CENTRE');
    }

    public function test_mock_secretariat_can_log_in_via_mock_portal_and_is_blocked_from_main_login(): void
    {
        $user = User::create([
            'name' => 'Existing Secretariat',
            'email' => 'existing-secretariat@example.com',
            'password' => bcrypt('password123'),
            'portal_role' => 'mock_secretariat',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $mockLoginResponse = $this->post('/mock-portal/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $mockLoginResponse->assertRedirect('/mock-portal/secretariat');
        $this->assertAuthenticatedAs($user);

        $guestLoginResponse = $this->get('/login');
        $guestLoginResponse->assertRedirect('/mock-portal/secretariat');

        $mainDashboardResponse = $this->get('/dashboard');
        $mainDashboardResponse->assertRedirect('/mock-portal');

        auth()->logout();

        $mainLoginResponse = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $mainLoginResponse->assertRedirect('/login');
        $mainLoginResponse->assertSessionHasErrors('email');
    }

    public function test_mock_secretariat_registration_is_not_limited_to_five_accounts(): void
    {
        foreach (range(1, 5) as $index) {
            User::create([
                'name' => "Secretariat {$index}",
                'email' => "secretariat{$index}@example.com",
                'password' => bcrypt('password123'),
                'portal_role' => 'mock_secretariat',
                'status' => User::STATUS_ACTIVE,
                'password_reset_required' => false,
            ]);
        }

        $response = $this->from('/mock-portal/register')->post('/mock-portal/register', [
            'name' => 'Secretariat 6',
            'email' => 'secretariat6@example.com',
            'password' => 'password123',
            'portal_role' => 'mock_secretariat',
        ]);

        $response->assertRedirect('/mock-portal/secretariat');

        $this->assertSame(6, User::where('portal_role', 'mock_secretariat')->count());
        $this->assertDatabaseHas('users', [
            'email' => 'secretariat6@example.com',
            'portal_role' => 'mock_secretariat',
        ]);
    }
}
