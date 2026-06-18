<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\School;
use App\Models\Candidate;
use App\Models\SubjectMarks;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\District;
use App\Models\Subject;

class PsleCandidateSchoolLinkRepairTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolActiveTarget;
    private School $schoolActiveCurrent;
    private School $schoolInactive;
    private ExamType $psle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->psle = ExamType::create([
            'code' => 'PSLE',
            'name' => 'Primary School Leaving Examination',
            'education_level' => 'PRIMARY',
        ]);

        $region = Region::create(['code' => 'R01', 'name' => 'IRINGA']);
        $district = District::create([
            'region_id' => $region->id,
            'code' => 'D01',
            'name' => 'IRINGA MC',
        ]);

        // Create Active Target School
        $this->schoolActiveTarget = School::create([
            'code' => 'PS0402107',
            'name' => 'ULANDA PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $district->id,
            'region_id' => $region->id,
            'is_active' => true,
        ]);

        // Create Active Current Wrong School
        $this->schoolActiveCurrent = School::create([
            'code' => 'PS0402999',
            'name' => 'OTHER PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $district->id,
            'region_id' => $region->id,
            'is_active' => true,
        ]);

        // Create Inactive School
        $this->schoolInactive = School::create([
            'code' => 'PS0402888',
            'name' => 'INACTIVE PRIMARY SCHOOL',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $district->id,
            'region_id' => $region->id,
            'is_active' => false,
        ]);
    }

    /**
     * Test Case 1: Exact prefix target school exists and candidate has no marks => safe repair.
     */
    public function test_safe_repair_candidate_school_link_moves_successfully()
    {
        $candidate = Candidate::create([
            'school_id' => $this->schoolActiveCurrent->id,
            'candidate_id' => 'PS0402107-0001',
            'full_name' => 'BARAKA FREDY MUYINGA',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Dry-run execution
        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Wrong school and safe to move: 1')
            ->expectsOutputToContain('[DRY RUN] Run with --commit to repair')
            ->assertExitCode(0);

        // Verify not moved yet in dry run
        $candidate->refresh();
        $this->assertEquals($this->schoolActiveCurrent->id, $candidate->school_id);

        // Commit execution
        $this->artisan('psle:repair-candidate-school-links', ['--commit' => true])
            ->expectsOutputToContain('Successfully repaired 1 candidate school link(s).')
            ->assertExitCode(0);

        // Verify moved to correct school
        $candidate->refresh();
        $this->assertEquals($this->schoolActiveTarget->id, $candidate->school_id);
    }

    /**
     * Test Case 2: Exact target is missing but current school code starts with candidate prefix
     * => classified as ALREADY_ACCEPTABLE_PREFIX_VARIANT (not modified).
     */
    public function test_benign_prefix_variant_tolerance_classified_and_ignored()
    {
        // Candidate prefix is 'PS0402107' (Ulanda) but linked to school with code 'PS04021070' (which doesn't exist, but starts with prefix)
        // Let's create school with code 'PS04021070' and link candidate with prefix 'PS0402107'
        $schoolLong = School::create([
            'code' => 'PS04021070',
            'name' => 'ULANDA EXTENSION',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $this->schoolActiveTarget->district_id,
            'region_id' => $this->schoolActiveTarget->region_id,
            'is_active' => true,
        ]);

        $candidate = Candidate::create([
            'school_id' => $schoolLong->id,
            'candidate_id' => 'PS0402107-0002', // Prefix 'PS0402107' has no exact target since we deleted or don't use it, wait, schoolActiveTarget has exactly 'PS0402107'!
            // Wait, if Ulanda exists, it has exact target. To test missing exact target but current starts with it:
            // Let's make candidate prefix 'PS0402998' (which doesn't exist) and link it to schoolLong with code 'PS04029980' (starts with 'PS0402998')
            'full_name' => 'ADAM RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Wait, let's create schoolLong code as 'PS04029980' and candidate code as 'PS0402998-0001'
        $schoolLong->update(['code' => 'PS04029980']);
        $candidate->update(['candidate_id' => 'PS0402998-0001']);

        // Run repair command
        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Prefix variant already linked to active school: 1')
            ->expectsOutputToContain('ALREADY_ACCEPTABLE_PREFIX_VARIANT')
            ->assertExitCode(0);

        // Verify candidate not modified
        $candidate->refresh();
        $this->assertEquals($schoolLong->id, $candidate->school_id);
    }

    /**
     * Test Case 3: Exact target missing and current school is unrelated => blocked (format mismatch / missing target).
     */
    public function test_unrelated_missing_target_blocked()
    {
        $candidate = Candidate::create([
            'school_id' => $this->schoolActiveCurrent->id,
            'candidate_id' => 'PS9999999-0001', // Completely non-existent school
            'full_name' => 'ADAM RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Missing target school codes: 1')
            ->expectsOutputToContain('BLOCKED_MISSING_TARGET_SCHOOL')
            ->assertExitCode(0);

        // Candidate with a minor typo in prefix (length 9 vs 9 and levenshtein <= 3) -> Format Mismatch
        $candidateTypo = Candidate::create([
            'school_id' => $this->schoolActiveCurrent->id,
            'candidate_id' => 'PS0402998-0001', // Typo vs 'PS0402999' (differs by 1 char)
            'full_name' => 'CARLOS FREDY',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Missing target school codes: 2')
            ->expectsOutputToContain('BLOCKED_CODE_FORMAT_MISMATCH')
            ->assertExitCode(0);
    }

    /**
     * Test Case 4: Target school exists but is inactive => blocked.
     */
    public function test_inactive_target_school_blocked()
    {
        $candidate = Candidate::create([
            'school_id' => $this->schoolActiveCurrent->id,
            'candidate_id' => 'PS0402888-0001', // Target school is schoolInactive
            'full_name' => 'ADAM RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Target inactive: 1')
            ->expectsOutputToContain('BLOCKED_TARGET_INACTIVE')
            ->assertExitCode(0);

        $candidate->refresh();
        $this->assertEquals($this->schoolActiveCurrent->id, $candidate->school_id);
    }

    /**
     * Test Case 5: Candidate has marks => blocked.
     */
    public function test_candidate_with_marks_blocked()
    {
        $candidate = Candidate::create([
            'school_id' => $this->schoolActiveCurrent->id,
            'candidate_id' => 'PS0402107-0001', // Target school is schoolActiveTarget
            'full_name' => 'ADAM RUBEN',
            'gender' => 'M',
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        // Add a mark entry using RawMark factory
        \App\Models\RawMark::factory()->create([
            'candidate_id' => $candidate->id,
            'candidate_index_number' => $candidate->candidate_id,
            'school_id' => $this->schoolActiveCurrent->id,
            'subject_id' => 1,
            'exam_year_id' => 1,
            'paper_1_marks' => 45,
            'subject_status' => null,
            'has_errors' => false,
        ]);

        $this->artisan('psle:repair-candidate-school-links')
            ->expectsOutputToContain('Wrong school but has marks: 1')
            ->expectsOutputToContain('BLOCKED_HAS_MARKS')
            ->assertExitCode(0);

        $candidate->refresh();
        $this->assertEquals($this->schoolActiveCurrent->id, $candidate->school_id);
    }
}
