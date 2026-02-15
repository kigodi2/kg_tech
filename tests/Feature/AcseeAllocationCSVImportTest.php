<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\Subject;
use App\Models\Combination;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Services\AcseeAllocationCSVImporter;
use App\Services\AcseeAllocationTemplateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AcseeAllocationCSVImportTest extends TestCase
{
    protected ExamYear $examYear;
    protected ExamType $examType;
    protected Subject $generalStudies;
    protected Combination $combination;
    protected AcseeAllocationCSVImporter $importer;
    protected AcseeAllocationTemplateService $templateService;

    public function setUp(): void
    {
        parent::setUp();

        // Create exam type
        $this->examType = ExamType::create([
            'code' => 'ACSEE',
            'name' => 'ACSEE',
            'education_level' => 'SECONDARY',
        ]);

        // Create exam year
        $this->examYear = ExamYear::create([
            'year' => 2026,
            'year_label' => '2026',
            'is_active' => true,
        ]);

        // Create General Studies subject
        $this->generalStudies = Subject::create([
            'code' => '111',
            'name' => 'General Studies',
            'category' => 'SCIENCE',
            'writtenPapers' => 1,
            'hasPractical' => false,
            'hasProject' => false,
            'is_active' => true,
        ]);

        // Create other subjects
        Subject::create(['code' => '001', 'name' => 'Physics', 'category' => 'SCIENCE', 'writtenPapers' => 2, 'hasPractical' => false, 'hasProject' => false, 'is_active' => true]);
        Subject::create(['code' => '002', 'name' => 'Chemistry', 'category' => 'SCIENCE', 'writtenPapers' => 2, 'hasPractical' => false, 'hasProject' => false, 'is_active' => true]);
        Subject::create(['code' => '003', 'name' => 'Biology', 'category' => 'SCIENCE', 'writtenPapers' => 2, 'hasPractical' => false, 'hasProject' => false, 'is_active' => true]);

        // Create combination
        $this->combination = Combination::create([
            'code' => 'PCB',
            'exam_type_id' => $this->examType->id,
            'subjects' => 'Physics, Chemistry, Biology',
            'category' => 'SCIENCE',
        ]);

        // Attach subjects to combination
        $physics = Subject::where('code', '001')->first();
        $chemistry = Subject::where('code', '002')->first();
        $biology = Subject::where('code', '003')->first();

        $this->combination->subjects()->attach([
            $this->generalStudies->id,
            $physics->id,
            $chemistry->id,
            $biology->id,
        ]);

        $this->importer = app(AcseeAllocationCSVImporter::class);
        $this->templateService = app(AcseeAllocationTemplateService::class);
    }

    /**
     * Test: SCHOOL candidate allocation with valid combination
     */
    public function test_school_allocation_with_valid_combination()
    {
        // Create SCHOOL candidate
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0001',
            'full_name' => 'Test Student',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        // Register candidate for exam
        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        // Create CSV file
        $csv = "exam_year,index_number,combination_code,replace_allocations\n";
        $csv .= "2026,S0445-0001,PCB,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');

        // Validate
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['total_rows']);
        $this->assertEquals(1, $result['valid_count']);
        $this->assertEquals(0, $result['invalid_count']);
    }

    /**
     * Test: SCHOOL allocation fails if combination missing
     */
    public function test_school_allocation_fails_if_combination_missing()
    {
        // Create SCHOOL candidate
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0002',
            'full_name' => 'Test Student 2',
            'gender' => 'F',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $csv = "exam_year,index_number,combination_code,replace_allocations\n";
        $csv .= "2026,S0445-0002,INVALID,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['invalid_count']);
        $this->assertStringContainsString('Combination', $result['errors'][0]['error_messages'][0]);
    }

    /**
     * Test: PRIVATE candidate allocation with valid subject codes
     */
    public function test_private_allocation_with_valid_subject_codes()
    {
        // Create PRIVATE candidate
        $candidate = Candidate::create([
            'candidate_id' => 'P0652-0001',
            'full_name' => 'Private Student',
            'gender' => 'M',
            'candidate_type' => 'PRIVATE',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $csv = "exam_year,index_number,subject_codes,replace_allocations\n";
        $csv .= "2026,P0652-0001,111|001|002|003,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['valid_count']);
    }

    /**
     * Test: PRIVATE allocation fails without General Studies (111)
     */
    public function test_private_allocation_fails_without_general_studies()
    {
        $candidate = Candidate::create([
            'candidate_id' => 'P0652-0002',
            'full_name' => 'Private Student 2',
            'gender' => 'F',
            'candidate_type' => 'PRIVATE',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $csv = "exam_year,index_number,subject_codes,replace_allocations\n";
        $csv .= "2026,P0652-0002,001|002|003,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('General Studies', $result['errors'][0]['error_messages'][0]);
    }

    /**
     * Test: PRIVATE allocation fails with less than 3 principal subjects
     */
    public function test_private_allocation_fails_with_less_than_3_principals()
    {
        $candidate = Candidate::create([
            'candidate_id' => 'P0652-0003',
            'full_name' => 'Private Student 3',
            'gender' => 'M',
            'candidate_type' => 'PRIVATE',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $csv = "exam_year,index_number,subject_codes,replace_allocations\n";
        $csv .= "2026,P0652-0003,111|001|002,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('principal', strtolower($result['errors'][0]['error_messages'][0]));
    }

    /**
     * Test: Candidate type mismatch detection
     */
    public function test_candidate_type_mismatch_detection()
    {
        // Create SCHOOL candidate
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0003',
            'full_name' => 'School Student',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        // Try to import as PRIVATE (subject codes format)
        $csv = "exam_year,index_number,subject_codes,replace_allocations\n";
        $csv .= "2026,S0445-0003,111|001|002|003,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        
        // Should fail when filtering by SCHOOL only (CSV is PRIVATE format)
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'SCHOOL');
        
        $this->assertFalse($result['success']);
    }

    /**
     * Test: Duplicate candidate prevention in file
     */
    public function test_duplicate_candidate_prevention()
    {
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0004',
            'full_name' => 'Duplicate Test',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        // Same candidate listed twice
        $csv = "exam_year,index_number,combination_code,replace_allocations\n";
        $csv .= "2026,S0445-0004,PCB,NO\n";
        $csv .= "2026,S0445-0004,PCB,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Duplicate', $result['errors'][0]['error_messages'][0]);
    }

    /**
     * Test: Replace allocations mode
     */
    public function test_replace_allocations_mode()
    {
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0005',
            'full_name' => 'Replace Test',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        // Insert initial allocation
        $physics = Subject::where('code', '001')->first();
        CandidateSubjectSelection::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
            'subject_id' => $physics->id,
            'year' => 2026,
            'is_principal' => true,
        ]);

        $initialCount = $candidate->subjectSelections()->count();
        $this->assertEquals(1, $initialCount);

        // Import with replace=YES
        $csv = "exam_year,index_number,combination_code,replace_allocations\n";
        $csv .= "2026,S0445-0005,PCB,YES\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->commitImport($file, $this->examYear->id, 'ALL', false);

        $this->assertTrue($result['success']);
        
        // Should now have allocations from PCB combination (4 subjects)
        $newCount = $candidate->subjectSelections()->count();
        $this->assertEquals(4, $newCount);
    }

    /**
     * Test: Idempotency (re-import = safe)
     */
    public function test_idempotency_of_allocations()
    {
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0006',
            'full_name' => 'Idempotency Test',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $csv = "exam_year,index_number,combination_code,replace_allocations\n";
        $csv .= "2026,S0445-0006,PCB,NO\n";

        // Import first time
        $file1 = UploadedFile::fromString($csv, 'test1.csv');
        $result1 = $this->importer->commitImport($file1, $this->examYear->id, 'ALL', false);
        $this->assertTrue($result1['success']);

        $countAfterFirst = $candidate->subjectSelections()->count();

        // Import same file again
        $file2 = UploadedFile::fromString($csv, 'test2.csv');
        $result2 = $this->importer->commitImport($file2, $this->examYear->id, 'ALL', false);
        $this->assertTrue($result2['success']);

        $countAfterSecond = $candidate->subjectSelections()->count();

        // Should be idempotent (same count)
        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    /**
     * Test: Template generation - SCHOOL
     */
    public function test_school_template_generation()
    {
        $content = $this->templateService->generateSchoolTemplate();
        
        $this->assertStringContainsString('exam_year', $content);
        $this->assertStringContainsString('index_number', $content);
        $this->assertStringContainsString('combination_code', $content);
        $this->assertStringContainsString('replace_allocations', $content);
        $this->assertStringContainsString('S0445', $content);
        $this->assertStringContainsString('PCB', $content);
    }

    /**
     * Test: Template generation - PRIVATE
     */
    public function test_private_template_generation()
    {
        $content = $this->templateService->generatePrivateTemplate();
        
        $this->assertStringContainsString('exam_year', $content);
        $this->assertStringContainsString('index_number', $content);
        $this->assertStringContainsString('subject_codes', $content);
        $this->assertStringContainsString('replace_allocations', $content);
        $this->assertStringContainsString('P0652', $content);
        $this->assertStringContainsString('111|', $content);
    }

    /**
     * Test: Error report generation
     */
    public function test_error_report_generation()
    {
        $candidate = Candidate::create([
            'candidate_id' => 'S0445-0007',
            'full_name' => 'Error Test',
            'gender' => 'M',
            'candidate_type' => 'SCHOOL',
            'exam_type' => 'ACSEE',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        // Invalid combination code
        $csv = "exam_year,index_number,combination_code,replace_allocations\n";
        $csv .= "2026,S0445-0007,INVALID,NO\n";

        $file = UploadedFile::fromString($csv, 'test.csv');
        $result = $this->importer->validateCSV($file, $this->examYear->id, 'ALL');
        
        $this->assertFalse($result['success']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(1, $result['errors'][0]['row_number']);
        $this->assertEquals('S0445-0007', $result['errors'][0]['index_number']);
    }
}
