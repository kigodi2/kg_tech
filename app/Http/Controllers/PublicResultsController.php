<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\SubjectMarks;
use App\Services\Results\NectaGradingService;
use Illuminate\Http\Request;

class PublicResultsController extends Controller
{
    protected $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Search public results by index number and/or school name
     */
    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'exam_year' => 'required|string',
                'exam_type' => 'required|string',
                'index_number' => 'nullable|string',
                'school_name' => 'nullable|string',
            ]);

            $examYear = (int)$validated['exam_year']; // Convert to integer
            $examType = strtoupper($validated['exam_type']);
            $indexNumber = trim($validated['index_number'] ?? '');
            $schoolName = trim($validated['school_name'] ?? '');

            // Get exam type
            $examTypeModel = \App\Models\ExamType::where('code', $examType)->first();
            if (!$examTypeModel) {
                return response()->json(['success' => false, 'message' => 'Invalid exam type', 'results' => []]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'results' => []]);
        }

        // Build query
        $query = Candidate::with(['school', 'marks' => function($q) use ($examTypeModel, $examYear) {
            $q->where('exam_type_id', $examTypeModel->id)
              ->where('year', $examYear)
              ->with('subject');
        }]);

        // Filter by index number if provided
        if (!empty($indexNumber)) {
            $query->where('candidate_id', 'LIKE', "%{$indexNumber}%");
        }

        // Filter by school name if provided
        if (!empty($schoolName)) {
            $query->whereHas('school', function($q) use ($schoolName) {
                $q->where('name', 'LIKE', "%{$schoolName}%")
                  ->orWhere('code', 'LIKE', "%{$schoolName}%");
            });
        }

        // Check if candidate has marks for this year and type
        $query->whereHas('marks', function($q) use ($examTypeModel, $examYear) {
            $q->where('exam_type_id', $examTypeModel->id)
              ->where('year', $examYear);
        });

        $candidates = $query->limit(50)->get();

        if ($candidates->isEmpty()) {
            return response()->json(['success' => true, 'results' => []]);
        }

        // Format results
        $results = $candidates->map(function($candidate) use ($examTypeModel, $examYear) {
            $totalMarks = 0;
            $totalPoints = 0;
            $validSubjectCount = 0;

            foreach ($candidate->marks as $mark) {
                $totalMarks += $mark->average ?? 0;
                
                // Calculate points (excluding general studies and basic applied math)
                $subjectName = $mark->subject?->name ?? '';
                if (!in_array(strtoupper($subjectName), ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'])) {
                    $grade = $mark->grade_from_average;
                    $points = $this->gradingService->getGradePoints($grade);
                    $totalPoints += $points;
                    $validSubjectCount++;
                }
            }

            // Calculate GPA
            $gpa = $validSubjectCount > 0 ? round($totalPoints / $validSubjectCount, 4) : 0;
            $gpaInfo = $this->gradingService->getGpaCompetence($gpa);

            // Calculate division
            $division = 'ABS';
            if ($candidate->marks->count() > 0) {
                if ($totalPoints > 0 && $totalPoints <= 9) {
                    $division = 'I';
                } elseif ($totalPoints >= 10 && $totalPoints <= 12) {
                    $division = 'II';
                } elseif ($totalPoints >= 13 && $totalPoints <= 17) {
                    $division = 'III';
                } elseif ($totalPoints >= 18 && $totalPoints <= 19) {
                    $division = 'IV';
                } else {
                    $division = '0';
                }
            }

            return [
                'candidate_id' => $candidate->id,
                'school_id' => $candidate->school_id,
                'index_number' => $candidate->candidate_id,
                'candidate_name' => $candidate->full_name,
                'school_name' => $candidate->school?->name ?? 'Unknown School',
                'school_code' => $candidate->school?->code ?? '',
                'total_marks' => number_format($totalMarks, 2),
                'division' => $division,
                'gpa' => number_format($gpa, 4),
                'gpa_color' => $gpaInfo['color'] ?? '#f0f0f0',
            ];
        })->toArray();

        return response()->json(['success' => true, 'results' => $results]);
    }

    /**
     * Get detailed results for a candidate
     */
    public function candidate($examYear, $examType, $candidateId)
    {
        $examTypeModel = \App\Models\ExamType::where('code', strtoupper($examType))->first();
        if (!$examTypeModel) {
            abort(404, 'Exam type not found');
        }

        $candidate = Candidate::with(['school', 'marks' => function($q) use ($examTypeModel, $examYear) {
            $q->where('exam_type_id', $examTypeModel->id)
              ->where('year', $examYear)
              ->with('subject');
        }])->findOrFail($candidateId);

        // Calculate metrics
        $totalMarks = 0;
        $totalPoints = 0;
        $validSubjectCount = 0;
        $subjectGrades = [];

        foreach ($candidate->marks as $mark) {
            $totalMarks += $mark->average ?? 0;
            $subjectName = $mark->subject?->name ?? '';
            $grade = $mark->grade_from_average;
            
            $subjectGrades[] = [
                'subject' => $subjectName,
                'marks' => $mark->average,
                'grade' => $grade,
            ];

            if (!in_array(strtoupper($subjectName), ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'])) {
                $points = $this->gradingService->getGradePoints($grade);
                $totalPoints += $points;
                $validSubjectCount++;
            }
        }

        $gpa = $validSubjectCount > 0 ? round($totalPoints / $validSubjectCount, 4) : 0;
        $gpaInfo = $this->gradingService->getGpaCompetence($gpa);

        return view('public.results.candidate', compact(
            'candidate',
            'examYear',
            'examType',
            'totalMarks',
            'gpa',
            'gpaInfo',
            'subjectGrades'
        ));
    }

    /**
     * Get all results for a school
     */
    public function school($examYear, $examType, $schoolId)
    {
        $examYear = (int)$examYear;
        $examTypeModel = \App\Models\ExamType::where('code', strtoupper($examType))->first();
        if (!$examTypeModel) {
            abort(404, 'Exam type not found');
        }

        $school = \App\Models\School::findOrFail($schoolId);
        
        // Get all candidates in the school with their marks
        $candidates = Candidate::where('school_id', $schoolId)
            ->with(['marks' => function($q) use ($examTypeModel, $examYear) {
                $q->where('exam_type_id', $examTypeModel->id)
                  ->where('year', $examYear)
                  ->with('subject');
            }])
            ->get();

        // Process candidates and calculate metrics
        $candidatesWithMetrics = [];
        foreach ($candidates as $candidate) {
            $totalMarks = 0;
            $totalPoints = 0;
            $validSubjectCount = 0;
            $marksCount = 0;

            foreach ($candidate->marks as $mark) {
                $marksCount++;
                $totalMarks += $mark->average ?? 0;
                
                $subjectName = $mark->subject?->name ?? '';
                if (!in_array(strtoupper($subjectName), ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'])) {
                    $grade = $mark->grade_from_average;
                    $points = $this->gradingService->getGradePoints($grade);
                    $totalPoints += $points;
                    $validSubjectCount++;
                }
            }

            $gpa = $validSubjectCount > 0 ? round($totalPoints / $validSubjectCount, 4) : 0;
            $gpaInfo = $this->gradingService->getGpaCompetence($gpa);

            // Calculate division
            $division = '0';
            if ($marksCount > 0) {
                if ($totalPoints > 0 && $totalPoints <= 9) {
                    $division = 'I';
                } elseif ($totalPoints >= 10 && $totalPoints <= 12) {
                    $division = 'II';
                } elseif ($totalPoints >= 13 && $totalPoints <= 17) {
                    $division = 'III';
                } elseif ($totalPoints >= 18 && $totalPoints <= 19) {
                    $division = 'IV';
                }
            }

            $candidatesWithMetrics[] = [
                'candidate' => $candidate,
                'totalMarks' => $totalMarks,
                'average' => $marksCount > 0 ? $totalMarks / $marksCount : 0,
                'gpa' => $gpa,
                'gpaInfo' => $gpaInfo,
                'division' => $division,
                'totalPoints' => $totalPoints,
            ];
        }

        // Sort by division (passed candidates first), then GPA (ascending - lower is better)
        usort($candidatesWithMetrics, function($a, $b) {
            // Passed candidates (divisions I-IV) come before fail (0)
            $aIsPassed = $a['division'] !== '0';
            $bIsPassed = $b['division'] !== '0';
            
            if ($aIsPassed !== $bIsPassed) {
                return $aIsPassed ? -1 : 1;
            }
            
            // Both passed or both failed - sort by GPA (ascending)
            return $a['gpa'] <=> $b['gpa'];
        });

        // Calculate totals for summary sections (these come from the @php block in the view)
        // Note: The view already calculates these, so we don't need to pass them from here
        // But we'll add totalAbsent for the division performance section
        $totalAbsent = array_sum(array_map(function($data) {
            return $data['totalPoints'] === 0 ? 1 : 0;
        }, $candidatesWithMetrics));

        return view('public.results.school', compact(
            'school',
            'examYear',
            'examType',
            'examTypeModel',
            'candidatesWithMetrics',
            'totalAbsent'
        ));
    }
}
