<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$indexNumbers = [
    'PS0402062-0069',
    'PS0402064-0062',
    'PS0403109-0048',
    'PS0403062-0036',
    'PS9403085-0011',
    'PS0404090-0001',
    'PS040409-0002'
];

foreach ($indexNumbers as $idx) {
    echo "=== INDEX: {$idx} ===\n";
    
    // Find in candidates table
    $candidate = DB::table('candidates')
        ->where('candidate_id', $idx)
        ->first();
        
    if ($candidate) {
        echo "  [FOUND CANDIDATE] ID: {$candidate->id}, Name: {$candidate->full_name}, School: {$candidate->school_id}\n";
        // Check if registered
        $reg = DB::table('candidate_exam_registrations')
            ->where('candidate_id', $candidate->id)
            ->first();
        if ($reg) {
            echo "    [REGISTRATION] Year: {$reg->year}, Exam Type ID: {$reg->exam_type_id}\n";
        } else {
            echo "    [NO REGISTRATION FOUND]\n";
        }
    } else {
        echo "  [NO CANDIDATE FOUND BY INDEX]\n";
        // Try searching for partial index number or PREM number?
        // Let's do a partial search
        $similar = DB::table('candidates')
            ->where('candidate_id', 'like', '%' . substr($idx, -7) . '%')
            ->get();
        if ($similar->count() > 0) {
            echo "    Similar candidates found:\n";
            foreach ($similar as $s) {
                echo "      - ID: {$s->id}, Index: {$s->candidate_id}, Name: {$s->full_name}\n";
            }
        }
    }

    // Find raw marks
    $rawMarks = DB::table('raw_marks')
        ->where('candidate_index_number', $idx)
        ->get();
        
    echo "  [RAW MARKS] Count: " . $rawMarks->count() . "\n";
    foreach ($rawMarks as $rm) {
        echo "    - ID: {$rm->id}, candidate_id field: '" . ($rm->candidate_id ?? 'NULL') . "', school_id: {$rm->school_id}, subject_id: {$rm->subject_id}\n";
    }
    echo "\n";
}
