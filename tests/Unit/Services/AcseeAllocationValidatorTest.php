<?php

namespace Tests\Unit\Services;

use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use App\Services\AcseeAllocationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcseeAllocationValidatorTest extends TestCase
{
    use RefreshDatabase;
    protected $validator;
    protected $candidate;
    protected $examType;
    protected $examYear;
    protected $generalStudies;
    protected $subject1;
    protected $subject2;
    protected $subject3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new AcseeAllocationValidator();

        // Create exam type and year directly (no factory)
        $this->examType = ExamType::create(['name' => 'ACSEE']);
        $this->examYear = ExamYear::create(['year' => 2026, 'year_label' => '2026']);

        // Create subjects
        $this->generalStudies = Subject::create([
            'code' => '111',
            'name' => 'GENERAL STUDIES'
        ]);
        $this->subject1 = Subject::create(['code' => '101', 'name' => 'Physics']);
        $this->subject2 = Subject::create(['code' => '102', 'name' => 'Chemistry']);
        $this->subject3 = Subject::create(['code' => '103', 'name' => 'Biology']);

        // Create a test candidate directly
        $school = \App\Models\School::create([
            'code' => 'TEST001',
            'name' => 'Test School',
            'registration_number' => 'REG001'
        ]);
        
        $this->candidate = Candidate::create([
            'school_id' => $school->id,
            'candidate_id' => 'TEST-001',
            'full_name' => 'Test Student',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'SCHOOL'
        ]);
    }

    /**
     * Test validation passes with >=3 principals + General Studies
     */
    public function test_validation_passes_with_three_principals_and_general_studies()
    {
        $subjectIds = [
            $this->subject1->id,
            $this->subject2->id,
            $this->subject3->id,
            $this->generalStudies->id,
        ];

        $result = $this->validator->validate(
            $this->candidate,
            $this->examType->id,
            $this->examYear->id,
            $subjectIds
        );

        $this->assertTrue($result['ok']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(3, $result['principal_subject_ids']);
        $this->assertCount(4, $result['all_subject_ids']);
    }

    /**
     * Test validation fails without General Studies
     */
    public function test_validation_fails_without_general_studies()
    {
        $subjectIds = [
            $this->subject1->id,
            $this->subject2->id,
            $this->subject3->id,
        ];

        $result = $this->validator->validate(
            $this->candidate,
            $this->examType->id,
            $this->examYear->id,
            $subjectIds
        );

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('General Studies', $result['errors'][0]);
    }

    /**
     * Test validation fails with less than 3 principal subjects
     */
    public function test_validation_fails_with_less_than_three_principals()
    {
        $subjectIds = [
            $this->subject1->id,
            $this->subject2->id,
            $this->generalStudies->id,
        ];

        $result = $this->validator->validate(
            $this->candidate,
            $this->examType->id,
            $this->examYear->id,
            $subjectIds
        );

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Minimum 3 principal subjects', $result['errors'][1]);
    }

    /**
     * Test duplicate subjects are detected and removed
     */
    public function test_duplicates_are_detected_and_removed()
    {
        $subjectIds = [
            $this->subject1->id,
            $this->subject2->id,
            $this->subject3->id,
            $this->subject1->id, // duplicate
            $this->generalStudies->id,
        ];

        $result = $this->validator->validate(
            $this->candidate,
            $this->examType->id,
            $this->examYear->id,
            $subjectIds
        );

        $this->assertTrue($result['ok']);
        // Should only have 4 unique subjects
        $this->assertCount(4, $result['all_subject_ids']);
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('Duplicate', $result['warnings'][0]);
    }

    /**
     * Test validation with 4 principal subjects
     */
    public function test_validation_passes_with_four_principals_and_general_studies()
    {
        $subject4 = Subject::factory()->create(['code' => '104', 'name' => 'Mathematics']);
        $subjectIds = [
            $this->subject1->id,
            $this->subject2->id,
            $this->subject3->id,
            $subject4->id,
            $this->generalStudies->id,
        ];

        $result = $this->validator->validate(
            $this->candidate,
            $this->examType->id,
            $this->examYear->id,
            $subjectIds
        );

        $this->assertTrue($result['ok']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(4, $result['principal_subject_ids']);
    }
}
