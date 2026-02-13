@extends('layout')

@php
// Services are provided by ResultsComposer
// No need to instantiate them here
@endphp

@section('content')
<div style="background-color: #B0E0E6; min-height: 100vh; padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'Maiandra GD', sans-serif;">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb Navigation -->
        <div style="margin-bottom: 1rem;">
            <a href="{{ route('hierarchy.schools', $school->district_id) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                ← Back to Schools
            </a>
        </div>
        
        <!-- NECTA Official Header -->
        <div style="background-color: #B0E0E6; padding-top: 1.5rem; padding-bottom: 1.5rem; padding-left: 1rem; padding-right: 1rem; margin-bottom: 1.5rem;">
            <div class="flex items-center justify-between gap-4">
                <!-- Left Emblem -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
                
                <!-- Center Content -->
                <div class="flex-1 text-center px-4">
                    <p class="text-lg font-bold text-blue-900">PRIME MINISTER'S OFFICE</p>
                    <p class="text-lg font-bold text-blue-900">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION - JANUARY, 2026</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">{{ $school->code }} - {{ strtoupper($school->name) }}</p>
                </div>
                
                <!-- Right Emblem -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
            </div>
        </div>

        @php
            // CALCULATE CANDIDATES WITH METRICS EARLY - before summary table
            // This ensures we use the SAME calculations for both summary and detailed sections
            
            $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
            
            // Build candidatesWithMetrics - SAME as detailed section
            $candidatesWithMetrics = $candidates->map(function($cand) use ($acseeType) {
                $candidateMarks = \App\Models\SubjectMarks::where('candidate_id', $cand->id)
                    ->where('exam_type_id', $acseeType?->id)
                    ->get();
                
                $marksCount = 0;
                $totalMarks = 0;
                $totalPoints = 0;
                $validSubjectCount = 0;
                
                foreach($candidateMarks as $mark) {
                    if ($mark->marks_obtained !== null) {
                        $marksCount++;
                        $totalMarks += $mark->average;
                        
                        // Calculate points for division (excluding subjects)
                        $subjectName = $mark->subject?->name ?? '';
                        if (!in_array(strtoupper($subjectName), ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'])) {
                            $grade = $mark->grade_from_average;
                            $gradePoints = match($grade) {
                                'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'S' => 6, 'F' => 7,
                                default => 7
                            };
                            $totalPoints += $gradePoints;
                            $validSubjectCount++;
                        }
                    }
                }
                
                $subjectSelections = \App\Models\CandidateSubjectSelection::where('candidate_id', $cand->id)
                    ->where('exam_type_id', $acseeType?->id)
                    ->count();
                
                // Determine status
                if ($marksCount === 0) {
                    $status = 'ABS';
                } elseif ($marksCount < $subjectSelections) {
                    $status = 'INC';
                } else {
                    $status = 'COMPLETE';
                }
                
                $avgMark = $marksCount > 0 ? $totalMarks / $marksCount : 0;
                
                // Recalculate division from total points (NECTA 7-point scale)
                $division = 0; // Default to 0
                if ($totalPoints > 0 && $totalPoints <= 9) $division = 1; // DIV I
                elseif ($totalPoints >= 10 && $totalPoints <= 12) $division = 2; // DIV II
                elseif ($totalPoints >= 13 && $totalPoints <= 17) $division = 3; // DIV III
                elseif ($totalPoints >= 18 && $totalPoints <= 19) $division = 4; // DIV IV
                else $division = 0; // DIV 0 (fail)
                
                return [
                    'candidate' => $cand,
                    'status' => $status,
                    'division' => $division,
                    'avg' => $avgMark,
                    'totalPoints' => $totalPoints,
                ];
            })->sortBy(function($item) {
                // Sort by: status (COMPLETE first), then GPA ascending (lower GPA = better performance)
                $statusOrder = ['COMPLETE' => 0, 'INC' => 1, 'ABS' => 2];
                return [
                    $statusOrder[$item['status']] ?? 999,
                    $item['totalPoints'], // Ascending order (lower points = better)
                    $item['avg'] // Then by average mark ascending
                ];
            })->values();
            
            // Now count divisions by sex from candidatesWithMetrics
            $divisionStatsBySex = [
                'F' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
                'M' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
            ];
            
            $absIncStatsBySex = [
                'F' => ['ABS' => 0, 'INC' => 0],
                'M' => ['ABS' => 0, 'INC' => 0],
            ];
            
            $totalDivisions = [
                'I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0,
            ];
            
            // Count total candidates by gender
             $genderCounts = [
                 'F' => 0,
                 'M' => 0,
             ];
             
             // Count by gender and division from the same metrics
             foreach($candidatesWithMetrics as $data) {
                 $candidate = $data['candidate'];
                 $gender = $candidate->gender;
                 $status = $data['status'];
                 $division = $data['division'];
                 
                 $genderCounts[$gender]++;
                 
                 if ($status === 'ABS') {
                     $absIncStatsBySex[$gender]['ABS']++;
                 } elseif ($status === 'INC') {
                     $absIncStatsBySex[$gender]['INC']++;
                 } else {
                     // COMPLETE: count by division
                     if ($division === 1) {
                         $divisionStatsBySex[$gender]['I']++;
                         $totalDivisions['I']++;
                     } elseif ($division === 2) {
                         $divisionStatsBySex[$gender]['II']++;
                         $totalDivisions['II']++;
                     } elseif ($division === 3) {
                         $divisionStatsBySex[$gender]['III']++;
                         $totalDivisions['III']++;
                     } elseif ($division === 4) {
                         $divisionStatsBySex[$gender]['IV']++;
                         $totalDivisions['IV']++;
                     } else {
                         $divisionStatsBySex[$gender]['0']++;
                         $totalDivisions['0']++;
                     }
                 }
             }
        @endphp

        <!-- SECTION 1: DIVISION PERFORMANCE SUMMARY -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0;">
            <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="8">DIVISION PERFORMANCE SUMMARY</th>
                    </tr>
                    <tr style="background-color: LIGHTYELLOW;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">SEX</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">I</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">II</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">III</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">IV</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">0</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">INC</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">ABS</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    {{-- Dynamic sex rows: show only if candidates of that gender are registered --}}
                    @if($genderCounts['F'] > 0)
                    <tr style="color: #000080;">
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">F</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['0'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['INC'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['ABS'] }}</td>
                    </tr>
                    @endif
                    @if($genderCounts['M'] > 0)
                    <tr style="color: #000080;">
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">M</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['0'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['M']['INC'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['M']['ABS'] }}</td>
                    </tr>
                    @endif
                    {{-- Always show Total (T) row --}}
                    <tr style="background-color: LIGHTYELLOW; color: #000080;">
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">T</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['0'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- SECTION 2: DETAILED RESULTS TABLE (NECTA FORMAT) -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0.25rem;">
            <div class="overflow-x-auto">
            <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">CNO</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">SEX</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">COMB</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 50%;">DETAILED SUBJECTS RESULT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">TOTAL</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">AVG</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">GRD</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">PTS</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">DIV</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">GPA</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">POS</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @php
                        // candidatesWithMetrics already calculated earlier (before Section 1)
                        // No need to recalculate - just use it
                        $positionCounter = 1;
                    @endphp
                    @forelse($candidatesWithMetrics as $position => $data)
                        @php $candidate = $data['candidate']; @endphp
                        @php
                            $registration = $candidate->examRegistrations->first();
                            $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
                            $subjectSelections = \App\Models\CandidateSubjectSelection::where('candidate_id', $candidate->id)
                                ->when($acseeType, function($q) use ($acseeType) {
                                    $q->where('exam_type_id', $acseeType->id);
                                })
                                ->with('subject')
                                ->orderBy('subject_id')
                                ->get();
                            
                            // Fetch all marks for this candidate at once (more efficient)
                            $candidateMarks = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)
                                ->where('exam_type_id', $acseeType?->id)
                                ->get()
                                ->keyBy('subject_id');
                            
                            // Build subject results using model accessors
                             // Model accessors calculate average and grade automatically
                             $subjectResults = $subjectSelections->map(function($selection) use ($candidateMarks) {
                                 $mark = $candidateMarks->get($selection->subject_id);
                                 $subject = $selection->subject;
                                 $name = $subject?->name ?? '-';
                                 
                                 if (!$mark || $mark->marks_obtained === null) {
                                     return $name . '= -';
                                 }
                                 
                                 // Use model accessors (cleaner!)
                                 $average = $mark->average;          // Accessor calculates average
                                 $grade = $mark->grade_from_average; // Accessor calculates grade
                                 
                                 return $name . '=' . $average . " '" . $grade . "'";
                             })->join(', ');
                            
                            // Check if marks are entered and determine status (ABS, INC, or normal)
                            $marksCount = 0;
                            $subjectsAllocated = $subjectSelections->count();
                            
                            foreach($candidateMarks as $mark) {
                                if ($mark->marks_obtained !== null) {
                                    $marksCount++;
                                }
                            }
                            
                            // Determine candidate status
                            if ($marksCount === 0) {
                                // No marks in ANY subject = ABS (Absent)
                                $candidateStatus = 'ABS';
                                $hasMarks = false;
                            } elseif ($marksCount < $subjectsAllocated) {
                                // Marks in SOME but not ALL subjects = INC (Incomplete)
                                $candidateStatus = 'INC';
                                $hasMarks = true;
                            } else {
                                // Marks in ALL subjects = Normal display
                                $candidateStatus = 'COMPLETE';
                                $hasMarks = true;
                            }
                            
                            // Calculate total marks from model accessors
                             // Model accessors provide already-calculated averages
                             $calculatedTotalMarks = 0;
                             foreach ($candidateMarks as $mark) {
                                 if ($mark->marks_obtained !== null) {
                                     $calculatedTotalMarks += $mark->average; // Use accessor
                                 }
                             }
                             
                             // Use calculated total from averages
                              $totalMarks = $calculatedTotalMarks > 0 ? $calculatedTotalMarks : ($registration?->total_marks ?? 0);
                              
                              // Calculate average early (needed for GRD calculation)
                              $averageMarks = $marksCount > 0 ? ($totalMarks / $marksCount) : 0;
                              
                              // RECALCULATE GRD, PTS, DIV from averaged marks
                              // (Pre-stored values were calculated before averaging fix)
                              $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
                              $totalPoints = 0;
                              $validSubjectCount = 0;
                              
                              // Recalculate points from each subject's averaged grade
                              foreach ($candidateMarks as $mark) {
                                  if ($mark->marks_obtained !== null) {
                                      $subjectName = $mark->subject?->name ?? '';
                                      $gradePoints = get_grade_points($mark->grade_from_average);
                                      
                                      // Only count non-excluded subjects for points and GPA
                                      if (!is_excluded_subject($subjectName)) {
                                          // Add to total points (only non-excluded)
                                          $totalPoints += $gradePoints;
                                          $validSubjectCount++;
                                      }
                                  }
                              }
                              
                              // Recalculate GRD from the average mark (not best individual grade)
                              // GRD is the grade equivalent of the average mark
                              $grd = $marksCount > 0 ? get_grade_from_mark($averageMarks) : '-';
                              
                              // Recalculate GPA
                              $gpa = $validSubjectCount > 0 
                                  ? round($totalPoints / $validSubjectCount, 4)
                                  : 0;
                              
                              // Recalculate DIV from new points
                              $divisionInfo = get_division_info($totalPoints);
                              $division = $divisionInfo['name'];
                              
                              // For display
                              $points = $totalPoints > 0 ? $totalPoints : '-';
                        @endphp
                        <tr style="color: #000080; border: 1px solid #999;">
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->combination ?? '-' }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                @if($candidateStatus === 'ABS')
                                    ABS
                                @elseif($candidateStatus === 'INC')
                                    INC
                                @else
                                    {{ $subjectResults ?: '-' }}
                                @endif
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                @if($candidateStatus === 'ABS')
                                    ABS
                                @elseif($candidateStatus === 'INC')
                                    INC
                                @else
                                    {{ $totalMarks ?: '-' }}
                                @endif
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                @if($candidateStatus === 'ABS')
                                    ABS
                                @elseif($candidateStatus === 'INC')
                                    INC
                                @else
                                    {{ $averageMarks ? number_format($averageMarks, 2) : '-' }}
                                @endif
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">
                                 @if($candidateStatus === 'ABS')
                                     ABS
                                 @elseif($candidateStatus === 'INC')
                                     INC
                                 @else
                                     {{ $grd }}
                                 @endif
                             </td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                 @if($candidateStatus === 'ABS')
                                     ABS
                                 @elseif($candidateStatus === 'INC')
                                     INC
                                 @else
                                     {{ $points }}
                                 @endif
                             </td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">
                                 @if($candidateStatus === 'ABS')
                                     ABS
                                 @elseif($candidateStatus === 'INC')
                                     INC
                                 @else
                                     {{ $division }}
                                 @endif
                             </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                @if($candidateStatus === 'ABS')
                                    ABS
                                @elseif($candidateStatus === 'INC')
                                    INC
                                @else
                                    {{ number_format($gpa, 4) }}
                                @endif
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">
                                @if($candidateStatus === 'ABS')
                                    ABS
                                @elseif($candidateStatus === 'INC')
                                    INC
                                @else
                                    {{ $positionCounter++ }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="border-2 border-gray-600 p-1 text-xs text-center">No candidates found for this school</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- SECTION 3: EXAMINATION CENTRE OVERALL PERFORMANCE -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 1.5rem;">
            <table class="w-full border-collapse" style="margin-bottom: 0; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="11">EXAMINATION CENTRE OVERALL PERFORMANCE</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">EXAMINATION CENTRE REGION</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $district->region->name }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">EXAMINATION CENTRE DISTRICT</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $district->name }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">TOTAL REGISTERED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $candidates->count() }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">TOTAL PASSED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $totalDivisions['I'] + $totalDivisions['II'] + $totalDivisions['III'] + $totalDivisions['IV'] }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">TOTAL FAILED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $totalDivisions['0'] }}</td>
                    </tr>
                    <tr>
                         <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">EXAMINATION CENTRE GPA</td>
                         @php
                            // Calculate EXAMINATION CENTRE GPA as average of all individual complete candidates' GPAs
                            $completeCandidates = 0;
                            $sumOfGpas = 0;
                            
                            foreach($candidatesWithMetrics as $data) {
                                if ($data['status'] === 'COMPLETE') {
                                    // Get each candidate's individual GPA
                                    // Get marks for this candidate to calculate their GPA
                                    $candidateMarks = \App\Models\SubjectMarks::where('candidate_id', $data['candidate']->id)
                                        ->where('exam_type_id', $acseeType?->id)
                                        ->get();
                                    
                                    $totalPoints = 0;
                                    $validSubjectCount = 0;
                                    
                                    foreach($candidateMarks as $mark) {
                                        if ($mark->marks_obtained !== null) {
                                            $subjectName = $mark->subject?->name ?? '';
                                            if (!in_array(strtoupper($subjectName), ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'])) {
                                                $grade = $mark->grade_from_average;
                                                $gradePoints = match($grade) {
                                                    'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'S' => 6, 'F' => 7,
                                                    default => 7
                                                };
                                                $totalPoints += $gradePoints;
                                                $validSubjectCount++;
                                            }
                                        }
                                    }
                                    
                                    // Calculate this candidate's GPA
                                    $candidateGpa = $validSubjectCount > 0 ? $totalPoints / $validSubjectCount : 0;
                                    $sumOfGpas += $candidateGpa;
                                    $completeCandidates++;
                                }
                            }
                            
                            if ($completeCandidates > 0) {
                                 $overallGpa = $sumOfGpas / $completeCandidates;
                                 $gpaInfo = get_gpa_info($overallGpa);
                             } else {
                                 $gpaInfo = ['text' => 'N/A', 'color' => '#CCCCCC'];
                             }
                            @endphp
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; background-color: {{ $gpaInfo['color'] }};">{{ $gpaInfo['text'] }}</td>
                      </tr>
                </tbody>
            </table>

            <!-- EXAMINATION CENTRE DIVISION PERFORMANCE -->
            @php
                // Calculate proper counts for division performance
                $absCount = 0;
                $incCount = 0;
                $completeCount = 0;
                
                foreach ($candidates as $candidate) {
                    $subjectSelections = \App\Models\CandidateSubjectSelection::where('candidate_id', $candidate->id)
                        ->where('exam_type_id', $acseeType?->id ?? 2)
                        ->count();
                    
                    $candidateMarks = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)
                        ->where('exam_type_id', $acseeType?->id ?? 2)
                        ->get();
                    
                    $marksCount = 0;
                    foreach($candidateMarks as $mark) {
                        if ($mark->marks_obtained !== null) {
                            $marksCount++;
                        }
                    }
                    
                    if ($marksCount === 0) {
                        $absCount++;
                    } elseif ($marksCount < $subjectSelections) {
                        $incCount++;
                    } else {
                        $completeCount++;
                    }
                }
                
                $totalRegistered = $candidates->count();
                $totalSat = $totalRegistered - $absCount; // SAT = Registered - Absent
            @endphp
            <table class="w-full border-collapse" style="margin-bottom: 0; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                   <tr style="background-color: #003366;">
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="11">EXAMINATION CENTRE DIVISION PERFORMANCE</th>
                   </tr>
                   <tr style="background-color: LIGHTYELLOW;">
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">REGIST</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">ABSENT</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">SAT</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">WITHHELD</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">INC</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">CLEAN</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">DIV I</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">DIV II</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">DIV III</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">DIV IV</th>
                       <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">DIV 0</th>
                   </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                   <tr>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalRegistered }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $absCount }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalSat }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">0</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $incCount }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $completeCount }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['I'] }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['II'] }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['III'] }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['IV'] }}</td>
                       <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['0'] }}</td>
                   </tr>
                </tbody>
            </table>

            <!-- Subjects Performance -->
            <table class="w-full border-collapse" style="table-layout: auto; margin-bottom: 1.5rem; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="13">EXAMINATION CENTRE SUBJECTS PERFORMANCE</th>
                    </tr>
                    <tr style="background-color: LIGHTYELLOW;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080;">CODE</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #000080; white-space: nowrap;">SUBJECT NAME</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">A</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">B</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">C</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">D</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">E</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">S</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">F</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">ABS</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">TOTAL</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #000080; min-width: 50px;">GPA</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #000080; white-space: nowrap;">COMPETENCY LEVEL</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                    @forelse($subjectsPerformance as $subject)
                         @php
                             $subjectGpaInfo = get_gpa_info($subject['gpa']);
                             $competencyClass = match(true) {
                                 str_contains($subject['competency'], 'Grade A') => 'bg-red-200',
                                 str_contains($subject['competency'], 'Grade B') => 'bg-blue-200',
                                 str_contains($subject['competency'], 'Grade C') => 'bg-green-200',
                                 str_contains($subject['competency'], 'Grade D') => 'bg-yellow-300',
                                 default => 'bg-red-300',
                             };
                         @endphp
                         <tr>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $subject['code'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; white-space: nowrap;">{{ $subject['name'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeA'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeB'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeC'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeD'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeE'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeS'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['gradeF'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['absent'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ $subject['total'] }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; min-width: 50px;">{{ number_format($subject['gpa'], 4) }}</td>
                             <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: left; white-space: nowrap; background-color: {{ $subjectGpaInfo['color'] }};">{{ $subject['competency'] }}</td>
                         </tr>
                     @empty
                        <tr>
                            <td colspan="13" class="border-2 border-gray-600 p-1 text-xs text-center">No subjects found for this school</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
