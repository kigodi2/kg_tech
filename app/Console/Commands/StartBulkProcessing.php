<?php

namespace App\Console\Commands;

use App\Models\BulkImport;
use App\Models\BulkImportFile;
use App\Models\Subject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class StartBulkProcessing extends Command
{
    protected $signature = 'import:start-bulk-processing {bulkImportId} {zipPath}';
    protected $description = 'Start bulk import processing (extract, register files, process)';

    public function handle()
    {
        $bulkImportId = $this->argument('bulkImportId');
        $zipPath = $this->argument('zipPath');

        Log::info("Starting bulk processing: ID {$bulkImportId}, ZIP: {$zipPath}");

        try {
            $bulkImport = BulkImport::find($bulkImportId);
            if (!$bulkImport) {
                Log::error("Bulk import not found: {$bulkImportId}");
                return 1;
            }

            // Extract manifest
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception("Cannot open ZIP");
            }

            $manifestContent = $zip->getFromName('manifest.json');
            $manifest = json_decode($manifestContent, true);
            $zip->close();

            if (!$manifest || !isset($manifest['files'])) {
                throw new \Exception("Invalid manifest");
            }

            // Extract ZIP
            $extractPath = storage_path("app/temp/imports/{$bulkImportId}");
            @mkdir($extractPath, 0755, true);

            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true || !$zip->extractTo($extractPath)) {
                throw new \Exception("Cannot extract ZIP");
            }
            $zip->close();

            // Process each file
            foreach ($manifest['files'] as $fileInfo) {
                $filename = $fileInfo['filename'] ?? null;
                if (!$filename) {
                    continue;
                }

                $subjectCode = explode('_', $filename)[0];
                $subject = Subject::where('code', $subjectCode)->first();

                $filePath = $extractPath . '/' . $filename;
                if (!file_exists($filePath)) {
                    continue;
                }

                // Create import file record
                $importFile = BulkImportFile::create([
                    'bulk_import_id' => $bulkImportId,
                    'subject_id' => $subject?->id,
                    'subject_code' => $subjectCode,
                    'filename' => $filename,
                    'status' => 'pending',
                ]);

                // Process immediately
                $this->processFile($importFile, $filePath);
            }

            // Mark bulk import as complete
            $bulkImport->status = 'completed';
            $bulkImport->processed_files = BulkImportFile::where('bulk_import_id', $bulkImportId)
                ->where('status', 'completed')
                ->count();
            $bulkImport->save();

            Log::info("Bulk processing completed: {$bulkImportId}");
            return 0;
        } catch (\Exception $e) {
            Log::error("Bulk processing error: " . $e->getMessage());
            $bulkImport = BulkImport::find($bulkImportId);
            if ($bulkImport) {
                $bulkImport->status = 'failed';
                $bulkImport->save();
            }
            return 1;
        }
    }

    private function processFile($importFile, $filePath)
    {
        $importFile->status = 'processing';
        $importFile->save();

        $successCount = 0;
        $failureCount = 0;
        $rowNumber = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($rowNumber === 1 || empty($row[0])) {
                    continue;
                }

                try {
                    $candidateId = trim($row[0]);
                    $mark = isset($row[2]) ? trim($row[2]) : null;

                    if ($mark === '' || $mark === null) {
                        $failureCount++;
                        continue;
                    }

                    $mark = (float)$mark;

                    $candidate = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                    if (!$candidate) {
                        $failureCount++;
                        continue;
                    }

                    \App\Models\SubjectMarks::updateOrCreate(
                        [
                            'candidate_id' => $candidate->id,
                            'subject_id' => $importFile->subject_id,
                            'exam_year_id' => $importFile->bulkImport->exam_year_id,
                        ],
                        [
                            'mark_p1' => $mark,
                            'marked_by' => 0,
                            'marked_at' => now(),
                        ]
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    $failureCount++;
                }
            }

            fclose($handle);
        }

        $importFile->status = 'completed';
        $importFile->rows_total = $rowNumber - 1;
        $importFile->rows_success = $successCount;
        $importFile->rows_failed = $failureCount;
        $importFile->completed_at = now();
        $importFile->save();
    }
}
