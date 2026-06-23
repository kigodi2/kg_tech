<?php

namespace App\Services\Results;

use Illuminate\Support\Collection;

class SchoolResultSummaryService
{
    protected CandidateResultStatusService $statusService;

    public function __construct(CandidateResultStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Compute summaries for a school based on candidate evaluations.
     *
     * @param Collection $candidates Collection of candidate arrays or DTOs
     * @return array
     */
    public function summarizeCandidates(Collection $candidates): array
    {
        $registered = $candidates->count();
        $abs = $candidates->where('status', 'ABS')->count();
        $inc = $candidates->where('status', 'INC')->count();
        $complete = $candidates->where('status', 'COMPLETE')->count();
        $sat = $registered - $abs;

        // Averages and rankings must use COMPLETE candidates only
        $completeCandidates = $candidates->where('status', 'COMPLETE');
        $totalMarksSum = (float) $completeCandidates->sum('total_marks');
        $averageMarks = $complete > 0 ? round($totalMarksSum / $complete, 4) : null;
        $averageGrade = $averageMarks !== null ? $this->statusService->gradeFromRaw50($averageMarks / 6.0) : null;

        // Grade distributions (COMPLETE only)
        $gradeDistribution = [
            'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0
        ];
        foreach ($completeCandidates as $cand) {
            $g = strtoupper((string) ($cand['overall_grade'] ?? 'E'));
            if (isset($gradeDistribution[$g])) {
                $gradeDistribution[$g]++;
            }
        }

        // Pass summaries (COMPLETE only)
        $passAc = $gradeDistribution['A'] + $gradeDistribution['B'] + $gradeDistribution['C'];
        $passAd = $passAc + $gradeDistribution['D'];

        $passRateAc = $complete > 0 ? ($passAc / $complete) * 100.0 : 0.0;
        $passRateAd = $complete > 0 ? ($passAd / $complete) * 100.0 : 0.0;

        // Badge: COMPLETE, INCOMPLETE, or NO_MARKS
        // "at least one recorded mark... but some candidates are INC or ABS."
        // COMPLETE: no candidate is INC, and we have at least one complete candidate.
        // INCOMPLETE: at least one candidate has INC.
        // NO_MARKS: all candidates are ABS, or no candidates.
        if ($inc > 0) {
            $badge = 'INCOMPLETE';
        } elseif ($complete > 0) {
            $badge = 'COMPLETE';
        } else {
            $badge = 'NO_MARKS';
        }

        return [
            'registered' => $registered,
            'sat' => $sat,
            'abs' => $abs,
            'inc' => $inc,
            'complete' => $complete,
            'clean' => $complete,
            'total_marks_sum' => $totalMarksSum,
            'average_marks' => $averageMarks,
            'average_grade' => $averageGrade,
            'grade_distribution' => $gradeDistribution,
            'pass_ac' => $passAc,
            'pass_ad' => $passAd,
            'pass_rate_ac' => $passRateAc,
            'pass_rate_ad' => $passRateAd,
            'status_badge' => $badge,
        ];
    }
}
