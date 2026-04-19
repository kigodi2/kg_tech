<?php

namespace App\Services\MarkEntry;

use App\Models\Candidate;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Subject;
use App\Models\SubjectPaperWeight;
use App\Models\SubjectMarks;
use App\Services\Results\NectaGradingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MarkPromotionService
{
    private NectaGradingService $gradingService;
    private array $paperWeightCache = [];
    private array $subjectCache = [];

    public function __construct(NectaGradingService $gradingService)
    {
        $this->gradingService = $gradingService;
    }

    public function promote(MarkImportBatch $batch): array
    {
        $promoted = 0;
        $skipped = 0;
        $failed = 0;
        $incCount = 0;
        $absentCount = 0;
        $errors = [];

        $validMarks = $batch->rawMarks()->get();

        if ($validMarks->isEmpty()) {
            Log::warning("Promotion: Batch {$batch->id} has no valid marks to promote.");
            return [
                'promoted' => 0,
                'skipped' => 0,
                'failed' => 0,
                'inc' => 0,
                'absent' => 0,
                'total' => 0,
                'errors' => [],
            ];
        }

        $candidateIdByIndex = $this->buildCandidateMapForBatch($batch);

        DB::transaction(function () use ($validMarks, $batch, $candidateIdByIndex, &$promoted, &$skipped, &$failed, &$incCount, &$absentCount, &$errors) {
            foreach ($validMarks as $rawMark) {
                try {
                    $candidateId = (int) ($rawMark->candidate_id ?? 0);
                    if ($candidateId <= 0 && !empty($rawMark->candidate_index_number)) {
                        $candidateId = (int) ($candidateIdByIndex[$rawMark->candidate_index_number] ?? 0);
                    }

                    if ($candidateId <= 0) {
                        $skipped++;
                        continue;
                    }

                    $subjectId = $rawMark->subject_id ?: $batch->subject_id;
                    if (!$subjectId) {
                        $skipped++;
                        continue;
                    }

                    $subject = $this->subjectForId((int) $subjectId);
                    $subjectMaxMarks = (int) ($subject?->max_marks ?: 100);

                    $subjectStatus = $rawMark->subject_status;

                    // Handle INC status — promote with INC, do NOT grade as 0
                    if ($subjectStatus === 'INC') {
                        $paper3 = $this->resolvePaper3Mark($rawMark);
                        SubjectMarks::updateOrCreate(
                            [
                                'candidate_id' => $candidateId,
                                'exam_type_id' => $batch->exam_type_id,
                                'subject_id' => $subjectId,
                                'year' => $batch->exam_year,
                            ],
                            [
                                'paper_1' => $this->toFloatNullable($rawMark->paper_1_marks),
                                'paper_2' => $this->toFloatNullable($rawMark->paper_2_marks),
                                'paper_3' => $paper3,
                                'marks_obtained' => null,
                                'max_marks' => $subjectMaxMarks,
                                'percentage' => null,
                                'grade' => 'INC',
                                'subject_status' => 'INC',
                            ]
                        );
                        $this->markRawAsProcessed($rawMark, $candidateId);
                        $incCount++;
                        $promoted++;
                        continue;
                    }

                    // Handle absent (X) — promote with X status, do NOT grade as 0
                    if ($subjectStatus === 'X') {
                        SubjectMarks::updateOrCreate(
                            [
                                'candidate_id' => $candidateId,
                                'exam_type_id' => $batch->exam_type_id,
                                'subject_id' => $subjectId,
                                'year' => $batch->exam_year,
                            ],
                            [
                                'paper_1' => null,
                                'paper_2' => null,
                                'paper_3' => null,
                                'marks_obtained' => null,
                                'max_marks' => $subjectMaxMarks,
                                'percentage' => null,
                                'grade' => 'X',
                                'subject_status' => 'X',
                            ]
                        );
                        $this->markRawAsProcessed($rawMark, $candidateId);
                        $absentCount++;
                        $promoted++;
                        continue;
                    }

                    // Normal promotion — normalize papers to SubjectMark100.
                    $paper1 = $this->toFloatNullable($rawMark->paper_1_marks);
                    $paper2 = $this->toFloatNullable($rawMark->paper_2_marks);
                    $paper3 = $this->resolvePaper3Mark($rawMark);
                    $paperValues = [];
                    if ($paper1 !== null) $paperValues['paper_1'] = $paper1;
                    if ($paper2 !== null) $paperValues['paper_2'] = $paper2;
                    if ($paper3 !== null) $paperValues['paper_3'] = $paper3;

                    $subjectMark100 = $this->normalizeSubjectMarkTo100($paperValues, $subject);
                    $grade = $this->gradingService->calculateGrade($subjectMark100);

                    SubjectMarks::updateOrCreate(
                        [
                            'candidate_id' => $candidateId,
                            'exam_type_id' => $batch->exam_type_id,
                            'subject_id' => $subjectId,
                            'year' => $batch->exam_year,
                        ],
                        [
                            'paper_1' => $paper1,
                            'paper_2' => $paper2,
                            'paper_3' => $paper3,
                            'marks_obtained' => $subjectMark100,
                            'max_marks' => $subjectMaxMarks,
                            'percentage' => $subjectMark100,
                            'grade' => $grade,
                            'subject_status' => null,
                        ]
                    );

                    // Mark raw mark as processed (and persist resolved candidate link when possible)
                    $this->markRawAsProcessed($rawMark, $candidateId);

                    $promoted++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'raw_mark_id' => $rawMark->id,
                        'candidate_id' => $candidateId ?? $rawMark->candidate_id,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Promotion failed for raw_mark {$rawMark->id}: {$e->getMessage()}");
                }
            }
        });

        Log::info("Promotion complete for batch {$batch->id}: promoted={$promoted}, skipped={$skipped}, failed={$failed}, inc={$incCount}, absent={$absentCount}");

        return [
            'promoted' => $promoted,
            'skipped' => $skipped,
            'failed' => $failed,
            'inc' => $incCount,
            'absent' => $absentCount,
            'total' => $validMarks->count(),
            'errors' => $errors,
        ];
    }

    /**
     * Build candidate_id lookup by index number for the batch scope.
     * This repairs rows where raw_marks.candidate_id is null at promotion time.
     */
    private function buildCandidateMapForBatch(MarkImportBatch $batch): array
    {
        $query = Candidate::query()
            ->select('candidates.id', 'candidates.candidate_id')
            ->where('candidates.school_id', $batch->school_id)
            ->whereNotNull('candidates.candidate_id')
            ->whereHas('examRegistrations', function ($q) use ($batch) {
                $q->where('exam_type_id', $batch->exam_type_id)
                  ->where(function ($y) use ($batch) {
                      $y->where('year', (int) $batch->exam_year)
                        ->orWhereHas('examYear', function ($ey) use ($batch) {
                            $ey->where('year_label', (string) $batch->exam_year);
                        });
                  });
            });

        return $query->get()
            ->mapWithKeys(fn ($c) => [(string) $c->candidate_id => (int) $c->id])
            ->all();
    }

    /**
     * Best-effort persistence of processing state and resolved candidate link.
     * Do not fail promotion if this persistence step fails.
     */
    private function markRawAsProcessed(RawMark $rawMark, int $candidateId): void
    {
        try {
            $updates = ['processed_at' => now()];
            if (empty($rawMark->candidate_id) && $candidateId > 0) {
                $updates['candidate_id'] = $candidateId;
            }
            $rawMark->update($updates);
        } catch (\Throwable $e) {
            Log::warning("Promotion post-update failed for raw_mark {$rawMark->id}: {$e->getMessage()}");
        }
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '' || $value === '-') {
            return 0.0;
        }
        return (float) $value;
    }

    private function subjectForId(int $subjectId): ?Subject
    {
        if ($subjectId <= 0) {
            return null;
        }

        if (array_key_exists($subjectId, $this->subjectCache)) {
            return $this->subjectCache[$subjectId];
        }

        $subject = Subject::query()->find($subjectId);
        $this->subjectCache[$subjectId] = $subject;
        return $subject;
    }

    private function paperWeightsForSubject(int $subjectId): array
    {
        if ($subjectId <= 0) {
            return [];
        }

        if (array_key_exists($subjectId, $this->paperWeightCache)) {
            return $this->paperWeightCache[$subjectId];
        }

        if (!Schema::hasTable('subject_paper_weights')) {
            $this->paperWeightCache[$subjectId] = [];
            return [];
        }

        $rows = SubjectPaperWeight::query()
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereIn('paper_code', ['paper_1', 'paper_2', 'paper_3'])
            ->orderBy('paper_code')
            ->get(['paper_code', 'weight', 'max_mark'])
            ->map(fn ($r) => [
                'paper_code' => (string) $r->paper_code,
                'weight' => (float) ($r->weight ?? 1.0),
                'max_mark' => $this->paperMaxMark((string) $r->paper_code, $r->max_mark),
            ])
            ->values()
            ->all();

        $this->paperWeightCache[$subjectId] = $rows;
        return $rows;
    }

    /**
     * SubjectMark100 = (WeightedSum / WeightedMax) × 100
     */
    private function normalizeSubjectMarkTo100(array $paperValues, ?Subject $subject): float
    {
        if (empty($paperValues)) {
            return 0.0;
        }

        if ($subject) {
            $weights = $this->paperWeightsForSubject((int) $subject->id);
            if (!empty($weights)) {
                $weightedSum = 0.0;
                $weightedMax = 0.0;
                foreach ($weights as $row) {
                    $paperCode = (string) ($row['paper_code'] ?? '');
                    if ($paperCode === '' || !array_key_exists($paperCode, $paperValues)) {
                        continue;
                    }

                    $weight = (float) ($row['weight'] ?? 1.0);
                    $maxMark = (float) ($row['max_mark'] ?? 100.0);
                    $mark = (float) $paperValues[$paperCode];

                    $weightedSum += ($mark * $weight);
                    $weightedMax += ($maxMark * $weight);
                }

                if ($weightedMax > 0) {
                    return round(($weightedSum / $weightedMax) * 100.0, 0);
                }
            }
        }

        // Fallback: equal paper weights with canonical maxima.
        $weightedSum = 0.0;
        $weightedMax = 0.0;
        foreach ($paperValues as $paperCode => $mark) {
            $maxMark = $this->paperMaxMark((string) $paperCode);
            $weightedSum += (float) $mark;
            $weightedMax += $maxMark;
        }

        if ($weightedMax <= 0) {
            return 0.0;
        }

        return round(($weightedSum / $weightedMax) * 100.0, 0);
    }

    private function paperMaxMark(string $paperCode, mixed $configuredMax = null): float
    {
        $canonical = $paperCode === 'paper_3' ? 50.0 : 100.0;
        if ($configuredMax === null || $configuredMax === '') {
            return $canonical;
        }

        $value = (float) $configuredMax;
        return $value > 0 ? $value : $canonical;
    }

    private function toFloatNullable($value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }
        return (float) $value;
    }

    /**
     * Canonical practical/project alias handling:
     * - prefer explicit paper_3_marks
     * - fallback to practical_marks
     * - fallback to project_marks
     */
    private function resolvePaper3Mark(RawMark $rawMark): ?float
    {
        $paper3 = $this->toFloatNullable($rawMark->paper_3_marks);
        if ($paper3 !== null) {
            return $paper3;
        }

        $practical = $this->toFloatNullable($rawMark->practical_marks);
        if ($practical !== null) {
            return $practical;
        }

        return $this->toFloatNullable($rawMark->project_marks);
    }
}
