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
use App\Models\District;
use App\Models\Role;
use App\Models\MarkImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsleCandidateUniqueUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Officer']);

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

        $region = Region::create(['code' => 'R01', 'name' => 'DODOMA']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'D01',
            'name' => 'MPWAPWA',
        ]);

        // Explicit school with correct code length "PS0404006"
        $this->school = School::create([
            'code' => 'PS0404006',
            'name' => 'Mpwapwa Primary School',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $district->id,
            'region_id' => $region->id,
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_entry_officer',
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
    }

    private function makeCsvUpload(string $csvContent): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('candidates.csv', $csvContent);
    }

    /**
     * Test bio updates on an existing candidate preserve their raw marks exactly.
     */
    public function test_bio_updates_preserve_marks_exactly(): void
    {
        // 1. Create a candidate
        $candidate = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0001',
            'prem_no' => '20201520091',
            'full_name' => 'ORIGINAL NAME',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'PSLE',
            'status' => 'registered',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-' . $candidate->id,
            'status' => 'APPROVED',
        ]);

        // 2. Attach raw mark to candidate
        $batch = MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year' => '2026',
            'created_by' => $this->admin->id,
            'status' => 'draft',
            'batch_code' => 'BATCH-1',
        ]);

        $rawMark = RawMark::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'candidate_id' => $candidate->id,
            'candidate_index_number' => $candidate->candidate_id,
            'paper_1_marks' => 42,
            'is_validated' => false,
            'row_number' => 1,
            'raw_data' => '{}',
            'mark_import_batch_id' => $batch->id,
        ]);

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $candidate->id,
            'paper_1_marks' => 42,
        ]);

        // 3. Update candidate bio details via POST /api/candidates (updates in-place)
        $payload = [
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0001',
            'prem_no' => '20201520091',
            'full_name' => 'UPDATED NAME',
            'gender' => 'F',
            'exam_type' => 'PSLE',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/candidates', $payload);
        $response->assertStatus(201);

        // Verify the database: candidate is updated in-place and ID did not change
        $candidate->refresh();
        $this->assertEquals('UPDATED NAME', $candidate->full_name);
        $this->assertEquals('F', $candidate->gender);
        $this->assertEquals('20201520091', $candidate->prem_no);

        // Raw marks must still point to the exact same candidate id and remain untouched
        $this->assertDatabaseHas('raw_marks', [
            'id' => $rawMark->id,
            'candidate_id' => $candidate->id,
            'paper_1_marks' => 42,
        ]);
        $this->assertEquals(1, RawMark::where('candidate_id', $candidate->id)->count());
    }

    /**
     * Test single candidate edits validate and block candidate_id and prem_no conflicts.
     */
    public function test_single_candidate_edits_block_conflicts(): void
    {
        // Candidate A
        $candA = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0001',
            'prem_no' => '20201520091',
            'full_name' => 'CANDIDATE A',
            'gender' => 'M',
            'exam_type' => 'PSLE',
        ]);

        // Candidate B
        $candB = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0002',
            'prem_no' => '20201520092',
            'full_name' => 'CANDIDATE B',
            'gender' => 'F',
            'exam_type' => 'PSLE',
        ]);

        // Attempting to update Candidate B's prem_no to Candidate A's prem_no must fail
        $payloadConflictPrem = [
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0002',
            'prem_no' => '20201520091', // Conflict
            'full_name' => 'CANDIDATE B',
            'gender' => 'F',
            'exam_type' => 'PSLE',
        ];

        $responsePrem = $this->actingAs($this->admin)->putJson("/api/candidates/{$candB->id}", $payloadConflictPrem);
        $responsePrem->assertStatus(422);
        $responsePrem->assertJsonValidationErrors('prem_no');

        // Attempting to update Candidate B's candidate_id to Candidate A's candidate_id must fail
        $payloadConflictIndex = [
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0001', // Conflict
            'prem_no' => '20201520092',
            'full_name' => 'CANDIDATE B',
            'gender' => 'F',
            'exam_type' => 'PSLE',
        ];

        $responseIndex = $this->actingAs($this->admin)->putJson("/api/candidates/{$candB->id}", $payloadConflictIndex);
        $responseIndex->assertStatus(422);
        $responseIndex->assertJsonValidationErrors('candidate_id');
    }

    /**
     * Test bulk uploads of duplicate candidates result in updated/skipped counts rather than duplicates.
     */
    public function test_bulk_uploads_of_duplicate_candidates(): void
    {
        // Pre-create Candidate
        $cand = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0001',
            'prem_no' => '20201520091',
            'full_name' => 'OLD NAME',
            'gender' => 'M',
            'exam_type' => 'PSLE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $cand->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-' . $cand->id,
            'status' => 'APPROVED',
        ]);

        // CSV containing the same candidate but with updated name under replace mode
        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0404006-0001,20201520091,UPDATED CSV NAME,M,PS0404006\n";

        $file = $this->makeCsvUpload($csvContent);

        // Preview with mode = replace
        $previewResponse = $this->actingAs($this->officer)->postJson('/mark-entry/psle/candidates/import/validate', [
            'file' => $file,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'on_exists_mode' => 'replace',
        ]);

        $previewResponse->assertStatus(200);
        $previewResponse->assertJsonPath('success', true);
        $previewResponse->assertJsonPath('data.summary.already_existing', 1);
        $previewResponse->assertJsonPath('data.summary.rows_to_replace', 1);

        // Commit with mode = replace
        $fileCommit = $this->makeCsvUpload($csvContent);
        $commitResponse = $this->actingAs($this->officer)->postJson('/mark-entry/psle/candidates/import/commit', [
            'file' => $fileCommit,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'on_exists_mode' => 'replace',
        ]);

        $commitResponse->assertStatus(200);
        $commitResponse->assertJsonPath('success', true);
        $commitResponse->assertJsonPath('summary.updated', 1);
        $commitResponse->assertJsonPath('summary.inserted', 0);

        // Verify Candidate updated in place
        $cand->refresh();
        $this->assertEquals('UPDATED CSV NAME', $cand->full_name);
        $this->assertEquals(1, Candidate::where('candidate_id', 'PS0404006-0001')->count());

        // Now test skip mode
        $csvContentSkip = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0404006-0001,20201520091,SKIP ME NAME,M,PS0404006\n";

        $fileSkip = $this->makeCsvUpload($csvContentSkip);
        $commitSkipResponse = $this->actingAs($this->officer)->postJson('/mark-entry/psle/candidates/import/commit', [
            'file' => $fileSkip,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'on_exists_mode' => 'skip',
        ]);

        $commitSkipResponse->assertStatus(200);
        $commitSkipResponse->assertJsonPath('success', true);
        $commitSkipResponse->assertJsonPath('summary.updated', 0);
        $commitSkipResponse->assertJsonPath('summary.inserted', 0);
        $commitSkipResponse->assertJsonPath('summary.skipped', 1);

        // Name must remain 'UPDATED CSV NAME'
        $cand->refresh();
        $this->assertEquals('UPDATED CSV NAME', $cand->full_name);
    }

    /**
     * Test the duplicate diagnostic Artisan command.
     */
    public function test_diagnostic_command_runs_successfully(): void
    {
        // Add a duplicate pair
        $cand1 = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0001',
            'prem_no' => '20201520091',
            'full_name' => 'CANDIDATE 1',
            'gender' => 'M',
            'exam_type' => 'PSLE',
        ]);

        $cand2 = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'PS0404006-0003',
            'prem_no' => '20201520091', // Duplicate PREM
            'full_name' => 'CANDIDATE 2',
            'gender' => 'M',
            'exam_type' => 'PSLE',
        ]);

        foreach ([$cand1, $cand2] as $c) {
            CandidateExamRegistration::create([
                'candidate_id' => $c->id,
                'exam_type_id' => $this->psle->id,
                'exam_year_id' => $this->examYear->id,
                'year' => 2026,
                'registration_number' => 'REG-' . $c->id,
                'status' => 'APPROVED',
            ]);
        }

        // Execute Artisan command
        $this->artisan('psle:diagnose-duplicates')
            ->assertExitCode(0)
            ->expectsOutput("=== PSLE CANDIDATE DUPLICATES DIAGNOSTIC REPORT ===")
            ->expectsOutput("=== DIAGNOSTICS COMPLETE ===");
    }
}
