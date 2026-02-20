<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Combination;
use App\Models\District;
use App\Models\ExamType;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Region;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\User;
use App\Services\MarkEntry\MarkBatchStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkBatchStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private MarkBatchStateMachine $stateMachine;
    private User $admin;
    private User $teacher;
    private School $school;
    private District $district;
    private Subject $subject;
    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = app(MarkBatchStateMachine::class);

        // Create prerequisite FK rows
        $region = Region::firstOrCreate(['id' => 1], ['name' => 'Test Region', 'code' => 'TR']);
        $this->district = District::firstOrCreate(['id' => 1], [
            'name' => 'Test District', 'code' => 'TD', 'region_id' => $region->id,
        ]);
        $this->school = School::firstOrCreate(['id' => 1], [
            'name' => 'Test School', 'code' => 'S0001',
            'district_id' => $this->district->id, 'region_id' => $region->id,
        ]);
        $this->examType = ExamType::firstOrCreate(['id' => 1], [
            'name' => 'ACSEE', 'code' => 'ACSEE',
        ]);
        $this->subject = Subject::firstOrCreate(['id' => 1], [
            'name' => 'Physics', 'code' => 'PHY', 'exam_type_id' => $this->examType->id,
        ]);

        $adminRole = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
        $teacherRole = Role::firstOrCreate(['code' => 'teacher'], ['name' => 'Teacher']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->teacher = User::factory()->create([
            'role_id' => $teacherRole->id,
            'school_id' => $this->school->id,
        ]);
    }

    private function makeBatch(string $status = 'validated', int $errors = 0): MarkImportBatch
    {
        return MarkImportBatch::factory()->create([
            'status' => $status,
            'lifecycle_state' => $status,
            'error_records' => $errors,
            'valid_records' => 10,
            'total_records' => 10 + $errors,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->examType->id,
            'district_id' => $this->district->id,
        ]);
    }

    public function test_cannot_submit_batch_with_errors(): void
    {
        $batch = $this->makeBatch('validated', 3);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot submit batch with validation errors');

        $this->stateMachine->submit($batch, $this->teacher);
    }

    public function test_cannot_approve_if_not_submitted(): void
    {
        $batch = $this->makeBatch('validated');

        $this->expectException(\LogicException::class);

        $this->stateMachine->approve($batch, $this->admin);
    }

    public function test_cannot_lock_if_not_approved(): void
    {
        $batch = $this->makeBatch('submitted');

        $this->expectException(\LogicException::class);

        $this->stateMachine->lock($batch, $this->admin);
    }

    public function test_submit_transitions_validated_to_submitted(): void
    {
        $batch = $this->makeBatch('validated', 0);
        $result = $this->stateMachine->submit($batch, $this->teacher);

        $this->assertTrue($result['success']);
        $this->assertEquals('submitted', $result['new_status']);
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_approve_transitions_submitted_to_approved(): void
    {
        $batch = $this->makeBatch('submitted');
        $result = $this->stateMachine->approve($batch, $this->admin, 'Looks good');

        $this->assertTrue($result['success']);
        $this->assertEquals('approved', $batch->fresh()->status);
    }

    public function test_reject_requires_reason(): void
    {
        $batch = $this->makeBatch('submitted');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 10 characters');

        $this->stateMachine->reject($batch, $this->admin, 'short');
    }

    public function test_reject_transitions_submitted_to_rejected(): void
    {
        $batch = $this->makeBatch('submitted');
        $result = $this->stateMachine->reject($batch, $this->admin, 'The marks contain inconsistencies in paper 2');

        $this->assertTrue($result['success']);
        $this->assertEquals('rejected', $batch->fresh()->status);
        $this->assertEquals('The marks contain inconsistencies in paper 2', $batch->fresh()->rejection_reason);
    }

    public function test_lock_promotes_marks_to_subject_marks(): void
    {
        $batch = $this->makeBatch('approved');

        // Create valid raw marks
        RawMark::factory()->count(3)->create([
            'mark_import_batch_id' => $batch->id,
            'has_errors' => false,
            'subject_id' => $batch->subject_id,
        ]);

        $beforeCount = SubjectMarks::count();
        $result = $this->stateMachine->lock($batch, $this->admin);

        $this->assertTrue($result['success']);
        $this->assertEquals('locked', $batch->fresh()->status);
        $this->assertArrayHasKey('promotion', $result);
        $this->assertGreaterThanOrEqual($beforeCount, SubjectMarks::count());
    }

    public function test_unlock_requires_admin_and_reason(): void
    {
        $batch = $this->makeBatch('locked');

        $this->expectException(\InvalidArgumentException::class);

        $this->stateMachine->unlock($batch, $this->admin, 'short');
    }

    public function test_unlock_transitions_locked_to_submitted(): void
    {
        $batch = $this->makeBatch('locked');
        $result = $this->stateMachine->unlock($batch, $this->admin, 'Need to correct paper 3 marks for 5 candidates');

        $this->assertTrue($result['success']);
        $this->assertEquals('submitted', $batch->fresh()->status);
    }

    public function test_lifecycle_state_logged_on_transition(): void
    {
        $batch = $this->makeBatch('validated', 0);
        $this->stateMachine->submit($batch, $this->teacher);

        $this->assertDatabaseHas('mark_entry_lifecycle_states', [
            'mark_import_batch_id' => $batch->id,
            'current_state' => 'submitted',
            'previous_state' => 'validated',
        ]);
    }

    public function test_full_lifecycle_happy_path(): void
    {
        $batch = $this->makeBatch('validated', 0);

        // Submit
        $this->stateMachine->submit($batch, $this->teacher);
        $this->assertEquals('submitted', $batch->fresh()->status);

        // Approve
        $this->stateMachine->approve($batch, $this->admin, 'All good');
        $this->assertEquals('approved', $batch->fresh()->status);

        // Lock
        $result = $this->stateMachine->lock($batch->fresh(), $this->admin);
        $this->assertEquals('locked', $batch->fresh()->status);
        $this->assertArrayHasKey('promotion', $result);
    }

    public function test_lock_is_idempotent_already_locked_throws(): void
    {
        $batch = $this->makeBatch('locked');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Cannot lock: batch status is 'locked'");

        $this->stateMachine->lock($batch, $this->admin);
    }

    public function test_submit_is_idempotent_already_submitted_throws(): void
    {
        $batch = $this->makeBatch('submitted');

        $this->expectException(\LogicException::class);

        $this->stateMachine->submit($batch, $this->teacher);
    }

    public function test_unlock_reverts_raw_mark_lock_flags(): void
    {
        $batch = $this->makeBatch('locked');

        // Create locked raw marks
        $rawMark = RawMark::factory()->create([
            'mark_import_batch_id' => $batch->id,
            'has_errors' => false,
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $this->admin->id,
        ]);

        $this->stateMachine->unlock($batch, $this->admin, 'Correcting paper 3 marks for candidate batch');

        $rawMark->refresh();
        $this->assertFalse((bool) $rawMark->is_locked);
        $this->assertNull($rawMark->locked_at);
        $this->assertNull($rawMark->locked_by);
    }

    public function test_existing_marks_not_modified_on_status_change(): void
    {
        $batch = $this->makeBatch('validated', 0);

        // Create raw marks with known values
        $rawMark = RawMark::factory()->create([
            'mark_import_batch_id' => $batch->id,
            'has_errors' => false,
            'paper_1_marks' => 75,
            'paper_2_marks' => 80,
        ]);

        $originalP1 = $rawMark->paper_1_marks;
        $originalP2 = $rawMark->paper_2_marks;

        // Submit (should not touch mark values)
        $this->stateMachine->submit($batch, $this->teacher);

        $rawMark->refresh();
        $this->assertEquals($originalP1, $rawMark->paper_1_marks);
        $this->assertEquals($originalP2, $rawMark->paper_2_marks);
    }

    public function test_unlock_creates_audit_event(): void
    {
        $batch = $this->makeBatch('locked');

        $this->stateMachine->unlock($batch, $this->admin, 'Fixing errors found after initial lock');

        $this->assertDatabaseHas('mark_entry_lifecycle_states', [
            'mark_import_batch_id' => $batch->id,
            'previous_state' => 'locked',
            'current_state' => 'submitted',
        ]);

        $this->assertDatabaseHas('mark_batch_approvals', [
            'mark_import_batch_id' => $batch->id,
            'approval_type' => 'unlock',
        ]);
    }

    public function test_cannot_submit_draft_with_errors(): void
    {
        $batch = $this->makeBatch('draft', 5);

        $this->expectException(\InvalidArgumentException::class);

        $this->stateMachine->submit($batch, $this->teacher);
    }

    public function test_submit_draft_with_zero_errors_succeeds(): void
    {
        $batch = $this->makeBatch('draft', 0);
        $result = $this->stateMachine->submit($batch, $this->teacher);

        $this->assertTrue($result['success']);
        $this->assertEquals('submitted', $batch->fresh()->status);
    }
}
