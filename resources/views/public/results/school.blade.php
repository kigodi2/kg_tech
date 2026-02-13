@extends('layout')

@section('content')
<div style="background-color: #B0E0E6; min-height: 100vh; padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'Maiandra GD', sans-serif;">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb Navigation -->
        <div style="margin-bottom: 1rem;">
            <a href="/results/{{ $examYear }}/{{ strtolower($examType) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                ← Back to Results Search
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
                    <p class="text-lg font-bold text-blue-900 mt-1">OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION - {{ $examYear }}</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">{{ $school->code }} - {{ strtoupper($school->name) }}</p>
                </div>
                
                <!-- Right Emblem -->
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
            </div>
        </div>

        @php
            // Calculate division statistics by sex and overall
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
            
            $genderCounts = [
                'F' => 0,
                'M' => 0,
            ];
            
            // Count by gender and division
            foreach($candidatesWithMetrics as $data) {
                $candidate = $data['candidate'];
                $gender = $candidate->gender;
                $division = $data['division'];
                $totalPoints = $data['totalPoints'];
                
                $genderCounts[$gender]++;
                
                if ($totalPoints === 0) {
                    // Mark as ABS
                    $absIncStatsBySex[$gender]['ABS']++;
                } else {
                    // Count by division
                    if ($division === 'I') {
                        $divisionStatsBySex[$gender]['I']++;
                        $totalDivisions['I']++;
                    } elseif ($division === 'II') {
                        $divisionStatsBySex[$gender]['II']++;
                        $totalDivisions['II']++;
                    } elseif ($division === 'III') {
                        $divisionStatsBySex[$gender]['III']++;
                        $totalDivisions['III']++;
                    } elseif ($division === 'IV') {
                        $divisionStatsBySex[$gender]['IV']++;
                        $totalDivisions['IV']++;
                    } else {
                        $divisionStatsBySex[$gender]['0']++;
                        $totalDivisions['0']++;
                    }
                }
            }
            
            // Calculate averages
            $totalCandidates = count($candidatesWithMetrics);
            $totalPassed = $totalDivisions['I'] + $totalDivisions['II'] + $totalDivisions['III'] + $totalDivisions['IV'];
            $totalFailed = $totalDivisions['0'];
            $totalAbsent = $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'];
            $overallGpa = 0;
            $completeCount = 0;
            
            foreach($candidatesWithMetrics as $data) {
                if ($data['totalPoints'] > 0) {
                    $overallGpa += $data['gpa'];
                    $completeCount++;
                }
            }
            $overallGpa = $completeCount > 0 ? $overallGpa / $completeCount : 0;
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
                        // Separate candidates into passed and abs
                        $passedCandidates = array_filter($candidatesWithMetrics, fn($d) => $d['totalPoints'] > 0);
                        $absCandidates = array_filter($candidatesWithMetrics, fn($d) => $d['totalPoints'] === 0);
                        
                        $positionCounter = 1;
                    @endphp
                    
                    @forelse($passedCandidates as $data)
                        @php 
                            $candidate = $data['candidate'];
                            $totalMarks = $data['totalMarks'];
                            $averageMarks = $data['average'];
                            $gpa = $data['gpa'];
                            $gpaInfo = $data['gpaInfo'];
                            $division = $data['division'];
                            $totalPoints = $data['totalPoints'];
                            
                            // Get detailed subjects
                            $subjectResults = [];
                            foreach($candidate->marks as $mark) {
                                $subject = $mark->subject;
                                $average = $mark->average ?? 0;
                                $grade = $mark->grade_from_average ?? '-';
                                $subjectResults[] = ($subject?->name ?? '-') . '=' . $average . " '" . $grade . "'";
                            }
                            $subjectResultsStr = implode(', ', $subjectResults) ?: '-';
                        @endphp
                        <tr style="color: #000080; border: 1px solid #999;">
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->combination ?? '-' }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $subjectResultsStr }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                {{ $totalMarks > 0 ? number_format($totalMarks, 2) : '-' }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                {{ $averageMarks > 0 ? number_format($averageMarks, 2) : '-' }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">
                                @if($averageMarks > 0)
                                    @php
                                        if ($averageMarks >= 79.5) $grd = 'A';
                                        elseif ($averageMarks >= 69.5) $grd = 'B';
                                        elseif ($averageMarks >= 59.5) $grd = 'C';
                                        elseif ($averageMarks >= 49.5) $grd = 'D';
                                        elseif ($averageMarks >= 39.5) $grd = 'E';
                                        elseif ($averageMarks >= 34.5) $grd = 'S';
                                        else $grd = 'F';
                                    @endphp
                                    {{ $grd }}
                                @else
                                    -
                                @endif
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                {{ $totalPoints > 0 ? $totalPoints : '-' }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">
                                {{ $division }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">
                                {{ number_format($gpa, 4) }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">
                                {{ $positionCounter++ }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="border: 1px solid #999; padding: 1rem; text-align: center; color: #999;">No results</td>
                        </tr>
                    @endforelse

                    @forelse($absCandidates as $data)
                        @php 
                            $candidate = $data['candidate'];
                        @endphp
                        <tr style="color: #000080; border: 1px solid #999;">
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $candidate->combination ?? '-' }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">ABS</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="border: 1px solid #999; padding: 1rem; text-align: center; color: #999;">No absent candidates</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- SECTION 3: EXAMINATION CENTRE OVERALL PERFORMANCE -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0;">
            <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="2">EXAMINATION CENTRE OVERALL PERFORMANCE</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="1">EXAMINATION CENTRE SCHOOL</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $school->name }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="1">TOTAL REGISTERED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $totalCandidates }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="1">TOTAL PASSED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $totalPassed }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="1">TOTAL FAILED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $totalFailed }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="1">EXAMINATION CENTRE GPA</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; background-color: {{ $overallGpa > 0 ? '#DEF043' : '#CCCCCC' }};">{{ number_format($overallGpa, 4) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- SECTION 4: EXAMINATION CENTRE DIVISION PERFORMANCE -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0;">
            <div class="overflow-x-auto">
            <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">REGIST</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">ABSENT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">SAT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">WITHHELD</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">INC</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">CLEAN</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV I</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV II</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV III</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV IV</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV 0</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalCandidates }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalAbsent }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalCandidates - $totalAbsent }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">0</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">0</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalCandidates - $totalAbsent }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center; font-weight: bold;">{{ $totalDivisions['0'] }}</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>

        <!-- SECTION 5: EXAMINATION CENTRE SUBJECTS PERFORMANCE -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0;">
            <div class="overflow-x-auto">
            <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">CODE</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 20%;">SUBJECT NAME</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">A</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">B</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">C</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">D</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">E</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">S</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">F</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">ABS</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">TOTAL</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 8%;">GPA</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 20%;">COMPETENCY LEVEL</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @php
                        // Build subject performance data
                        $subjectPerformance = [];
                        foreach($candidatesWithMetrics as $data) {
                            $candidate = $data['candidate'];
                            foreach($candidate->marks as $mark) {
                                $subjectId = $mark->subject_id;
                                $grade = $mark->grade_from_average ?? '-';
                                
                                if (!isset($subjectPerformance[$subjectId])) {
                                    $subjectPerformance[$subjectId] = [
                                        'code' => $mark->subject?->code ?? '-',
                                        'name' => $mark->subject?->name ?? 'Unknown',
                                        'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'S' => 0, 'F' => 0, 'ABS' => 0
                                    ];
                                }
                                
                                if ($grade && in_array($grade, ['A', 'B', 'C', 'D', 'E', 'S', 'F'])) {
                                    $subjectPerformance[$subjectId][$grade]++;
                                } elseif ($grade === '-') {
                                    $subjectPerformance[$subjectId]['ABS']++;
                                }
                            }
                        }
                    @endphp
                    
                    @forelse($subjectPerformance as $subjectId => $data)
                        @php
                            $total = $data['A'] + $data['B'] + $data['C'] + $data['D'] + $data['E'] + $data['S'] + $data['F'] + $data['ABS'];
                            $passed = $data['A'] + $data['B'] + $data['C'] + $data['D'] + $data['E'];
                            $subjectGpa = $passed > 0 ? round(($data['A']*5 + $data['B']*4 + $data['C']*3 + $data['D']*2 + $data['E']*1) / $passed, 4) : 0;
                            
                            // Determine competency color
                            $competencyColor = '#f0f0f0';
                            $competencyText = '-';
                            if ($subjectGpa > 0) {
                                if ($subjectGpa >= 4.51) { $competencyColor = '#90EE90'; $competencyText = 'Grade A (Excellent)'; }
                                elseif ($subjectGpa >= 3.51) { $competencyColor = '#FFD700'; $competencyText = 'Grade B (Very Good)'; }
                                elseif ($subjectGpa >= 2.51) { $competencyColor = '#87CEEB'; $competencyText = 'Grade C (Good)'; }
                                elseif ($subjectGpa >= 1.51) { $competencyColor = '#FFA500'; $competencyText = 'Grade D (Average)'; }
                                elseif ($subjectGpa >= 1) { $competencyColor = '#FF6347'; $competencyText = 'Grade E (Below Average)'; }
                                else { $competencyColor = '#FF0000'; $competencyText = 'Grade F (Fail)'; }
                            }
                        @endphp
                        <tr>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['code'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: left;">{{ $data['name'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['A'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['B'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['C'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['D'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['E'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['S'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['F'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $data['ABS'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ $total }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: center;">{{ number_format($subjectGpa, 4) }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; text-align: left; background-color: {{ $competencyColor }}; color: white; font-weight: bold;">{{ $competencyText }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" style="border: 1px solid #999; padding: 1rem; text-align: center; color: #999;">No subject data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- Print Button -->
        <div style="margin-top: 1.5rem; text-align: center;">
            <button onclick="window.print()" style="background-color: #003366; color: white; padding: 0.75rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                <i class="fas fa-print"></i> Print Results
            </button>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            padding-top: 0;
        }
        .container {
            max-width: 100%;
        }
        button {
            display: none;
        }
    }
</style>
@endsection
