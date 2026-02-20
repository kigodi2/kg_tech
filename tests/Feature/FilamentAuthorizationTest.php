<?php

namespace Tests\Feature;

use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Combination;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAuthorizationTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();
    $this->seed(RoleSeeder::class);
  }

  protected function createAdmin(): User
  {
    return User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
      'status' => User::STATUS_ACTIVE,
    ]);
  }

  protected function createNonAdmin(): User
  {
    return User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_REGIONAL_OFFICER)->first()->id,
      'status' => User::STATUS_ACTIVE,
    ]);
  }

  /**
   * Test that admin can access Subject resource list page
   */
  public function test_admin_can_access_subject_index(): void
  {
    $admin = $this->createAdmin();

    $this->actingAs($admin)
      ->get('/admin/subjects')
      ->assertOk();
  }

  /**
   * Test that non-admin cannot access Subject resource list page
   */
  public function test_non_admin_cannot_access_subject_index(): void
  {
    $nonAdmin = $this->createNonAdmin();

    $this->actingAs($nonAdmin)
      ->get('/admin/subjects')
      ->assertForbidden();
  }

  /**
   * Test that admin can access Combination resource list page
   */
  public function test_admin_can_access_combination_index(): void
  {
    $admin = $this->createAdmin();

    $this->actingAs($admin)
      ->get('/admin/combinations')
      ->assertOk();
  }

  /**
   * Test that admin can access Role resource list page
   */
  public function test_admin_can_access_role_index(): void
  {
    $admin = $this->createAdmin();

    $this->actingAs($admin)
      ->get('/admin/roles')
      ->assertOk();
  }

  /**
   * Test that admin can access RawMark resource list page (read-only)
   */
  public function test_admin_can_access_rawmark_index(): void
  {
    $admin = $this->createAdmin();

    $this->actingAs($admin)
      ->get('/admin/raw-marks')
      ->assertOk();
  }

  /**
   * Test subject policy - viewAny
   */
  public function test_subject_policy_viewAny_requires_admin(): void
  {
    $admin = $this->createAdmin();
    $nonAdmin = $this->createNonAdmin();

    $this->assertTrue($admin->can('viewAny', Subject::class));
    $this->assertFalse($nonAdmin->can('viewAny', Subject::class));
  }

  /**
   * Test subject policy - create
   */
  public function test_subject_policy_create_requires_admin(): void
  {
    $admin = $this->createAdmin();
    $nonAdmin = $this->createNonAdmin();

    $this->assertTrue($admin->can('create', Subject::class));
    $this->assertFalse($nonAdmin->can('create', Subject::class));
  }

  /**
   * Test combination policy - update
   */
  public function test_combination_policy_update_requires_admin(): void
  {
    $admin = $this->createAdmin();
    $nonAdmin = $this->createNonAdmin();
    $examType = ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
    $combination = Combination::create([
        'code' => 'TEST_COMBO',
        'exam_type_id' => $examType->id,
        'subjects' => 'Test Subjects',
    ]);

    $this->assertTrue($admin->can('update', $combination));
    $this->assertFalse($nonAdmin->can('update', $combination));
  }

  /**
   * Test role policy - create is always forbidden
   */
  public function test_role_policy_create_is_forbidden(): void
  {
    $admin = $this->createAdmin();

    $this->assertFalse($admin->can('create', Role::class));
  }

  /**
   * Test role policy - delete is always forbidden
   */
  public function test_role_policy_delete_is_forbidden(): void
  {
    $admin = $this->createAdmin();
    $role = Role::where('code', Role::CODE_SCHOOL_REGISTRAR)->first();

    $this->assertFalse($admin->can('delete', $role));
  }

  /**
   * Test Filament admin panel access is restricted to active admins
   */
  public function test_admin_panel_requires_active_admin(): void
  {
    $activeAdmin = $this->createAdmin();
    $suspendedAdmin = User::factory()->create([
      'role_id' => Role::where('code', Role::CODE_ADMIN)->first()->id,
      'status' => User::STATUS_SUSPENDED,
    ]);
    $nonAdmin = $this->createNonAdmin();

    $this->actingAs($activeAdmin)
      ->get('/admin')
      ->assertOk();

    $this->actingAs($suspendedAdmin)
      ->get('/admin')
      ->assertForbidden();

    $this->actingAs($nonAdmin)
      ->get('/admin')
      ->assertForbidden();
  }

  /**
   * Test user can view their own profile
   */
  public function test_user_can_view_own_profile(): void
  {
    $admin = $this->createAdmin();

    $this->actingAs($admin)
      ->get('/admin/users/' . $admin->id)
      ->assertOk();
  }
}
