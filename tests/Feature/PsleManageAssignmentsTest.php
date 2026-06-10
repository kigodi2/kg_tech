<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Role;
use App\Models\MarkingCentre;
use App\Models\MarkEntryAssignment;
use App\Models\District;
use App\Models\Region;
use App\Models\MarkImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleManageAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private User $reo;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private Subject $subject;
    private Candidate $candidate;
    private Region $region;
    private District $district;
    private MarkingCentre $markingCentre;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Helpers\MarkEntrySettings::setGeofenceEnabled(false);

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);

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

        $this->region = Region::factory()->create(['name' => 'IRINGA']);
        $this->district = District::create([
            'region_id' => $this->region->id,
            'code' => 'IR01',
            'name' => 'IRINGA MC',
        ]);

        $this->school = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $this->district->id,
            'region_id' => $this->region->id,
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => null,
            'status' => 'active',
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->region->id,
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

        $this->markingCentre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'MC1',
            'name' => 'Test Marking Centre',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_assignments_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=assignments');
        
        $response->assertStatus(200);
        $response->assertViewHas('assignments');
    }

    public function test_meo_cannot_access_assignments_page(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=assignments');
        
        $response->assertRedirect('/mark-entry/psle');
        $response->assertSessionHas('warning');
    }

    public function test_empty_assignment_submission_returns_validation_errors(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/assignments/create', []);
        
        $response->assertSessionHasErrors(['assigned_to', 'marking_centre_id', 'region_id', 'school_id', 'subject_id']);
    }

    public function test_admin_can_create_valid_assignment(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/assignments/create', [
            'assigned_to' => $this->officer->id,
            'marking_centre_id' => $this->markingCentre->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'assignment_type' => 'entry',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mark_entry_assignments', [
            'assigned_to' => $this->officer->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'status' => 'active',
        ]);
    }

    public function test_duplicate_assignment_is_blocked(): void
    {
        // Create first assignment
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $this->markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
            'starts_at' => now(),
        ]);

        // Try creating duplicate
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/assignments/create', [
            'assigned_to' => $this->officer->id,
            'marking_centre_id' => $this->markingCentre->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'assignment_type' => 'entry',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_created_assignment_appears_in_meo_start_entry_scope(): void
    {
        // Unassigned school
        $otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $this->district->id,
            'region_id' => $this->region->id,
        ]);

        // MEO has active assignment for $this->school
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $this->markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=start-entry');
        
        $response->assertStatus(200);
        $schools = $response->viewData('schools');
        $this->assertTrue($schools->contains('id', $this->school->id));
        $this->assertFalse($schools->contains('id', $otherSchool->id));
    }

    public function test_meo_cannot_save_marks_for_unassigned_school_subject(): void
    {
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => $this->candidate->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('type', 'authorization_error');
    }

    public function test_meo_can_save_marks_for_assigned_school_subject(): void
    {
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $this->markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => $this->candidate->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
        ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
    }

    public function test_assignment_delete_is_blocked_when_marks_exist(): void
    {
        $assignment = MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $this->markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
            'starts_at' => now(),
        ]);

        // Save a mark via the API to populate all sqlite constraints and generate row_number correctly
        $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => $this->candidate->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 38,
        ])->assertOk();

        $response = $this->actingAs($this->admin)->post("/mark-entry/psle/assignments/{$assignment->id}/revoke");

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('mark_entry_assignments', [
            'id' => $assignment->id,
            'status' => 'revoked',
        ]);
        $this->assertNull($assignment->fresh()->deleted_at);
    }

    public function test_assignment_can_be_soft_deleted_safely_if_no_marks_exist(): void
    {
        $assignment = MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $this->markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
            'starts_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post("/mark-entry/psle/assignments/{$assignment->id}/revoke");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('mark_entry_assignments', [
            'id' => $assignment->id,
            'status' => 'revoked',
        ]);
    }

    public function test_reo_cannot_assign_outside_region(): void
    {
        $otherRegion = Region::factory()->create(['name' => 'KILIMANJARO']);
        $otherDistrict = District::create([
            'region_id' => $otherRegion->id,
            'code' => 'KL01',
            'name' => 'MOSHI MC',
        ]);
        $otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $otherDistrict->id,
            'region_id' => $otherRegion->id,
        ]);

        $response = $this->actingAs($this->reo)->post('/mark-entry/psle/assignments/create', [
            'assigned_to' => $this->officer->id,
            'marking_centre_id' => $this->markingCentre->id,
            'region_id' => $otherRegion->id,
            'district_id' => $otherDistrict->id,
            'school_id' => $otherSchool->id,
            'subject_id' => $this->subject->id,
            'assignment_type' => 'entry',
        ]);

        $response->assertStatus(403);
    }
}
