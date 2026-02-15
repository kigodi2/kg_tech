<?php

namespace App\Services\MarkEntry\Audit;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryChange;
use App\Models\RawMark;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

class MarkEntryAuditService {

    /**
     * Log a batch-level action (unlock, lock, etc.)
     */
    public function logAction(
        MarkImportBatch $batch,
        string $action,
        $user,
        array $details = []
    ): void {
        \Log::info("Batch {$action}: {$batch->id}", [
            'batch_id' => $batch->id,
            'action' => $action,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'details' => $details,
            'timestamp' => now(),
        ]);
    }

    /**
     * Log a mark entry change
     */
    public function logChange(
        RawMark $mark,
        $user,
        string $changeType,
        string $fieldName,
        $oldValue,
        $newValue,
        ?string $reason = null,
        ?string $ipAddress = null
    ): MarkEntryChange {
        
        return MarkEntryChange::create([
            'raw_mark_id' => $mark->id,
            'changed_by' => $user->id,
            'change_type' => $changeType,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'changed_at' => now(),
            'ip_address' => $ipAddress ?? request()->ip(),
        ]);
    }

    /**
     * Get audit trail for a batch
     */
    public function getBatchAuditTrail(MarkImportBatch $batch, int $perPage = 50): Paginator {
        return MarkEntryChange::whereHas('mark', function ($q) use ($batch) {
            $q->where('mark_import_batch_id', $batch->id);
        })
            ->with(['mark', 'changedByUser'])
            ->orderBy('changed_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get audit trail for a mark
     */
    public function getMarkAuditTrail(RawMark $mark): Collection {
        return MarkEntryChange::where('raw_mark_id', $mark->id)
            ->with('changedByUser')
            ->orderBy('changed_at', 'asc')
            ->get();
    }

    /**
     * Get user activity in mark entry
     */
    public function getUserActivity($userId, int $perPage = 50): Paginator {
        return MarkEntryChange::where('changed_by', $userId)
            ->with(['mark', 'changedByUser'])
            ->orderBy('changed_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity by change type
     */
    public function getActivityByType(string $changeType): Collection {
        return MarkEntryChange::where('change_type', $changeType)
            ->with(['mark', 'changedByUser'])
            ->orderBy('changed_at', 'desc')
            ->get();
    }

    /**
     * Get batch activity summary
     */
    public function getBatchActivitySummary(MarkImportBatch $batch): array {
        $changes = MarkEntryChange::whereHas('mark', function ($q) use ($batch) {
            $q->where('mark_import_batch_id', $batch->id);
        })->get();

        return [
            'total_changes' => $changes->count(),
            'by_change_type' => $changes->groupBy('change_type')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'by_user' => $changes->groupBy('changed_by')
                ->map(function ($group) {
                    $user = $group->first()->changedByUser;
                    return [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'changes' => $group->count(),
                    ];
                })
                ->values()
                ->toArray(),
            'date_range' => [
                'from' => $changes->min('changed_at'),
                'to' => $changes->max('changed_at'),
            ],
        ];
    }

    /**
     * Check if mark was modified after validation
     */
    public function wasModifiedAfterValidation(RawMark $mark): bool {
        $batch = $mark->batch;
        
        if (!$batch->validated_at) {
            return false;
        }

        return MarkEntryChange::where('raw_mark_id', $mark->id)
            ->where('changed_at', '>', $batch->validated_at)
            ->exists();
    }

    /**
     * Get modification report for batch
     */
    public function getModificationReport(MarkImportBatch $batch): array {
        $marks = $batch->rawMarks()->get();
        $modifiedMarks = [];

        foreach ($marks as $mark) {
            if ($this->wasModifiedAfterValidation($mark)) {
                $modifiedMarks[] = [
                    'mark_id' => $mark->id,
                    'candidate_id' => $mark->candidate_id,
                    'changes' => $this->getMarkAuditTrail($mark)->toArray(),
                ];
            }
        }

        return [
            'total_marks' => $marks->count(),
            'modified_marks' => count($modifiedMarks),
            'modification_percentage' => $marks->count() > 0 
                ? round((count($modifiedMarks) / $marks->count()) * 100, 2)
                : 0,
            'modified_marks_details' => $modifiedMarks,
        ];
    }
}
