<?php

namespace App\Services\MarkImport;

use ZipArchive;
use Exception;

/**
 * ZipPreviewService
 *
 * Provides preview information about a ZIP file before import.
 * Validates structure, counts rows, detects issues.
 */
class ZipPreviewService
{
    const MAX_CSV_ROWS_TO_COUNT = 5000;

    /**
     * Preview ZIP file contents (supports both school and district)
     *
     * @param string $zipPath
     * @return array Preview data
     * @throws Exception
     */
    public function preview(string $zipPath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new Exception("Cannot open ZIP file");
        }

        // Extract and validate manifest
        $manifest = $this->extractManifest($zip);
        
        if (!$manifest) {
            throw new Exception("No valid manifest.json found in ZIP");
        }

        // Check if this is district-level or school-level
        $scope = $manifest['scope'] ?? [];
        $isDistrict = isset($scope['type']) && $scope['type'] === 'district';

        if ($isDistrict) {
            $preview = $this->previewDistrictZip($zip, $manifest);
        } else {
            $preview = $this->previewSchoolZip($zip, $manifest);
        }

        $zip->close();

        return $preview;
    }

    /**
     * Preview school-level ZIP
     *
     * @param ZipArchive $zip
     * @param array $manifest
     * @return array
     */
    private function previewSchoolZip(ZipArchive $zip, array $manifest): array
    {
        // Analyze files
        $files = [];
        $totalCandidates = 0;
        $issues = [];

        // If manifest['files'] is a list, convert to array with filenames as keys
        $manifestFiles = $manifest['files'] ?? [];
        if (!empty($manifestFiles) && isset($manifestFiles[0])) {
            // Convert indexed array to keyed array
            $temp = [];
            foreach ($manifestFiles as $fileInfo) {
                $temp[$fileInfo['filename'] ?? 'unknown'] = $fileInfo;
            }
            $manifestFiles = $temp;
        }

        foreach ($manifestFiles as $filename => $fileInfo) {
            if ($filename === 'manifest.json') {
                continue;
            }

            // Handle both direct filename and object format
            if (is_array($fileInfo) && isset($fileInfo['filename'])) {
                $filename = $fileInfo['filename'];
            }

            $subjectCode = explode('_', $filename)[0];
            $subjectName = $fileInfo['subject_name'] ?? $subjectCode;
            $rowCount = $this->countCsvRows($zip, $filename);

            $files[] = [
                'filename' => $filename,
                'subject_code' => $subjectCode,
                'subject_name' => $subjectName,
                'candidates' => $rowCount,
            ];

            $totalCandidates += $rowCount;

            // Detect issues - only error if CSV truly empty (no rows at all)
            // Don't error on 0 candidates if file exists and is readable
        }

        // Check for duplicate subjects
        $subjectCodes = array_column($files, 'subject_code');
        $duplicates = array_diff_assoc($subjectCodes, array_unique($subjectCodes));

        if (!empty($duplicates)) {
            $issues[] = "Duplicate subject found: " . implode(', ', array_unique($duplicates));
        }

        return [
            'scope_type' => 'school',
            'school' => $manifest['school_name'] ?? $manifest['school_code'] ?? 'Unknown',
            'school_code' => $manifest['school_code'] ?? null,
            'exam_year' => $manifest['exam_year'] ?? null,
            'subjects' => $files,
            'total_files' => count($files),
            'total_candidates' => $totalCandidates,
            'is_signed' => isset($manifest['signature']),
            'signature_algorithm' => $manifest['signature']['algorithm'] ?? null,
            'generated_at' => $manifest['generated_at'] ?? null,
            'issues' => $issues,
            'is_valid' => empty($issues),
        ];
    }

    /**
     * Preview district-level ZIP
     *
     * @param ZipArchive $zip
     * @param array $manifest
     * @return array
     */
    private function previewDistrictZip(ZipArchive $zip, array $manifest): array
    {
        $issues = [];
        $schools = [];
        $totalCandidates = 0;
        $totalSubjects = 0;

        foreach ($manifest['schools'] ?? [] as $schoolData) {
            $schoolCode = $schoolData['school_code'];
            $schoolName = $schoolData['school_name'];
            $schoolSubjects = [];
            $schoolCandidates = 0;

            foreach ($schoolData['subjects'] ?? [] as $subjectData) {
                $subjectCode = $subjectData['code'];
                $papers = $subjectData['papers'] ?? [];
                $candidates = $subjectData['candidates'] ?? 0;

                $schoolSubjects[] = [
                    'code' => $subjectCode,
                    'papers' => $papers,
                    'candidates' => $candidates,
                ];

                $schoolCandidates += $candidates;
                $totalSubjects++;
            }

            $schools[] = [
                'school_code' => $schoolCode,
                'school_name' => $schoolName,
                'subjects' => $schoolSubjects,
                'total_candidates' => $schoolCandidates,
                'total_subjects' => count($schoolSubjects),
            ];

            $totalCandidates += $schoolCandidates;
        }

        // Validate school count
        if (empty($schools)) {
            $issues[] = "District ZIP must contain at least one school";
        }

        return [
            'scope_type' => 'district',
            'district' => $manifest['scope']['code'] ?? 'Unknown',
            'exam_year' => $manifest['exam_year'] ?? null,
            'schools' => $schools,
            'total_schools' => count($schools),
            'total_subjects' => $totalSubjects,
            'total_candidates' => $totalCandidates,
            'is_signed' => isset($manifest['signature']),
            'signature_algorithm' => $manifest['signature']['algorithm'] ?? null,
            'generated_at' => $manifest['generated_at'] ?? null,
            'issues' => $issues,
            'is_valid' => empty($issues),
        ];
    }

    /**
     * Extract manifest from ZIP
     *
     * @param ZipArchive $zip
     * @return array|null
     */
    private function extractManifest(ZipArchive $zip): ?array
    {
        $index = $zip->locateName('manifest.json');

        if ($index === false) {
            return null;
        }

        $content = $zip->getFromIndex($index);

        return json_decode($content, true);
    }

    /**
     * Count rows in a CSV file within the ZIP
     *
     * @param ZipArchive $zip
     * @param string $filename
     * @return int
     */
    private function countCsvRows(ZipArchive $zip, string $filename): int
    {
        $index = $zip->locateName($filename);

        if ($index === false) {
            return 0;
        }

        $content = $zip->getFromIndex($index);
        $lines = explode("\n", $content);

        // Subtract 1 for header row
        $count = max(0, count($lines) - 2);

        return min($count, self::MAX_CSV_ROWS_TO_COUNT);
    }

    /**
     * Validate ZIP structure
     *
     * @param string $zipPath
     * @return array Validation result
     */
    public function validate(string $zipPath): array
    {
        $errors = [];

        // Check file exists
        if (!file_exists($zipPath)) {
            $errors[] = "ZIP file does not exist";
            return ['valid' => false, 'errors' => $errors];
        }

        // Check file is readable
        if (!is_readable($zipPath)) {
            $errors[] = "ZIP file is not readable";
            return ['valid' => false, 'errors' => $errors];
        }

        // Check ZIP is valid
        $zip = new ZipArchive();
        $openResult = $zip->open($zipPath);
        if ($openResult !== true) {
            $errors[] = "ZIP file is corrupted or invalid (error code: $openResult)";
            return ['valid' => false, 'errors' => $errors];
        }

        // Check manifest exists
        $manifestIndex = $zip->locateName('manifest.json');
        if ($manifestIndex === false) {
            $errors[] = "manifest.json not found in ZIP";
            $zip->close();
            return ['valid' => false, 'errors' => $errors];
        }

        // Try to parse manifest
        try {
            $manifestContent = $zip->getFromIndex($manifestIndex);
            $manifest = json_decode($manifestContent, true);
            
            if (!$manifest) {
                $errors[] = "manifest.json is not valid JSON";
            } else {
                // Allow either exam_year or just the basic structure
                if (!isset($manifest['exam_year'])) {
                    $errors[] = "manifest.json missing exam_year field";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error reading manifest.json: " . $e->getMessage();
        }

        $zip->close();

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
