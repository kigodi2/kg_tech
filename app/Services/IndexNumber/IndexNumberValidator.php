<?php

namespace App\Services\IndexNumber;

use App\Models\School;
use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Services\IndexNumber\DTO\ParsedIndexNumber;
use App\Services\IndexNumber\DTO\ValidationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * IndexNumberValidator
 * 
 * Production-grade validation engine for NECTA-style index numbers.
 * 
 * Features:
 * - Parse and validate NECTA-style index numbers (CCCC-SSSS format)
 * - Auto-detect candidate_type from centre prefix (S=School, P=Private)
 * - Resolve centre codes to actual schools/private centres
 * - Enforce duplicate protection per exam context
 * - Provide standardized validation output
 * 
 * Usage:
 *   $validator = new IndexNumberValidator();
 *   $result = $validator->validate('S0445-0001', [
 *       'exam_year_id' => 1,
 *       'exam_type_id' => 2,
 *       'candidate_id' => null,  // null=create, int=update
 *   ]);
 *   
 *   if ($result->ok) {
 *       // Use $result->parsed, $result->resolved_school_id, etc.
 *   } else {
 *       // Display $result->errors() to user
 *   }
 */
class IndexNumberValidator
{
    /**
     * Parse an index number string (basic structure validation)
     * 
     * @param string $indexNumber - Raw index number (e.g., "S0445-0001")
     * @return ParsedIndexNumber|null - Parsed object or null if parsing failed
     */
    public function parse(string $indexNumber): ?ParsedIndexNumber
    {
        if (empty(trim($indexNumber))) {
            return null;
        }

        return ParsedIndexNumber::fromString($indexNumber);
    }

    /**
     * Validate an index number with full context checking
     * 
     * @param string $indexNumber - Raw index number
     * @param array $context - Validation context:
     *    - exam_year_id (int): Required
     *    - exam_type_id (int): Required
     *    - candidate_id (int|null): null=create, int=update (ignores duplicate if same candidate)
     * @return ValidationResult - Comprehensive validation result
     */
    public function validate(string $indexNumber, array $context = []): ValidationResult
    {
        $result = new ValidationResult();

        // Step 1: Check if empty
        if (empty(trim($indexNumber))) {
            return $result->addError(
                'INDEX_EMPTY',
                config('necta.error_codes.INDEX_EMPTY')
            );
        }

        // Step 2: Parse the index number
        $parsed = $this->parse($indexNumber);
        if (!$parsed) {
            return $result->addError(
                'INDEX_FORMAT_INVALID',
                config('necta.error_codes.INDEX_FORMAT_INVALID')
            );
        }

        $result->parsed = $parsed;

        // Step 3: Validate format details
        $formatValidation = $this->validateFormat($parsed);
        if (!$formatValidation->ok) {
            return $result->addError(
                $formatValidation->errors()[0]['code'],
                $formatValidation->errors()[0]['message']
            );
        }

        // Step 4: Resolve centre
        $centreResolution = $this->resolveCentre($parsed->centre_code, $parsed->candidate_type);
        if (!$centreResolution['ok']) {
            return $result->addError(
                $centreResolution['error_code'],
                $centreResolution['error_message']
            );
        }

        // Set resolved IDs
        if ($parsed->candidate_type === 'SCHOOL') {
            $result->resolved_school_id = $centreResolution['school_id'] ?? null;
        } else {
            $result->resolved_private_centre_id = $centreResolution['private_centre_id'] ?? null;
        }

        // Step 5: Check exam context (required for duplicate detection)
        if (!isset($context['exam_year_id']) || !isset($context['exam_type_id'])) {
            // Note: Log this but don't fail if these are missing (may be optional depending on flow)
            Log::warning('Exam context missing for index number validation', [
                'index_number' => $indexNumber,
                'context' => $context,
            ]);
        }

        // Step 6: Enforce duplicate protection (if context available)
        if (config('necta.validation.enforce_unique_per_exam_context', true)) {
            if (isset($context['exam_year_id']) && isset($context['exam_type_id'])) {
                $duplicate = $this->findDuplicate(
                    $parsed->normalized,
                    $context['exam_year_id'],
                    $context['exam_type_id'],
                    $context['candidate_id'] ?? null
                );

                if ($duplicate) {
                    $result->duplicate_candidate_id = $duplicate->id;
                    return $result->addError(
                        'DUPLICATE_INDEX_NUMBER',
                        config('necta.error_codes.DUPLICATE_INDEX_NUMBER')
                    );
                }
            }
        }

        // All validations passed
        return $result->markSuccess();
    }

