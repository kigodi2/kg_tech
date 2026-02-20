<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use App\Models\UserScope;
use App\Models\District;
use App\Models\Region;
use App\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();
    // Seed roles
    $this->seed(RoleSeeder::class);
  }

  /**
   * Test that user role code retrieval works correctly
   */
  public function test_user_roleCode_returns_correct_code(): void
  {
    // Create admin
    $admin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
    ]);

    $this->assertEquals(Role::CODE_ADMIN, $admin->roleCode());

    // Create regional officer
    $regional = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_REGIONAL_OFFICER)->first()->id,
    ]);

    $this->assertEquals(Role::CODE_REGIONAL_OFFICER, $regional->roleCode());
  }

  /**
   * Test isAdmin() helper method
   */
  public function test_isAdmin_returns_true_for_admin_user(): void
  {
    $admin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
    ]);

    $this->assertTrue($admin->isAdmin());

    $nonAdmin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_REGIONAL_OFFICER)->first()->id,
    ]);

    $this->assertFalse($nonAdmin->isAdmin());
  }

  /**
   * Test hasRole() method
   */
  public function test_hasRole_checks_role_code_correctly(): void
  {
    $user = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_SCHOOL_REGISTRAR)->first()->id,
    ]);

    $this->assertTrue($user->hasRole(Role::CODE_SCHOOL_REGISTRAR));
    $this->assertFalse($user->hasRole(Role::CODE_ADMIN));
  }

  /**
   * Test role-specific helper methods
   */
  public function test_role_specific_helpers(): void
  {
    $regional = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_REGIONAL_OFFICER)->first()->id,
    ]);

    $this->assertTrue($regional->isRegionalOfficer());
    $this->assertFalse($regional->isAdmin());

    $registrar = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_SCHOOL_REGISTRAR)->first()->id,
    ]);

    $this->assertTrue($registrar->isSchoolRegistrar());
    $this->assertFalse($registrar->isDistrictSupervisor());
  }

  /**
   * Test scope access - canAccessRegion
   */
  public function test_canAccessRegion_allows_admin_access(): void
  {
    $admin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
    ]);

    // Admin can access any region
    $this->assertTrue($admin->canAccessRegion(999));
  }

  /**
   * Test scope access - canAccessDistrict
   */
  public function test_canAccessDistrict_allows_admin_access(): void
  {
    $admin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
    ]);

    // Admin can access any district
    $this->assertTrue($admin->canAccessDistrict(999));
  }

  /**
   * Test scope access - canAccessSchool
   */
  public function test_canAccessSchool_allows_admin_access(): void
  {
    $admin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
    ]);

    // Admin can access any school
    $this->assertTrue($admin->canAccessSchool(999));
  }

  /**
   * Test user status checks
   */
  public function test_user_status_checks(): void
  {
    $activeUser = User::factory()->create([
      'status' => User::STATUS_ACTIVE,
    ]);

    $suspendedUser = User::factory()->create([
      'status' => User::STATUS_SUSPENDED,
    ]);

    $this->assertTrue($activeUser->isActive());
    $this->assertFalse($activeUser->isSuspended());

    $this->assertFalse($suspendedUser->isActive());
    $this->assertTrue($suspendedUser->isSuspended());
  }

  /**
   * Test that panel access is restricted to active admins
   */
  public function test_canAccessPanel_requires_admin_and_active(): void
  {
    $panel = \Filament\Filament::getPanel('admin');

    // Active admin should access
    $activeAdmin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
      'status' => User::STATUS_ACTIVE,
    ]);

    $this->assertTrue($activeAdmin->canAccessPanel($panel));

    // Suspended admin should not access
    $suspendedAdmin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
      'status' => User::STATUS_SUSPENDED,
    ]);

    $this->assertFalse($suspendedAdmin->canAccessPanel($panel));

    // Active non-admin should not access
    $activeNonAdmin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_SCHOOL_REGISTRAR)->first()->id,
      'status' => User::STATUS_ACTIVE,
    ]);

    $this->assertFalse($activeNonAdmin->canAccessPanel($panel));
  }

  /**
   * Test Filament display name
   */
  public function test_getFilamentName_returns_full_name(): void
  {
    $user = User::factory()->create([
      'first_name' => 'John',
      'last_name' => 'Doe',
    ]);

    $this->assertEquals('John Doe', $user->getFilamentName());
  }
}
