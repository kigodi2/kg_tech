<?php

namespace App\Services;

use App\Models\MarkEntryOutlier;
use App\Models\RawMark;
use App\Models\MarkImportBatch;
use App\Models\MarkEntryAssignment;
use App\Models\Candidate;
use App\Models\User;
use App\Models\GovernanceAuditLog;
use Illuminate\Support\Facades\DB;

class MarkEntryOutlierService
{
    /**
     * Detect outliers for a specific batch.
     */
    public function detectForBatch(MarkImportBatch $batch)
    {
        $marks = RawMark::where('mark_import_batch_id', $batch->id)->get();
        
        foreach ($marks as $mark) {
            $this->checkExtremeMarks($mark);
        }

        $this->checkRepeatedMarksPattern($batch);
        $this->checkFastEntryPattern($batch);
    }

    /**
     * Check for extreme high/low marks for a single record.
     */
    public function checkExtremeMarks(RawMark $mark)
    {
        // Rule 1: Extreme High (e.g., > 48 out of 50 for PSLE)
        if ($mark->total_score >= 48) {
            $this->createOutlier([
                'raw_mark_id' => $mark->id,
                'candidate_id' => $mark->candidate_id,
                'school_id' => $mark->school_id,
                'subject_id' => $mark->subject_id,
                'batch_id' => $mark->mark_import_batch_id,
                'assignment_id' => $mark->assignment_id,
                'officer_id' => $mark->batch->created_by ?? null,
                'outlier_type' => 'Extreme High',
                'severity' => 'medium',
                'observed_value' => $mark->total_score,
                'message' => "Candidate scored an exceptionally high mark ({$mark->total_score}).",
            ]);
        }

        // Rule 2: Extreme Low (e.g., 0 while others are high - need context)
        if ($mark->total_score == 0) {
            $this->createOutlier([
                'raw_mark_id' => $mark->id,
                'candidate_id' => $mark->candidate_id,
                'school_id' => $mark->school_id,
                'subject_id' => $mark->subject_id,
                'batch_id' => $mark->mark_import_batch_id,
                'assignment_id' => $mark->assignment_id,
                'officer_id' => $mark->batch->created_by ?? null,
                'outlier_type' => 'Extreme Low',
                'severity' => 'low',
                'observed_value' => $mark->total_score,
                'message' => "Candidate scored zero marks.",
            ]);
        }
    }

    /**
     * Check if an officer is entering marks too fast.
     */
    public function checkFastEntryPattern(MarkImportBatch $batch)
    {
        // Get marks created in the last 1 minute for this officer
        $startTime = now()->subMinute();
        $count = RawMark::where('mark_import_batch_id', $batch->id)
            ->where('created_at', '>=', $startTime)
            ->count();

        if ($count > 30) { // e.g., > 30 marks per minute is suspicious for manual entry
            $this->createOutlier([
                'batch_id' => $batch->id,
                'officer_id' => $batch->created_by,
                'outlier_type' => 'Too-Fast Entry',
                'severity' => 'high',
                'observed_value' => $count,
                'message' => "Officer entered {$count} marks in 1 minute. Suspicious speed for manual entry.",
            ]);
        }
    }

    /**
     * Check if many candidates have the exact same mark.
     */
    public function checkRepeatedMarksPattern(MarkImportBatch $batch)
    {
        $patterns = RawMark::where('mark_import_batch_id', $batch->id)
            ->select('paper_1_marks', DB::raw('count(*) as count'))
            ->groupBy('paper_1_marks')
            ->having('count', '>', 10) // e.g., > 10 identical marks in one batch
            ->get();

        foreach ($patterns as $p) {
            $this->createOutlier([
                'batch_id' => $batch->id,
                'outlier_type' => 'Repeated Marks Pattern',
                'severity' => 'medium',
                'observed_value' => $p->count,
                'message' => "Detected {$p->count} identical scores of {$p->paper_1_marks} in this batch.",
            ]);
        }
    }

    /**
     * Internal helper to create or update an outlier record.
     */
    protected function createOutlier(array $data)
    {
        // Avoid duplicate pending outliers for the same reason
        $exists = MarkEntryOutlier::where([
            'batch_id' => $data['batch_id'] ?? null,
            'raw_mark_id' => $data['raw_mark_id'] ?? null,
            'outlier_type' => $data['outlier_type'],
            'status' => 'pending'
        ])->exists();

        if ($exists) return;

        if (!\Illuminate\Support\Facades\Schema::hasTable('mark_entry_outliers')) {
            return;
        }

        // Auto-fill context from batch if provided
        if (isset($data['batch_id']) && !isset($data['exam_year_id'])) {
            $batch = MarkImportBatch::find($data['batch_id']);
            if ($batch) {
                $data['exam_year_id'] = $batch->exam_year_id;
                $data['region_id'] = $batch->region_id;
                $data['district_id'] = $batch->district_id;
            }
        }

        $outlier = MarkEntryOutlier::create($data);
        
        // Audit log
        GovernanceAuditLog::log(
            'OUTLIER_DETECTED',
            $outlier->id,
            null,
            ['type' => $outlier->outlier_type, 'severity' => $outlier->severity]
        );
        
        return $outlier;
    }

    /**
     * Resolve an outlier.
     */
    public function resolveOutlier(MarkEntryOutlier $outlier, User $user, $comment = null)
    {
        $outlier->update([
            'status' => 'resolved',
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'verification_comment' => $comment
        ]);

        GovernanceAuditLog::log(
            'OUTLIER_RESOLVED',
            $outlier->id,
            $user->id,
            ['comment' => $comment]
        );
    }

    /**
     * Verify an outlier as correct.
     */
    public function verifyOutlier(MarkEntryOutlier $outlier, User $user, $comment = null)
    {
        $outlier->update([
            'status' => 'verified',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_comment' => $comment
        ]);

        GovernanceAuditLog::log(
            'OUTLIER_VERIFIED',
            $outlier->id,
            $user->id,
            ['comment' => $comment]
        );
    }
}