    /**
     * Validate the format of a parsed index number
     * Checks regex patterns for centre code and serial
     */
    private function validateFormat(ParsedIndexNumber $parsed): ValidationResult
    {
        $result = new ValidationResult();

        $config = config('necta.index_number', []);
        $centreRegex = '/' . $config['centre_code_regex'] . '/';
        $serialRegex = '/' . $config['serial_regex'] . '/';

        // Validate centre code format
        if (!preg_match($centreRegex, $parsed->centre_code)) {
            if (strlen($parsed->centre_code) < 5) {
                $result->addError(
                    'CENTRE_CODE_INVALID',
                    config('necta.error_codes.CENTRE_CODE_INVALID')
                );
            } else {
                $result->addError(
                    'CENTRE_PREFIX_UNKNOWN',
                    config('necta.error_codes.CENTRE_PREFIX_UNKNOWN')
                );
            }
            return $result;
        }

        // Validate serial format
        if (!preg_match($serialRegex, $parsed->serial)) {
            return $result->addError(
                'SERIAL_INVALID',
                config('necta.error_codes.SERIAL_INVALID')
            );
        }

        return $result->markSuccess();
    }

    /**
     * Resolve a centre code to a real school or private centre
     * 
     * @param string $centreCode - Centre code (e.g., "S0445", "P0652")
     * @param string $candidateType - Candidate type (SCHOOL or PRIVATE)
     * @return array - Resolution result with keys:
     *    - ok (bool)
     *    - school_id (int|null)
     *    - private_centre_id (int|null)
     *    - error_code (string|null)
     *    - error_message (string|null)
     */
    public function resolveCentre(string $centreCode, string $candidateType): array
    {
        if ($candidateType === 'SCHOOL') {
            return $this->resolveSchoolCentre($centreCode);
        } else {
            return $this->resolvePrivateCentre($centreCode);
        }
    }

    /**
     * Resolve SCHOOL centre code to schools table
     */
    private function resolveSchoolCentre(string $centreCode): array
    {
        if (!config('necta.validation.enforce_known_centre', true)) {
            return ['ok' => true, 'school_id' => null];
        }

        $schoolColumn = config('necta.validation.school_centre_column', 'registration_number');

        $school = School::where($schoolColumn, $centreCode)->first();

        if (!$school) {
            return [
                'ok' => false,
                'school_id' => null,
                'error_code' => 'CENTRE_NOT_FOUND',
                'error_message' => config('necta.error_codes.CENTRE_NOT_FOUND'),
            ];
        }

        return [
            'ok' => true,
            'school_id' => $school->id,
        ];
    }

    /**
     * Resolve PRIVATE centre code to private_centres table
     * Includes fallback mapping if table doesn't exist
     */
    private function resolvePrivateCentre(string $centreCode): array
    {
        if (!config('necta.validation.enforce_known_centre', true)) {
            return ['ok' => true, 'private_centre_id' => null];
        }

        // TODO: When private_centres table is created, implement proper resolution
        // For now, use fallback mapping
        $config = config('necta.private_centre', []);

        // Try fallback mapping first
        if ($config['use_fallback_mapping'] ?? false) {
            $mapping = $config['fallback_mapping'] ?? [];
            if (isset($mapping[$centreCode])) {
                return [
                    'ok' => true,
                    'private_centre_id' => $mapping[$centreCode],
                ];
            }
        }

        // If private_centres table exists, use it
        if (class_exists('App\Models\PrivateCentre')) {
            $centreColumn = $config['centre_column'] ?? 'registration_number';
            $centre = \App\Models\PrivateCentre::where($centreColumn, $centreCode)->first();
            
            if ($centre) {
                return [
                    'ok' => true,
                    'private_centre_id' => $centre->id,
                ];
            }
        }

        // Centre not found
        return [
            'ok' => false,
            'private_centre_id' => null,
            'error_code' => 'CENTRE_NOT_FOUND',
            'error_message' => config('necta.error_codes.CENTRE_NOT_FOUND'),
        ];
    }

    /**
     * Check for duplicate index numbers in the same exam context
     * 
     * @param string $normalizedIndexNumber - Normalized index number
     * @param int $examYearId - Exam year ID (used to get year label from exam_years table)
     * @param int $examTypeId - Exam type ID
     * @param int|null $ignoreCandidateId - If set, ignore this candidate (for updates)
     * @return Candidate|null - Duplicate candidate or null
     */
    private function findDuplicate(
        string $normalizedIndexNumber,
        int $examYearId,
        int $examTypeId,
        ?int $ignoreCandidateId = null
    ): ?Candidate {
        // Get the year label from exam_years table
        $examYear = ExamYear::find($examYearId);
        if (!$examYear) {
            return null;
        }

        $query = Candidate::where('candidate_id', $normalizedIndexNumber)
            ->whereHas('examRegistrations', function ($q) use ($examYear, $examTypeId) {
                $q->where('exam_type_id', $examTypeId)
                  ->where('year', $examYear->year_label);
            });

        if ($ignoreCandidateId) {
            $query->where('id', '!=', $ignoreCandidateId);
        }

        return $query->first();
    }

    /**
     * Get standardized error codes
     */
    public static function getErrorCodes(): array
    {
        return config('necta.error_codes', []);
    }

    /**
     * Check if an error code exists in config
     */
    public static function hasErrorCode(string $code): bool
    {
        return isset(config('necta.error_codes')[$code]);
    }

    /**
     * Get error message for a code
     */
    public static function getErrorMessage(string $code): ?string
    {
        return config('necta.error_codes.' . $code);
    }
}
