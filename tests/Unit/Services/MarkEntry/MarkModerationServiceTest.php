<?php

namespace Tests\Unit\Services\MarkEntry;

use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use App\Models\MarkEntryLifecycleState;
use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamType;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use App\Services\MarkEntry\Moderation\MarkModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private MarkModerationService $service;
    private LifecycleStateService $lifecycleService;
    private User $reviewer;
    private User $approver;
    private MarkImportBatch $batch;
    private Region $region;
    private District $district;
    private School $school;
    private Subject $subject;
    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test users
        $role = Role::firstOrCreate(
            ['code' => 'hod'],
            ['name' => 'Head of Department', 'description' => 'HOD']
        );
        
        $this->reviewer = User::firstOrCreate(
            ['email' => 'reviewer@example.com'],
            [
                'name' => 'Reviewer User',
                'password' => bcrypt('password'),
                'role_id' => $role->id,
                'status' => 'active'
            ]
        );

        $this->approver = User::firstOrCreate(
            ['email' => 'approver@example.com'],
            [
                'name' => 'Approver User',
                'password' => bcrypt('password'),
                'role_id' => $role->id,
                'status' => 'active'
            ]
        );

        // Create Region & District first (required for foreign keys)
        $this->region = Region::create([
            'name' => 'Test Region',
            'code' => 'TR_' . uniqid()
        ]);

        $this->district = District::create([
            'name' => 'Test District',
            'code' => 'TD_' . uniqid(),
            'region_id' => $this->region->id
        ]);

        // Setup required foreign models - using all required fields
        $this->school = School::create([
            'registration_number' => 'TEST_SCHOOL_' . uniqid(),
            'code' => 'TSCH_' . uniqid(),
            'name' => 'Test School',
            'region_id' => $this->region->id,
            'district_id' => $this->district->id
        ]);

        $this->examType = ExamType::create([
            'code' => 'TESTTYPE_' . uniqid(),
            'name' => 'Test Exam Type'
        ]);

        $this->subject = Subject::create([
            'code' => 'TST_' . uniqid(),
            'name' => 'Test Subject',
            'exam_type_id' => $this->examType->id
        ]);

        // Setup test batch in validated state
        $this->batch = MarkImportBatch::create([
            'batch_code' => 'BATCH_' . uniqid(),
            'file_name' => 'marks_batch.csv',
            'batch_hash' => hash('sha256', 'marks_batch.csv'),
            'exam_year' => 2024,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
            'lifecycle_state' => 'validated',
            'total_records' => 500,
            'processed_records' => 500
        ]);

        $this->lifecycleService = app(LifecycleStateService::class);
        $this->service = new MarkModerationService($this->lifecycleService);
    }

    // ============ Review Creation Tests ============

    /**
     * Test createReview creates moderation review record
     */
    public function test_create_review_creates_record(): void
    {
        $this->actingAs($this->reviewer);

        $review = $this->service->createReview(
            $this->batch,
            $this->reviewer,
            'school_hod'
        );

        $this->assertNotNull($review);
        $this->assertEquals($this->batch->id, $review->mark_import_batch_id);
        $this->assertEquals($this->reviewer->id, $review->reviewer_id);
        $this->assertEquals('school_hod', $review->review_type);
        $this->assertEquals('pending', $review->status);
    }

    /**
     * Test createReview transitions batch to awaiting_moderation
     */
    public function test_create_review_transitions_to_awaiting_moderation(): void
    {
        $this->actingAs($this->reviewer);

        $this->service->createReview(
            $this->batch,
            $this->reviewer,
            'school_hod'
        );

        $batch = $this->batch->fresh();

        $this->assertEquals('awaiting_moderation', $batch->lifecycle_state);
    }

    /**
     * Test createReview creates lifecycle state record
     */
    public function test_create_review_creates_lifecycle_state(): void
    {
        $this->actingAs($this->reviewer);

        $this->service->createReview(
            $this->batch,
            $this->reviewer,
            'school_hod'
        );

        $lifecycle = MarkEntryLifecycleState::where('mark_import_batch_id', $this->batch->id)
            ->where('current_state', 'awaiting_moderation')
            ->first();

        $this->assertNotNull($lifecycle);
    }

    /**
     * Test createReview with different review types
     */
    public function test_create_review_with_various_types(): void
    {
        $this->actingAs($this->reviewer);

        $types = ['school_hod', 'district_supervisor', 'admin', 'school_hod'];

        foreach ($types as $type) {
            // Create fresh Subject & ExamType for each batch
            $examType = ExamType::create([
                'code' => 'TESTTYPE_' . uniqid(),
                'name' => "Test Exam Type {$type}"
            ]);

            $subject = Subject::create([
                'code' => 'TST_' . uniqid(),
                'name' => "Test Subject {$type}",
                'exam_type_id' => $examType->id
            ]);

            $uniqueId = uniqid();
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . $uniqueId,
                'file_name' => "batch_{$type}_{$uniqueId}.csv",
                'batch_hash' => hash('sha256', "batch_{$type}_{$uniqueId}.csv"),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'lifecycle_state' => 'validated',
                'total_records' => 100,
                'processed_records' => 100
            ]);

            $review = $this->service->createReview($batch, $this->reviewer, $type);

            $this->assertEquals($type, $review->review_type);
        }
    }

    // ============ Batch Approval Tests ============

    /**
     * Test approveBatch marks review as approved
     */
    public function test_approve_batch_updates_review_status(): void
    {
        $this->actingAs($this->approver);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $review = $this->service->approveBatch(
            $this->batch->fresh(),
            $this->approver,
            'Data quality acceptable'
        );

        $this->assertEquals('approved', $review->status);
        $this->assertEquals('Data quality acceptable', $review->feedback);
        $this->assertEquals($this->approver->id, $review->reviewer_id);
        $this->assertNotNull($review->reviewed_at);
    }

    /**
     * Test approveBatch transitions batch to approved state
     */
    public function test_approve_batch_transitions_to_approved(): void
    {
        $this->actingAs($this->approver);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->approveBatch($this->batch->fresh(), $this->approver);

        $batch = $this->batch->fresh();

        $this->assertEquals('approved', $batch->lifecycle_state);
    }

    /**
     * Test approveBatch creates lifecycle state record
     */
    public function test_approve_batch_creates_lifecycle_record(): void
    {
        $this->actingAs($this->approver);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->approveBatch($this->batch->fresh(), $this->approver, 'Approved');

        $lifecycle = MarkEntryLifecycleState::where('mark_import_batch_id', $this->batch->id)
            ->where('current_state', 'approved')
            ->first();

        $this->assertNotNull($lifecycle);
        $this->assertStringContainsString($this->approver->name, $lifecycle->transition_reason);
    }

    /**
     * Test approveBatch with feedback message
     */
    public function test_approve_batch_with_custom_feedback(): void
    {
        $this->actingAs($this->approver);

        $feedback = 'Excellent data quality. Ready for submission.';
        
        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $review = $this->service->approveBatch(
            $this->batch->fresh(),
            $this->approver,
            $feedback
        );

        $this->assertEquals($feedback, $review->feedback);
    }

    /**
     * Test approveBatch without feedback
     */
    public function test_approve_batch_without_feedback(): void
    {
        $this->actingAs($this->approver);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $review = $this->service->approveBatch($this->batch->fresh(), $this->approver);

        $this->assertNull($review->feedback);
    }

    /**
     * Test approveBatch throws exception if no review exists
     */
    public function test_approve_batch_requires_existing_review(): void
    {
        $this->actingAs($this->approver);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active moderation review found');

        $this->service->approveBatch($this->batch, $this->approver);
    }

    // ============ Batch Rejection Tests ============

    /**
     * Test rejectBatch marks review as rejected
     */
    public function test_reject_batch_updates_review_status(): void
    {
        $this->actingAs($this->reviewer);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $reason = 'Missing candidate entries in 3 schools';
        
        $review = $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, $reason);

        $this->assertEquals('rejected', $review->status);
        $this->assertEquals($reason, $review->feedback);
        $this->assertEquals($this->reviewer->id, $review->reviewer_id);
        $this->assertNotNull($review->reviewed_at);
    }

    /**
     * Test rejectBatch transitions batch to rejected state
     */
    public function test_reject_batch_transitions_to_rejected(): void
    {
        $this->actingAs($this->reviewer);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, 'Data issues');

        $batch = $this->batch->fresh();

        $this->assertEquals('rejected', $batch->lifecycle_state);
    }

    /**
     * Test rejectBatch creates lifecycle state record
     */
    public function test_reject_batch_creates_lifecycle_record(): void
    {
        $this->actingAs($this->reviewer);

        $reason = 'Validation errors found';

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, $reason);

        $lifecycle = MarkEntryLifecycleState::where('mark_import_batch_id', $this->batch->id)
            ->where('current_state', 'rejected')
            ->first();

        $this->assertNotNull($lifecycle);
        $this->assertStringContainsString($reason, $lifecycle->transition_reason);
    }

    /**
     * Test rejectBatch sets requires_resubmission flag
     */
    public function test_reject_batch_sets_resubmission_flag(): void
    {
        $this->actingAs($this->reviewer);

        $reason = 'Incomplete data';

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, $reason);

        $batch = $this->batch->fresh();

        $this->assertTrue((bool)$batch->requires_resubmission);
        $this->assertEquals($reason, $batch->rejection_reason);
    }

    /**
     * Test rejectBatch throws exception if no review exists
     */
    public function test_reject_batch_requires_existing_review(): void
    {
        $this->actingAs($this->reviewer);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active moderation review found');

        $this->service->rejectBatch($this->batch, $this->reviewer, 'Invalid data');
    }

    /**
     * Test rejectBatch stores rejection reason
     */
    public function test_reject_batch_stores_rejection_reason(): void
    {
        $this->actingAs($this->reviewer);

        $reason = 'Missing entries for 50 candidates';

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, $reason);

        $batch = $this->batch->fresh();

        $this->assertEquals($reason, $batch->rejection_reason);
    }

    // ============ Workflow Tests ============

    /**
     * Test complete approval workflow
     */
    public function test_complete_approval_workflow(): void
    {
        $this->actingAs($this->reviewer);

        // Step 1: Create review
        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->assertEquals('awaiting_moderation', $this->batch->fresh()->lifecycle_state);

        // Step 2: Approve batch
        $this->service->approveBatch($this->batch->fresh(), $this->approver, 'Approved');
        $this->assertEquals('approved', $this->batch->fresh()->lifecycle_state);

        // Verify review is approved
        $review = MarkModerationReview::where('mark_import_batch_id', $this->batch->id)
            ->latest('id')
            ->first();

        $this->assertEquals('approved', $review->status);
    }

    /**
     * Test rejection and resubmission workflow
     */
    public function test_rejection_and_resubmission_workflow(): void
    {
        $this->actingAs($this->reviewer);

        // Step 1: Create review
        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');

        // Step 2: Reject batch
        $this->service->rejectBatch(
            $this->batch->fresh(),
            $this->reviewer,
            'Missing data for schools'
        );

        $batch = $this->batch->fresh();
        $this->assertEquals('rejected', $batch->lifecycle_state);
        $this->assertTrue((bool)$batch->requires_resubmission);

        // Step 3: Simulate resubmission by transitioning back to draft
        $this->lifecycleService->transition($batch, 'draft', $this->reviewer, 'Corrected data');
        $batch = $batch->fresh();

        $this->assertEquals('draft', $batch->lifecycle_state);
        // Note: requires_resubmission flag remains true until explicitly cleared
        // This is intentional to maintain audit trail of previous rejections
    }

    // ============ Review History Tests ============

    /**
     * Test multiple reviews on same batch
     */
    public function test_multiple_reviews_create_history(): void
    {
        $this->actingAs($this->reviewer);

        // First attempt - reject
        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, 'Issue 1');

        // Resubmit - transition back to draft, then validate again
        $batch = $this->batch->fresh();
        $this->lifecycleService->transition($batch, 'draft', $this->reviewer);
        
        // Transition through validation workflow to get back to validated state
        $batch = $batch->fresh();
        $this->lifecycleService->transition($batch, 'validating', $this->reviewer);
        $batch = $batch->fresh();
        $this->lifecycleService->transition($batch, 'validated', $this->reviewer);

        // Second attempt - approve
        $batch = $batch->fresh();
        $this->service->createReview($batch, $this->reviewer, 'admin');
        $this->service->approveBatch($batch->fresh(), $this->approver, 'Approved');

        $reviews = MarkModerationReview::where('mark_import_batch_id', $this->batch->id)
            ->get();

        $this->assertCount(2, $reviews);
        $this->assertEquals('rejected', $reviews->first()->status);
        $this->assertEquals('approved', $reviews->last()->status);
    }

    /**
     * Test review records different reviewers
     */
    public function test_review_records_different_reviewers(): void
    {
        $this->actingAs($this->reviewer);

        // First review by reviewer
        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');

        // Reject and resubmit
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, 'Issue');
        $this->lifecycleService->transition($this->batch->fresh(), 'draft', $this->reviewer);

        // Second review by approver
        $this->service->createReview($this->batch->fresh(), $this->approver, 'admin');

        $reviews = MarkModerationReview::where('mark_import_batch_id', $this->batch->id)
            ->get();

        $reviewerIds = $reviews->pluck('reviewer_id')->unique();

        $this->assertContains($this->reviewer->id, $reviewerIds->toArray());
        $this->assertContains($this->approver->id, $reviewerIds->toArray());
    }

    // ============ Transaction Tests ============

    /**
     * Test approveBatch is transactional
     */
    public function test_approve_batch_is_transactional(): void
    {
        $this->actingAs($this->approver);

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->approveBatch($this->batch->fresh(), $this->approver, 'Approved');

        // Both review and batch state should be updated
        $this->assertDatabaseHas('mark_moderation_reviews', [
            'mark_import_batch_id' => $this->batch->id,
            'status' => 'approved'
        ]);

        $this->assertDatabaseHas('mark_import_batches', [
            'id' => $this->batch->id,
            'lifecycle_state' => 'approved'
        ]);
    }

    /**
     * Test rejectBatch is transactional
     */
    public function test_reject_batch_is_transactional(): void
    {
        $this->actingAs($this->reviewer);

        $reason = 'Data errors found';

        $this->service->createReview($this->batch, $this->reviewer, 'school_hod');
        $this->service->rejectBatch($this->batch->fresh(), $this->reviewer, $reason);

        // Review, batch state, and rejection flag should all be updated
        $this->assertDatabaseHas('mark_moderation_reviews', [
            'mark_import_batch_id' => $this->batch->id,
            'status' => 'rejected'
        ]);

        $this->assertDatabaseHas('mark_import_batches', [
            'id' => $this->batch->id,
            'lifecycle_state' => 'rejected',
            'requires_resubmission' => true
        ]);
    }
}
