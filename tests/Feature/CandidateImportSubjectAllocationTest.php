<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use App\Services\Candidates\CandidateImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CandidateImportSubjectAllocationTest extends TestCase
{
    protected $importService;
    protected $school;
    protected $district;
    protected $examYear;
    protected $examType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importService = app(CandidateImportService::class);

        // Setup test data
        $this->district = District::firstOrCreate(
            ['code' => 'TEST'],
            ['name' => 'Test District']
        );

        $this->school = School::firstOrCreate(
            ['code' => 'TESTSCH'],
            [
                'name' => 'Test School',
                'district_id' => $this->district->id
            ]
        );

        $this->examYear = ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['year' => 2026, 'is_active' => true]
        );

        $this->examType = ExamType::firstOrCreate(
            ['code' => 'ACSEE'],
            ['name' => 'ACSEE']
        );
    }

    protected function createTestSubjects()
    {
        // Create General Studies (111)
        Subject::firstOrCreate(
            ['code' => '111'],
            [
                'name' => 'General Studies',
                'exam_type_id' => $this->examType->id,
            ]
        );

        // Create principal subjects
        $subjectCodes = ['102', '103', '104', '121', '122'];
        foreach ($subjectCodes as $code) {
            Subject::firstOrCreate(
                ['code' => $code],
                [
                    'name' => "Subject $code",
                    'exam_type_id' => $this->examType->id,
                ]
            );
        }
    }

    /** @test */
    public function test_private_candidate_with_subjects_gets_allocated()
    {
        $this->createTestSubjects();

        // Create CSV with PRIVATE candidate and subjects
        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0001-0001,John Private,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        $result = $this->importService->commitImport($file, '2026', 'ACSEE', 'skip');

        // Verify import succeeded
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported_count']);
        $this->assertGreaterThan(0, $result['allocations_created_count']);

        // Verify candidate was created
        $candidate = Candidate::where('candidate_id', 'P0001-0001')->first();
        $this->assertNotNull($candidate);
        $this->assertEquals('John Private', $candidate->full_name);
        $this->assertEquals('PRIVATE', $candidate->candidate_type);

        // Verify allocations were created
        $allocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->where('exam_type_id', $this->examType->id)
            ->where('exam_year_id', $this->examYear->id)
            ->get();

        $this->assertCount(4, $allocations);

        // Verify General Studies is present and is_principal=false
        $gs = $allocations->first(fn($a) => $a->subject->code === '111');
        $this->assertNotNull($gs);
        $this->assertFalse($gs->is_principal);

        // Verify principal subjects are marked is_principal=true
        $principals = $allocations->filter(fn($a) => $a->subject->code !== '111');
        $this->assertCount(3, $principals);
        $principals->each(fn($p) => $this->assertTrue($p->is_principal));
    }

    /** @test */
    public function test_missing_general_studies_validation_fails()
    {
        $this->createTestSubjects();

        // Create CSV without General Studies (111)
        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0002-0001,John NoGS,M,TESTSCH,PRIVATE,102|103|121,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        // Validation phase should succeed (candidate is created)
        $result = $this->importService->commitImport($file, '2026', 'ACSEE', 'skip');

        // Candidate should still be created
        $candidate = Candidate::where('candidate_id', 'P0002-0001')->first();
        $this->assertNotNull($candidate);

        // But no allocations should be created
        $allocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->count();
        $this->assertEquals(0, $allocations);
    }

    /** @test */
    public function test_insufficient_principal_subjects_validation_fails()
    {
        $this->createTestSubjects();

        // Create CSV with only GS + 1 other (needs 3 principals)
        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0003-0001,John Insufficient,M,TESTSCH,PRIVATE,111|102,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        $result = $this->importService->commitImport($file, '2026', 'ACSEE', 'skip');

        // Candidate should be created
        $candidate = Candidate::where('candidate_id', 'P0003-0001')->first();
        $this->assertNotNull($candidate);

        // But no allocations
        $allocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->count();
        $this->assertEquals(0, $allocations);
    }

    /** @test */
    public function test_idempotency_reimport_does_not_duplicate()
    {
        $this->createTestSubjects();

        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0004-0001,John Idempotent,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        // First import
        $result1 = $this->importService->commitImport($file, '2026', 'ACSEE', 'skip');
        $this->assertEquals(1, $result1['imported_count']);
        $allocCount1 = $result1['allocations_created_count'];

        // Get candidate
        $candidate = Candidate::where('candidate_id', 'P0004-0001')->first();
        $initialAllocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->count();

        // Re-import same file (skip mode - should not update)
        $file2 = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );
        $result2 = $this->importService->commitImport($file2, '2026', 'ACSEE', 'skip');

        // Should be skipped
        $this->assertEquals(0, $result2['imported_count']);
        $this->assertEquals(1, $result2['skipped_count']);

        // Allocations should not change
        $finalAllocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->count();
        $this->assertEquals($initialAllocations, $finalAllocations);
    }

    /** @test */
    public function test_replace_mode_reallocates_subjects()
    {
        $this->createTestSubjects();

        // First import
        $csvContent1 = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0005-0001,John Replaced,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026
CSV;

        $file1 = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent1),
            'test.csv'
        );

        $result1 = $this->importService->commitImport($file1, '2026', 'ACSEE', 'skip');
        $candidate = Candidate::where('candidate_id', 'P0005-0001')->first();

        $initialAllocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->pluck('subject_id')
            ->sort()
            ->values();

        // Second import with different subjects
        $csvContent2 = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0005-0001,John Replaced,M,TESTSCH,PRIVATE,111|104|121|122,ACSEE,2026
