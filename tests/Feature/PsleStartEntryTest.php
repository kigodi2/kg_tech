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
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleStartEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regionalOfficer;
    private User $unassignedOfficer;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $schoolInRegion;
    private School $schoolOutsideRegion;
    private Subject $subject;

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

        $region1 = Region::factory()->create();
        $region2 = Region::factory()->create();

        // Region 1
        $this->schoolInRegion = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $region1->id,
        ]);

        // Region 2 (different region_id)
        $this->schoolOutsideRegion = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $region2->id,
        ]);

        $this->regionalOfficer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->schoolInRegion->region_id,
            'status' => 'active',
        ]);

        $this->unassignedOfficer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => null,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);
    }

    public function test_region_assigned_meo_can_search_schools_in_region_even_with_zero_marks_and_zero_candidates(): void
    {
        $response = $this->actingAs($this->regionalOfficer)->get('/mark-entry/psle?view=start-entry');

        $response->assertOk();
        $response->assertSee($this->schoolInRegion->name);
        $response->assertDontSee($this->schoolOutsideRegion->name);
    }

    public function test_region_assigned_meo_can_open_entry_sheet_and_see_candidates(): void
    {
        $c1 = Candidate::factory()->school()->create([
            'school_id' => $this->schoolInRegion->id,
            'exam_type' => 'PSLE',
            'candidate_id' => 'PSLE-2026-0002',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $c1->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0002',
            'status' => 'APPROVED',
        ]);

        $c2 = Candidate::factory()->school()->create([
            'school_id' => $this->schoolInRegion->id,
            'exam_type' => 'PSLE',
            'candidate_id' => 'PSLE-2026-0001',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $c2->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->regionalOfficer)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolInRegion->id}&subject_id={$this->subject->id}&exam_year_id={$this->examYear->id}"
        );

        $response->assertOk();
        // Check both candidates appear in correct sorted order (PSLE-2026-0001 before PSLE-2026-0002)
        $content = $response->getContent();
        $pos1 = strpos($content, '2026-0001');
        $pos2 = strpos($content, '2026-0002');
        $this->assertNotFalse($pos1, '2026-0001 not found in roster!');
        $this->assertNotFalse($pos2, '2026-0002 not found in roster!');
        $this->assertTrue($pos1 < $pos2, "Roster is not sorted by index number ascending!");
    }

    public function test_region_assigned_meo_cannot_access_outside_region_school(): void
    {
        $response = $this->actingAs($this->regionalOfficer)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolOutsideRegion->id}&subject_id={$this->subject->id}&exam_year_id={$this->examYear->id}"
        );

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Unauthorized: This school is outside your assigned region.');
    }

    public function test_region_assigned_meo_can_save_mark_without_preexisting_assignment(): void
    {
        $candidate = Candidate::factory()->school()->create([
            'school_id' => $this->schoolInRegion->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->regionalOfficer)->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => $candidate->id,
            'school_id' => $this->schoolInRegion->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $candidate->id,
            'school_id' => $this->schoolInRegion->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => 45,
        ]);
    }

    public function test_region_assigned_meo_cannot_save_mark_for_outside_region_school(): void
    {
        $candidate = Candidate::factory()->school()->create([
            'school_id' => $this->schoolOutsideRegion->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-9999',
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->regionalOfficer)->postJson('/api/mark-entry/psle/marks/save', [
            'candidate_id' => $candidate->id,
            'school_id' => $this->schoolOutsideRegion->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
        ]);

        $response->assertStatus(403);
    }
}
