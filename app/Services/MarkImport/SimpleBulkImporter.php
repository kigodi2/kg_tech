<?php

namespace App\Services\MarkImport;

use App\Models\BulkImport;
use App\Models\BulkImportFile;
use App\Models\Candidate;
use App\Models\Subject;
use App\Models\SubjectMarks;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * SimpleBulkImporter - Direct synchronous processing
 * 
 * No queues, no background jobs - just process immediately
 */
class SimpleBulkImporter
{
    public function importZip(BulkImport $bulkImport, string $zipPath): array
    {
        Log::info("Starting sync import: ID {$bulkImport->id}");
        
        try {
            // Extract and read manifest
            $manifest = $this->extractManifest($zipPath);
            if (!$manifest) {
                throw new \Exception("Invalid manifest.json");
            }

            // Extract ZIP to temp
            $extractPath = $this->extractZip($zipPath, $bulkImport->id);

            $totalSuccess = 0;
            $totalFailed = 0;
            $filesProcessed = 0;
            $totalFiles = count($manifest['files'] ?? []);

            // Process each file
            foreach ($manifest['files'] as $fileInfo) {
                $filename = $fileInfo['filename'] ?? null;
                if (!$filename || $filename === 'manifest.json') {
                    continue;
                }

                $filePath = $extractPath . '/' . $filename;
                if (!file_exists($filePath)) {
                    Log::warning("File not found: {$filename}");
                    continue;
                }

                // Get subject
                $subjectCode = explode('_', $filename)[0];
                $subject = Subject::where('code', $subjectCode)->first();

                // Create file record
                $importFile = BulkImportFile::create([
                    'bulk_import_id' => $bulkImport->id,
                    'subject_id' => $subject?->id,
                    'subject_code' => $subjectCode,
                    'filename' => $filename,
                    'status' => 'processing',
                ]);

                // Process this file
                $result = $this->processFile($importFile, $filePath, $bulkImport);
                
                $totalSuccess += $result['success'];
                $totalFailed += $result['failed'];
                $filesProcessed++;

                // Update parent import progress
                $bulkImport->processed_files = $filesProcessed;
                $bulkImport->save();

                Log::info("File {$filesProcessed}/{$totalFiles} processed: {$filename}");
            }

            // Mark import complete
            $bulkImport->status = 'completed';
            $bulkImport->processed_files = $filesProcessed;
            $bulkImport->save();

            // Cleanup
            $this->removeDirectory($extractPath);

            Log::info("Import complete: {$bulkImport->id}");

            return [
                'success' => true,
                'total_files' => $totalFiles,
                'processed_files' => $filesProcessed,
                'total_candidates' => $totalSuccess + $totalFailed,
                'successful_candidates' => $totalSuccess,
                'failed_candidates' => $totalFailed,
            ];
        } catch (\Exception $e) {
            Log::error("Import failed: " . $e->getMessage());
            $bulkImport->status = 'failed';
            $bulkImport->save();
            throw $e;
        }
    }

    private function processFile(BulkImportFile $importFile, string $filePath, BulkImport $bulkImport): array
    {
        $successCount = 0;
        $failureCount = 0;
        $rowNumber = 0;
        $examYearId = $bulkImport->exam_year_id;
        $subjectId = $importFile->subject_id;
        
        // Get exam type ID (ACSEE is assumed)
        $examType = \App\Models\ExamType::where('code', 'ACSEE')->first();
        $examTypeId = $examType?->id ?? 1;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Skip header
                if ($rowNumber === 1) {
                    continue;
                }

                // Skip empty rows
                if (empty($row[0])) {
                    continue;
                }

                try {
                    $candidateId = trim($row[0]);
                    
                    // Try to find mark in columns 2 and onwards (skip header, sex, etc)
                    $mark = null;
                    for ($i = 2; $i < count($row); $i++) {
                        $val = isset($row[$i]) ? trim($row[$i]) : null;
                        if ($val !== '' && $val !== null && $val !== 'X') {
                            $mark = $val;
                            break;
                        }
                    }

                    // Skip if no mark found
                    if ($mark === '' || $mark === null) {
                        $failureCount++;
                        continue;
                    }

                    // Parse mark
                    $mark = (float)$mark;
                    $subject = Subject::find($subjectId);
                    $maxMarks = (float) ($subject?->max_marks ?? 100);

                    if ($mark < 0 || $mark > $maxMarks) {
                        $failureCount++;
                        continue;
                    }

                    // Find candidate
                    $candidate = Candidate::where('candidate_id', $candidateId)->first();
                    if (!$candidate) {
                        $failureCount++;
                        continue;
                    }

                    // Save mark using the correct column names
                    SubjectMarks::updateOrCreate(
                        [
                            'candidate_id' => $candidate->id,
                            'exam_type_id' => $examTypeId,
                            'subject_id' => $subjectId,
                            'year' => $bulkImport->examYear->year_label,
                        ],
                        [
                            'marks_obtained' => $mark,
                            'max_marks' => (int) $maxMarks,
                        ]
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    Log::warning("Row {$rowNumber} error: " . $e->getMessage());
                    $failureCount++;
                }
            }
            fclose($handle);
        }

        // Update file record with valid status
        $importFile->status = $successCount > 0 ? 'success' : 'failed';
        $importFile->rows_total = $rowNumber - 1;
        $importFile->rows_success = $successCount;
        $importFile->rows_failed = $failureCount;
        $importFile->completed_at = now();
        $importFile->save();

        return [
            'success' => $successCount,
            'failed' => $failureCount,
        ];
    }

    private function extractManifest(string $zipPath): ?array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return null;
        }

        $content = $zip->getFromName('manifest.json');
        $zip->close();

        return json_decode($content, true);
    }

    private function extractZip(string $zipPath, int $bulkImportId): string
    {
        $extractPath = storage_path("app/temp/imports/{$bulkImportId}");
        @mkdir($extractPath, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \Exception("Cannot open ZIP");
        }

        if (!$zip->extractTo($extractPath)) {
            throw new \Exception("Cannot extract ZIP");
        }

        $zip->close();
        return $extractPath;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff(scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                @unlink($filePath);
            }
        }
        @rmdir($path);
    }
}
