<?php

namespace App\Services\MarkEntry\Analytics;

use App\Models\MarkImportBatch;
use App\Models\RawMark;
use Illuminate\Support\Collection;

class MarkAnalyticsService {

    /**
     * Get overall mark entry analytics
     */
    public function getOverallAnalytics(): array {
        $batches = MarkImportBatch::all();
        
        return [
            'total_batches' => $batches->count(),
            'draft_batches' => $batches->where('lifecycle_state', 'draft')->count(),
            'validated_batches' => $batches->where('lifecycle_state', 'validated')->count(),
            'pending_moderation' => $batches->where('lifecycle_state', 'awaiting_moderation')->count(),
            'approved_batches' => $batches->where('lifecycle_state', 'approved')->count(),
            'rejected_batches' => $batches->where('lifecycle_state', 'rejected')->count(),
            'submitted_batches' => $batches->where('lifecycle_state', 'submitted')->count(),
            'total_marks_imported' => RawMark::count(),
            'marks_with_errors' => RawMark::where('has_errors', true)->count(),
        ];
    }

    /**
     * Get analytics by exam year
     */
    public function getByExamYear(): array {
        return MarkImportBatch::selectRaw(
            'exam_year,
            COUNT(*) as total_batches,
            SUM(CASE WHEN lifecycle_state = "draft" THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN lifecycle_state = "validated" THEN 1 ELSE 0 END) as validated,
            SUM(CASE WHEN lifecycle_state = "approved" THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN lifecycle_state = "submitted" THEN 1 ELSE 0 END) as submitted,
            SUM(total_records) as total_records,
            SUM(valid_records) as valid_records,
            SUM(error_records) as error_records'
        )
        ->groupBy('exam_year')
        ->orderBy('exam_year', 'desc')
        ->get()
        ->toArray();
    }

    /**
     * Get analytics by subject
     */
    public function getBySubject(): array {
        return MarkImportBatch::with('subject')
            ->selectRaw(
                'subject_id,
                COUNT(*) as total_batches,
                SUM(total_records) as total_records,
                SUM(valid_records) as valid_records,
                SUM(error_records) as error_records'
            )
            ->groupBy('subject_id')
            ->orderBy('total_batches', 'desc')
            ->get()
            ->map(function ($batch) {
                return [
                    'subject' => $batch->subject?->name,
                    'total_batches' => $batch->total_batches,
                    'total_records' => $batch->total_records,
                    'valid_records' => $batch->valid_records,
                    'error_records' => $batch->error_records,
                    'error_rate' => $batch->total_records > 0 
                        ? round(($batch->error_records / $batch->total_records) * 100, 2) 
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get analytics by school
     */
    public function getBySchool(): array {
        return MarkImportBatch::with('school')
            ->selectRaw(
                'school_id,
                COUNT(*) as total_batches,
                SUM(total_records) as total_records,
                SUM(valid_records) as valid_records,
                SUM(error_records) as error_records'
            )
            ->groupBy('school_id')
            ->orderBy('total_batches', 'desc')
            ->get()
            ->map(function ($batch) {
                return [
                    'school' => $batch->school?->name,
                    'total_batches' => $batch->total_batches,
                    'total_records' => $batch->total_records,
                    'valid_records' => $batch->valid_records,
                    'error_records' => $batch->error_records,
                    'error_rate' => $batch->total_records > 0 
                        ? round(($batch->error_records / $batch->total_records) * 100, 2) 
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get batch processing timeline
     */
    public function getBatchTimeline(MarkImportBatch $batch): array {
        return [
            'created' => $batch->created_at,
            'imported' => $batch->imported_at,
            'validated' => $batch->validated_at,
            'locked' => $batch->locked_at,
            'processed' => $batch->processed_at,
            'lifecycle_history' => json_decode($batch->lifecycle_history ?? '[]', true),
        ];
    }

    /**
     * Get error rate statistics
     */
    public function getErrorRateStats(): array {
        $batches = MarkImportBatch::where('total_records', '>', 0)->get();
        
        if ($batches->isEmpty()) {
            return [
                'average_error_rate' => 0,
                'highest_error_rate' => 0,
                'lowest_error_rate' => 0,
                'batches_with_errors' => 0,
                'error_free_batches' => 0,
            ];
        }

        $errorRates = $batches->map(function ($batch) {
            return ($batch->error_records / $batch->total_records) * 100;
        });

        return [
            'average_error_rate' => round($errorRates->average(), 2),
            'highest_error_rate' => round($errorRates->max(), 2),
            'lowest_error_rate' => round($errorRates->min(), 2),
            'batches_with_errors' => $batches->filter(fn($b) => $b->error_records > 0)->count(),
            'error_free_batches' => $batches->filter(fn($b) => $b->error_records === 0)->count(),
        ];
    }
}
