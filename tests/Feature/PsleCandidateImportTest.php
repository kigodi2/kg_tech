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

class PsleCandidateImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;

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

        $region = Region::create(['code' => 'R01', 'name' => 'DODOMA']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'D01',
            'name' => 'MPWAPWA',
        ]);

        $this->school = School::create([
            'code' => 'PS0404006',
            'name' => 'Mpwapwa Primary School',
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

    public function test_import_with_mixed_valid_and_invalid_rows_succeeds_and_imports_only_valid()
    {
        $this->withoutMiddleware();

        // 1 valid row (matching target school prefix PS0404006-0001, valid sex M, valid PReM No)
        // 1 invalid row due to invalid sex (X)
        // 1 invalid row due to duplicate candidate ID inside file
        $csvContent = "candidate_number,PReM_No,pupil_name,sex,school_code\n"
            . "PS0404006-0001,20201520091,ASHERI JOSHUA CHAULA,M,PS0404006\n" // Valid
            . "PS0404006-0002,20201520092,INVALID GENDER PUPIL,X,PS0404006\n" // Invalid sex
            . "PS0404006-0001,20201520091,ASHERI JOSHUA CHAULA,M,PS0404006\n"; // Duplicate CNO inside file

        $file = $this->makeCsvUpload($csvContent);

        // 1. Call Validate API
        $response = $this->actingAs($this->admin)->postJson('/api/candidates/import/validate', [
            'file' => $file,
            'exam_type' => 'PSLE',
            'exam_year' => '2026',
            'on_exists_mode' => 'skip',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', false); // Because there are validation errors
        $response->assertJsonPath('can_import', true); // But we still have valid rows to import
        $response->assertJsonPath('summary.valid_rows', 1);
        $response->assertJsonPath('summary.invalid_rows', 2);

        // Recreate file stream since UploadedFile fake content gets consumed/read
        $fileForCommit = $this->makeCsvUpload($csvContent);

        // 2. Call Commit API (which used to be blocked by the 'success' check)
        $commitResponse = $this->actingAs($this->admin)->postJson('/api/candidates/import/commit', [
            'file' => $fileForCommit,
            'exam_type' => 'PSLE',
            'exam_year' => '2026',
            'on_exists_mode' => 'skip',
        ]);

        $commitResponse->assertStatus(200);
        $commitResponse->assertJsonPath('success', true);
        $commitResponse->assertJsonPath('imported_count', 1);
        $commitResponse->assertJsonPath('skipped_count', 0);

        // Verify valid candidate was successfully saved to DB
        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'PS0404006-0001',
            'full_name' => 'ASHERI JOSHUA CHAULA',
            'gender' => 'M',
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
        ]);

        // Verify invalid candidate with gender X was NOT saved to DB
        $this->assertDatabaseMissing('candidates', [
            'full_name' => 'INVALID GENDER PUPIL',
        ]);

        // Verify that candidate_exam_registrations row exists for PSLE + selected exam year
        $candidate = Candidate::where('candidate_id', 'PS0404006-0001')->firstOrFail();
        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'status' => 'PENDING',
        ]);
    }
}
