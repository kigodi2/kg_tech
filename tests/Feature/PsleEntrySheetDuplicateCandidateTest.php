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
use App\Models\MarkEntryAssignment;
use App\Models\MarkingCentre;
use App\Models\Region;
use App\Models\District;
use App\Models\MarkImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsleEntrySheetDuplicateCandidateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private Subject $subject;
    private MarkingCentre $markingCentre;

    protected function setUp(): void
    {
        parent::setUp();

        \App\Helpers\MarkEntrySettings::setGeofenceEnabled(false);

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

        $region = Region::factory()->create(['name' => 'KILIMANJARO']);
        $district = District::create(['region_id' => $region->id, 'name' => 'MOSHI', 'code' => 'MOSHI01']);

        // Explicit school with correct code length "PS0404090"
        $this->school = School::factory()->create([
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'code' => 'PS0404090',
            'name' => 'Lwing\'ulo Primary School',
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $region->id,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $this->markingCentre = MarkingCentre::create([
            'region_id' => $region->id,
            'code' => 'MC1',
            'name' => 'Test Marking Centre',
            'status' => 'active',
        ]);

        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $this->markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
        ]);
    }

    public function test_entry_sheet_deduplicates_and_prioritizes_correct_prefixes(): void
    {
        // 1. Candidate A (Unique, correct prefix)
        $candA = Candidate::factory()->school()->create(['school_id' => $this->school->id, 'candidate_id' => 'PS0404090-0001', 'full_name' => 'CANDIDATE A', 'gender' => 'M', 'prem_no' => '11111']);
        
        // 2. Candidate B (Has duplicate records with different prefixes)
        // Record 1: Incorrect/truncated prefix
        $candB1 = Candidate::factory()->school()->create(['school_id' => $this->school->id, 'candidate_id' => 'PS040409-0002', 'full_name' => 'CANDIDATE B', 'gender' => 'F', 'prem_no' => '22222']);
        // Record 2: Correct prefix but higher index number
        $candB2 = Candidate::factory()->school()->create(['school_id' => $this->school->id, 'candidate_id' => 'PS0404090-0003', 'full_name' => 'CANDIDATE B', 'gender' => 'F', 'prem_no' => '22222']);

        // 3. Candidate C (Unique, incorrect prefix only)
        $candC = Candidate::factory()->school()->create(['school_id' => $this->school->id, 'candidate_id' => 'PS040409-0004', 'full_name' => 'CANDIDATE C', 'gender' => 'M', 'prem_no' => '33333']);

        // Link registrations to all 4 records
        foreach ([$candA, $candB1, $candB2, $candC] as $cand) {
            CandidateExamRegistration::create([
                'candidate_id' => $cand->id,
                'exam_type_id' => $this->psle->id,
                'exam_year_id' => $this->examYear->id,
                'year' => 2026,
                'registration_number' => 'REG-' . $cand->id,
                'status' => 'APPROVED',
            ]);
        }

        // Fetch roster through the controller logic simulated
        $response = $this->actingAs($this->officer)->get("/mark-entry/psle?view=entry-sheet&exam_year_id={$this->examYear->id}&district_id={$this->school->district_id}&school_id={$this->school->id}&subject_id={$this->subject->id}");

        $response->assertStatus(200);

        // Access the passed view candidates and assert count is exactly 3 (A, B2, C)
        $renderedCandidates = $response->viewData('candidates');
        
        $this->assertCount(3, $renderedCandidates);

        $renderedIds = $renderedCandidates->pluck('candidate_id')->toArray();
        
        // Assert correct prefix PS0404090-0003 was prioritized and kept for CANDIDATE B
        $this->assertTrue(in_array('PS0404090-0003', $renderedIds, true));
        // Assert incorrect prefix record PS040409-0002 was excluded
        $this->assertFalse(in_array('PS040409-0002', $renderedIds, true));
        // Assert CANDIDATE C (which only exists with truncated prefix) was kept
        $this->assertTrue(in_array('PS040409-0004', $renderedIds, true));
    }

    public function test_editing_mark_updates_row_without_duplication(): void
    {
        $cand = Candidate::factory()->school()->create(['school_id' => $this->school->id, 'candidate_id' => 'PS0404090-0001', 'full_name' => 'CANDIDATE A', 'gender' => 'M']);
        CandidateExamRegistration::create([
            'candidate_id' => $cand->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-1',
            'status' => 'APPROVED',
        ]);

        $batch = MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school->region_id, 'district_id' => $this->school->district_id,
            'school_id' => $this->school->id, 'subject_id' => $this->subject->id, 'exam_year' => '2026', 'created_by' => $this->admin->id, 'status' => 'draft', 'batch_code' => 'BATCH-TEST'
        ]);

        // Save mark first
        $payload = [
            'candidate_id' => $cand->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
            'mark_import_batch_id' => $batch->id,
        ];

        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $payload);
        $response->assertOk();

        $this->assertSame(1, RawMark::where('candidate_id', $cand->id)->count());

        // Save edited mark again
        $payload['score'] = 48;
        $responseEdit = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $payload);
        $responseEdit->assertOk();

        // Verify that it updated the existing record instead of creating a duplicate row
        $this->assertSame(1, RawMark::where('candidate_id', $cand->id)->count());
        $this->assertEquals(48, RawMark::where('candidate_id', $cand->id)->value('paper_1_marks'));
    }
}
