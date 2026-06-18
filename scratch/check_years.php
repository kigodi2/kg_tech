<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$years = DB::table('exam_years')->get();
echo "Exam Years in DB:\n";
foreach ($years as $y) {
    echo "ID: {$y->id}, Year: {$y->year_label}, Is Active: " . ($y->is_active ? 'YES' : 'NO') . "\n";
}
