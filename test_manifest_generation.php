<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$container = $app->make(\Illuminate\Contracts\Container\Container::class);

// Get the service
$service = $container->make(\App\Services\MarkImport\BulkCsvExportService::class);

// Test with school 1, exam year 1
try {
    echo "Testing manifest generation...\n";
    
    $result = $service->generateBulkExport(1, 1);
    
    echo "ZIP Path: " . $result['zip_path'] . "\n";
    echo "Filename: " . $result['filename'] . "\n";
    
    // Check if manifest.json is in the ZIP
    $zip = new ZipArchive();
    if ($zip->open($result['zip_path'])) {
        echo "\nFiles in ZIP:\n";
        for ($i = 0; $i < $zip->numFiles; $i++) {
            echo "  - " . $zip->getNameIndex($i) . "\n";
        }
        
        // Check for manifest
        $manifestContent = $zip->getFromName('manifest.json');
        if ($manifestContent) {
            echo "\n✓ manifest.json FOUND!\n";
            echo "Content:\n";
            echo $manifestContent . "\n";
        } else {
            echo "\n✗ manifest.json NOT FOUND IN ZIP\n";
        }
        
        $zip->close();
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
