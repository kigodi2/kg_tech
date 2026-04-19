@extends('layout')

@section('content')
<div style="background-color: #B0E0E6; min-height: 100vh; padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'Maiandra GD', sans-serif; font-weight: 700; white-space: nowrap;">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb Navigation -->
        <div style="margin-bottom: 1rem;">
            <a href="/results/{{ $examYear }}/{{ strtolower($examType) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 1.05rem;">
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
            $displayCombination = function (?string $combination): string {
                $value = trim((string) $combination);
                if ($value === '') {
                    return '-';
                }
                return strtoupper($value) === 'PMCS' ? 'PMCs' : $value;
            };

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
            
            // Count by gender, status, and division
            foreach($candidatesWithMetrics as $data) {
                $candidate = $data['candidate'];
                $gender = strtoupper((string) ($candidate->gender ?? ''));
                $division = $data['division'];
                $candidateStatus = $data['candidateStatus'] ?? 'COMPLETE';
                if (!isset($genderCounts[$gender])) {
                    continue;
                }

                if ($candidateStatus === 'ABS') {
                    $absIncStatsBySex[$gender]['ABS']++;
                } elseif ($candidateStatus === 'INC') {
                    $absIncStatsBySex[$gender]['INC']++;
                } else {
                    if ($division === 'I') {
                        $totalDivisions['I']++;
                        $divisionStatsBySex[$gender]['I']++;
                    } elseif ($division === 'II') {
                        $totalDivisions['II']++;
                        $divisionStatsBySex[$gender]['II']++;
                    } elseif ($division === 'III') {
                        $totalDivisions['III']++;
                        $divisionStatsBySex[$gender]['III']++;
                    } elseif ($division === 'IV') {
                        $totalDivisions['IV']++;
                        $divisionStatsBySex[$gender]['IV']++;
                    } else {
                        $totalDivisions['0']++;
                        $divisionStatsBySex[$gender]['0']++;
                    }
                }

                $genderCounts[$gender]++;
            }
            
            // Calculate averages
            $totalCandidates = count($candidatesWithMetrics);
            $totalPassed = $totalDivisions['I'] + $totalDivisions['II'] + $totalDivisions['III'] + $totalDivisions['IV'];
            $totalFailed = $totalDivisions['0'];
            $totalInc = $absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC'];
            $totalAbsent = $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'];
            $overallGpaPoints = 0;
            $overallGpaSubjects = 0;
            
            foreach($candidatesWithMetrics as $data) {
                if (($data['candidateStatus'] ?? 'COMPLETE') === 'COMPLETE') {
                    $overallGpaPoints += (float) ($data['gpaPointsSum'] ?? 0);
                    $overallGpaSubjects += (int) ($data['gpaSubjectCount'] ?? 0);
                }
            }
            $overallGpa = $overallGpaSubjects > 0 ? $overallGpaPoints / $overallGpaSubjects : 0;
            $overallGpaInfo = app(\App\Services\Results\NectaGradingService::class)->getGpaCompetence((float) $overallGpa);
        @endphp

        <!-- SECTION 1: DIVISION PERFORMANCE SUMMARY -->
        <div style="background-color: #B0E0E6; padding: 1rem; margin-bottom: 0;">
            <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                <thead>
                    <tr style="background-color: #003366;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="8">DIVISION PERFORMANCE SUMMARY</th>
                    </tr>
                    <tr style="background-color: LIGHTYELLOW;">
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">SEX</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">I</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">II</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">III</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">IV</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">0</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">INC</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #000080;">ABS</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @if($genderCounts['F'] > 0)
                    <tr style="color: #000080;">
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">F</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['F']['0'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['INC'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['ABS'] }}</td>
                    </tr>
                    @endif
                    @if($genderCounts['M'] > 0)
                    <tr style="color: #000080;">
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">M</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $divisionStatsBySex['M']['0'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['M']['INC'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['M']['ABS'] }}</td>
                    </tr>
                    @endif
                    <tr style="background-color: LIGHTYELLOW; color: #000080;">
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">T</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['0'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['INC'] + $absIncStatsBySex['M']['INC'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $absIncStatsBySex['F']['ABS'] + $absIncStatsBySex['M']['ABS'] }}</td>
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
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 7%;">CNO</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">SEX</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">COMB</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 48%;">DETAILED SUBJECTS RESULT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">TOTAL</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">AVG</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">GRD</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">AGGT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">DIV</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 4%;">GPA</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 3%;">POS</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @php
                        $completeCandidates = array_filter($candidatesWithMetrics, fn($d) => ($d['candidateStatus'] ?? 'COMPLETE') === 'COMPLETE');
                        $incCandidates = array_filter($candidatesWithMetrics, fn($d) => ($d['candidateStatus'] ?? '') === 'INC');
                        $absCandidates = array_filter($candidatesWithMetrics, fn($d) => ($d['candidateStatus'] ?? '') === 'ABS');
                        
                        $positionCounter = 1;
                    @endphp
                    
                    @forelse($completeCandidates as $data)
                        @php 
                            $candidate = $data['candidate'];
                            $totalMarks = $data['totalMarks'];
                            $averageMarks = $data['average'];
                            $gpa = $data['gpa'];
                            $gpaDisplay = abs($gpa - round($gpa)) < 0.00005
                                ? number_format($gpa, 0)
                                : number_format($gpa, 4);
                            $gpaInfo = $data['gpaInfo'];
                            $division = $data['division'];
                            $totalPoints = $data['totalPoints'];
                            
                            $subjectResultsStr = $data['subjectResultsStr'] ?: '-';
                        @endphp
                        <tr style="color: #000080; border: 1px solid #999;">
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $displayCombination($candidate->combination) }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $subjectResultsStr }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">
                                {{ number_format($totalMarks, 0) }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">
                                {{ number_format($averageMarks, 2) }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">
                                @php
                                    if ($averageMarks >= 80) $grd = 'A';
                                    elseif ($averageMarks >= 70) $grd = 'B';
                                    elseif ($averageMarks >= 60) $grd = 'C';
                                    elseif ($averageMarks >= 50) $grd = 'D';
                                    elseif ($averageMarks >= 45) $grd = 'E';
                                    elseif ($averageMarks >= 35) $grd = 'S';
                                    else $grd = 'F';
                                @endphp
                                {{ $grd }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">
                                {{ $totalPoints }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">
                                {{ $division }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">
                                {{ $gpaDisplay }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">
                                {{ $positionCounter++ }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" style="border: 1px solid #999; padding: 1rem; text-align: center; color: #999;">No results</td>
                        </tr>
                    @endforelse

                    @foreach($incCandidates as $data)
                        @php
                            $candidate = $data['candidate'];
                            $subjectResultsStr = $data['subjectResultsStr'] ?: 'INC';
                            $totalMarks = (float) ($data['totalMarks'] ?? 0);
                            $averageMarks = (float) ($data['average'] ?? 0);
                        @endphp
                        <tr style="color: #000080; border: 1px solid #999;">
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $displayCombination($candidate->combination) }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $subjectResultsStr }}
                            </td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $totalMarks > 0 ? number_format($totalMarks, 0) : 'INC' }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $averageMarks > 0 ? number_format($averageMarks, 2) : 'INC' }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">INC</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">-</td>
                        </tr>
                    @endforeach

                    @forelse($absCandidates as $data)
                        @php 
                            $candidate = $data['candidate'];
                        @endphp
                        <tr style="color: #000080; border: 1px solid #999;">
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $candidate->candidate_id }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $candidate->gender }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $displayCombination($candidate->combination) }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">ABS</td>
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
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="3">EXAMINATION CENTRE OVERALL PERFORMANCE</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold;" colspan="1">EXAMINATION CENTRE SCHOOL</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left;" colspan="2">{{ $school->name }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold;" colspan="1">TOTAL REGISTERED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left;" colspan="2">{{ $totalCandidates }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold;" colspan="1">TOTAL PASSED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left;" colspan="2">{{ $totalPassed }}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold;" colspan="1">TOTAL FAILED CANDIDATES</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left;" colspan="2">{{ $totalFailed }}</td>
                    </tr>
                    @php
                        $overallGpaBg = ($overallGpa > 0 && !empty($overallGpaInfo['color']))
                            ? (string) $overallGpaInfo['color']
                            : '#CCCCCC';
                        $overallGpaTextColor = in_array(strtoupper($overallGpaBg), ['#DEF043', '#1FEE0B'], true) ? '#000000' : '#FFFFFF';
                    @endphp
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold;" colspan="1">EXAMINATION CENTRE GPA</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; background-color: {{ $overallGpaBg }}; color: {{ $overallGpaTextColor }};">
                            {{ abs($overallGpa - round($overallGpa)) < 0.00005 ? number_format($overallGpa, 0) : number_format($overallGpa, 4) }}
                        </td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; background-color: {{ $overallGpaBg }}; color: {{ $overallGpaTextColor }};">
                            @if($overallGpa > 0)Grade {{ $overallGpaInfo['grade'] ?? '-' }} - {{ $overallGpaInfo['competence'] ?? '-' }}@else-@endif
                        </td>
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
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">REGIST</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">ABSENT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">SAT</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">WITHHELD</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">INC</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">CLEAN</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV I</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV II</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV III</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV IV</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">DIV 0</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    <tr>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalCandidates }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalAbsent }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalCandidates - $totalAbsent }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">0</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalInc }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ ($totalCandidates - $totalAbsent) - $totalInc }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['I'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['II'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['III'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['IV'] }}</td>
                        <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center; font-weight: bold;">{{ $totalDivisions['0'] }}</td>
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
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">CODE</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 20%;">SUBJECT NAME</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">A</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">B</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">C</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">D</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">E</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">S</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">F</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">ABS</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 5%;">TOTAL</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: 8%;">GPA</th>
                        <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF; width: 20%;">COMPETENCY LEVEL</th>
                    </tr>
                </thead>
                <tbody style="background-color: LIGHTYELLOW;">
                    @php
                        // Build subject performance data
                        $subjectPerformance = [];
                        foreach($candidatesWithMetrics as $data) {
                            $candidate = $data['candidate'];
                            $latestMarks = collect($data['latestMarks'] ?? $candidate->marks->sortByDesc('id')->unique('subject_id'));
                            foreach($latestMarks as $mark) {
                                $subjectId = $mark->subject_id;
                                $status = strtoupper((string) ($mark->subject_status ?? ''));
                                $grade = strtoupper((string) ($mark->grade ?? $mark->grade_from_average ?? ''));
                                
                                if (!isset($subjectPerformance[$subjectId])) {
                                    $subjectPerformance[$subjectId] = [
                                        'code' => $mark->subject?->code ?? '-',
                                        'name' => $mark->subject?->name ?? 'Unknown',
                                        'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'S' => 0, 'F' => 0, 'ABS' => 0
                                    ];
                                }
                                
                                if (in_array($status, ['ABS', 'X'], true) || $mark->marks_obtained === null) {
                                    $subjectPerformance[$subjectId]['ABS']++;
                                } elseif ($grade && in_array($grade, ['A', 'B', 'C', 'D', 'E', 'S', 'F'], true)) {
                                    $subjectPerformance[$subjectId][$grade]++;
                                }
                            }
                        }
                    @endphp
                    
                    @forelse($subjectPerformance as $subjectId => $data)
                        @php
                            $gradeService = app(\App\Services\Results\NectaGradingService::class);
                            $total = $data['A'] + $data['B'] + $data['C'] + $data['D'] + $data['E'] + $data['S'] + $data['F'] + $data['ABS'];
                            $graded = $data['A'] + $data['B'] + $data['C'] + $data['D'] + $data['E'] + $data['S'] + $data['F'];
                            // NECTA subject GPA (grade-point average): A=1 ... F=7, ABS excluded.
                            $subjectGpa = $graded > 0 ? round(($data['A']*1 + $data['B']*2 + $data['C']*3 + $data['D']*4 + $data['E']*5 + $data['S']*6 + $data['F']*7) / $graded, 4) : 0;
                            
                            // Determine competency color
                            $competencyColor = '#f0f0f0';
                            $competencyText = '-';
                            if ($subjectGpa > 0) {
                                $gpaInfo = $gradeService->getGpaCompetence($subjectGpa);
                                $competencyColor = $gpaInfo['color'] ?? '#f0f0f0';
                                $competencyText = "Grade {$gpaInfo['grade']} ({$gpaInfo['competence']})";
                            }
                        @endphp
                        <tr>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['code'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: left;">{{ $data['name'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['A'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['B'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['C'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['D'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['E'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['S'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['F'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $data['ABS'] }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ $total }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: center;">{{ abs($subjectGpa - round($subjectGpa)) < 0.00005 ? number_format($subjectGpa, 0) : number_format($subjectGpa, 4) }}</td>
                            <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; text-align: left; background-color: {{ $competencyColor }}; color: #000080; font-weight: bold;">{{ $competencyText }}</td>
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
            <button onclick="window.print()" style="background-color: #003366; color: white; padding: 0.90rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
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
