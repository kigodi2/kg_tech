<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\District;
use App\Models\School;
use Illuminate\Http\Request;

class HierarchyController extends Controller
{
    /**
     * Show all regions in 4-column grid
     */
    public function regions()
    {
        $regions = Region::withCount('districts')->get();
        return view('hierarchy.regions', compact('regions'));
    }

    /**
     * Show all districts for a region in 4-column grid
     */
    public function districts($regionId)
    {
        $region = Region::findOrFail($regionId);
        $districts = District::where('region_id', $regionId)->withCount('schools')->get();
        return view('hierarchy.districts', compact('region', 'districts'));
    }

    /**
     * Show all schools for a district in 4-column grid
     */
    public function schools($districtId)
    {
        $district = District::findOrFail($districtId);
        $schools = School::where('district_id', $districtId)->get();
        return view('hierarchy.schools', compact('district', 'schools'));
    }

    /**
     * Show detailed candidate results for a specific school
     */
    public function schoolResults($schoolId)
    {
        $school = School::findOrFail($schoolId);
        $district = $school->district;
        $acseeType = \App\Models\ExamType::where('code', 'ACSEE')->first();
        
        // Get all candidates from this school with their exam registrations
         // Sort by: divisions (I, II, III, IV) by GPA DESC, then ABS/INC at bottom
         $candidates = \App\Models\Candidate::where('school_id', $schoolId)
             ->with(['examRegistrations' => function ($query) use ($acseeType) {
                 if ($acseeType) {
                     $query->where('exam_type_id', $acseeType->id);
                 }
             }])
             ->get()
             ->sort(function ($a, $b) use ($acseeType) {
                 $regA = $a->examRegistrations->first();
                 $regB = $b->examRegistrations->first();
                 
                 // Get marks status for each candidate
                 $acseeId = $acseeType?->id;
                 $subjectCountA = \App\Models\CandidateSubjectSelection::where('candidate_id', $a->id)
                     ->where('exam_type_id', $acseeId)
                     ->count();
                 $marksCountA = \App\Models\SubjectMarks::where('candidate_id', $a->id)
                     ->where('exam_type_id', $acseeId)
                     ->whereNotNull('marks_obtained')
                     ->count();
                 
                 $subjectCountB = \App\Models\CandidateSubjectSelection::where('candidate_id', $b->id)
                     ->where('exam_type_id', $acseeId)
                     ->count();
                 $marksCountB = \App\Models\SubjectMarks::where('candidate_id', $b->id)
                     ->where('exam_type_id', $acseeId)
                     ->whereNotNull('marks_obtained')
                     ->count();
                 
                 // Determine status: COMPLETE (2), INC (1), or ABS (0)
                 $statusA = ($marksCountA === 0) ? 0 : (($marksCountA < $subjectCountA) ? 1 : 2);
                 $statusB = ($marksCountB === 0) ? 0 : (($marksCountB < $subjectCountB) ? 1 : 2);
                 
                 // COMPLETE candidates (2) come first, INC (1) in middle, ABS (0) at bottom
                 if ($statusA !== $statusB) {
                     return $statusB <=> $statusA; // Reverse comparison: higher status first
                 }
                 
                 // If both have same status, sort by division and GPA
                 $divisionA = $regA?->division ?? 999;
                 $divisionB = $regB?->division ?? 999;
                 
                 // Convert null/0 to 5 (for 0 division at end)
                 $divisionA = ($divisionA === 0 || $divisionA === null) ? 5 : $divisionA;
                 $divisionB = ($divisionB === 0 || $divisionB === null) ? 5 : $divisionB;
                 
                 if ($divisionA !== $divisionB) {
                     return $divisionA <=> $divisionB;
                 }
                 
                 // Same division: sort by GPA descending
                 $gpaA = floatval($regA?->gpa ?? 0);
                 $gpaB = floatval($regB?->gpa ?? 0);
                 return $gpaB <=> $gpaA; // Higher GPA first
             })
             ->values();

        // Calculate division statistics by sex
         $divisionStatsBySex = [
             'F' => [
                 'I' => $candidates->where('gender', 'F')->filter(fn($c) => $c->examRegistrations->first()?->division == 1)->count(),
                 'II' => $candidates->where('gender', 'F')->filter(fn($c) => $c->examRegistrations->first()?->division == 2)->count(),
                 'III' => $candidates->where('gender', 'F')->filter(fn($c) => $c->examRegistrations->first()?->division == 3)->count(),
                 'IV' => $candidates->where('gender', 'F')->filter(fn($c) => $c->examRegistrations->first()?->division == 4)->count(),
                 '0' => $candidates->where('gender', 'F')->filter(fn($c) => $c->examRegistrations->first()?->division == 0 || is_null($c->examRegistrations->first()?->division))->count(),
             ],
             'M' => [
                 'I' => $candidates->where('gender', 'M')->filter(fn($c) => $c->examRegistrations->first()?->division == 1)->count(),
                 'II' => $candidates->where('gender', 'M')->filter(fn($c) => $c->examRegistrations->first()?->division == 2)->count(),
                 'III' => $candidates->where('gender', 'M')->filter(fn($c) => $c->examRegistrations->first()?->division == 3)->count(),
                 'IV' => $candidates->where('gender', 'M')->filter(fn($c) => $c->examRegistrations->first()?->division == 4)->count(),
                 '0' => $candidates->where('gender', 'M')->filter(fn($c) => $c->examRegistrations->first()?->division == 0 || is_null($c->examRegistrations->first()?->division))->count(),
             ],
         ];
         
         // Calculate ABS (Absent) and INC (Incomplete) statistics by sex
         $absIncStatsBySex = [
             'F' => [
                 'ABS' => 0,
                 'INC' => 0,
             ],
             'M' => [
                 'ABS' => 0,
                 'INC' => 0,
             ],
         ];
         
         // Count ABS and INC candidates
         foreach ($candidates as $candidate) {
             $subjectSelections = \App\Models\CandidateSubjectSelection::where('candidate_id', $candidate->id)
                 ->where('exam_type_id', $acseeType->id)
                 ->count();
             
             $marksCount = \App\Models\SubjectMarks::where('candidate_id', $candidate->id)
                 ->where('exam_type_id', $acseeType->id)
                 ->whereNotNull('marks_obtained')
                 ->count();
             
             $gender = $candidate->gender;
             
             if ($marksCount === 0) {
                 // ABS: No marks in any subject
                 $absIncStatsBySex[$gender]['ABS']++;
             } elseif ($marksCount < $subjectSelections) {
                 // INC: Marks in some but not all subjects
                 $absIncStatsBySex[$gender]['INC']++;
             }
         }

        // Get unique subjects registered by candidates in this school
        $registeredSubjectIds = \App\Models\CandidateSubjectSelection::whereHas('candidate', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })
            ->when($acseeType, function ($q) use ($acseeType) {
                $q->where('exam_type_id', $acseeType->id);
            })
            ->distinct()
            ->pluck('subject_id')
            ->toArray();

