<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleModerationReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reo;
    private User $otherReo;
    private User $officer;
    private ExamYear $examYear;
    private ExamType $psle;
    private ExamType $acsee;
    private School $school;
    private School $otherSchool;
    private Subject $subject;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->acsee = ExamType::factory()->acsee()->create([
            'education_level' => 'SECONDARY',
        ]);

        $this->school = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $this->otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        $this->otherReo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->otherSchool->region_id,
            'status' => 'active',
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $this->candidate = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);
    }

    private function createBatch(string $status = 'draft', ?ExamType $examType = null, ?School $school = null): MarkImportBatch
    {
        $et = $examType ?? $this->psle;
        $sch = $school ?? $this->school;

        return MarkImportBatch::create([
            'batch_code' => 'BATCH-' . rand(1000, 9999),
            'batch_name' => 'Test Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $sch->region_id,
            'district_id' => $sch->district_id,
            'school_id' => $sch->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $et->id,
            'status' => $status,
            'total_records' => 0,
            'created_by' => $this->officer->id,
        ]);
    }

    public function test_moderation_page_shows_empty_state_when_no_submitted_batches_exist(): void
    {
        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=moderation-review');

        $response->assertStatus(200);
        $response->assertSee('No submitted batches awaiting review');
    }

    public function test_submitted_psle_batch_appears_in_pending_review(): void
    {
        $batch = $this->createBatch('submitted');

        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=moderation-review');

        $response->assertStatus(200);
        $response->assertSee($batch->batch_code);
    }

    public function test_admin_and_reo_can_approve_pending_psle_batch(): void
    {
        $batch = $this->createBatch('submitted');

        $response = $this->actingAs($this->reo)
            ->post("/mark-entry/psle/batches/{$batch->id}/approve", [
                'feedback' => 'Good batch',
            ]);

        $response->assertRedirect();
        $this->assertEquals('approved', $batch->fresh()->status);
    }

    public function test_admin_and_reo_can_reject_pending_psle_batch_with_reason(): void
    {
        $batch = $this->createBatch('submitted');

        $response = $this->actingAs($this->reo)
            ->post("/mark-entry/psle/batches/{$batch->id}/reject", [
                'reason' => 'Rejection reason must be long enough.',
            ]);

        $response->assertRedirect();
        $this->assertEquals('rejected', $batch->fresh()->status);
        $this->assertEquals('Rejection reason must be long enough.', $batch->fresh()->rejection_reason);
    }

    public function test_rejection_requires_valid_reason(): void
    {
        $batch = $this->createBatch('submitted');

        // Reason too short (< 10 chars)
        $response = $this->actingAs($this->reo)
            ->post("/mark-entry/psle/batches/{$batch->id}/reject", [
                'reason' => 'Too short',
            ]);

        $response->assertSessionHasErrors(['reason']);
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_reo_sees_only_assigned_region_batches(): void
    {
        $batchMyRegion = $this->createBatch('submitted', $this->psle, $this->school);
        $batchOtherRegion = $this->createBatch('submitted', $this->psle, $this->otherSchool);

        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=moderation-review');

        $response->assertStatus(200);
        $response->assertSee($batchMyRegion->batch_code);
        $response->assertDontSee($batchOtherRegion->batch_code);
    }

    public function test_reo_cannot_approve_or_reject_outside_region(): void
    {
        $batchOtherRegion = $this->createBatch('submitted', $this->psle, $this->otherSchool);

        $response = $this->actingAs($this->reo)
            ->postJson("/mark-entry/psle/batches/{$batchOtherRegion->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals('submitted', $batchOtherRegion->fresh()->status);
    }

    public function test_meo_cannot_approve_or_reject(): void
    {
        $batch = $this->createBatch('submitted');

        $response = $this->actingAs($this->officer)
            ->postJson("/mark-entry/psle/batches/{$batch->id}/approve");

        $response->assertStatus(403);
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_admin_can_lock_and_unlock_batches(): void
    {
        $batch = $this->createBatch('approved');

        // Admin locks
        $response = $this->actingAs($this->admin)
            ->post("/mark-entry/psle/batches/{$batch->id}/lock");

        $response->assertRedirect();
        $this->assertEquals('locked', $batch->fresh()->status);

        // Admin unlocks
        $response = $this->actingAs($this->admin)
            ->post("/mark-entry/psle/batches/{$batch->id}/unlock", [
                'reason' => 'Unlocking this batch for modifications.',
            ]);

        $response->assertRedirect();
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_non_psle_batch_does_not_appear_on_moderation(): void
    {
        $nonPsleBatch = $this->createBatch('submitted', $this->acsee);

        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=moderation-review');

        $response->assertStatus(200);
        $response->assertDontSee($nonPsleBatch->batch_code);
    }
}
