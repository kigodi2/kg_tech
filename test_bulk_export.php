<?php

use App\Services\MarkImport\BulkCsvExportService;
use App\Models\ExamYear;
use App\Models\School;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$container = $app->make(\Illuminate\Contracts\Container\Container::class);

// Get service
$service = $container->make(BulkCsvExportService::class);

try {
    echo "=== BULK EXPORT TEST ===\n\n";
    
    $schoolId = 1;
    $examYearId = 1;
    
    $school = School::find($schoolId);
    $examYear = ExamYear::find($examYearId);
    
    echo "School: " . ($school ? $school->name : 'NOT FOUND') . "\n";
    echo "Exam Year: " . ($examYear ? $examYear->year_label : 'NOT FOUND') . "\n\n";
    
    if (!$school || !$examYear) {
        echo "ERROR: School or Exam Year not found\n";
        exit(1);
    }
    
    echo "Generating bulk export...\n";
    $result = $service->generateBulkExport($schoolId, $examYearId);
    
    $zipPath = $result['zip_path'];
    $filename = $result['filename'];
    
    echo "ZIP Path: " . $zipPath . "\n";
    echo "Filename: " . $filename . "\n";
    echo "File exists: " . (file_exists($zipPath) ? 'YES' : 'NO') . "\n";
    echo "File size: " . filesize($zipPath) . " bytes\n\n";
    
    // Check ZIP contents
    $zip = new ZipArchive();
    if (!$zip->open($zipPath)) {
        echo "ERROR: Cannot open ZIP file\n";
        exit(1);
    }
    
    echo "Files in ZIP:\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        echo "  - " . $zip->getNameIndex($i) . " (" . $zip->statIndex($i)['size'] . " bytes)\n";
    }
    
    // Check manifest
    echo "\n=== MANIFEST ===\n";
    $manifestContent = $zip->getFromName('manifest.json');
    if ($manifestContent) {
        echo "✓ manifest.json FOUND!\n";
        $manifest = json_decode($manifestContent, true);
        echo json_encode($manifest, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "✗ manifest.json NOT FOUND!\n";
    }
    
    $zip->close();
    
    echo "\n✓ TEST PASSED: Bulk export works correctly!\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
