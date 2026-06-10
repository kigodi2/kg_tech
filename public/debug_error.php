<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $batches = \App\Models\MarkImportBatch::where([
        'school_id' => 7849,
        'subject_id' => 130,
        'exam_year_id' => 1,
    ])->get();

    echo "<h3>Batches found: " . $batches->count() . "</h3>";
    foreach ($batches as $b) {
        echo "ID: " . $b->id . "<br>";
        echo "Code: " . $b->batch_code . "<br>";
        echo "Status: " . $b->status . "<br>";
        echo "Created By (User ID): " . $b->created_by . "<br>";
        echo "Created At: " . $b->created_at . "<br>";
        echo "Updated At: " . $b->updated_at . "<br>";
        echo "-------------------------------------<br>";
    }

} catch (\Throwable $e) {
    echo "<h3>Failed!</h3>";
    echo "<b>Exception:</b> " . get_class($e) . "<br>";
    echo "<b>Message:</b> " . $e->getMessage() . "<br>";
    echo "<b>File:</b> " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "<pre><b>Trace:</b><br>" . $e->getTraceAsString() . "</pre>";
}


