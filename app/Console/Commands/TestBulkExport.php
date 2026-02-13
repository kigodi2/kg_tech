<?php

namespace App\Console\Commands;

use App\Models\ExamYear;
use App\Models\School;
use App\Services\MarkImport\BulkCsvExportService;
use Illuminate\Console\Command;
use ZipArchive;

class TestBulkExport extends Command
{
    protected $signature = 'test:bulk-export {school_id=1} {exam_year_id=1}';
    protected $description = 'Test bulk CSV export with manifest generation';

    public function handle(BulkCsvExportService $exportService)
    {
        $schoolId = $this->argument('school_id');
        $examYearId = $this->argument('exam_year_id');

        $this->info("=== BULK EXPORT TEST ===\n");

        $school = School::find($schoolId);
        $examYear = ExamYear::find($examYearId);

        $this->line("School: " . ($school ? $school->name : 'NOT FOUND'));
        $this->line("Exam Year: " . ($examYear ? $examYear->year_label : 'NOT FOUND') . "\n");

        if (!$school || !$examYear) {
            $this->error('School or Exam Year not found');
            return 1;
        }

        try {
            $this->line('Generating bulk export...');
            $result = $exportService->generateBulkExport($schoolId, $examYearId);

            $zipPath = $result['zip_path'];
            $filename = $result['filename'];

            $this->line("ZIP Path: " . $zipPath);
            $this->line("Filename: " . $filename);
            $this->line("File exists: " . (file_exists($zipPath) ? 'YES' : 'NO'));
            $this->line("File size: " . filesize($zipPath) . " bytes\n");

            // Check ZIP contents
            $zip = new ZipArchive();
            if (!$zip->open($zipPath)) {
                $this->error('Cannot open ZIP file');
                return 1;
            }

            $this->line('Files in ZIP:');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $stat = $zip->statIndex($i);
                $this->line("  - $name (" . $stat['size'] . " bytes)");
            }

            // Check manifest
            $this->line("\n=== MANIFEST ===");
            $manifestContent = $zip->getFromName('manifest.json');
            if ($manifestContent) {
                $this->info('✓ manifest.json FOUND!');
                $manifest = json_decode($manifestContent, true);
                $this->line(json_encode($manifest, JSON_PRETTY_PRINT));
            } else {
                $this->error('✗ manifest.json NOT FOUND!');
            }

            $zip->close();

            $this->info("\n✓ TEST PASSED: Bulk export works correctly!");
            return 0;

        } catch (\Exception $e) {
            $this->error('ERROR: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