CSV;

        $file2 = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent2),
            'test.csv'
        );

        // Re-import in REPLACE mode
        $result2 = $this->importService->commitImport($file2, '2026', 'ACSEE', 'replace');

        $this->assertEquals(1, $result2['imported_count']);
        $this->assertEquals(0, $result2['skipped_count']);

        // Fetch updated allocations
        $updatedAllocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->pluck('subject_id')
            ->sort()
            ->values();

        // Should be different (replaced)
        $this->assertNotEquals($initialAllocations, $updatedAllocations);
        $this->assertCount(4, $updatedAllocations);
    }

    /** @test */
    public function test_subject_codes_and_ids_both_supported()
    {
        $this->createTestSubjects();

        // Create CSV with subject codes instead of IDs
        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0006-0001,John Coded,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        $result = $this->importService->commitImport($file, '2026', 'ACSEE', 'skip');

        // Should succeed and allocate
        $this->assertTrue($result['success']);
        $candidate = Candidate::where('candidate_id', 'P0006-0001')->first();
        $allocations = CandidateSubjectSelection::where('candidate_id', $candidate->id)->count();
        $this->assertEquals(4, $allocations);
    }

    /** @test */
    public function test_school_candidate_without_subjects_works()
    {
        // Ensure combination exists
        $combination = \App\Models\Combination::firstOrCreate(
            ['code' => 'PCM'],
            ['name' => 'Physics Chemistry Math']
        );

        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,combination,candidate_type,exam_type,exam_year
S0007-0001,John School,M,TESTSCH,PCM,SCHOOL,,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        $result = $this->importService->commitImport($file, '2026', 'ACSEE', 'skip');

        // School candidates should work without subjects column
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported_count']);
    }

    /** @test */
    public function test_marks_not_deleted_during_allocation()
    {
        $this->createTestSubjects();

        // Create PRIVATE candidate
        $candidate = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'P0008-0001',
            'full_name' => 'John Marks',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'PRIVATE',
            'status' => 'registered',
            'is_active' => true,
        ]);

        // Add a mark
        $subject = Subject::where('code', '102')->first();
        \App\Models\SubjectMarks::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'exam_type_id' => $this->examType->id,
            'mark' => 85,
        ]);

        // Verify mark exists
        $initialMarks = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)->count();
        $this->assertEquals(1, $initialMarks);

        // Now allocate subjects
        $csvContent = <<<CSV
candidate_id,full_name,gender,school_code,candidate_type,subjects,exam_type,exam_year
P0008-0001,John Marks,M,TESTSCH,PRIVATE,111|102|103|121,ACSEE,2026
CSV;

        $file = UploadedFile::createFromBase64(
            'data:text/csv;base64,' . base64_encode($csvContent),
            'test.csv'
        );

        // Import in replace mode
        $result = $this->importService->commitImport($file, '2026', 'ACSEE', 'replace');

        // Mark should still exist
        $finalMarks = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)->count();
        $this->assertEquals(1, $finalMarks);

        // Verify mark value unchanged
        $mark = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)->first();
        $this->assertEquals(85, $mark->mark);
    }
}
