<?php

namespace Tests\Feature;

use App\Helpers\SystemSettingsHelper;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemMaintenanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Route::middleware('web')->get('/maintenance-probe', fn () => response('available'));
        Route::middleware('api')->get('/api/maintenance-probe', fn () => response()->json(['ok' => true]));
    }

    public function test_guest_can_open_login_page_during_maintenance(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', true, 'boolean', 'Put system in maintenance mode');

        $this->get('/login')
            ->assertOk()
            ->assertSee('login', false);
    }

    public function test_guest_can_enter_from_root_during_maintenance(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', true, 'boolean', 'Put system in maintenance mode');

        $this->get('/')
            ->assertRedirect(route('public.home'));
    }

    public function test_guest_admin_panel_entry_redirects_to_admin_login_during_maintenance(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', true, 'boolean', 'Put system in maintenance mode');

        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_named_admin_can_continue_using_system_during_maintenance(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', true, 'boolean', 'Put system in maintenance mode');

        $admin = User::factory()->create([
            'email' => 'agreykigodi@gmail.com',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get('/maintenance-probe')
            ->assertOk()
            ->assertSee('available');
    }

    public function test_non_admin_user_sees_maintenance_page_during_maintenance(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', true, 'boolean', 'Put system in maintenance mode');

        $user = User::factory()->create([
            'email' => 'officer@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/maintenance-probe')
            ->assertStatus(503)
            ->assertSee('System Maintenance in Progress')
            ->assertSee('Maintenance Mode Active');
    }

    public function test_json_requests_receive_maintenance_response(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', true, 'boolean', 'Put system in maintenance mode');

        $this->getJson('/api/maintenance-probe')
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'System is under maintenance',
                'reason' => 'Server migration and performance upgrade after high traffic challenge on 28th May, 2026',
            ]);
    }

    public function test_users_regain_access_when_maintenance_is_off(): void
    {
        SystemSettingsHelper::setSetting('maintenance_mode', false, 'boolean', 'Put system in maintenance mode');

        $user = User::factory()->create([
            'email' => 'officer@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/maintenance-probe')
            ->assertOk()
            ->assertSee('available');
    }
}
