<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExamRegistration;
use App\Services\IndexNumber\IndexNumberValidator;
use App\Services\IndexNumber\DTO\ParsedIndexNumber;

class IndexNumberValidationTest extends TestCase
{
    protected IndexNumberValidator $validator;
    protected School $school;
    protected ExamYear $examYear;
    protected ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new IndexNumberValidator();

        // Create test data
        $this->school = School::factory()->create([
            'registration_number' => 'S0445',
            'name' => 'Test School',
        ]);

        $this->examYear = ExamYear::factory()->create([
            'year_label' => '2026',
        ]);

        $this->examType = ExamType::factory()->create([
            'code' => 'ACSEE',
            'name' => 'Advanced Certificate of Secondary Education Examination',
        ]);
    }

    /**
     * Test: Parse valid SCHOOL index number
     */
    public function test_parse_valid_school_index_number()
    {
        $parsed = $this->validator->parse('S0445-0001');

        $this->assertNotNull($parsed);
        $this->assertEquals('S0445', $parsed->centre_code);
        $this->assertEquals('S', $parsed->prefix);
        $this->assertEquals('0001', $parsed->serial);
        $this->assertEquals('SCHOOL', $parsed->candidate_type);
    }

    /**
     * Test: Parse valid PRIVATE index number
     */
    public function test_parse_valid_private_index_number()
    {
        $parsed = $this->validator->parse('P0652-0502');

        $this->assertNotNull($parsed);
        $this->assertEquals('P0652', $parsed->centre_code);
        $this->assertEquals('P', $parsed->prefix);
        $this->assertEquals('0502', $parsed->serial);
        $this->assertEquals('PRIVATE', $parsed->candidate_type);
    }

    /**
     * Test: Parse fails with missing delimiter
     */
    public function test_parse_fails_missing_delimiter()
    {
        $parsed = $this->validator->parse('S04450001');

        $this->assertNull($parsed);
    }

    /**
     * Test: Parse fails with empty string
     */
    public function test_parse_fails_empty_string()
    {
        $parsed = $this->validator->parse('');

        $this->assertNull($parsed);
    }

    /**
     * Test: Validate accepts SCHOOL candidate with known centre
     */
    public function test_validate_school_candidate_with_known_centre()
    {
        $result = $this->validator->validate('S0445-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertTrue($result->ok);
        $this->assertEquals('S0445-0001', $result->parsed->normalized);
        $this->assertEquals($this->school->id, $result->resolved_school_id);
        $this->assertEmpty($result->errors());
    }

    /**
     * Test: Validate rejects SCHOOL candidate with unknown centre
     */
    public function test_validate_school_candidate_with_unknown_centre()
    {
        $result = $this->validator->validate('S9999-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertFalse($result->ok);
        $this->assertEquals('CENTRE_NOT_FOUND', $result->firstError()['code']);
    }

    /**
     * Test: Validate rejects malformed index number (no hyphen)
     */
    public function test_validate_rejects_malformed_no_hyphen()
    {
        $result = $this->validator->validate('S04450001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertFalse($result->ok);
        $this->assertEquals('INDEX_FORMAT_INVALID', $result->firstError()['code']);
    }

    /**
     * Test: Validate rejects invalid serial (not numeric)
     */
    public function test_validate_rejects_invalid_serial()
    {
        $result = $this->validator->validate('S0445-ABCD', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertFalse($result->ok);
        $this->assertEquals('SERIAL_INVALID', $result->firstError()['code']);
    }

    /**
     * Test: Validate rejects invalid centre code (bad prefix)
     */
    public function test_validate_rejects_invalid_prefix()
    {
        $result = $this->validator->validate('X0445-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertFalse($result->ok);
        $this->assertEquals('CENTRE_PREFIX_UNKNOWN', $result->firstError()['code']);
    }

    /**
     * Test: Validate rejects empty index number
     */
    public function test_validate_rejects_empty()
    {
        $result = $this->validator->validate('', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertFalse($result->ok);
        $this->assertEquals('INDEX_EMPTY', $result->firstError()['code']);
    }

    /**
     * Test: Validate detects duplicate index number in same exam context
     */
    public function test_validate_detects_duplicate_in_same_exam_context()
    {
        // Create first candidate with this index number
        $candidate1 = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'candidate_id' => 'S0445-0001',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate1->id,
            'exam_type_id' => $this->examType->id,
            'year' => 2026,
            'registration_number' => 'REG-001',
        ]);

        // Try to create second candidate with same index number
        $result = $this->validator->validate('S0445-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertFalse($result->ok);
        $this->assertEquals('DUPLICATE_INDEX_NUMBER', $result->firstError()['code']);
        $this->assertEquals($candidate1->id, $result->duplicate_candidate_id);
    }

    /**
     * Test: Validate allows same index number in different exam years
     */
    public function test_validate_allows_same_index_different_years()
    {
        // Create candidate in 2025
        $examYear2025 = ExamYear::factory()->create(['year_label' => '2025']);
        
        $candidate1 = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'candidate_id' => 'S0445-0001',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate1->id,
            'exam_type_id' => $this->examType->id,
            'year' => 2025,
            'registration_number' => 'REG-002',
        ]);

        // Try same index in 2026 - should be allowed
        $result = $this->validator->validate('S0445-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $this->assertTrue($result->ok);
    }

    /**
     * Test: Validate ignores current candidate on update
     */
    public function test_validate_ignores_same_candidate_on_update()
    {
        $candidate = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'candidate_id' => 'S0445-0001',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $candidate->id,
            'exam_type_id' => $this->examType->id,
            'year' => 2026,
            'registration_number' => 'REG-003',
        ]);

        // Validate same index number but for the same candidate (update scenario)
        $result = $this->validator->validate('S0445-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
            'candidate_id' => $candidate->id,  // Same candidate
        ]);

        $this->assertTrue($result->ok);
    }

    /**
     * Test: Normalize converts to uppercase
     */
    public function test_normalize_converts_to_uppercase()
    {
        $parsed = $this->validator->parse('s0445-0001');

        $this->assertNotNull($parsed);
        $this->assertEquals('S0445-0001', $parsed->normalized);
    }

    /**
     * Test: Normalize trims whitespace
     */
    public function test_normalize_trims_whitespace()
    {
        $parsed = $this->validator->parse('  S0445-0001  ');

        $this->assertNotNull($parsed);
        $this->assertEquals('S0445-0001', $parsed->normalized);
    }

    /**
     * Test: ValidationResult toArray method
     */
    public function test_validation_result_to_array()
    {
        $result = $this->validator->validate('S0445-0001', [
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->examType->id,
        ]);

        $array = $result->toArray();

        $this->assertTrue($array['ok']);
        $this->assertNotNull($array['parsed']);
        $this->assertEquals('S0445-0001', $array['parsed']['normalized']);
        $this->assertEquals($this->school->id, $array['resolved']['school_id']);
    }

    /**
     * Test: Error codes are available
     */
    public function test_error_codes_available()
    {
        $codes = IndexNumberValidator::getErrorCodes();

        $this->assertArrayHasKey('INDEX_EMPTY', $codes);
        $this->assertArrayHasKey('INDEX_FORMAT_INVALID', $codes);
        $this->assertArrayHasKey('CENTRE_NOT_FOUND', $codes);
        $this->assertArrayHasKey('DUPLICATE_INDEX_NUMBER', $codes);
    }
}
