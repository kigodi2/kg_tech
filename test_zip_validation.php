<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

$container = $app->make(\Illuminate\Contracts\Container\Container::class);

// Get the service
$zipService = $container->make(\App\Services\MarkImport\ZipPreviewService::class);

// Get the temp directory
$tempDir = storage_path('app/temp');
$files = glob($tempDir . '/*.zip');

if (empty($files)) {
    echo "No ZIP files found in $tempDir\n";
    echo "Please upload a file first, then run this script\n";
    exit(1);
}

// Test the most recent ZIP
$zipFile = end($files);
echo "Testing ZIP file: " . basename($zipFile) . "\n";
echo "Size: " . filesize($zipFile) . " bytes\n\n";

// Validate
echo "=== Running Validation ===\n";
$result = $zipService->validate($zipFile);

echo "Valid: " . ($result['valid'] ? 'YES' : 'NO') . "\n";
echo "Errors: " . count($result['errors']) . "\n";

if (!empty($result['errors'])) {
    echo "\nError details:\n";
    foreach ($result['errors'] as $error) {
        echo "  - " . $error . "\n";
    }
}

echo "\n=== Trying to generate preview ===\n";
try {
    $preview = $zipService->preview($zipFile);
    echo "Preview generated successfully!\n";
    echo "Preview data:\n";
    echo json_encode($preview, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Preview generation failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
