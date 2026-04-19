<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\SubjectMarks;
use App\Services\Results\NectaGradingService;
use App\Services\Results\PublicAcseeCandidateMetricsService;
use Illuminate\Http\Request;

class PublicResultsController extends Controller
{
    protected $gradingService;
    protected $candidateMetricsService;

    public function __construct(
        NectaGradingService $gradingService,
        PublicAcseeCandidateMetricsService $candidateMetricsService
    )
    {
        $this->gradingService = $gradingService;
        $this->candidateMetricsService = $candidateMetricsService;
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

        $candidateIds = $candidates->pluck('id')->values();
        $examYearModel = \App\Models\ExamYear::query()
            ->where('year_label', $examYear)
            ->first();
        $activeSnapshot = \App\Models\ResultSnapshot::query()
            ->where('exam_year_id', $examYearModel?->id)
            ->where('is_active', true)
            ->first();
        $latestProcessId = \App\Models\ResultProcess::query()
            ->where('exam_type_id', $examTypeModel->id)
            ->where('exam_year_id', $examYearModel?->id)
            ->where('status', 'completed')
            ->latest('id')
            ->value('id');

        $useSnapshotForStoredResults = false;
        if ($activeSnapshot && $candidateIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasColumn('final_grades', 'snapshot_id')) {
            $useSnapshotForStoredResults = \Illuminate\Support\Facades\DB::table('final_grades')
                ->where('exam_type_id', $examTypeModel->id)
                ->where('year', $examYear)
                ->where('snapshot_id', $activeSnapshot->id)
                ->whereIn('candidate_id', $candidateIds)
                ->exists();
        }

        $storedFinalRows = collect();
        if ($candidateIds->isNotEmpty()) {
            $storedFinalBase = \Illuminate\Support\Facades\DB::table('final_grades as fg')
                ->where('fg.exam_type_id', $examTypeModel->id)
                ->where('fg.year', $examYear)
                ->whereIn('fg.candidate_id', $candidateIds);

            if ($useSnapshotForStoredResults) {
                $storedFinalBase->where('fg.snapshot_id', $activeSnapshot->id);
            } elseif ($latestProcessId) {
                $storedFinalBase->where(function ($q) use ($latestProcessId) {
                    $q->where('fg.process_id', $latestProcessId)
                        ->whereNull('fg.snapshot_id');
                });
            }

            $storedFinalRows = $storedFinalBase
                ->get(['fg.candidate_id', 'fg.gpa', 'fg.division', 'fg.grading_breakdown'])
                ->keyBy('candidate_id');

            $missingFinalCandidateIds = $candidateIds
                ->diff($storedFinalRows->keys())
                ->values();

            if ($missingFinalCandidateIds->isNotEmpty()) {
                $fallbackFinalRows = \Illuminate\Support\Facades\DB::table('final_grades as fg')
                    ->where('fg.exam_type_id', $examTypeModel->id)
                    ->where('fg.year', $examYear)
                    ->whereIn('fg.candidate_id', $missingFinalCandidateIds)
                    ->orderByDesc('fg.id')
                    ->get(['fg.id', 'fg.candidate_id', 'fg.gpa', 'fg.division', 'fg.grading_breakdown'])
                    ->unique('candidate_id')
                    ->keyBy('candidate_id');

                $storedFinalRows = $storedFinalRows->union($fallbackFinalRows);
            }
        }

        $storedStatusRows = collect();
        if ($candidateIds->isNotEmpty() && \Illuminate\Support\Facades\Schema::hasColumn('candidate_results', 'result_status')) {
            $storedStatusBase = \Illuminate\Support\Facades\DB::table('candidate_results as cr')
                ->where('cr.exam_type_id', $examTypeModel->id)
                ->where('cr.year', $examYear)
                ->whereIn('cr.candidate_id', $candidateIds);

            if ($useSnapshotForStoredResults && $activeSnapshot && \Illuminate\Support\Facades\Schema::hasColumn('candidate_results', 'snapshot_id')) {
                $storedStatusBase->where('cr.snapshot_id', $activeSnapshot->id);
            } elseif ($latestProcessId) {
                $storedStatusBase->where(function ($q) use ($latestProcessId) {
                    $q->where('cr.process_id', $latestProcessId)
                        ->whereNull('cr.snapshot_id');
                });
            }

            $storedStatusRows = $storedStatusBase
                ->get(['cr.candidate_id', 'cr.result_status'])
                ->keyBy('candidate_id');

            $missingStatusCandidateIds = $candidateIds
                ->diff($storedStatusRows->keys())
                ->values();

            if ($missingStatusCandidateIds->isNotEmpty()) {
                $fallbackStatusRows = \Illuminate\Support\Facades\DB::table('candidate_results as cr')
                    ->where('cr.exam_type_id', $examTypeModel->id)
                    ->where('cr.year', $examYear)
                    ->whereIn('cr.candidate_id', $missingStatusCandidateIds)
                    ->orderByDesc('cr.id')
                    ->get(['cr.id', 'cr.candidate_id', 'cr.result_status'])
                    ->unique('candidate_id')
                    ->keyBy('candidate_id');

                $storedStatusRows = $storedStatusRows->union($fallbackStatusRows);
            }
        }

        $computedMetrics = $this->candidateMetricsService->computeForCandidateIds(
            $candidateIds,
            $examTypeModel,
            $examYear,
            $storedFinalRows,
            $storedStatusRows
        );

        $candidatesWithMetrics = $candidates->map(function ($candidate) use ($computedMetrics) {
            $metrics = $computedMetrics->get($candidate->id, [
                'totalMarks' => 0.0,
                'average' => 0.0,
                'gpa' => 0.0,
                'gpaInfo' => null,
                'division' => 'ABS',
                'division_numeric' => 0,
                'totalPoints' => 0.0,
                'gpaPointsSum' => 0.0,
                'gpaSubjectCount' => 0,
                'subjectResultsStr' => 'ABS',
                'candidateStatus' => 'ABS',
                'latestMarks' => collect(),
            ]);

            return array_merge($metrics, [
                'candidate' => $candidate,
            ]);
        })->values()->all();

        // Sort by status and performance:
        // COMPLETE first ranked by GPA asc, then INC, then ABS.
        usort($candidatesWithMetrics, function($a, $b) {
            $statusOrder = ['COMPLETE' => 0, 'INC' => 1, 'ABS' => 2];
            $aStatus = $a['candidateStatus'] ?? 'COMPLETE';
            $bStatus = $b['candidateStatus'] ?? 'COMPLETE';
            $aStatusOrder = $statusOrder[$aStatus] ?? 3;
            $bStatusOrder = $statusOrder[$bStatus] ?? 3;

            if ($aStatusOrder !== $bStatusOrder) {
                return $aStatusOrder <=> $bStatusOrder;
            }

            if ($aStatus !== 'COMPLETE') {
                return strcmp((string) $a['candidate']->candidate_id, (string) $b['candidate']->candidate_id);
            }

            // Rank by GPA ascending (lower is better position).
            $gpaCmp = (float) ($a['gpa'] ?? 99) <=> (float) ($b['gpa'] ?? 99);
            if ($gpaCmp !== 0) {
                return $gpaCmp;
            }

            // Tie-breakers: lower AGGT (better), then higher AVG, then index number.
            $aggtCmp = (float) ($a['totalPoints'] ?? 999) <=> (float) ($b['totalPoints'] ?? 999);
            if ($aggtCmp !== 0) {
                return $aggtCmp;
            }

            $avgCmp = (float) ($b['average'] ?? 0) <=> (float) ($a['average'] ?? 0);
            if ($avgCmp !== 0) {
                return $avgCmp;
            }

            return strcmp((string) $a['candidate']->candidate_id, (string) $b['candidate']->candidate_id);
        });

        // Calculate totals for summary sections (these come from the @php block in the view)
        // Note: The view already calculates these, so we don't need to pass them from here
        // But we'll add totalAbsent for the division performance section
        $totalAbsent = array_sum(array_map(function($data) {
            return ($data['candidateStatus'] ?? null) === 'ABS' ? 1 : 0;
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

    /**
     * Public results view should respect required paper presence; missing any required paper => INC.
     */
    private function requiredPaperCodesForPublicResults($subject): array
    {
        if (!$subject) {
            return ['paper_1'];
        }

        $codes = [];
        $written = max(1, min(2, (int) ($subject->written_papers ?? 1)));
        for ($i = 1; $i <= $written; $i++) {
            $codes[] = "paper_{$i}";
        }
        if (!empty($subject->has_practical)) {
            $codes[] = 'paper_3';
        }

        return array_values(array_unique($codes));
    }

    /**
     * NECTA public rules:
     * - GS excluded from AGGT/GPA scope.
     * - BAM excluded from AGGT/GPA scope.
     */
    private function shouldIncludeInPublicAggtAndGpa(string $subjectName, bool $isPrincipal): bool
    {
        $normalized = strtoupper(trim($subjectName));
        if ($normalized === 'GENERAL STUDIES') {
            return false;
        }

        if ($normalized === 'BASIC APPLIED MATHEMATICS') {
            return false;
        }

        return true;
    }

    /**
     * Select the preferred mark row for a subject:
     * latest complete row (required papers present, not INC) else latest row.
     */
    private function pickPreferredPublicMarkForSubjectRows($subjectRows): ?SubjectMarks
    {
        $rows = collect($subjectRows)->sortByDesc('id')->values();
        if ($rows->isEmpty()) {
            return null;
        }

        $subject = $rows->first()?->subject;
        $requiredPapers = $this->requiredPaperCodesForPublicResults($subject);
        $hasPositiveByPaper = [];
        foreach ($requiredPapers as $paperCode) {
            $hasPositiveByPaper[$paperCode] = $rows->contains(function ($mark) use ($paperCode) {
                $v = $mark->{$paperCode} ?? null;
                return $v !== null && (float) $v > 0;
            });
        }

        $preferred = $rows->first(function ($mark) use ($requiredPapers, $hasPositiveByPaper) {
            if (!$mark) {
                return false;
            }

            $status = strtoupper((string) ($mark->subject_status ?? ''));
            if ($status === 'INC') {
                return false;
            }

            foreach ($requiredPapers as $paperCode) {
                $value = $mark->{$paperCode} ?? null;
                if ($value === null) {
                    return false;
                }
                if (($hasPositiveByPaper[$paperCode] ?? false) && (float) $value <= 0) {
                    return false;
                }
            }

            return true;
        });

        return $preferred ?: $rows->first();
    }

    /**
     * Display-only subject short labels used in NECTA-style detailed subject strings.
     * Does not mutate stored subject names/codes.
     */
    private function formatNectaSubjectLabel(?string $subjectCode, ?string $subjectName): string
    {
        $aliases = (array) config('necta_subject_aliases.acsee', []);
        $code = trim((string) ($subjectCode ?? ''));
        if ($code !== '' && isset($aliases[$code])) {
            return (string) $aliases[$code];
        }

        return strtoupper(trim((string) ($subjectName ?? 'SUBJECT')));
    }
}
