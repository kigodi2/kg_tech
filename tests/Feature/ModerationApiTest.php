<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\District;
use App\Models\School;
use App\Models\MarkImportBatch;

class ModerationApiTest extends TestCase
{
  use RefreshDatabase;

  public function test_guest_cannot_access_pending_moderation()
  {
    $response = $this->getJson('/api/mark-entry/moderation/pending');
    $response->assertStatus(401);
  }

  public function test_admin_can_fetch_pending_batches()
  {
    // Create admin role and user
    $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
    $user = User::factory()->create(['role_id' => $role->id]);

    // Create minimal region/district/school required by MarkImportBatch
    $region = \App\Models\Region::create(['name' => 'Test Region', 'code' => 'TR1']);
    $district = District::create(['name' => 'Test District', 'code' => 'TD1', 'region_id' => $region->id]);
    $school = School::create(['name' => 'Test School', 'code' => 'TS1', 'district_id' => $district->id, 'region_id' => $region->id]);

    // Create subject and exam type, then create a pending batch
    $examType = \App\Models\ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
    $subject = \App\Models\Subject::create(['code' => 'SUB1', 'name' => 'Test Subject', 'exam_type_id' => $examType->id]);

    MarkImportBatch::create([
      'batch_code' => 'BATCH-001',
      'exam_year' => 2025,
      'region_id' => null,
      'district_id' => $district->id,
      'school_id' => $school->id,
      'subject_id' => $subject->id,
      'exam_type_id' => $examType->id,
      'status' => 'validated',
      'total_records' => 10,
      'valid_records' => 10,
      'error_records' => 0,
      'imported_by' => $user->id,
      'lifecycle_state' => 'awaiting_moderation',
    ]);

    $this->withoutMiddleware()
      ->actingAs($user)
      ->getJson('/api/mark-entry/moderation/pending')
      ->assertStatus(200)
      ->assertJsonStructure(['data', 'pagination' => ['total', 'per_page', 'current_page', 'last_page']]);
  }
}
