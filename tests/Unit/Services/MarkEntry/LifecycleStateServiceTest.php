<?php

namespace Tests\Unit\Services\MarkEntry;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamType;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleStateServiceTest extends TestCase
{
    use RefreshDatabase;

    private LifecycleStateService $service;
    private User $user;
    private MarkImportBatch $batch;
    private Region $region;
    private District $district;
    private School $school;
    private Subject $subject;
    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup test user
        $role = Role::firstOrCreate(
            ['code' => 'admin'],
            ['name' => 'Admin', 'description' => 'Administrator']
        );
        
        $this->user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
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

        // Setup test batch
        $this->batch = MarkImportBatch::create([
            'batch_code' => 'BATCH_' . uniqid(),
            'file_name' => 'test_batch.csv',
            'batch_hash' => hash('sha256', 'test_batch.csv'),
            'exam_year' => 2024,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
            'lifecycle_state' => 'draft',
            'total_records' => 100,
            'processed_records' => 0
        ]);

        $this->service = app(LifecycleStateService::class);
    }

    // ============ Transition Tests ============

    /**
     * Test valid transition from draft to validating
     */
    public function test_transition_draft_to_validating(): void
    {
        $lifecycle = $this->service->transition(
            $this->batch,
            'validating',
            $this->user,
            'Starting validation'
        );

        $this->assertNotNull($lifecycle);
        $this->assertEquals('draft', $lifecycle->previous_state);
        $this->assertEquals('validating', $lifecycle->current_state);
        $this->assertEquals($this->user->id, $lifecycle->transitioned_by);
        $this->assertNotNull($lifecycle->transitioned_at);
        $this->assertEquals('validating', $this->batch->fresh()->lifecycle_state);
    }

    /**
     * Test valid transition from validating to validated
     */
    public function test_transition_validating_to_validated(): void
    {
        $this->batch->update(['lifecycle_state' => 'validating']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'validated',
            $this->user,
            'Validation passed'
        );

        $this->assertEquals('validated', $lifecycle->current_state);
        $this->assertEquals('validating', $lifecycle->previous_state);
        $this->assertEquals('validated', $this->batch->fresh()->lifecycle_state);
    }

    /**
     * Test valid transition from validated to awaiting_moderation
     */
    public function test_transition_validated_to_awaiting_moderation(): void
    {
        $this->batch->update(['lifecycle_state' => 'validated']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'awaiting_moderation',
            $this->user
        );

        $this->assertEquals('awaiting_moderation', $lifecycle->current_state);
    }

    /**
     * Test valid transition from awaiting_moderation to approved
     */
    public function test_transition_awaiting_moderation_to_approved(): void
    {
        $this->batch->update(['lifecycle_state' => 'awaiting_moderation']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'approved',
            $this->user,
            'Approved by moderator'
        );

        $this->assertEquals('approved', $lifecycle->current_state);
    }

    /**
     * Test valid transition from approved to submitted
     */
    public function test_transition_approved_to_submitted(): void
    {
        $this->batch->update(['lifecycle_state' => 'approved']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'submitted',
            $this->user
        );

        $this->assertEquals('submitted', $lifecycle->current_state);
    }

    /**
     * Test valid transition from submitted to archived
     */
    public function test_transition_submitted_to_archived(): void
    {
        $this->batch->update(['lifecycle_state' => 'submitted']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'archived',
            $this->user
        );

        $this->assertEquals('archived', $lifecycle->current_state);
    }

    // ============ Rejection Flow Tests ============

    /**
     * Test valid transition from draft to rejected
     */
    public function test_transition_draft_to_rejected(): void
    {
        $lifecycle = $this->service->transition(
            $this->batch,
            'rejected',
            $this->user,
            'Duplicate submission'
        );

        $this->assertEquals('rejected', $lifecycle->current_state);
        $this->assertEquals('Duplicate submission', $lifecycle->transition_reason);
    }

    /**
     * Test valid transition from rejected back to draft
     */
    public function test_transition_rejected_to_draft(): void
    {
        $this->batch->update(['lifecycle_state' => 'rejected']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'draft',
            $this->user,
            'Resubmitting corrected batch'
        );

        $this->assertEquals('draft', $lifecycle->current_state);
    }

    /**
     * Test valid transition from awaiting_moderation to rejected
     */
    public function test_transition_awaiting_moderation_to_rejected(): void
    {
        $this->batch->update(['lifecycle_state' => 'awaiting_moderation']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'rejected',
            $this->user,
            'Data quality issues'
        );

        $this->assertEquals('rejected', $lifecycle->current_state);
    }

    /**
     * Test valid transition from validation_failed back to draft
     */
    public function test_transition_validation_failed_to_draft(): void
    {
        $this->batch->update(['lifecycle_state' => 'validation_failed']);

        $lifecycle = $this->service->transition(
            $this->batch,
            'draft',
            $this->user,
            'Fixing validation errors'
        );

        $this->assertEquals('draft', $lifecycle->current_state);
    }

    // ============ Invalid Transition Tests ============

    /**
     * Test invalid transition from archived (terminal state)
     */
    public function test_cannot_transition_from_archived(): void
    {
        $this->batch->update(['lifecycle_state' => 'archived']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Cannot transition from 'archived' to 'draft'");

        $this->service->transition($this->batch, 'draft', $this->user);
    }

    /**
     * Test invalid transition from draft to submitted
     */
    public function test_cannot_skip_required_states(): void
    {
        $this->expectException(\Exception::class);

        $this->service->transition($this->batch, 'submitted', $this->user);
    }

    /**
     * Test invalid transition from draft to archived
     */
    public function test_cannot_transition_draft_to_archived(): void
    {
        $this->expectException(\Exception::class);

        $this->service->transition($this->batch, 'archived', $this->user);
    }

    /**
     * Test valid transition from validated back to draft (for corrections)
     */
    public function test_cannot_transition_validated_to_draft(): void
    {
        $this->batch->update(['lifecycle_state' => 'validated']);

        $lifecycle = $this->service->transition($this->batch, 'draft', $this->user, 'Need to fix data');

        $this->assertEquals('draft', $lifecycle->current_state);
        $this->assertEquals('validated', $lifecycle->previous_state);
    }

    // ============ State Query Tests ============

    /**
     * Test getCurrentState returns correct state
     */
    public function test_get_current_state(): void
    {
        $this->batch->update(['lifecycle_state' => 'validating']);

        $currentState = $this->service->getCurrentState($this->batch);

        $this->assertEquals('validating', $currentState);
    }

    /**
     * Test getCurrentState returns draft as default
     */
    public function test_get_current_state_defaults_to_draft(): void
    {
        // Create another school/subject/exam type for this test
        $school = School::create([
            'registration_number' => 'TEST_SCHOOL_' . uniqid(),
            'code' => 'TSCH_' . uniqid(),
            'name' => 'Test School 2',
            'region_id' => $this->region->id,
            'district_id' => $this->district->id
        ]);

        $examType = ExamType::create([
            'code' => 'TESTTYPE2_' . uniqid(),
            'name' => 'Test Exam Type 2'
        ]);

        $subject = Subject::create([
            'code' => 'TST2_' . uniqid(),
            'name' => 'Test Subject 2',
            'exam_type_id' => $examType->id
        ]);

        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH_' . uniqid(),
            'file_name' => 'new_batch.csv',
            'batch_hash' => hash('sha256', 'new_batch.csv'),
            'exam_year' => 2024,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'exam_type_id' => $examType->id,
            'total_records' => 50,
            'processed_records' => 0
        ]);

        $currentState = $this->service->getCurrentState($batch);

        $this->assertEquals('draft', $currentState);
    }

    // ============ Transition Validation Tests ============

    /**
     * Test canTransition returns true for valid transitions
     */
    public function test_can_transition_valid_path(): void
    {
        $this->batch->update(['lifecycle_state' => 'draft']);

        $canTransition = $this->service->canTransition($this->batch, 'validating');

        $this->assertTrue($canTransition);
    }

    /**
     * Test canTransition returns false for invalid transitions
     */
    public function test_can_transition_invalid_path(): void
    {
        $this->batch->update(['lifecycle_state' => 'draft']);

        $canTransition = $this->service->canTransition($this->batch, 'approved');

        $this->assertFalse($canTransition);
    }

    /**
     * Test canTransition from archived returns false
     */
    public function test_cannot_transition_from_archived_state(): void
    {
        $this->batch->update(['lifecycle_state' => 'archived']);

        $canTransition = $this->service->canTransition($this->batch, 'draft');

        $this->assertFalse($canTransition);
    }

    // ============ Available Transitions Tests ============

    /**
     * Test getAvailableTransitions for draft state
     */
    public function test_available_transitions_from_draft(): void
    {
        $this->batch->update(['lifecycle_state' => 'draft']);

        $available = $this->service->getAvailableTransitions($this->batch);

        $this->assertContains('validating', $available);
        $this->assertContains('rejected', $available);
        $this->assertCount(2, $available);
    }

    /**
     * Test getAvailableTransitions for validating state
     */
    public function test_available_transitions_from_validating(): void
    {
        $this->batch->update(['lifecycle_state' => 'validating']);

        $available = $this->service->getAvailableTransitions($this->batch);

        $this->assertContains('validated', $available);
        $this->assertContains('validation_failed', $available);
    }

    /**
     * Test getAvailableTransitions for awaiting_moderation state
     */
    public function test_available_transitions_from_awaiting_moderation(): void
    {
        $this->batch->update(['lifecycle_state' => 'awaiting_moderation']);

        $available = $this->service->getAvailableTransitions($this->batch);

        $this->assertContains('approved', $available);
        $this->assertContains('rejected', $available);
    }

    /**
     * Test getAvailableTransitions for archived state (terminal)
     */
    public function test_available_transitions_from_archived(): void
    {
        $this->batch->update(['lifecycle_state' => 'archived']);

        $available = $this->service->getAvailableTransitions($this->batch);

        $this->assertEmpty($available);
    }

    // ============ Audit Trail Tests ============

    /**
     * Test transition creates audit record
     */
    public function test_transition_creates_lifecycle_record(): void
    {
        $this->service->transition($this->batch, 'validating', $this->user, 'Test reason');

        $lifecycle = MarkEntryLifecycleState::where('mark_import_batch_id', $this->batch->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($lifecycle);
        $this->assertEquals('validating', $lifecycle->current_state);
        $this->assertEquals('Test reason', $lifecycle->transition_reason);
    }

    /**
     * Test multiple transitions create multiple records
     */
    public function test_multiple_transitions_create_audit_trail(): void
    {
        $this->service->transition($this->batch, 'validating', $this->user);
        
        // Refresh batch and transition from validating state
        $batch = $this->batch->fresh();
        $this->assertEquals('validating', $batch->lifecycle_state);
        
        $this->service->transition($batch, 'validated', $this->user);

        $count = MarkEntryLifecycleState::where('mark_import_batch_id', $this->batch->id)->count();

        $this->assertEquals(2, $count);
    }

    /**
     * Test transition preserves user information
     */
    public function test_transition_records_user_id(): void
    {
        $this->service->transition($this->batch, 'validating', $this->user);

        $lifecycle = MarkEntryLifecycleState::where('mark_import_batch_id', $this->batch->id)
            ->latest('id')
            ->first();

        $this->assertEquals($this->user->id, $lifecycle->transitioned_by);
    }

    // ============ Transaction Tests ============

    /**
     * Test transition is transactional (batch state updates with lifecycle record)
     */
    public function test_transition_is_transactional(): void
    {
        $lifecycle = $this->service->transition($this->batch, 'validating', $this->user);

        // Refresh batch to see updated state
        $batch = $this->batch->fresh();
        
        // Both batch and lifecycle record should exist and be consistent
        $this->assertEquals('validating', $batch->lifecycle_state);
        $this->assertDatabaseHas('mark_entry_lifecycle_states', [
            'id' => $lifecycle->id,
            'current_state' => 'validating'
        ]);
    }

    // ============ Default Reason Tests ============

    /**
     * Test custom reason overrides default
     */
    public function test_custom_reason_used_when_provided(): void
    {
        $customReason = 'Custom validation message';
        
        $lifecycle = $this->service->transition(
            $this->batch,
            'validating',
            $this->user,
            $customReason
        );

        $this->assertEquals($customReason, $lifecycle->transition_reason);
    }

    /**
     * Test default reason used when not provided
     */
    public function test_default_reason_used_when_not_provided(): void
    {
        $lifecycle = $this->service->transition(
            $this->batch,
            'validating',
            $this->user
        );

        $this->assertEquals('Validation in progress', $lifecycle->transition_reason);
    }
}
