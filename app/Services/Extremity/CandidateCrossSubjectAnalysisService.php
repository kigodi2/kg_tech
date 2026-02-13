<?php

namespace App\Services\Extremity;

use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\CandidateExtremityAnalysis;
use App\Models\CandidateSubjectOutlier;
use App\Models\CandidateExamRegistration;
use App\Models\SubjectMarks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CandidateCrossSubjectAnalysisService
{
    private const ZSCORE_THRESHOLD = 2.5;
    private const DEVIATION_THRESHOLD = 35;
    private const MIN_SUBJECTS = 3;

    /**
     * Run cross-subject extremity analysis for candidates
     */
    public function analyzeCandidates(ExamYear $examYear, ExamType $examType): void
    {
        $logId = DB::table('candidate_extremity_logs')->insertGetId([
            'exam_year_id' => $examYear->id,
            'exam_type_id' => $examType->id,
            'status' => 'processing',
            'analysis_started_at' => now(),
            'triggered_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $candidates = Candidate::whereHas('examRegistrations', function ($q) use ($examYear, $examType) {
                $q->where('exam_year_id', $examYear->id)
                  ->where('exam_type_id', $examType->id);
            })->get();

            $highRiskCount = 0;
            $moderateRiskCount = 0;
            $lowRiskCount = 0;
            $totalOutliers = 0;

            foreach ($candidates as $candidate) {
                $analysis = $this->analyzeCandidateSubjects($candidate, $examYear, $examType);

                if ($analysis) {
                    $report = $this->createReport($candidate, $examYear, $examType, $analysis);
                    $totalOutliers += $report->outlier_subject_count;

                    match ($report->risk_level) {
                        'High' => $highRiskCount++,
                        'Moderate' => $moderateRiskCount++,
                        'Low' => $lowRiskCount++,
                    };
                }
            }

            DB::table('candidate_extremity_logs')
                ->where('id', $logId)
                ->update([
                    'candidates_analyzed' => $candidates->count(),
                    'high_risk_count' => $highRiskCount,
                    'moderate_risk_count' => $moderateRiskCount,
                    'low_risk_count' => $lowRiskCount,
                    'total_outliers_detected' => $totalOutliers,
                    'analysis_completed_at' => now(),
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);

            Log::info('Candidate cross-subject analysis completed', [
                'exam_year_id' => $examYear->id,
                'candidates_analyzed' => $candidates->count(),
                'outliers_detected' => $totalOutliers,
            ]);

        } catch (\Exception $e) {
            DB::table('candidate_extremity_logs')
                ->where('id', $logId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'analysis_completed_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::error('Candidate cross-subject analysis failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Analyze single candidate across all their subjects
     */
    private function analyzeCandidateSubjects(Candidate $candidate, ExamYear $examYear, ExamType $examType): ?array
    {
        $registration = CandidateExamRegistration::where('candidate_id', $candidate->id)
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $examType->id)
            ->first();

        if (!$registration) {
            return null;
        }

        $subjectSelections = $candidate->subjectSelections()
            ->where('exam_year_id', $examYear->id)
            ->where('exam_type_id', $examType->id)
            ->get();

        if ($subjectSelections->count() < self::MIN_SUBJECTS) {
            return null;
        }

        $subjectMarks = [];
        $subjectData = [];

        foreach ($subjectSelections as $selection) {
            $result = SubjectMarks::where('candidate_id', $candidate->id)
                ->where('subject_id', $selection->subject_id)
                ->where('exam_type_id', $examType->id)
                ->where('year', $examYear->year_label)
                ->first();

            if ($result && $result->marks_obtained !== null) {
                $subjectMarks[$selection->subject_id] = $result->marks_obtained;
                $subjectData[$selection->subject_id] = [
                    'subject_id' => $selection->subject_id,
                    'subject_code' => $selection->subject->code,
                    'subject_name' => $selection->subject->name,
                    'marks' => $result->marks_obtained,
                ];
            }
        }

        if (empty($subjectMarks)) {
            return null;
        }

        $stats = $this->calculateCandidateStats($subjectMarks);
        $stats['subjects'] = $subjectData;
        $stats['combination'] = $candidate->combination ?? 'N/A';

        $outliers = $this->detectSubjectOutliers($subjectMarks, $stats);
        $stats['outliers'] = $outliers;

        return $stats;
    }

    /**
     * Calculate candidate's statistics across their subjects
     */
    private function calculateCandidateStats(array $subjectMarks): array
    {
        $scores = array_values($subjectMarks);
        $count = count($scores);

        sort($scores);

        $mean = array_sum($scores) / $count;
        $median = $this->calculateMedian($scores);

        $variance = array_sum(array_map(function ($score) use ($mean) {
            return pow($score - $mean, 2);
        }, $scores)) / $count;
        $stdDev = sqrt($variance);

        return [
            'average_score' => round($mean, 3),
            'median_score' => round($median, 3),
            'std_dev_across_subjects' => round($stdDev, 3),
            'min_score' => round(min($scores), 3),
            'max_score' => round(max($scores), 3),
            'subject_count' => $count,
        ];
    }

    /**
     * Detect subjects where candidate's performance is anomalous
     */
    private function detectSubjectOutliers(array $subjectMarks, array $stats): array
    {
        $outliers = [];
        $mean = $stats['average_score'];
        $stdDev = $stats['std_dev_across_subjects'];

        foreach ($subjectMarks as $subjectId => $score) {
            $deviation = $score - $mean;
            $deviationPercent = ($deviation / $mean) * 100;
            $zscore = $stdDev > 0 ? $deviation / $stdDev : 0;

            $isOutlier = false;

            if (abs($zscore) > self::ZSCORE_THRESHOLD) {
                $isOutlier = true;
            }

            if (abs($deviationPercent) > self::DEVIATION_THRESHOLD) {
                $isOutlier = true;
            }

            if ($isOutlier) {
                $outliers[] = [
                    'subject_id' => $subjectId,
                    'score' => $score,
                    'candidate_average' => $mean,
                    'deviation_from_average' => round($deviation, 3),
                    'deviation_percentage' => round($deviationPercent, 3),
                    'zscore' => round($zscore, 3),
                    'outlier_type' => $score > $mean ? 'high' : 'low',
                ];
            }
        }

        return $outliers;
    }

    /**
     * Create candidate extremity report
     */
    private function createReport(Candidate $candidate, ExamYear $examYear, ExamType $examType, array $analysis): CandidateExtremityAnalysis
    {
        $flags = [];
        $riskLevel = 'Low';

        if (count($analysis['outliers']) > 0) {
            $outlierCount = count($analysis['outliers']);
            $outlierPercentage = ($outlierCount / $analysis['subject_count']) * 100;

            if ($outlierPercentage >= 50) {
                $flags[] = 'Multiple Subject Outliers';
                $riskLevel = 'High';
            } elseif ($outlierPercentage >= 33) {
                $flags[] = 'Several Subject Anomalies';
                $riskLevel = 'Moderate';
            } else {
                $flags[] = 'Single Subject Outlier';
                $riskLevel = 'Moderate';
            }

            $extremeOutliers = array_filter($analysis['outliers'], fn($o) => abs($o['zscore']) > 3);
            if (count($extremeOutliers) > 0) {
                $flags[] = 'Extreme Subject Deviation';
                $riskLevel = 'High';
            }
        }

        if ($analysis['std_dev_across_subjects'] < 5 && count($analysis['outliers']) > 0) {
            $flags[] = 'Uniform Performance (Suspiciously Similar Scores)';
            $riskLevel = 'High';
        }

        $outlierSubjectNames = array_map(function ($outlier) {
            return [
                'subject_id' => $outlier['subject_id'],
                'score' => $outlier['score'],
                'deviation' => $outlier['deviation_from_average'],
                'type' => $outlier['outlier_type'],
            ];
        }, $analysis['outliers']);

        $report = CandidateExtremityAnalysis::create([
            'candidate_id' => $candidate->id,
            'exam_year_id' => $examYear->id,
            'exam_type_id' => $examType->id,
            'combination' => $analysis['combination'],
            'subject_count' => $analysis['subject_count'],
            'average_score' => $analysis['average_score'],
            'median_score' => $analysis['median_score'],
            'std_dev_across_subjects' => $analysis['std_dev_across_subjects'],
            'min_score' => $analysis['min_score'],
            'max_score' => $analysis['max_score'],
            'outlier_subject_count' => count($analysis['outliers']),
            'outlier_subjects' => json_encode($outlierSubjectNames),
            'subject_analysis' => json_encode($analysis['subjects']),
            'risk_level' => $riskLevel,
            'flags' => json_encode($flags),
            'analysis_notes' => "Analyzed {$analysis['subject_count']} subjects. Found " . count($analysis['outliers']) . " outliers.",
        ]);

        foreach ($analysis['outliers'] as $outlier) {
            CandidateSubjectOutlier::create([
                'candidate_extremity_id' => $report->id,
                'subject_id' => $outlier['subject_id'],
                'score' => $outlier['score'],
                'candidate_average' => $analysis['average_score'],
                'deviation_from_average' => $outlier['deviation_from_average'],
                'deviation_percentage' => $outlier['deviation_percentage'],
                'zscore' => $outlier['zscore'],
                'outlier_type' => $outlier['outlier_type'],
            ]);
        }

        Log::info('Candidate extremity analysis created', [
            'candidate_id' => $candidate->id,
            'exam_year_id' => $examYear->id,
            'outlier_count' => count($analysis['outliers']),
            'risk_level' => $riskLevel,
        ]);

        return $report;
    }

    /**
     * Helper: Calculate median
     */
    private function calculateMedian(array $scores): float
    {
        $count = count($scores);
        $mid = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($scores[$mid - 1] + $scores[$mid]) / 2;
        }

        return $scores[$mid];
    }
}
