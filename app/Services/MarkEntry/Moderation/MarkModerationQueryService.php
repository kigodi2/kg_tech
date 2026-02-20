<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MarkModerationQueryService
{

    /**
     * Get batches awaiting moderation with pagination
     */
    public function getPendingReviews(int $perPage = 20): LengthAwarePaginator
    {
        return MarkImportBatch::where(function ($q) {
                $q->whereIn('lifecycle_state', ['awaiting_moderation', 'validated', 'draft', 'imported'])
                  ->orWhereNull('lifecycle_state');
            })
            ->with(['school', 'subject', 'examType', 'latestReview.reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get all moderation reviews for a batch
     */
    public function getBatchReviews(MarkImportBatch $batch): Collection
    {
        return $batch->reviews()
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get moderation statistics
     */
    public function getModeratorStats($userId): array
    {
        return [
            'total_reviews' => MarkModerationReview::where('reviewer_id', $userId)->count(),
            'approved' => MarkModerationReview::where('reviewer_id', $userId)
                ->where('status', 'approved')->count(),
            'rejected' => MarkModerationReview::where('reviewer_id', $userId)
                ->where('status', 'rejected')->count(),
            'pending' => MarkModerationReview::where('reviewer_id', $userId)
                ->where('status', 'pending')->count(),
        ];
    }

    /**
     * Search pending reviews by batch code or school
     */
    public function searchPending(string $query): Collection
    {
        return MarkImportBatch::where('lifecycle_state', 'awaiting_moderation')
            ->where(function ($q) use ($query) {
                $q->where('batch_code', 'like', "%{$query}%")
                    ->orWhereHas('school', function ($sq) use ($query) {
                        $sq->where('name', 'like', "%{$query}%");
                    });
            })
            ->with(['school', 'subject', 'examType'])
            ->get();
    }
}
