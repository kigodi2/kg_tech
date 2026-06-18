<?php

namespace App\Services\Results;

use App\Models\SchoolResultCorrectionBatch;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\AuditLog;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

class SchoolRollbackService
{
    /**
     * Initiate rollback for a single school.
     */
    public function initiateRollback(School $school, int $examYear, string $reason, int $userId): SchoolResultCorrectionBatch
    {
        $examYearModel = ExamYear::where('id', $examYear)
            ->orWhere('year_label', $examYear)
            ->firstOrFail();

        $psleType = ExamType::where('code', 'PSLE')->firstOrFail();

        // 1. Run inside DB transaction
        return DB::transaction(function () use ($school, $examYearModel, $psleType, $reason, $userId) {
            // 2. Reject if another active correction batch exists for the same exam year and psle with status open, corrected, or recalculated
            $activeBatchExists = SchoolResultCorrectionBatch::where('exam_year', $examYearModel->year_label)
                ->where('exam_type', 'PSLE')
                ->whereIn('status', [
                    SchoolResultCorrectionBatch::STATUS_OPEN,
                    SchoolResultCorrectionBatch::STATUS_CORRECTED,
                    SchoolResultCorrectionBatch::STATUS_RECALCULATED
                ])
                ->exists();

            if ($activeBatchExists) {
                throw new \Exception("Cannot initiate rollback. There is already an active correction batch for this exam year.");
            }

            // 3. Find the active published result snapshot for the year
            $activeSnapshot = DB::table('result_snapshots')
                ->where('exam_year_id', $examYearModel->id)
                ->where('exam_type', 'PSLE')
                ->where('is_active', true)
                ->where('is_rolled_back', false)
                ->first();

            if (!$activeSnapshot) {
                throw new \Exception("No active published results found for this year to rollback.");
            }

            // 4. Create correction batch
            $batch = SchoolResultCorrectionBatch::create([
                'exam_year' => $examYearModel->year_label,
                'exam_type' => 'PSLE',
                'school_id' => $school->id,
                'school_name_snapshot' => $school->name,
                'status' => SchoolResultCorrectionBatch::STATUS_OPEN,
                'reason' => $reason,
                'opened_by' => $userId,
                'opened_at' => now(),
            ]);

            // 5. Unlock only raw marks belonging to the selected school, year, and PSLE
            DB::table('raw_marks')
                ->where('school_id', $school->id)
                ->where('exam_year_id', $examYearModel->id)
                ->update([
                    'is_locked' => false,
                    'locked_at' => null,
                    'locked_by' => null,
                    'correction_batch_id' => $batch->id,
                    'correction_status' => 'open',
                    'correction_opened_at' => now(),
                    'correction_opened_by' => $userId,
                ]);

            // 6. Mark the active snapshot as stale
            DB::table('result_snapshots')
                ->where('id', $activeSnapshot->id)
                ->update([
                    'is_stale' => true,
                    'stale_reason' => "School correction active for {$school->name}: {$reason}",
                    'stale_at' => now(),
                    'stale_by' => $userId,
                    'correction_batch_id' => $batch->id,
                ]);

            // 7. Log audit event
            $this->logAuditAction(
                $userId,
                $examYearModel->id,
                'psle_school_rollback_initiated',
                "Initiated rollback for school {$school->name} (ID: {$school->id}) for exam year {$examYearModel->year_label}. Reason: {$reason}"
            );

            return $batch;
        });
    }

