<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Candidate;
use App\Models\SubjectMarks;
use App\Services\Results\NectaGradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * PublicAcseeResultsController
 * 
 * Provides public-facing ACSEE results portal matching NECTA's structure.
 * 
 * Data Flow:
 * 1. index() - Lists centres (schools) with optional alphabet filtering
 *    - Filters schools that have ACSEE candidates for selected year
 *    - Caches centre list per year for performance
 * 
 * 2. show() - Displays centre details with division summary + candidate results
 *    - Calculates division performance by sex using FinalGrade + SubjectMarks
 *    - Displays candidate rows with: CNO | SEX | AGGT | DIV | DETAILED SUBJECTS
 *    - Uses existing NectaGradingService for calculations
 * 
 * Database Tables Used:
 * - schools (code, name)
 * - candidates (school_id, candidate_id, full_name, gender)
 * - subject_marks (candidate_id, subject_id, marks_obtained, grade)
 * - final_grades (candidate_id, division, gpa) [preferred if available]
 * - exam_years (year_label, is_active)
 * - exam_types (code='ACSEE')
 */
class PublicAcseeResultsController extends Controller
{
    protected $gradingService;

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    /**
     * Display list of centres with alphabet filtering.
     * 
     * Query params:
     * - year: exam year (defaults to active year)
     * - letter: A-Z or ALL (defaults to ALL)
     */
    public function index(Request $request)
    {
        $examYear = $request->query('year');
        $letter = $request->query('letter', 'ALL');

        // Get ACSEE exam type
        $examTypeAcsee = ExamType::where('code', 'ACSEE')->first();
        if (!$examTypeAcsee) {
            abort(404, 'ACSEE exam type not found');
        }

        // Determine exam year
        if (!$examYear) {
            $activeYear = ExamYear::where('is_active', true)->first();
            $examYear = $activeYear ? $activeYear->year_label : date('Y');
        }

        // Extract numeric year from year_label (e.g., "2025" from "2025")
        $yearNumeric = (int) preg_replace('/[^0-9]/', '', $examYear);

        // Get all schools with ACSEE candidates for this year
        $cacheKey = "acsee_centres_{$yearNumeric}";
        $allCentres = Cache::remember($cacheKey, 3600, function() use ($examTypeAcsee, $yearNumeric) {
            return School::whereHas('candidates', function($q) use ($examTypeAcsee, $yearNumeric) {
                $q->whereHas('examRegistrations', function($q2) use ($examTypeAcsee, $yearNumeric) {
                    $q2->where('exam_type_id', $examTypeAcsee->id)
                       ->where('year', $yearNumeric);
                });
            })
            ->orderBy('name')
            ->get();
        });

        // Apply alphabet filtering
        $centres = $allCentres;
        if ($letter !== 'ALL' && strlen($letter) === 1) {
            $centres = $allCentres->filter(function($school) use ($letter) {
                return strtoupper(substr($school->name, 0, 1)) === strtoupper($letter);
            })->values();
        }

        return view('results.acsee.public.index', compact(
            'centres',
            'examYear',
            'yearNumeric',
            'letter'
        ));
    }

