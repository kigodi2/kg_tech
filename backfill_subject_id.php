<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$updated = DB::statement('
    UPDATE raw_marks 
    SET subject_id = (
        SELECT subject_id FROM mark_import_batches 
        WHERE mark_import_batches.id = raw_marks.mark_import_batch_id
    )
    WHERE subject_id IS NULL
');

$withSubject = DB::table('raw_marks')->whereNotNull('subject_id')->count();
$total = DB::table('raw_marks')->count();
echo "Records with subject_id: $withSubject / $total\n";