    /**
     * Complete correction phase.
     */
    public function completeCorrection(SchoolResultCorrectionBatch $batch, int $userId): SchoolResultCorrectionBatch
    {
        if ($batch->status !== SchoolResultCorrectionBatch::STATUS_OPEN) {
            throw new \Exception("Cannot complete correction. Batch is not in open status.");
        }

        // Validate school marks are complete
        if (!$this->validateSchoolMarksComplete($batch)) {
            throw new \Exception("Cannot complete correction. School marks are incomplete or contain critical validation errors.");
        }

        $examYearModel = ExamYear::where('year_label', $batch->exam_year)->firstOrFail();

        return DB::transaction(function () use ($batch, $examYearModel, $userId) {
            // 1. Lock raw marks
            DB::table('raw_marks')
                ->where('school_id', $batch->school_id)
                ->where('exam_year_id', $examYearModel->id)
                ->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => $userId,
                    'correction_status' => 'corrected',
                ]);

            // 2. Update batch status
            $batch->update([
                'status' => SchoolResultCorrectionBatch::STATUS_CORRECTED,
                'corrected_by' => $userId,
                'corrected_at' => now(),
            ]);

            // 3. Log audit event
            $this->logAuditAction(
                $userId,
                $examYearModel->id,
                'psle_school_correction_completed',
                "Completed marks correction for school {$batch->school_name_snapshot} (ID: {$batch->school_id}) for exam year {$batch->exam_year}."
            );

            return $batch;
        });
    }

    /**
     * Recalculate year-level results.
     */
    public function recalculateResults(SchoolResultCorrectionBatch $batch, int $userId): SchoolResultCorrectionBatch
    {
        if ($batch->status !== SchoolResultCorrectionBatch::STATUS_CORRECTED) {
            throw new \Exception("Cannot recalculate results. Batch is not in corrected status.");
        }

        $examYearModel = ExamYear::where('year_label', $batch->exam_year)->firstOrFail();
        $psleType = ExamType::where('code', 'PSLE')->firstOrFail();

        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

        return DB::transaction(function () use ($batch, $examYearModel, $psleType, $schoolIds, $tasidoRegionIds, $userId) {
            // 1. Deactivate all existing snapshots
            DB::table('result_snapshots')
                ->where('exam_year_id', $examYearModel->id)
                ->where('exam_type', 'PSLE')
                ->update(['is_active' => false]);

            // 2. Calculate new version number
            $latestVersion = DB::table('result_snapshots')
                ->where('exam_year_id', $examYearModel->id)
                ->where('exam_type', 'PSLE')
                ->max('version') ?? 0;
            $newVersion = $latestVersion + 1;

            // 3. Create process log
            $processId = DB::table('result_processes')->insertGetId([
                'exam_type_id' => $psleType->id,
                'exam_year_id' => $examYearModel->id,
                'user_id' => $userId,
                'type' => 'final',
                'status' => 'in_progress',
                'total_candidates' => 0,
                'processed_count' => 0,
                'error_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Create Snapshot record linked to correction_batch_id
            $snapshotId = DB::table('result_snapshots')->insertGetId([
                'exam_type' => 'PSLE',
                'exam_year_id' => $examYearModel->id,
                'process_id' => $processId,
                'version' => $newVersion,
                'is_active' => true,
                'is_rolled_back' => false,
                'correction_batch_id' => $batch->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. Run results computation
            $processed = $this->computeAndSaveResults($examYearModel, $psleType->id, $schoolIds, $tasidoRegionIds, $snapshotId, $processId);

            // 6. Update process log status
            DB::table('result_processes')->where('id', $processId)->update([
                'status' => 'completed',
                'total_candidates' => $processed,
                'processed_count' => $processed,
                'processed_at' => now(),
                'completed_at' => now(),
            ]);

            // 7. Update batch status
            $batch->update([
                'status' => SchoolResultCorrectionBatch::STATUS_RECALCULATED,
                'recalculated_by' => $userId,
                'recalculated_at' => now(),
            ]);

            // 8. Log audit event
            $this->logAuditAction(
                $userId,
                $examYearModel->id,
                'psle_school_results_recalculated',
                "Recalculated results for exam year {$batch->exam_year}. Generated new snapshot version {$newVersion} (Process ID: {$processId})."
            );

            return $batch;
        });
    }

    /**
     * Republish results.
     */
    public function republishResults(SchoolResultCorrectionBatch $batch, int $userId): SchoolResultCorrectionBatch
    {
        if ($batch->status !== SchoolResultCorrectionBatch::STATUS_RECALCULATED) {
            throw new \Exception("Cannot republish results. Batch is not in recalculated status.");
        }

        $examYearModel = ExamYear::where('year_label', $batch->exam_year)->firstOrFail();

        return DB::transaction(function () use ($batch, $examYearModel, $userId) {
            // Find active snapshot
            $snapshot = DB::table('result_snapshots')
                ->where('exam_year_id', $examYearModel->id)
                ->where('exam_type', 'PSLE')
                ->where('is_active', true)
                ->where('is_rolled_back', false)
                ->first();

            if (!$snapshot) {
                throw new \Exception("No active snapshot found to publish.");
            }

            // Update or Create publication record
            $existing = DB::table('psle_result_publications')
                ->where('exam_year_id', $examYearModel->id)
                ->where('snapshot_id', $snapshot->id)
                ->first();

            if ($existing) {
                DB::table('psle_result_publications')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'published_by' => $userId,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('psle_result_publications')->insert([
                    'exam_year_id' => $examYearModel->id,
                    'snapshot_id' => $snapshot->id,
                    'region_id' => null,
                    'council_id' => null,
                    'school_id' => null,
                    'publication_scope' => 'TASIDO',
                    'status' => 'published',
                    'version_no' => $snapshot->version,
                    'published_by' => $userId,
                    'published_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update batch status
            $batch->update([
                'status' => SchoolResultCorrectionBatch::STATUS_REPUBLISHED,
                'republished_by' => $userId,
                'republished_at' => now(),
            ]);

            // Log audit event
            $this->logAuditAction(
                $userId,
                $examYearModel->id,
                'psle_school_results_republished',
                "Republished results snapshot version {$snapshot->version} for exam year {$batch->exam_year} after correction."
            );

            return $batch;
        });
    }

    /**
     * Cancel school rollback.
     */
    public function cancelRollback(SchoolResultCorrectionBatch $batch, string $reason, int $userId): SchoolResultCorrectionBatch
    {
        if ($batch->status !== SchoolResultCorrectionBatch::STATUS_OPEN) {
            throw new \Exception("Cannot cancel rollback. Batch status is not open.");
        }

        $examYearModel = ExamYear::where('year_label', $batch->exam_year)->firstOrFail();

        return DB::transaction(function () use ($batch, $examYearModel, $reason, $userId) {
            // 1. Relock raw marks of the school
            DB::table('raw_marks')
                ->where('school_id', $batch->school_id)
                ->where('exam_year_id', $examYearModel->id)
                ->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => $userId,
                    'correction_batch_id' => null,
                    'correction_status' => null,
                ]);

            // 2. Find and restore the previous snapshot
            $staleSnapshot = DB::table('result_snapshots')
                ->where('exam_year_id', $examYearModel->id)
                ->where('exam_type', 'PSLE')
                ->where('correction_batch_id', $batch->id)
                ->first();

            if ($staleSnapshot) {
                DB::table('result_snapshots')
                    ->where('id', $staleSnapshot->id)
                    ->update([
                        'is_active' => true,
                        'is_stale' => false,
                        'stale_reason' => null,
                        'stale_at' => null,
                        'stale_by' => null,
                    ]);

                // Restore matching publication status to published
                DB::table('psle_result_publications')
                    ->where('exam_year_id', $examYearModel->id)
                    ->where('snapshot_id', $staleSnapshot->id)
                    ->update([
                        'status' => 'published',
                        'updated_at' => now(),
                    ]);
            }

            // 3. Update batch status
            $metadata = array_merge($batch->metadata ?? [], ['cancel_reason' => $reason]);
            $batch->update([
                'status' => SchoolResultCorrectionBatch::STATUS_CANCELLED,
                'cancelled_by' => $userId,
                'cancelled_at' => now(),
                'metadata' => $metadata,
            ]);

            // 4. Log audit event
            $this->logAuditAction(
                $userId,
                $examYearModel->id,
                'psle_school_rollback_cancelled',
                "Cancelled rollback for school {$batch->school_name_snapshot} (ID: {$batch->school_id}) for exam year {$batch->exam_year}. Reason: {$reason}"
            );

            return $batch;
        });
    }

    /**
     * Validate school marks completeness.
     */
    public function validateSchoolMarksComplete(SchoolResultCorrectionBatch $batch): bool
    {
        $examYearModel = ExamYear::where('year_label', $batch->exam_year)->first();
        if (!$examYearModel) {
            return false;
        }
        $psleType = ExamType::where('code', 'PSLE')->first();
        if (!$psleType) {
            return false;
        }

        $minScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('minimum_subject_score', 0);
        $maxScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('maximum_subject_score', 50);
        $absentCode = \App\Helpers\SystemSettingsHelper::getSetting('absent_code', 'ABS');
        $incompleteCode = \App\Helpers\SystemSettingsHelper::getSetting('incomplete_code', 'INC');

        $subjects = DB::table('subjects')->where('exam_type_id', $psleType->id)->get();
        $subjectIds = $subjects->pluck('id')->toArray();

        $candidates = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->where('c.school_id', $batch->school_id)
            ->where('cer.exam_type_id', $psleType->id)
            ->where('cer.year', $batch->exam_year)
            ->select('c.id', 'c.candidate_id')
            ->get();

        if ($candidates->isEmpty()) {
            return true;
        }

        $candidateIds = $candidates->pluck('id')->toArray();

        $rawMarks = DB::table('raw_marks')
            ->where('school_id', $batch->school_id)
            ->where('exam_year_id', $examYearModel->id)
            ->whereIn('candidate_id', $candidateIds)
            ->get()
            ->groupBy('candidate_id');

        foreach ($candidates as $cand) {
            $candMarks = $rawMarks->get($cand->id) ?? collect();
            $candMarksBySubject = $candMarks->keyBy('subject_id');

            foreach ($subjectIds as $subId) {
                if (!$candMarksBySubject->has($subId)) {
                    return false; // missing mark
                }

                $record = $candMarksBySubject->get($subId);
                if (is_null($record->paper_1_marks)) {
                    $status = strtoupper(trim((string) $record->subject_status));
                    if ($status !== strtoupper($absentCode) && $status !== strtoupper($incompleteCode)) {
                        return false; // null mark without valid absent/incomplete status
                    }
                } else {
                    $markVal = (float) $record->paper_1_marks;
                    if ($markVal < $minScore || $markVal > $maxScore) {
                        return false; // out of range
                    }
                }
            }
        }

        // Also check for orphan raw marks for this school and year
        $orphanCount = DB::table('raw_marks as rm')
            ->leftJoin('candidate_exam_registrations as cer', function ($join) use ($psleType, $batch) {
                $join->on('cer.candidate_id', '=', 'rm.candidate_id')
                    ->where('cer.exam_type_id', '=', $psleType->id)
                    ->where('cer.year', '=', $batch->exam_year);
            })
            ->where('rm.school_id', $batch->school_id)
            ->where('rm.exam_year_id', $examYearModel->id)
            ->whereNull('cer.candidate_id')
            ->count();

        if ($orphanCount > 0) {
            return false;
        }

        return true;
    }

    /**
     * Compute and save results for all schools in the TASIDO region (year-level recalculation).
     */
    private function computeAndSaveResults(ExamYear $examYear, int $examTypeId, array $schoolIds, array $tasidoRegionIds, $snapshotId, $processId)
    {
        $minScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('minimum_subject_score', 0);
        $maxScore = (float) \App\Helpers\SystemSettingsHelper::getSetting('maximum_subject_score', 50);
        $absentCode = \App\Helpers\SystemSettingsHelper::getSetting('absent_code', 'ABS');
        $incompleteCode = \App\Helpers\SystemSettingsHelper::getSetting('incomplete_code', 'INC');

        $subjects = DB::table('subjects')->where('exam_type_id', $examTypeId)->get();
        $subjectIds = $subjects->pluck('id')->toArray();

        $totalProcessed = 0;

        DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $tasidoRegionIds)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $examYear->year_label)
            ->select(['c.id as candidate_pk', 'c.gender', 'c.school_id'])
            ->orderBy('c.id')
            ->chunk(5000, function ($candidatesChunk) use ($schoolIds, $examYear, $examTypeId, $snapshotId, $processId, $subjectIds, $absentCode, $incompleteCode, &$totalProcessed) {
                $candidateIds = $candidatesChunk->pluck('candidate_pk')->toArray();

                $allRawMarks = DB::table('raw_marks')
                    ->whereIn('school_id', $schoolIds)
                    ->where('exam_year_id', $examYear->id)
                    ->whereIn('candidate_id', $candidateIds)
                    ->get()
                    ->groupBy('candidate_id');

                $candidateResultsData = [];
                $subjectMarksData = [];

                foreach ($candidatesChunk as $cand) {
                    $candMarks = $allRawMarks->get($cand->candidate_pk, collect());
                    $candMarksBySubject = $candMarks->keyBy('subject_id');

                    $totalMarks = 0.0;
                    $hasInc = false;
                    $gradedCount = 0;
                    $absCount = 0;

                    $tempSubjectData = [];

                    foreach ($subjectIds as $subId) {
                        $rm = $candMarksBySubject->get($subId);

                        $marksObtained = null;
                        $maxMarks = 50.0;
                        $percentage = null;
                        $grade = 'E';

                        if ($rm) {
                            if (!is_null($rm->paper_1_marks)) {
                                $marksObtained = (float) $rm->paper_1_marks;
                                $percentage = ($marksObtained / $maxMarks) * 100;
                                $grade = $this->gradeFromRaw50($marksObtained);
                                $totalMarks += $marksObtained;
                                $gradedCount++;
                            } else {
                                $status = strtoupper(trim((string) $rm->subject_status));
                                if ($status === strtoupper($absentCode)) {
                                    $grade = 'ABS';
                                    $absCount++;
                                } elseif ($status === strtoupper($incompleteCode)) {
                                    $grade = 'INC';
                                    $hasInc = true;
                                }
                            }
                        } else {
                            $grade = 'ABS';
                            $absCount++;
                        }

                        $tempSubjectData[] = [
                            'candidate_id' => $cand->candidate_pk,
                            'exam_type_id' => $examTypeId,
                            'subject_id' => $subId,
                            'year' => $examYear->year_label,
                            'marks_obtained' => $marksObtained,
                            'max_marks' => $maxMarks,
                            'percentage' => $percentage,
                            'grade' => $grade,
                            'snapshot_id' => $snapshotId,
                            'process_id' => $processId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    $overallStatus = 'RELEASED';
                    if ($hasInc || ($gradedCount < count($subjectIds) && $absCount < count($subjectIds))) {
                        $overallStatus = 'PENDING';
                    }

                    $overallGrade = 'E';
                    if ($absCount === count($subjectIds)) {
                        $overallGrade = 'ABS';
                    } elseif ($hasInc || ($gradedCount < count($subjectIds))) {
                        $overallGrade = 'INC';
                    } else {
                        $avg = $gradedCount > 0 ? ($totalMarks / $gradedCount) : 0.0;
                        $overallGrade = $this->gradeFromRaw50($avg);
                    }

                    $candidateResultsData[] = [
                        'candidate_id' => $cand->candidate_pk,
                        'exam_type_id' => $examTypeId,
                        'year' => $examYear->year_label,
                        'total_marks' => $gradedCount > 0 ? $totalMarks : null,
                        'total_percentage' => $gradedCount > 0 ? ($totalMarks / (count($subjectIds) * 50)) * 100 : null,
                        'overall_grade' => $overallGrade,
                        'status' => $overallStatus,
                        'released_at' => $overallStatus === 'RELEASED' ? now() : null,
                        'snapshot_id' => $snapshotId,
                        'process_id' => $processId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    foreach ($tempSubjectData as $tsd) {
                        $subjectMarksData[] = $tsd;
                    }
                }

                foreach (array_chunk($candidateResultsData, 500) as $chunk) {
                    DB::table('candidate_results')->insert($chunk);
                }

                foreach (array_chunk($subjectMarksData, 500) as $chunk) {
                    DB::table('subject_marks')->insert($chunk);
                }

                $totalProcessed += count($candidateResultsData);
            });

        return $totalProcessed;
    }

    private function gradeFromRaw50($mark): string
    {
        if (is_null($mark)) return 'E';
        if ($mark >= 241 / 6) return 'A';
        if ($mark >= 181 / 6) return 'B';
        if ($mark >= 121 / 6) return 'C';
        if ($mark >= 61 / 6) return 'D';
        return 'E';
    }

    private function logAuditAction(int $userId, int $examYearId, string $action, string $details): void
    {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'exam_year_id' => $examYearId,
                'module' => 'results',
                'action' => $action,
                'details' => $details,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        } catch (\Exception $e) {
            // Silence log exceptions
        }
    }
}
