<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\Role;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\SubjectMarks;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class PsleCandidateRegistrationLinkageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $schoolUlanda;
    private School $schoolOther;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::create([
            'code' => 'PSLE',
            'name' => 'Primary School Leaving Examination',
            'education_level' => 'PRIMARY',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => '01',
            'name' => 'KISWAHILI',
            'category' => 'ARTS',
            'written_papers' => 1,
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $region = Region::create(['code' => 'R01', 'name' => 'IRINGA']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'D01',
            'name' => 'IRINGA',
        ]);

        // Create ULANDA Primary School
        $this->schoolUlanda = School::create([
            'code' => 'PS0402107',
            'name' => 'ULANDA PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $district->id,
            'region_id' => $region->id,
        ]);

        // Create another school for mismatch tests
        $this->schoolOther = School::create([
            'code' => 'PS0402999',
            'name' => 'OTHER PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $district->id,
            'region_id' => $region->id,
        ]);
    }

    private function makeCsvUpload(string $csvContent): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('candidates.csv', $csvContent);
    }

    /**
     * Test repair registrations command.
     */
    public function test_repair_registrations_command_successfully_creates_missing_registrations()
    {
        // Create candidate with exam_type 'MOCK' and missing registration
        $candidate1 = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0003',
            'full_name' => 'BARAKA FREDY MUYINGA',
            'gender' => 'M',
            'exam_type' => 'MOCK',
            'is_active' => true,
        ]);

        // Create candidate with exam_type 'PSLE' and already registered
        $candidate2 = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0001',
            'full_name' => 'ADAM EMMANUEL RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate2->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-12345',
            'is_active' => true,
            'status' => 'registered',
        ]);

        // Run Dry-Run command
        $this->artisan('psle:repair-candidate-registrations', [
            '--school' => 'PS0402107',
        ])
        ->expectsOutputToContain('Found 1 candidate(s) missing PSLE 2026 registrations.')
        ->expectsOutputToContain('[DRY RUN] Run with --commit to save registrations.')
        ->assertExitCode(0);

        // Run Commit command
        $this->artisan('psle:repair-candidate-registrations', [
            '--school' => 'PS0402107',
            '--commit' => true,
        ])
        ->expectsOutputToContain('Successfully repaired 1 candidate exam registration(s).')
        ->assertExitCode(0);

        // Verify registration created and exam_type updated in database
        $candidate1->refresh();
        $this->assertEquals('PSLE', $candidate1->exam_type);
        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $candidate1->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
        ]);
    }

    /**
     * Test repair candidate school links command.
     */
    public function test_repair_candidate_school_links_command_moves_school_link_safely()
    {
        // Candidate 1: Mismatched prefix (PS0402107) linked to schoolOther, has NO marks
        $candidateSafe = Candidate::create([
            'school_id' => $this->schoolOther->id,
            'candidate_id' => 'PS0402107-0004',
            'full_name' => 'CARLOS YOHANES MSOKELE',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Candidate 2: Mismatched prefix (PS0402107) linked to schoolOther, has marks (BLOCKED)
        $candidateBlocked = Candidate::create([
            'school_id' => $this->schoolOther->id,
            'candidate_id' => 'PS0402107-0005',
            'full_name' => 'CHRISTIAN EZEKIEL FUTE',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Create mark entry for blocked candidate
        SubjectMarks::create([
            'candidate_id' => $candidateBlocked->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->psle->id,
            'marks_obtained' => 45.0,
            'year' => 2026,
        ]);

        // Run Dry-Run command
        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Found 2 candidate(s) with mismatched school codes.')
            ->expectsOutputToContain('Safe to Repair')
            ->expectsOutputToContain('BLOCKED_HAS_MARKS')
            ->expectsOutputToContain('[DRY RUN] Run with --commit to repair')
            ->assertExitCode(0);

        // Run Commit command
        $this->artisan('psle:repair-candidate-school-links', ['--commit' => true])
            ->expectsOutputToContain('Successfully repaired 1 candidate school link(s).')
            ->assertExitCode(0);

        // Verify CandidateSafe was moved to Ulanda school
        $candidateSafe->refresh();
        $this->assertEquals($this->schoolUlanda->id, $candidateSafe->school_id);

        // Verify CandidateBlocked remained under schoolOther
        $candidateBlocked->refresh();
        $this->assertEquals($this->schoolOther->id, $candidateBlocked->school_id);
    }

    /**
     * Test Pupil Register displays candidate even if exam_type is temporarily mismatching but registered.
     */
    public function test_pupil_register_displays_registered_candidates_regardless_of_base_exam_type()
    {
        $this->withoutMiddleware();

        // Create a candidate with exam_type 'MOCK' but registered for PSLE 2026
        $candidate = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0006',
            'full_name' => 'DANIEL HASHIMU LUHWAVI',
            'gender' => 'M',
            'exam_type' => 'MOCK',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-9999',
            'is_active' => true,
            'status' => 'registered',
        ]);

        // Query the register
        $response = $this->actingAs($this->admin)->getJson("/api/exam-types/PSLE/candidates?school_id={$this->schoolUlanda->id}&exam_year=2026");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.candidate_id', 'PS0402107-0006');
    }

    /**
     * Test CSV import commit in skip mode creates registrations for existing candidates.
     */
    public function test_import_commit_creates_registration_for_existing_candidates_in_skip_mode()
    {
        $this->withoutMiddleware();

        // Create existing candidate without registration
        $candidate = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0003',
            'full_name' => 'BARAKA FREDY MUYINGA',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0402107-0003,20200187125,BARAKA FREDY MUYINGA,M,PS0402107\n";

        $file = $this->makeCsvUpload($csvContent);

        // Commit Import
        $response = $this->actingAs($this->admin)->postJson('/api/candidates/import/commit', [
            'file' => $file,
            'exam_type' => 'PSLE',
            'exam_year' => '2026',
            'on_exists_mode' => 'skip',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('skipped_count', 1);

        // Verify registration was created successfully
        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
        ]);
    }

    /**
     * Test CSV import commit handles duplicate registration conflicts safely.
     */
    public function test_import_commit_handles_duplicate_registration_conflicts_safely()
    {
        $this->withoutMiddleware();

        // 1. Create a candidate under schoolOther (conflict registration will be linked to this candidate)
        $conflictCandidate = Candidate::create([
            'school_id' => $this->schoolOther->id,
            'candidate_id' => 'PS0402107-0099',
            'full_name' => 'CONFLICT PUPIL',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Create the registration for the conflict candidate
        CandidateExamRegistration::create([
            'candidate_id' => $conflictCandidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0402107-0034',
            'is_active' => true,
            'status' => 'APPROVED',
        ]);

        // 2. Prepare a CSV file that tries to import candidate_id PS0402107-0034 (conflict) and PS0402107-0001 (valid)
        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0402107-0034,20200187125,CONFLICT PUPIL,M,PS0402107\n"
            . "PS0402107-0001,20200187126,VALID PUPIL,F,PS0402107\n";

        $file = $this->makeCsvUpload($csvContent);

        // Commit Import
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/candidates/import/commit', [
            'file' => $file,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->schoolUlanda->id,
            'on_exists_mode' => 'skip',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.inserted', 1);
        $response->assertJsonPath('summary.duplicate_conflicts', 1);

        // Verify valid candidate was registered successfully
        $validCandidate = Candidate::where('candidate_id', 'PS0402107-0001')->first();
        $this->assertNotNull($validCandidate);
        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $validCandidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0402107-0001',
        ]);
    }

    /**
     * Test CSV import commit skips true duplicate conflict without creating temp registration.
     */
    public function test_import_commit_skips_true_duplicate_conflict_without_creating_temp_registration()
    {
        $this->withoutMiddleware();

        // Candidate A (existing registration owner)
        $candidateA = Candidate::create([
            'school_id' => $this->schoolOther->id,
            'candidate_id' => 'PS0402107-0035',
            'full_name' => 'KYUNYU YOHANA KITILA',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Existing registration pointing to Candidate A, but using registration_number PSLE-PS0402107-0034
        $reg = CandidateExamRegistration::create([
            'candidate_id' => $candidateA->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0402107-0034',
            'is_active' => true,
            'status' => 'APPROVED',
        ]);

        // Candidate B (conflicting candidate being imported)
        $candidateB = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0034',
            'full_name' => 'JOYCE AYUBU WILSON',
            'gender' => 'F',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Prepare CSV with Candidate B's details
        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0402107-0034,20200187125,JOYCE AYUBU WILSON,F,PS0402107\n";

        $file = $this->makeCsvUpload($csvContent);

        // Commit Import
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/candidates/import/commit', [
            'file' => $file,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->schoolUlanda->id,
            'on_exists_mode' => 'skip',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.duplicate_conflicts', 1);
        $response->assertJsonPath('summary.inserted', 0);
        $response->assertJsonPath('summary.updated', 0);

        // Verify Candidate B was skipped and does NOT have any registrations
        $this->assertDatabaseMissing('candidate_exam_registrations', [
            'candidate_id' => $candidateB->id,
        ]);

        // Verify Candidate A still owns the registration
        $this->assertDatabaseHas('candidate_exam_registrations', [
            'id' => $reg->id,
            'candidate_id' => $candidateA->id,
            'registration_number' => 'PSLE-PS0402107-0034',
        ]);
    }

    /**
     * Test CSV import commit continues valid rows after duplicate conflict.
     */
    public function test_import_commit_continues_valid_rows_after_duplicate_conflict()
    {
        $this->withoutMiddleware();

        // Candidate A (existing registration owner)
        $candidateA = Candidate::create([
            'school_id' => $this->schoolOther->id,
            'candidate_id' => 'PS0402107-0035',
            'full_name' => 'KYUNYU YOHANA KITILA',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Existing registration
        $reg = CandidateExamRegistration::create([
            'candidate_id' => $candidateA->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0402107-0034',
            'is_active' => true,
            'status' => 'APPROVED',
        ]);

        // Prepare CSV with Candidate B (conflict) and Candidate C (valid new)
        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0402107-0034,20200187125,JOYCE AYUBU WILSON,F,PS0402107\n"
            . "PS0402107-0001,20200187126,VALID PUPIL,M,PS0402107\n";

        $file = $this->makeCsvUpload($csvContent);

        // Commit Import
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/candidates/import/commit', [
            'file' => $file,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->schoolUlanda->id,
            'on_exists_mode' => 'skip',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.duplicate_conflicts', 1);
        $response->assertJsonPath('summary.inserted', 1);

        // Verify Candidate C was registered successfully
        $candidateC = Candidate::where('candidate_id', 'PS0402107-0001')->first();
        $this->assertNotNull($candidateC);
        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $candidateC->id,
            'registration_number' => 'PSLE-PS0402107-0001',
        ]);
    }

    /**
     * Test same candidate existing registration is reused safely.
     */
    public function test_same_candidate_existing_registration_is_reused_safely()
    {
        $this->withoutMiddleware();

        // Candidate D (existing same candidate)
        $candidateD = Candidate::create([
            'school_id' => $this->schoolUlanda->id,
            'candidate_id' => 'PS0402107-0002',
            'full_name' => 'SAME CANDIDATE',
            'gender' => 'F',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Existing registration with old year/status
        $reg = CandidateExamRegistration::create([
            'candidate_id' => $candidateD->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2025,
            'registration_number' => 'PSLE-PS0402107-0002',
            'is_active' => true,
            'status' => 'PENDING',
        ]);

        // Prepare CSV with Candidate D
        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0402107-0002,20200187127,SAME CANDIDATE,F,PS0402107\n";

        $file = $this->makeCsvUpload($csvContent);

        // Commit Import
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/candidates/import/commit', [
            'file' => $file,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->schoolUlanda->id,
            'on_exists_mode' => 'replace',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.updated', 1);

        // Verify the existing registration was updated safely
        $reg->refresh();
        $this->assertEquals('APPROVED', $reg->status);
        $this->assertEquals(2026, $reg->year);

        // Verify there is exactly 1 registration in candidate_exam_registrations for Candidate D
        $count = CandidateExamRegistration::where('candidate_id', $candidateD->id)->count();
        $this->assertEquals(1, $count);
    }
}
