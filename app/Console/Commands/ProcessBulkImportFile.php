<?php

namespace App\Console\Commands;

use App\Models\BulkImportFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessBulkImportFile extends Command
{
    protected $signature = 'import:process-bulk-file {importFileId} {filePath}';
    protected $description = 'Process a single bulk import file (CSV)';

    public function handle()
    {
        $importFileId = $this->argument('importFileId');
        $filePath = $this->argument('filePath');

        Log::info("Starting background processing: File ID {$importFileId}, Path: {$filePath}");

        $importFile = BulkImportFile::find($importFileId);
        if (!$importFile) {
            Log::error("Import file not found: {$importFileId}");
            return 1;
        }

        if (!file_exists($filePath)) {
            Log::error("File not found: {$filePath}");
            $importFile->status = 'failed';
            $importFile->save();
            return 1;
        }

        try {
            $importFile->status = 'processing';
            $importFile->save();

            $successCount = 0;
            $failureCount = 0;

            // Open and process CSV
            if (($handle = fopen($filePath, 'r')) !== false) {
                $rowNumber = 0;

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
                        $mark = isset($row[2]) ? trim($row[2]) : null;

                        // Skip if no mark
                        if ($mark === '' || $mark === null) {
                            $failureCount++;
                            continue;
                        }

                        $mark = (float)$mark;

                        // Find candidate
                        $candidate = \App\Models\Candidate::where('candidate_id', $candidateId)->first();
                        if (!$candidate) {
                            $failureCount++;
                            continue;
                        }

                        // Save or update mark
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
                        Log::warning("Row {$rowNumber} error: " . $e->getMessage());
                        $failureCount++;
                    }
                }

                fclose($handle);
            }

            // Mark as complete
            $importFile->status = 'completed';
            $importFile->rows_total = ($rowNumber - 1);
            $importFile->rows_success = $successCount;
            $importFile->rows_failed = $failureCount;
            $importFile->completed_at = now();
            $importFile->save();

            Log::info("File {$importFileId} completed: {$successCount} success, {$failureCount} failed");

            return 0;
        } catch (\Exception $e) {
            Log::error("Error processing file {$importFileId}: " . $e->getMessage());
            $importFile->status = 'failed';
            $importFile->save();
            return 1;
        }
    }
}
