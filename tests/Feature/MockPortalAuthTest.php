<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\DistrictCouncil;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MockPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_entry_officer_can_log_in_from_mock_portal_and_lands_on_mark_entry(): void
    {
        $role = Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        $user = User::factory()->create([
            'email' => 'mock-login-mark-officer@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'portal_role' => null,
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $response = $this->from(route('mock-portal.login'))->post(route('mock-portal.login.submit'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/mark-entry/psle');
        $this->assertAuthenticatedAs($user);
    }

    public function test_mock_portal_wrong_password_returns_to_login_without_authenticating(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong-password@example.com',
            'password' => bcrypt('password123'),
            'portal_role' => 'mock_headteacher',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $response = $this->from(route('mock-portal.login'))->post(route('mock-portal.login.submit'), [
            'email' => $user->email,
            'password' => 'bad-password',
        ]);

        $response->assertRedirect(route('mock-portal.login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_mock_portal_user_can_request_password_reset_link(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'portal-user@example.com',
            'portal_role' => 'mock_headteacher',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $response = $this->from(route('mock-portal.login'))->post(route('mock-portal.password.email'), [
            'email' => $user->email,
            'auth_view' => 'forgot',
        ]);

        $response->assertRedirect(route('mock-portal.login'));
        $response->assertSessionHas('status', 'We have e-mailed your password reset link!');
        $response->assertSessionHas('mock_portal_auth_view', 'forgot');

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($record);
    }

    public function test_non_mock_portal_user_cannot_request_mock_portal_password_reset_link(): void
    {
        User::factory()->create([
            'email' => 'main-system-user@example.com',
            'portal_role' => 'user',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $response = $this->from(route('mock-portal.login'))->post(route('mock-portal.password.email'), [
            'email' => 'main-system-user@example.com',
            'auth_view' => 'forgot',
        ]);

        $response->assertRedirect(route('mock-portal.login'));
        $response->assertSessionHasErrors('email');
        $response->assertSessionHas('_old_input.auth_view', 'forgot');
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'main-system-user@example.com',
        ]);
    }

    public function test_mock_portal_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-user@example.com',
            'password' => bcrypt('old-password'),
            'portal_role' => 'mock_dao',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('reset-token'),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->post(route('mock-portal.password.update'), [
            'token' => 'reset-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('mock-portal.login'));
        $response->assertSessionHas('status', 'Your password has been reset! Please login with your new password.');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_non_mock_portal_user_cannot_be_reset_through_mock_portal(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-user@example.com',
            'password' => bcrypt('old-password'),
            'portal_role' => 'user',
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('reset-token'),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->from(route('mock-portal.password.reset', ['token' => 'reset-token', 'email' => $user->email]))
            ->post(route('mock-portal.password.update'), [
                'token' => 'reset-token',
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertRedirect(route('mock-portal.password.reset', ['token' => 'reset-token', 'email' => $user->email]));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_login_page_reopens_forgot_password_card_when_requested(): void
    {
        $response = $this->withSession([
            'mock_portal_auth_view' => 'forgot',
            'status' => 'We have e-mailed your password reset link!',
        ])->get(route('mock-portal.login'));

        $response->assertOk();
        $response->assertSee('id="forgot-card" style="display:block;', false);
        $response->assertSee('id="login-card" style="display:none;', false);
        $response->assertSee('We have e-mailed your password reset link!');
    }

    public function test_mock_rao_without_region_gets_clear_login_error(): void
    {
        User::factory()->create([
            'email' => 'rao-without-region@example.com',
            'password' => bcrypt('password123'),
            'portal_role' => 'mock_rao',
            'region_id' => null,
            'status' => User::STATUS_ACTIVE,
            'password_reset_required' => false,
        ]);

        $response = $this->from(route('mock-portal.login'))->post(route('mock-portal.login.submit'), [
            'email' => 'rao-without-region@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('mock-portal.login'));
        $response->assertSessionHasErrors([
            'email' => 'Your RAO account is not assigned to a region. Please contact the administrator.',
        ]);
        $this->assertGuest();
    }

    public function test_mock_portal_dao_registration_is_limited_to_five_accounts_per_district(): void
    {
        $region = Region::factory()->create([
            'name' => 'Iringa',
            'code' => 'IR',
            'is_active' => true,
        ]);

        $district = District::create([
            'name' => 'Iringa District',
            'code' => 'IR1',
            'region_id' => $region->id,
            'status' => 'active',
        ]);

        $council = DistrictCouncil::create([
            'name' => $district->name,
            'code' => $district->code,
            'region_id' => $region->id,
            'is_active' => true,
        ]);

        foreach (range(1, 5) as $index) {
            User::create([
                'name' => "DAO {$index}",
                'email' => "dao{$index}@example.com",
                'password' => bcrypt('password123'),
                'portal_role' => 'mock_dao',
                'district_council_id' => $council->id,
                'region_id' => $region->id,
                'status' => User::STATUS_ACTIVE,
                'password_reset_required' => false,
            ]);
        }

        $response = $this->from('/mock-portal/register')->post('/mock-portal/register', [
            'name' => 'DAO 6',
            'email' => 'dao6@example.com',
            'password' => 'password123',
            'portal_role' => 'mock_dao',
            'region_id' => $region->id,
            'district_id' => $district->id,
        ]);

        $response->assertRedirect('/mock-portal/register');
        $response->assertSessionHasErrors('district_id');

        $this->assertSame(5, User::where('portal_role', 'mock_dao')->count());
        $this->assertDatabaseMissing('users', [
            'email' => 'dao6@example.com',
        ]);
    }
}
