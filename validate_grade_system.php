<?php

/**
 * Grade Calculation System Validation Script
 * 
 * Run with: php artisan tinker < validate_grade_system.php
 * Or: php -r "include 'validate_grade_system.php';"
 */

echo "=== Grade Calculation System Validation ===\n\n";

// 1. Check database columns exist
echo "1. Checking database columns...\n";
$columns = \Illuminate\Support\Facades\DB::select("DESCRIBE candidate_exam_registrations");
$hasColumns = false;
foreach($columns as $col) {
    if(in_array($col->Field, ['total_marks', 'total_points'])) {
        echo "   ✓ Column: {$col->Field}\n";
        $hasColumns = true;
    }
}

if (!$hasColumns) {
    echo "   ✗ Missing columns!\n";
    exit(1);
}

// 2. Check model fillable
echo "\n2. Checking CandidateExamRegistration model...\n";
$model = new \App\Models\CandidateExamRegistration();
$fillable = $model->getFillable();
if (in_array('total_marks', $fillable) && in_array('total_points', $fillable)) {
    echo "   ✓ Fillable array updated\n";
} else {
    echo "   ✗ Fillable array missing fields\n";
    exit(1);
}

// 3. Check ExamYear has year_label
echo "\n3. Checking ExamYear...\n";
$examYear = \App\Models\ExamYear::first();
if ($examYear) {
    $yearLabel = $examYear->year_label ?? $examYear->year;
    echo "   ✓ Exam Year: {$yearLabel}\n";
} else {
    echo "   ✗ No exam years found\n";
    exit(1);
}

// 4. Check marks exist
echo "\n4. Checking marks in database...\n";
$marksCount = \App\Models\SubjectMarks::whereNotNull('marks_obtained')->count();
echo "   ✓ Marks found: {$marksCount}\n";

if ($marksCount === 0) {
    echo "   ⚠ No marks to calculate!\n";
    exit(0);
}

// 5. Test grade calculation on a candidate with marks
echo "\n5. Testing grade calculation...\n";
$mark = \App\Models\SubjectMarks::whereNotNull('marks_obtained')->first();
if ($mark) {
    $candidateId = $mark->candidate_id;
    $examYearId = 1; // Default to first exam year
    $examTypeId = $mark->exam_type_id;
    
    $service = app(\App\Services\Results\GradeCalculationService::class);
    $result = $service->calculateForCandidate($candidateId, $examYearId, $examTypeId);
    
    if ($result) {
        echo "   ✓ Grade calculation successful\n";
        
        $registration = \App\Models\CandidateExamRegistration::where('candidate_id', $candidateId)
            ->where('exam_year_id', $examYearId)
            ->where('exam_type_id', $examTypeId)
            ->first();
        
        if ($registration && $registration->total_marks !== null) {
            echo "   ✓ Data persisted to database\n";
            echo "      - Total Marks: {$registration->total_marks}\n";
            echo "      - Total Points: {$registration->total_points}\n";
            echo "      - GPA: {$registration->gpa}\n";
            echo "      - Division: {$registration->division}\n";
        } else {
            echo "   ✗ Data not persisted\n";
            exit(1);
        }
    } else {
        echo "   ✗ Grade calculation failed\n";
        exit(1);
    }
}

// 6. Check subjects performance data
echo "\n6. Checking subjects performance...\n";
$subjects = \App\Models\Subject::whereHas('marks')->count();
echo "   ✓ Subjects with marks: {$subjects}\n";

echo "\n=== All Validations Passed ===\n";
exit(0);
