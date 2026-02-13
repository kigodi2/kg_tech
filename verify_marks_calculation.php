#!/usr/bin/env php
<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\SubjectMarks;
use App\Models\Subject;
use App\Models\CandidateExamRegistration;

$app = require_once 'bootstrap/app.php';
$container = $app->make('Illuminate\Contracts\Container\Container');
$container->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n=== MARKS CALCULATION VERIFICATION ===\n";

// Find multi-paper subjects
$multiPaperSubjects = Subject::where(function ($q) {
    $q->where('written_papers', '>', 1)
      ->orWhere('has_practical', true)
      ->orWhere('has_project', true);
})->get();

echo "\nMulti-Paper Subjects Found: " . $multiPaperSubjects->count() . "\n";

foreach ($multiPaperSubjects as $subject) {
    $totalPapers = ($subject->written_papers ?? 1) + 
                  ($subject->has_practical ? 1 : 0) + 
                  ($subject->has_project ? 1 : 0);
    
    echo "\n--- Subject: {$subject->name} (ID: {$subject->id}) ---";
    echo "\nTotal Papers/Components: {$totalPapers}";
    echo "\nWritten Papers: " . ($subject->written_papers ?? 1);
    echo "\nHas Practical: " . ($subject->has_practical ? 'Yes' : 'No');
    echo "\nHas Project: " . ($subject->has_project ? 'Yes' : 'No');
    
    // Check sample marks for this subject
    $sampleMarks = SubjectMarks::where('subject_id', $subject->id)
        ->whereNotNull('marks_obtained')
        ->limit(5)
        ->get();
    
    if ($sampleMarks->count() > 0) {
        echo "\nSample Marks for {$subject->name}:\n";
        echo "Candidate ID | Paper1 | Paper2 | Paper3 | Marks_Obtained | Avg Calculation\n";
        echo str_repeat("-", 90) . "\n";
        
        foreach ($sampleMarks as $mark) {
            $paper1 = $mark->paper_1 ?? '-';
            $paper2 = $mark->paper_2 ?? '-';
            $paper3 = $mark->paper_3 ?? '-';
            $obtained = $mark->marks_obtained ?? '-';
            
            // Calculate expected average
            $papers = [];
            if (!empty($mark->paper_1)) $papers[] = (float)$mark->paper_1;
            if (!empty($mark->paper_2)) $papers[] = (float)$mark->paper_2;
            if (!empty($mark->paper_3)) $papers[] = (float)$mark->paper_3;
            
            if (count($papers) > 0) {
                $expected = $totalPapers > 1 
                    ? round(array_sum($papers) / count($papers), 2)
                    : ($papers[0] ?? '-');
                $match = ($obtained == $expected) ? '✓' : '✗';
            } else {
                $expected = '-';
                $match = '?';
            }
            
            printf("%-12s | %-6s | %-6s | %-6s | %-14s | %s\n",
                $mark->candidate_id,
                $paper1,
                $paper2,
                $paper3,
                $obtained,
                $expected . ' ' . $match
            );
        }
    } else {
        echo "\nNo marks found for this subject.\n";
    }
}

// Check candidate exam registrations
echo "\n\n=== CANDIDATE EXAM REGISTRATIONS ===\n";
echo "Sample registrations with marks breakdown:\n";

$registrations = CandidateExamRegistration::whereNotNull('total_marks')
    ->limit(5)
    ->get();

if ($registrations->count() > 0) {
    echo "Candidate ID | Total Marks | GPA       | Division | Points\n";
    echo str_repeat("-", 65) . "\n";
    
    foreach ($registrations as $reg) {
        printf("%-12s | %-11s | %-9s | %-8s | %-6s\n",
            $reg->candidate_id,
            $reg->total_marks ?? '-',
            $reg->gpa ?? '-',
            $reg->division ?? '-',
            $reg->total_points ?? '-'
        );
    }
} else {
    echo "No registrations with marks found.\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
