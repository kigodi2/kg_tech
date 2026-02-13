<?php
/**
 * Debug script to verify grade calculations
 * 
 * Usage: php debug_grades.php <candidate_id> <exam_year_id> <exam_type_id>
 * Example: php debug_grades.php 1 1 1
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\SubjectMarks;
use App\Models\CandidateExamRegistration;
use App\Services\Results\NectaGradingService;

$app = app();
$candidateId = $argv[1] ?? 1;
$examYearId = $argv[2] ?? 1;
$examTypeId = $argv[3] ?? 1;

echo "\n========================================\n";
echo "GRADE CALCULATION DEBUG REPORT\n";
echo "========================================\n";

// Get candidate
$candidate = Candidate::find($candidateId);
if (!$candidate) {
    echo "ERROR: Candidate not found: {$candidateId}\n";
    exit(1);
}

// Get exam year
$examYear = ExamYear::find($examYearId);
if (!$examYear) {
    echo "ERROR: Exam year not found: {$examYearId}\n";
    exit(1);
}

echo "\nCandidate: {$candidate->candidate_id} ({$candidate->full_name})\n";
echo "Exam Year: {$examYear->year} (ID: {$examYear->id})\n";
echo "Exam Type ID: {$examTypeId}\n";

// Get all marks for candidate
$marks = SubjectMarks::where('candidate_id', $candidateId)
    ->where('year', $examYear->year)
    ->where('exam_type_id', $examTypeId)
    ->with('subject')
    ->get();

echo "\n--- SUBJECT MARKS ---\n";
echo str_pad("Subject", 30) . " | " . str_pad("Marks", 10) . " | " . str_pad("Stored Grade", 12) . " | " . str_pad("Excluded", 10) . "\n";
echo str_repeat("-", 80) . "\n";

$gradingService = new NectaGradingService();
$totalMarks = 0;
$totalPoints = 0;
$validSubjectCount = 0;

foreach ($marks as $mark) {
    $subjectName = $mark->subject?->name ?? 'Unknown';
    $totalMarks += $mark->marks_obtained;
    
    // Recalculate what grade should be
    $calculatedGrade = $gradingService->calculateGrade($mark->marks_obtained);
    $isExcluded = $gradingService->isExcludedSubject($subjectName);
    
    if (!$isExcluded) {
        $points = $gradingService->getGradePoints($calculatedGrade);
        $totalPoints += $points;
        $validSubjectCount++;
    }
    
    $storedGrade = $mark->grade ?? 'NULL';
    $excludedFlag = $isExcluded ? 'YES' : 'NO';
    
    echo str_pad(substr($subjectName, 0, 28), 30) . " | " 
         . str_pad($mark->marks_obtained, 10) . " | " 
         . str_pad($storedGrade, 12) . " | " 
         . str_pad($excludedFlag, 10) . "\n";
}

echo str_repeat("-", 80) . "\n";
echo "Total Marks: {$totalMarks}\n";
echo "Valid Subjects: {$validSubjectCount}\n";
echo "Total Points: {$totalPoints}\n";

// Calculate GPA
$calculatedGPA = $validSubjectCount > 0 ? round($totalPoints / $validSubjectCount, 2) : 0;
echo "Calculated GPA: {$calculatedGPA}\n";

// Calculate division
$divisionInfo = $totalPoints > 0 ? $gradingService->calculateDivision($totalPoints) : null;
$calculatedDivision = $divisionInfo ? $divisionInfo['division'] : 'O';
echo "Calculated Division: {$calculatedDivision}\n";

// Check what's stored in exam registration
echo "\n--- DATABASE STORED VALUES ---\n";
$registration = CandidateExamRegistration::where('candidate_id', $candidateId)
    ->where('exam_year_id', $examYearId)
    ->where('exam_type_id', $examTypeId)
    ->first();

if ($registration) {
    echo "Total Marks (DB): " . ($registration->total_marks ?? 'NULL') . "\n";
    echo "Total Points (DB): " . ($registration->total_points ?? 'NULL') . "\n";
    echo "GPA (DB): " . ($registration->gpa ?? 'NULL') . "\n";
    echo "Division (DB): " . ($registration->division ?? 'NULL') . "\n";
    echo "Grade (DB): " . ($registration->grade ?? 'NULL') . "\n";
} else {
    echo "ERROR: No exam registration found\n";
}

// Check logs
echo "\n--- LAST 10 GRADE-RELATED LOG ENTRIES ---\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $cmd = "tail -100 " . escapeshellarg($logFile) . " | grep -i 'grade'";
    $output = shell_exec($cmd);
    echo $output ?: "No grade-related logs found\n";
} else {
    echo "Log file not found\n";
}

echo "\n========================================\n\n";
