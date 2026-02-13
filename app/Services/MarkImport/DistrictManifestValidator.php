<?php

namespace App\Services\MarkImport;

use App\Models\District;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Support\Facades\Validator;

/**
 * DistrictManifestValidator
 *
 * Validates district manifest.json schema and content.
 * Ensures:
 * - Manifest structure is valid
 * - Exam year matches selected year
 * - Scope is district
 * - All schools belong to district
 * - Subjects are valid
 * - Checksums are present
 */
class DistrictManifestValidator
{
    /**
     * Validate district manifest
     *
     * @param array $manifest
     * @param District $district
     * @param ExamYear $examYear
     * @return array {valid: bool, errors: array}
     */
    public function validate(array $manifest, District $district, ExamYear $examYear): array
    {
        $errors = [];

        // Validate manifest structure
        $structureErrors = $this->validateStructure($manifest);
        if (!empty($structureErrors)) {
            $errors = array_merge($errors, $structureErrors);
            return ['valid' => false, 'errors' => $errors];
        }

        // Validate exam year
        if ($manifest['exam_year'] != $examYear->year) {
            $errors['exam_year'] = "Manifest exam_year ({$manifest['exam_year']}) does not match selected exam year ({$examYear->year})";
        }

        // Validate scope
        $scope = $manifest['scope'] ?? [];
        if ($scope['type'] !== 'district') {
            $errors['scope.type'] = "Manifest scope must be 'district', got '{$scope['type']}'";
        }

        if ($scope['code'] !== $district->code) {
            $errors['scope.code'] = "Manifest scope code ({$scope['code']}) does not match district code ({$district->code})";
        }

        // Validate schools
        $schoolErrors = $this->validateSchools($manifest['schools'] ?? [], $district);
        $errors = array_merge($errors, $schoolErrors);

        // Validate generated_by
        $generatedByErrors = $this->validateGeneratedBy($manifest['generated_by'] ?? []);
        $errors = array_merge($errors, $generatedByErrors);

        // Validate checksums
        $checksumErrors = $this->validateZipChecksum($manifest);
        $errors = array_merge($errors, $checksumErrors);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate ZIP checksum
     *
     * @param array $manifest
     * @return array errors
     */
    private function validateZipChecksum(array $manifest): array
    {
        $errors = [];

        if (!isset($manifest['zip_checksum'])) {
            $errors['zip_checksum'] = "ZIP checksum is required for audit trail";
        } elseif (!$this->isValidChecksum($manifest['zip_checksum'])) {
            $errors['zip_checksum'] = "ZIP checksum has invalid format";
        }

        return $errors;
    }

    /**
     * Validate manifest top-level structure
     *
     * @param array $manifest
     * @return array errors
     */
    private function validateStructure(array $manifest): array
    {
        $errors = [];

        // Required fields
        $required = ['exam', 'exam_year', 'scope', 'generated_at', 'generated_by', 'schools'];

        foreach ($required as $field) {
            if (!isset($manifest[$field])) {
                $errors[$field] = "Field '$field' is required in manifest";
            }
        }

        // Validate types
        if (isset($manifest['exam']) && !is_string($manifest['exam'])) {
            $errors['exam'] = "Field 'exam' must be a string";
        }

        if (isset($manifest['exam_year']) && !is_int($manifest['exam_year'])) {
            $errors['exam_year'] = "Field 'exam_year' must be an integer";
        }

        if (isset($manifest['scope']) && !is_array($manifest['scope'])) {
            $errors['scope'] = "Field 'scope' must be an object";
        }

        if (isset($manifest['generated_at']) && !$this->isIso8601($manifest['generated_at'])) {
            $errors['generated_at'] = "Field 'generated_at' must be ISO 8601 datetime";
        }

        if (isset($manifest['schools']) && !is_array($manifest['schools'])) {
            $errors['schools'] = "Field 'schools' must be an array";
        }

        return $errors;
    }

    /**
     * Validate schools array
     *
     * @param array $schools
     * @param District $district
     * @return array errors
     */
    private function validateSchools(array $schools, District $district): array
    {
        $errors = [];

        if (empty($schools)) {
            $errors['schools'] = "Manifest must contain at least one school";
            return $errors;
        }

        $seenSchoolCodes = [];

        foreach ($schools as $index => $schoolData) {
            $prefix = "schools.{$index}";

            // Validate school structure
            if (!isset($schoolData['school_code'])) {
                $errors["{$prefix}.school_code"] = "Missing school_code";
                continue;
            }

            if (!isset($schoolData['school_name'])) {
                $errors["{$prefix}.school_name"] = "Missing school_name";
                continue;
            }

            $schoolCode = $schoolData['school_code'];
            $schoolName = $schoolData['school_name'];

            // Check for duplicates
            if (in_array($schoolCode, $seenSchoolCodes)) {
                $errors["{$prefix}.school_code"] = "Duplicate school_code: {$schoolCode}";
                continue;
            }
            $seenSchoolCodes[] = $schoolCode;

            // Validate school exists in district
            $school = School::where('code', $schoolCode)
                ->where('district_id', $district->id)
                ->first();

            if (!$school) {
                $errors["{$prefix}.school_code"] = "School {$schoolCode} not found in district {$district->code}";
                continue;
            }

            // Validate school name matches (optional but recommended)
            if ($schoolName !== $school->name) {
                // Log warning but don't fail - names may have been updated
                \Log::warning("School name mismatch for {$schoolCode}: manifest='{$schoolName}' vs db='{$school->name}'");
            }

            // Validate subjects
            $subjectErrors = $this->validateSubjects(
                $schoolData['subjects'] ?? [],
                $school,
                "{$prefix}.subjects"
            );
            $errors = array_merge($errors, $subjectErrors);

            // Validate candidates count
            if (isset($schoolData['candidates']) && !is_int($schoolData['candidates'])) {
                $errors["{$prefix}.candidates"] = "Field 'candidates' must be an integer";
            }

            // Validate total_candidates (alias for candidates)
            if (isset($schoolData['total_candidates']) && !is_int($schoolData['total_candidates'])) {
                $errors["{$prefix}.total_candidates"] = "Field 'total_candidates' must be an integer";
            }
        }

        return $errors;
    }

    /**
     * Validate subjects for a school
     *
     * @param array $subjects
     * @param School $school
     * @param string $pathPrefix
     * @return array errors
     */
    private function validateSubjects(array $subjects, School $school, string $pathPrefix): array
    {
        $errors = [];

        if (empty($subjects)) {
            $errors[$pathPrefix] = "School {$school->code} must have at least one subject";
            return $errors;
        }

        $seenSubjectCodes = [];

        foreach ($subjects as $index => $subjectData) {
            $subjectPrefix = "{$pathPrefix}.{$index}";

            if (!isset($subjectData['code'])) {
                $errors["{$subjectPrefix}.code"] = "Missing subject code";
                continue;
            }

            $subjectCode = $subjectData['code'];

            // Check for duplicates within school
            if (in_array($subjectCode, $seenSubjectCodes)) {
                $errors["{$subjectPrefix}.code"] = "Duplicate subject code in school: {$subjectCode}";
                continue;
            }
            $seenSubjectCodes[] = $subjectCode;

            // Validate subject exists
            $subject = Subject::where('code', $subjectCode)->first();
            if (!$subject) {
                $errors["{$subjectPrefix}.code"] = "Subject {$subjectCode} not found";
                continue;
            }

            // Validate papers
            if (isset($subjectData['papers']) && !is_array($subjectData['papers'])) {
                $errors["{$subjectPrefix}.papers"] = "Field 'papers' must be an array";
                continue;
            }

            if (!isset($subjectData['papers']) || empty($subjectData['papers'])) {
                $errors["{$subjectPrefix}.papers"] = "Subject {$subjectCode} must have at least one paper";
                continue;
            }

            // Validate checksum
            if (!isset($subjectData['checksum'])) {
                $errors["{$subjectPrefix}.checksum"] = "Subject {$subjectCode} is missing checksum";
            } elseif (!$this->isValidChecksum($subjectData['checksum'])) {
                $errors["{$subjectPrefix}.checksum"] = "Subject {$subjectCode} has invalid checksum format";
            }
        }

        return $errors;
    }

    /**
     * Validate generated_by section
     *
     * @param array $generatedBy
     * @return array errors
     */
    private function validateGeneratedBy(array $generatedBy): array
    {
        $errors = [];

        if (empty($generatedBy)) {
            $errors['generated_by'] = "Field 'generated_by' cannot be empty";
            return $errors;
        }

        if (!isset($generatedBy['user_id'])) {
            $errors['generated_by.user_id'] = "Field 'generated_by.user_id' is required";
        }

        if (!isset($generatedBy['role'])) {
            $errors['generated_by.role'] = "Field 'generated_by.role' is required";
        }

        // Validate role is district officer or higher
        $validRoles = ['district_officer', 'regional_officer', 'admin'];
        if (isset($generatedBy['role']) && !in_array($generatedBy['role'], $validRoles)) {
            $errors['generated_by.role'] = "Invalid role: {$generatedBy['role']}. Must be one of: " . implode(', ', $validRoles);
        }

        return $errors;
    }

    /**
     * Check if string is ISO 8601 datetime
     *
     * @param string $datetime
     * @return bool
     */
    private function isIso8601(string $datetime): bool
    {
        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z?$/';
        if (!preg_match($pattern, $datetime)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $datetime);
        if ($d === false) {
            $d = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $datetime);
        }

        return $d !== false;
    }

    /**
     * Check if checksum is valid format (sha256:...)
     *
     * @param string $checksum
     * @return bool
     */
    private function isValidChecksum(string $checksum): bool
    {
        return preg_match('/^sha256:[a-f0-9]{64}$/', $checksum) === 1;
    }
}