    /**
     * Display centre detail with division summary and candidate results.
     * 
     * Route params:
     * - centreCode: school code (e.g., P0101, S0150)
     * 
     * Query params:
     * - year: exam year (defaults to active year)
     */
    public function show($centreCode, Request $request)
    {
        $examYear = $request->query('year');

        // Get ACSEE exam type
        $examTypeAcsee = ExamType::where('code', 'ACSEE')->first();
        if (!$examTypeAcsee) {
            abort(404, 'ACSEE exam type not found');
        }

        // Find school by code
        $school = School::where('code', $centreCode)->first();
        if (!$school) {
            abort(404, "Centre {$centreCode} not found");
        }

        // Determine exam year
        if (!$examYear) {
            $activeYear = ExamYear::where('is_active', true)->first();
            $examYear = $activeYear ? $activeYear->year_label : date('Y');
        }

        $yearNumeric = (int) preg_replace('/[^0-9]/', '', $examYear);

        // Get all candidates for this school with ACSEE registration
        $candidates = Candidate::where('school_id', $school->id)
            ->whereHas('examRegistrations', function($q) use ($examTypeAcsee, $yearNumeric) {
                $q->where('exam_type_id', $examTypeAcsee->id)
                   ->where('year', $yearNumeric);
            })
            ->with(['marks' => function($q) use ($examTypeAcsee, $yearNumeric) {
                $q->where('exam_type_id', $examTypeAcsee->id)
                   ->where('year', $yearNumeric)
                   ->with('subject');
            }])
            ->get();

        if ($candidates->isEmpty()) {
            abort(404, "No results found for centre {$centreCode}");
        }

        // Process candidates and calculate metrics
        $candidatesData = [];
        $divisionStats = [
            'F' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
            'M' => ['I' => 0, 'II' => 0, 'III' => 0, 'IV' => 0, '0' => 0],
        ];

        foreach ($candidates as $candidate) {
            $marks = $candidate->marks;

            if ($marks->isEmpty()) {
                continue;
            }

            // Calculate totals and grades
            $totalMarks = 0;
            $totalPoints = 0;
            $validSubjectCount = 0;
            $subjectGrades = [];

            foreach ($marks as $mark) {
                $subjectName = $mark->subject?->name ?? '';
                $grade = $mark->grade ?? $this->gradingService->calculateGrade($mark->marks_obtained);
                
                $totalMarks += $mark->marks_obtained;
                $subjectGrades[] = [
                    'name' => $subjectName,
                    'grade' => $grade,
                ];

                // Calculate points (excluding general studies and basic applied math)
                if (!$this->gradingService->isExcludedSubject($subjectName)) {
                    $points = $this->gradingService->getGradePoints($grade);
                    $totalPoints += $points;
                    $validSubjectCount++;
                }
            }

            // Calculate GPA
            $gpa = $validSubjectCount > 0 ? round($totalPoints / $validSubjectCount, 2) : 0;

            // Calculate division
            $division = '0';
            if ($totalPoints > 0 && $totalPoints <= 9) {
                $division = 'I';
            } elseif ($totalPoints >= 10 && $totalPoints <= 12) {
                $division = 'II';
            } elseif ($totalPoints >= 13 && $totalPoints <= 17) {
                $division = 'III';
            } elseif ($totalPoints >= 18 && $totalPoints <= 19) {
                $division = 'IV';
            }

            // Track division stats by sex
            $divisionStats[$candidate->gender][$division]++;

            $candidatesData[] = [
                'candidate_number' => $candidate->candidate_id,
                'sex' => $candidate->gender,
                'aggregate' => $gpa,
                'division' => $division,
                'subject_grades' => $subjectGrades,
            ];
        }

        // Sort candidates by division (I-IV first, then 0)
        usort($candidatesData, function($a, $b) {
            $divisionOrder = ['I' => 0, 'II' => 1, 'III' => 2, 'IV' => 3, '0' => 4];
            $aOrder = $divisionOrder[$a['division']] ?? 5;
            $bOrder = $divisionOrder[$b['division']] ?? 5;
            if ($aOrder !== $bOrder) {
                return $aOrder <=> $bOrder;
            }
            // Sort by aggregate (lower is better for same division)
            return $a['aggregate'] <=> $b['aggregate'];
        });

        // Paginate candidates
        $perPage = 20;
        $page = request()->input('page', 1);
        $paginatedCandidates = new LengthAwarePaginator(
            collect($candidatesData)->forPage($page, $perPage),
            count($candidatesData),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('results.acsee.public.show', compact(
            'school',
            'examYear',
            'yearNumeric',
            'candidatesData',
            'paginatedCandidates',
            'divisionStats'
        ));
    }
}