        // Get only those subjects with their performance stats
        $subjects = \App\Models\Subject::whereIn('id', $registeredSubjectIds)
            ->with(['marks' => function ($query) use ($schoolId, $acseeType) {
                $query->whereHas('candidate', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                });
                if ($acseeType) {
                    $query->where('exam_type_id', $acseeType->id);
                }
            }])
            ->get();

        $subjectsPerformance = $subjects->map(function ($subject) {
            $marks = $subject->marks;
            
            // Count grades using grade_from_average (calculated from averaged marks)
            $gradeA = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'A')->count();
            $gradeB = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'B')->count();
            $gradeC = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'C')->count();
            $gradeD = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'D')->count();
            $gradeE = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'E')->count();
            $gradeS = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'S')->count();
            $gradeF = $marks->filter(fn($m) => $m->marks_obtained !== null && $m->grade_from_average === 'F')->count();
            $absent = $marks->filter(fn($m) => $m->marks_obtained === null)->count();
            $total = $marks->count();
            
            // Calculate GPA from grade points (NECTA 7-point scale: A=1, B=2, C=3, D=4, E=5, S=6, F=7)
            // Note: Subject performance table shows GPA for ALL subjects
            // Exclusion from school-level GPA happens in school overall GPA calculation
            $gradePointsSum = 0;
            $validMarkCount = 0;
            
            foreach ($marks as $mark) {
                if ($mark->marks_obtained !== null) {
                    $grade = $mark->grade_from_average;
                    $gradePoints = $this->gradeToPoints($grade);
                    $gradePointsSum += $gradePoints;
                    $validMarkCount++;
                }
            }
            
            $avgGpa = $validMarkCount > 0 ? ($gradePointsSum / $validMarkCount) : 0;
            
            return [
                'code' => $subject->code,
                'name' => $subject->name,
                'gradeA' => $gradeA,
                'gradeB' => $gradeB,
                'gradeC' => $gradeC,
                'gradeD' => $gradeD,
                'gradeE' => $gradeE,
                'gradeS' => $gradeS,
                'gradeF' => $gradeF,
                'absent' => $absent,
                'total' => $total,
                'gpa' => number_format($avgGpa, 4),
                'competency' => $this->getCompetencyLevel($avgGpa),
            ];
        })->sortBy('code');

        // Calculate overall school GPA and statistics
        $passedCandidates = $candidates->filter(fn($c) => in_array($c->examRegistrations->first()?->division, [1, 2, 3, 4]));
        $overallGpa = $passedCandidates->count() > 0 
            ? ($passedCandidates->sum(fn($c) => floatval($c->examRegistrations->first()?->gpa ?? 0)) / $passedCandidates->count())
            : 0;

        return view('hierarchy.school-results', compact(
             'school', 
             'district', 
             'candidates', 
             'divisionStatsBySex', 
             'absIncStatsBySex',
             'subjectsPerformance',
             'passedCandidates',
             'overallGpa'
         ));
    }

    /**
     * Convert grade to GPA
     */
    /**
     * Convert grade to NECTA grade points (7-point scale)
     */
    private function gradeToPoints($grade)
    {
        $gradeMap = [
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'S' => 6,
            'F' => 7,
        ];
        return $gradeMap[$grade] ?? 7;
    }

    /**
     * Check if subject is excluded from GPA calculation
     */
    private function isExcludedSubject($subjectName)
    {
        $excluded = ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'];
        return in_array(strtoupper($subjectName), $excluded);
    }

    /**
     * Get competency level based on average grade points (NECTA 7-point scale)
     * Lower points = better grade (A=1 is excellent, F=7 is fail)
     */
    private function getCompetencyLevel($avgPoints)
    {
        // Round to nearest integer for comparison
        $gpa = round($avgPoints);
        
        if ($gpa <= 1) return 'Grade A (Excellent)';
        if ($gpa <= 2) return 'Grade B (Very Good)';
        if ($gpa <= 3) return 'Grade C (Good)';
        if ($gpa <= 4) return 'Grade D (Average)';
        if ($gpa <= 5) return 'Grade E (Satisfactory)';
        if ($gpa <= 6) return 'Grade S (Unsatisfactory)';
        return 'Grade F (Fail)';
    }
}
