<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\Subject;
use App\Models\RawMark;
use App\Models\MarkImportBatch;
use App\Models\MarkEntryAssignment;

class PsleMeoCandidateRosterConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $meoIringa;
    private ExamYear $examYear2026;
    private ExamType $psle;
    private School $schoolUlanda;
    private School $schoolOther;
    private Subject $subjectKiswahili;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin Role
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'meo'], ['name' => 'Mark Entry Officer']);

        // Create Admin User
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        // Create IRINGA Region MEO
        $regionIringa = Region::create(['code' => 'R01', 'name' => 'IRINGA']);
        $this->meoIringa = User::factory()->create([
            'is_admin' => false,
            'portal_role' => 'meo',
            'region_id' => $regionIringa->id,
            'status' => 'active',
        ]);

        // Create active year 2026
        $this->examYear2026 = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        // Create PSLE exam type
        $this->psle = ExamType::create([
            'code' => 'PSLE',
            'name' => 'Primary School Leaving Examination',
            'education_level' => 'PRIMARY',
        ]);

        // Create a PSLE subject
        $this->subjectKiswahili = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => '01',
            'name' => 'KISWAHILI',
            'category' => 'ARTS',
            'written_papers' => 1,
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $districtIringa = District::create([
            'region_id' => $regionIringa->id,
            'code' => 'D01',
            'name' => 'IRINGA',
        ]);

        // Create ULANDA Primary School in IRINGA region
        $this->schoolUlanda = School::create([
            'code' => 'PS0402107',
            'name' => 'ULANDA PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $districtIringa->id,
            'region_id' => $regionIringa->id,
        ]);

        // Create another school in a different region (e.g. DODOMA)
        $regionDodoma = Region::create(['code' => 'R02', 'name' => 'DODOMA']);
        $districtDodoma = District::create([
            'region_id' => $regionDodoma->id,
            'code' => 'D02',
            'name' => 'MPWAPWA',
        ]);

        $this->schoolOther = School::create([
            'code' => 'PS0402999',
            'name' => 'OTHER PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $districtDodoma->id,
            'region_id' => $regionDodoma->id,
        ]);
    }

    /**
     * Test consistency between Admin Pupil Register and MEO Entry Sheet candidate rosters.
     */
    public function test_admin_and_meo_rosters_are_consistent_and_include_legacy_mock_candidates()
    {
        $this->withoutMiddleware();

        // 1. Create a legacy candidate with exam_type 'MOCK' but registered for PSLE 2026
        $candidateMock = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0002',
            'full_name' => 'BARAKA FREDY MUYINGA',
            'gender' => 'M',
            'exam_type' => 'MOCK',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidateMock->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear2026->id,
            'year' => 2026,
            'registration_number' => 'PS0402107-0002',
            'is_active' => true,
            'status' => 'registered',
        ]);

        // 2. Create a standard candidate with exam_type 'PSLE' and registered for PSLE 2026
        $candidatePsle = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0001',
            'full_name' => 'ADAM EMMANUEL RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidatePsle->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear2026->id,
            'year' => 2026,
            'registration_number' => 'PS0402107-0001',
            'is_active' => true,
            'status' => 'registered',
        ]);

        // 3. Query Admin Pupil Register count
        $adminResponse = $this->actingAs($this->admin)->getJson(
            "/api/exam-types/PSLE/candidates?school_id={$this->schoolUlanda->id}&exam_year=2026"
        );
        $adminResponse->assertStatus(200);
        $adminCandidates = $adminResponse->json('data');
        $this->assertCount(2, $adminCandidates);

        // 4. Query MEO Entry Sheet roster
        $meoResponse = $this->actingAs($this->meoIringa)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolUlanda->id}&subject_id={$this->subjectKiswahili->id}&exam_year_id={$this->examYear2026->id}"
        );
        $meoResponse->assertStatus(200);

        $meoCandidates = $meoResponse->original->getData()['candidates'];
        $this->assertCount(2, $meoCandidates);

        // Assert they are identical
        $adminIds = collect($adminCandidates)->pluck('candidate_id')->sort()->values()->toArray();
        $meoIds = collect($meoCandidates)->pluck('candidate_id')->sort()->values()->toArray();
        $this->assertEquals($adminIds, $meoIds);

        // Assert sorting is ascending by candidate index number
        $this->assertEquals('PS0402107-0001', $meoCandidates[0]->candidate_id);
        $this->assertEquals('PS0402107-0002', $meoCandidates[1]->candidate_id);
    }

    /**
     * Test candidates with no marks recorded still display on the MEO sheet.
     */
    public function test_unmarked_candidates_display_successfully()
    {
        $this->withoutMiddleware();

        $candidate = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0001',
            'full_name' => 'ADAM EMMANUEL RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear2026->id,
            'year' => 2026,
            'registration_number' => 'PS0402107-0001',
            'is_active' => true,
            'status' => 'registered',
        ]);

        // Load entry sheet and check that the candidate displays
        $response = $this->actingAs($this->meoIringa)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolUlanda->id}&subject_id={$this->subjectKiswahili->id}&exam_year_id={$this->examYear2026->id}"
        );
        $response->assertStatus(200);

        $candidates = $response->original->getData()['candidates'];
        $this->assertCount(1, $candidates);
        $this->assertCount(0, $candidates[0]->rawMarks); // No marks recorded, but candidate is visible
    }

    /**
     * Test regional MEO scoping permissions (cannot access school outside region).
     */
    public function test_regional_meo_access_is_enforced()
    {
        $this->withoutMiddleware();

        // Try to access schoolUlanda (inside IRINGA region) -> Allowed
        $responseAllowed = $this->actingAs($this->meoIringa)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolUlanda->id}&subject_id={$this->subjectKiswahili->id}&exam_year_id={$this->examYear2026->id}"
        );
        $responseAllowed->assertStatus(200);

        // Try to access schoolOther (inside DODOMA region, outside MEO's region) -> Blocked
        $responseBlocked = $this->actingAs($this->meoIringa)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolOther->id}&subject_id={$this->subjectKiswahili->id}&exam_year_id={$this->examYear2026->id}"
        );
        $responseBlocked->assertRedirect();
        $responseBlocked->assertSessionHas('error', 'Unauthorized: This school is outside your assigned region.');
    }

    /**
     * Test existing marks are correctly merged.
     */
    public function test_existing_marks_are_merged_without_hiding_missing_or_unmarked_candidates()
    {
        $this->withoutMiddleware();

        // 1. Unmarked Candidate
        $candidate1 = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0001',
            'full_name' => 'ADAM EMMANUEL RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candidate1->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear2026->id,
            'year' => 2026,
            'registration_number' => 'PS0402107-0001',
            'status' => 'registered',
        ]);

        // 2. Marked Candidate
        $candidate2 = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0002',
            'full_name' => 'BARAKA FREDY MUYINGA',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candidate2->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear2026->id,
            'year' => 2026,
            'registration_number' => 'PS0402107-0002',
            'status' => 'registered',
        ]);

        // Create a Mark Import Batch
        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH-TEST-99',
            'batch_name' => 'Test Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear2026->id,
            'region_id' => $this->schoolUlanda->region_id,
            'district_id' => $this->schoolUlanda->district_id,
            'school_id' => $this->schoolUlanda->id,
            'subject_id' => $this->subjectKiswahili->id,
            'exam_type_id' => $this->psle->id,
            'created_by' => $this->meoIringa->id,
            'status' => 'active',
            'total_records' => 1,
        ]);

        // Create Raw Mark for candidate 2 using factory
        RawMark::factory()->create([
            'candidate_id' => $candidate2->id,
            'candidate_index_number' => $candidate2->candidate_id,
            'school_id' => $this->schoolUlanda->id,
            'subject_id' => $this->subjectKiswahili->id,
            'exam_year_id' => $this->examYear2026->id,
            'mark_import_batch_id' => $batch->id,
            'paper_1_marks' => 42.0,
            'subject_status' => null,
            'has_errors' => false,
        ]);

        // Load MEO entry sheet
        $response = $this->actingAs($this->meoIringa)->get(
            "/mark-entry/psle?view=entry-sheet&school_id={$this->schoolUlanda->id}&subject_id={$this->subjectKiswahili->id}&exam_year_id={$this->examYear2026->id}"
        );
        $response->assertStatus(200);

        $candidates = $response->original->getData()['candidates'];
        $this->assertCount(2, $candidates);

        // Candidate 1 (unmarked) has no rawMarks in the Kiswahili subject
        $this->assertCount(0, $candidates[0]->rawMarks);

        // Candidate 2 (marked) has 1 Kiswahili mark
        $this->assertCount(1, $candidates[1]->rawMarks);
        $this->assertEquals(42.0, $candidates[1]->rawMarks[0]->paper_1_marks);
    }
}
