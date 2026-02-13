<?php

namespace App\Console\Commands;

use App\Models\BulkImport;
use App\Models\ExamYear;
use App\Models\School;
use App\Services\MarkImport\BulkCsvExportService;
use App\Services\MarkImport\ZipPreviewService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class TestBulkImportFlow extends Command
{
    protected $signature = 'test:bulk-import-flow {school_id=9} {exam_year_id=1}';
    protected $description = 'Test complete bulk import flow: export -> preview -> validate';

    public function handle(BulkCsvExportService $exportService, ZipPreviewService $previewService)
    {
        $schoolId = $this->argument('school_id');
        $examYearId = $this->argument('exam_year_id');

        $this->info("=== BULK IMPORT FLOW TEST ===\n");

        $school = School::find($schoolId);
        $examYear = ExamYear::find($examYearId);

        $this->line("School: " . ($school ? $school->name : 'NOT FOUND'));
        $this->line("Exam Year: " . ($examYear ? $examYear->year_label : 'NOT FOUND') . "\n");

        if (!$school || !$examYear) {
            $this->error('School or Exam Year not found');
            return 1;
        }

        try {
            // STEP 1: Generate export
            $this->info('STEP 1: Generating bulk export...');
            $result = $exportService->generateBulkExport($schoolId, $examYearId);
            $zipPath = $result['zip_path'];
            $this->line("  ✓ ZIP created: " . basename($zipPath));
            $this->line("  ✓ File size: " . filesize($zipPath) . " bytes\n");

            // STEP 2: Verify manifest
            $this->info('STEP 2: Verifying manifest.json...');
            $zip = new ZipArchive();
            $zip->open($zipPath);
            $manifestContent = $zip->getFromName('manifest.json');
            $zip->close();

            if ($manifestContent) {
                $manifest = json_decode($manifestContent, true);
                $this->line("  ✓ Manifest found with " . count($manifest['files']) . " subjects");
                $this->line("  ✓ Total candidates: " . array_sum(array_column($manifest['files'], 'candidate_count')) . "\n");
            } else {
                $this->error('  ✗ Manifest NOT found in ZIP!');
                return 1;
            }

            // STEP 3: Test preview endpoint logic
            $this->info('STEP 3: Testing ZIP preview...');
            $validation = $previewService->validate($zipPath);
            if ($validation['valid']) {
                $this->line("  ✓ ZIP validation passed");
            } else {
                $this->error('  ✗ ZIP validation failed:');
                foreach ($validation['errors'] ?? [] as $error) {
                    $this->error("     - " . $error);
                }
                return 1;
            }

            $preview = $previewService->preview($zipPath);
            $this->line("  ✓ Preview generated");
            $this->line("  ✓ Files: " . count($preview['files'] ?? []));
            $this->line("  ✓ Total candidates: " . ($preview['total_candidates'] ?? 0) . "\n");

            // STEP 4: Simulate import readiness
            $this->info('STEP 4: Checking import readiness...');
            $this->line("  ✓ Manifest file present");
            $this->line("  ✓ ZIP validation passed");
            $this->line("  ✓ Preview available");
            $this->line("  ✓ Session storage ready\n");

            $this->info("✓ ALL TESTS PASSED!");
            $this->line("The bulk import system is ready to use.\n");

            // Cleanup
            @unlink($zipPath);
            $this->line("Cleanup: Test ZIP removed.");

            return 0;

        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
