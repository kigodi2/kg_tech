<?php

namespace App\Services\MarkEntry;

use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\SubjectMarks;
use App\Services\Results\NectaGradingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarkPromotionService
{
    private NectaGradingService $gradingService;

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

        $validMarks = $batch->rawMarks()
            ->where('has_errors', false)
            ->get();

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

        DB::transaction(function () use ($validMarks, $batch, &$promoted, &$skipped, &$failed, &$incCount, &$absentCount, &$errors) {
            foreach ($validMarks as $rawMark) {
                try {
                    if (!$rawMark->candidate_id) {
                        $skipped++;
                        continue;
                    }

                    $subjectId = $rawMark->subject_id ?: $batch->subject_id;
                    if (!$subjectId) {
                        $skipped++;
                        continue;
                    }

                    $subjectStatus = $rawMark->subject_status;

                    // Handle INC status — promote with INC, do NOT grade as 0
                    if ($subjectStatus === 'INC') {
                        SubjectMarks::updateOrCreate(
                            [
                                'candidate_id' => $rawMark->candidate_id,
                                'exam_type_id' => $batch->exam_type_id,
                                'subject_id' => $subjectId,
                                'year' => $batch->exam_year,
                            ],
                            [
                                'paper_1' => $this->toFloatNullable($rawMark->paper_1_marks),
                                'paper_2' => $this->toFloatNullable($rawMark->paper_2_marks),
                                'paper_3' => $this->toFloatNullable($rawMark->paper_3_marks),
                                'marks_obtained' => null,
                                'max_marks' => 100,
                                'percentage' => null,
                                'grade' => 'INC',
                                'subject_status' => 'INC',
                            ]
                        );
                        $rawMark->update(['processed_at' => now()]);
                        $incCount++;
                        $promoted++;
                        continue;
                    }

                    // Handle absent (X) — promote with X status, do NOT grade as 0
                    if ($subjectStatus === 'X') {
                        SubjectMarks::updateOrCreate(
                            [
                                'candidate_id' => $rawMark->candidate_id,
                                'exam_type_id' => $batch->exam_type_id,
                                'subject_id' => $subjectId,
                                'year' => $batch->exam_year,
                            ],
                            [
                                'paper_1' => null,
                                'paper_2' => null,
                                'paper_3' => null,
                                'marks_obtained' => null,
                                'max_marks' => 100,
                                'percentage' => null,
                                'grade' => 'X',
                                'subject_status' => 'X',
                            ]
                        );
                        $rawMark->update(['processed_at' => now()]);
                        $absentCount++;
                        $promoted++;
                        continue;
                    }

                    // Normal promotion — all papers present
                    $paper1 = $this->toFloat($rawMark->paper_1_marks);
                    $paper2 = $this->toFloat($rawMark->paper_2_marks);
                    $paper3 = $this->toFloat($rawMark->paper_3_marks);

                    // Total = sum of all available papers
                    $total = $paper1 + $paper2 + $paper3;

                    // Count papers for average
                    $paperCount = 0;
                    if ($paper1 > 0) $paperCount++;
                    if ($paper2 > 0) $paperCount++;
                    if ($paper3 > 0) $paperCount++;

                    $average = $paperCount > 0 ? round($total / $paperCount, 2) : 0;
                    $grade = $this->gradingService->calculateGrade($average);

                    SubjectMarks::updateOrCreate(
                        [
                            'candidate_id' => $rawMark->candidate_id,
                            'exam_type_id' => $batch->exam_type_id,
                            'subject_id' => $subjectId,
                            'year' => $batch->exam_year,
                        ],
                        [
                            'paper_1' => $paper1,
                            'paper_2' => $paper2,
                            'paper_3' => $paper3,
                            'marks_obtained' => $total,
                            'max_marks' => 100,
                            'percentage' => $average,
                            'grade' => $grade,
                            'subject_status' => null,
                        ]
                    );

                    // Mark raw mark as processed
                    $rawMark->update(['processed_at' => now()]);

                    $promoted++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'raw_mark_id' => $rawMark->id,
                        'candidate_id' => $rawMark->candidate_id,
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

    private function toFloat($value): float
    {
        if ($value === null || $value === '' || $value === '-') {
            return 0.0;
        }
        return (float) $value;
    }

    private function toFloatNullable($value): ?float
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }
        return (float) $value;
    }
}
