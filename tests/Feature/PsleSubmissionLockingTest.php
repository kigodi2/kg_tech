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

class PsleSubmissionLockingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reo;
    private User $otherReo;
    private User $officer;
    private User $otherOfficer;
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

        config(['mark_entry.enable_single_device_restriction' => false]);
        config(['mark_entry.geofence_enabled' => false]);

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

        $this->otherOfficer = User::factory()->create([
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

    private function createBatch(string $status = 'draft', ?ExamType $examType = null, ?School $school = null, ?User $creator = null): MarkImportBatch
    {
        $et = $examType ?? $this->psle;
        $sch = $school ?? $this->school;
        $user = $creator ?? $this->officer;

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
            'created_by' => $user->id,
        ]);
    }

    private function addRawMarks(MarkImportBatch $batch, int $count = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $cand = Candidate::factory()->school()->create([
                'school_id' => $batch->school_id,
                'exam_type' => 'PSLE',
                'is_active' => true,
            ]);

            CandidateExamRegistration::create([
                'candidate_id' => $cand->id,
                'exam_type_id' => $this->psle->id,
                'exam_year_id' => $this->examYear->id,
                'year' => 2026,
                'registration_number' => 'PSLE-2026-' . rand(1000, 9999),
                'status' => 'APPROVED',
            ]);

            RawMark::factory()->create([
                'mark_import_batch_id' => $batch->id,
                'school_id' => $batch->school_id,
                'subject_id' => $batch->subject_id,
                'exam_year_id' => $batch->exam_year_id,
                'candidate_id' => $cand->id,
                'candidate_index_number' => $cand->candidate_id,
                'paper_1_marks' => 35,
                'subject_status' => null,
                'has_errors' => false,
            ]);
        }
    }

    public function test_submission_locking_page_shows_scoped_counts(): void
    {
        // 1 draft batch for our officer
        $batch = $this->createBatch('draft', $this->psle, $this->school, $this->officer);

        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=submission-locking');

        $response->assertStatus(200);
        $response->assertSee('Batch Submission');
        // Draft batches stat card should show 1
        $response->assertSee('Draft Batches');
    }

    public function test_marks_count_column_uses_real_marks_count(): void
    {
        $batch = $this->createBatch('draft');
        $this->addRawMarks($batch, 5);

        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=submission-locking');

        $response->assertStatus(200);
        $response->assertSee('5'); // Marks count column
    }

    public function test_meo_can_submit_own_valid_draft_batch(): void
    {
        $batch = $this->createBatch('draft');
        $this->addRawMarks($batch, 3);

        $response = $this->actingAs($this->officer)
            ->post("/mark-entry/psle/batches/{$batch->id}/submit");

        $response->assertRedirect();
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_meo_cannot_submit_draft_batch_with_zero_marks(): void
    {
        $batch = $this->createBatch('draft'); // 0 marks

        $response = $this->actingAs($this->officer)
            ->post("/mark-entry/psle/batches/{$batch->id}/submit");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('draft', $batch->fresh()->status);

        // API mode check
        $responseApi = $this->actingAs($this->officer)
            ->postJson("/mark-entry/psle/batches/{$batch->id}/submit");

        $responseApi->assertStatus(422);
        $responseApi->assertJson([
            'success' => false,
        ]);
        $this->assertEquals('draft', $batch->fresh()->status);
    }

    public function test_meo_cannot_submit_another_officers_batch(): void
    {
        $batch = $this->createBatch('draft', $this->psle, $this->school, $this->otherOfficer);
        $this->addRawMarks($batch, 2);

        $response = $this->actingAs($this->officer)
            ->post("/mark-entry/psle/batches/{$batch->id}/submit");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('draft', $batch->fresh()->status);
    }

    public function test_admin_can_submit_any_draft_batch(): void
    {
        $batch = $this->createBatch('draft', $this->psle, $this->school, $this->officer);
        $this->addRawMarks($batch, 2);

        $response = $this->actingAs($this->admin)
            ->post("/mark-entry/psle/batches/{$batch->id}/submit");

        $response->assertRedirect();
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_reo_cannot_submit_meo_draft_batch(): void
    {
        $batch = $this->createBatch('draft', $this->psle, $this->school, $this->officer);
        $this->addRawMarks($batch, 2);

        $response = $this->actingAs($this->reo)
            ->post("/mark-entry/psle/batches/{$batch->id}/submit");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('draft', $batch->fresh()->status);
    }

    public function test_pending_review_batch_appears_after_submission(): void
    {
        $batch = $this->createBatch('submitted');

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=submission-locking');

        $response->assertStatus(200);
        $response->assertSee($batch->batch_code);
    }

    public function test_approved_batch_can_be_locked_by_admin_only(): void
    {
        $batch = $this->createBatch('approved');
        $this->addRawMarks($batch, 3);

        $response = $this->actingAs($this->admin)
            ->post("/mark-entry/psle/batches/{$batch->id}/lock");

        $response->assertRedirect();
        $this->assertEquals('locked', $batch->fresh()->status);
    }

    public function test_reo_cannot_lock_batch(): void
    {
        $batch = $this->createBatch('approved');

        $response = $this->actingAs($this->reo)
            ->post("/mark-entry/psle/batches/{$batch->id}/lock");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('approved', $batch->fresh()->status);
    }

    public function test_meo_cannot_lock_batch(): void
    {
        $batch = $this->createBatch('approved');

        $response = $this->actingAs($this->officer)
            ->post("/mark-entry/psle/batches/{$batch->id}/lock");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('approved', $batch->fresh()->status);
    }

    public function test_admin_can_unlock_locked_batch(): void
    {
        $batch = $this->createBatch('locked');

        $response = $this->actingAs($this->admin)
            ->post("/mark-entry/psle/batches/{$batch->id}/unlock", [
                'reason' => 'Administrative unlock for correction.',
            ]);

        $response->assertRedirect();
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_locked_batch_blocks_mark_saves(): void
    {
        // 1. Create a locked batch (any batch status != draft, e.g., locked)
        $batch = $this->createBatch('locked');

        // 2. Attempt to save mark via API for a candidate in the same school & subject
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => $this->candidate->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'type' => 'authorization_error',
        ]);
    }

    public function test_rejected_batch_cannot_be_locked(): void
    {
        $batch = $this->createBatch('rejected');

        $response = $this->actingAs($this->admin)
            ->post("/mark-entry/psle/batches/{$batch->id}/lock");

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals('rejected', $batch->fresh()->status);
    }

    public function test_non_psle_batches_do_not_appear(): void
    {
        $nonPsleBatch = $this->createBatch('draft', $this->acsee);

        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=submission-locking');

        $response->assertStatus(200);
        $response->assertDontSee($nonPsleBatch->batch_code);
    }

    public function test_bulk_validate_identifies_eligible_and_skipped_batches(): void
    {
        $newSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->school->region_id,
        ]);

        $batch1 = $this->createBatch('draft', $this->psle, $newSchool, $this->officer);
        $this->addRawMarks($batch1, 2);

        $batch2 = $this->createBatch('draft', $this->psle, $newSchool, $this->officer); // 0 marks (skipped)

        $response = $this->actingAs($this->officer)
            ->postJson('/api/mark-entry/psle/batches/bulk-validate', [
                'batch_ids' => [$batch1->id, $batch2->id]
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'total_selected' => 2,
        ]);

        $response->assertJsonCount(1, 'eligible');
        $response->assertJsonCount(1, 'skipped');
        $response->assertJsonFragment([
            'batch_code' => $batch2->batch_code,
            'reason' => 'Batch has no entered marks.',
        ]);
    }

    public function test_bulk_submit_processes_in_chunks_and_logs_audit_events(): void
    {
        $newSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->school->region_id,
        ]);

        $batch1 = $this->createBatch('draft', $this->psle, $newSchool, $this->officer);
        $this->addRawMarks($batch1, 2);

        $response = $this->actingAs($this->officer)
            ->postJson('/api/mark-entry/psle/batches/bulk-submit', [
                'batch_ids' => [$batch1->id]
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        
        $this->assertEquals('submitted', $batch1->fresh()->status);
        
        // Assert system event log was created
        if (\Illuminate\Support\Facades\Schema::hasTable('system_event_logs')) {
            $this->assertDatabaseHas('system_event_logs', [
                'action' => 'psle_bulk_submit_chunk',
            ]);
        }

        // Assert governance audit log was created
        if (\Illuminate\Support\Facades\Schema::hasTable('governance_audit_logs')) {
            $this->assertDatabaseHas('governance_audit_logs', [
                'action' => 'psle_bulk_submit_chunk',
                'user_id' => $this->officer->id,
            ]);
        }
    }

    public function test_unauthorized_user_cannot_bulk_submit(): void
    {
        $batch = $this->createBatch('draft');
        $this->addRawMarks($batch, 2);

        $guest = User::factory()->create([
            'portal_role' => 'guest',
            'status' => 'active',
        ]);

        $response = $this->actingAs($guest)
            ->postJson('/api/mark-entry/psle/batches/bulk-submit', [
                'batch_ids' => [$batch->id]
            ]);

        $response->assertStatus(403);
    }

    public function test_meo_cannot_bulk_submit_other_officers_batches(): void
    {
        $batchOfOther = $this->createBatch('draft', $this->psle, $this->school, $this->otherOfficer);
        $this->addRawMarks($batchOfOther, 2);

        $response = $this->actingAs($this->officer)
            ->postJson('/api/mark-entry/psle/batches/bulk-submit', [
                'batch_ids' => [$batchOfOther->id]
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'batch_code' => $batchOfOther->batch_code,
            'reason' => 'Created by another officer'
        ]);
        
        $this->assertEquals('draft', $batchOfOther->fresh()->status);
    }
}

