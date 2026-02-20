<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamType;
use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use App\Models\MarkBatchApproval;

class ModerationApproveRejectTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
  }

  protected function createAdminUser()
  {
    $role = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
    return User::factory()->create(['role_id' => $role->id]);
  }

  protected function createPendingBatch($user)
  {
    $region = Region::create(['name' => 'R', 'code' => 'R1']);
    $district = District::create(['name' => 'D', 'code' => 'D1', 'region_id' => $region->id]);
    $school = School::create(['name' => 'S', 'code' => 'S1', 'district_id' => $district->id, 'region_id' => $region->id]);
    $examType = ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
    $subject = Subject::create(['code' => 'SUBX', 'name' => 'Subject X', 'exam_type_id' => $examType->id]);

    $batch = MarkImportBatch::create([
      'batch_code' => 'B-X-1',
      'exam_year' => 2025,
      'region_id' => $region->id,
      'district_id' => $district->id,
      'school_id' => $school->id,
      'subject_id' => $subject->id,
      'exam_type_id' => $examType->id,
      'status' => 'submitted',
      'total_records' => 5,
      'valid_records' => 5,
      'error_records' => 0,
      'imported_by' => $user->id,
      'lifecycle_state' => 'awaiting_moderation',
    ]);

    MarkModerationReview::create([
      'mark_import_batch_id' => $batch->id,
      'reviewer_id' => $user->id,
      'review_type' => 'moderation',
      'status' => 'pending',
    ]);

    return $batch;
  }

  public function test_admin_can_approve_batch_creates_review_and_approval()
  {
    $admin = $this->createAdminUser();
    $uploader = User::factory()->create();
    $batch = $this->createPendingBatch($uploader);

    $payload = ['feedback' => 'Looks good'];

    // Prime session to generate CSRF token and include it in request
    $this->actingAs($admin);
    $this->get('/');
    $token = session()->token();

    $this->withHeaders(['X-CSRF-TOKEN' => $token])
      ->postJson("/api/mark-entry/moderation/batch/{$batch->id}/approve", $payload)
      ->assertStatus(200)
      ->assertJson(['success' => true]);

    $this->assertDatabaseHas('mark_moderation_reviews', [
      'mark_import_batch_id' => $batch->id,
      'reviewer_id' => $admin->id,
      'status' => 'approved'
    ]);

    $this->assertDatabaseHas('mark_batch_approvals', [
      'mark_import_batch_id' => $batch->id,
      'approved_by' => $admin->id,
      'status' => 'approved'
    ]);
  }

  public function test_admin_can_reject_batch_creates_review()
  {
    $admin = $this->createAdminUser();
    $uploader = User::factory()->create();
    $batch = $this->createPendingBatch($uploader);

    $payload = ['reason' => 'Invalid marks format'];

    // Prime session to generate CSRF token and include it in request
    $this->actingAs($admin);
    $this->get('/');
    $token = session()->token();

    $this->withHeaders(['X-CSRF-TOKEN' => $token])
      ->postJson("/api/mark-entry/moderation/batch/{$batch->id}/reject", $payload)
      ->assertStatus(200)
      ->assertJson(['success' => true]);

    $this->assertDatabaseHas('mark_moderation_reviews', [
      'mark_import_batch_id' => $batch->id,
      'reviewer_id' => $admin->id,
      'status' => 'rejected'
    ]);
  }
}
