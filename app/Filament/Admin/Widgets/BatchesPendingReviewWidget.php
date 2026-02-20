<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\MarkImportBatch;
use Illuminate\Support\Carbon;

class BatchesPendingReviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $draft = MarkImportBatch::where('status', MarkImportBatch::STATUS_DRAFT)->count();
        $validated = MarkImportBatch::where('status', MarkImportBatch::STATUS_VALIDATED)->count();
        $locked = MarkImportBatch::where('status', MarkImportBatch::STATUS_LOCKED)->count();
        $processed = MarkImportBatch::where('status', MarkImportBatch::STATUS_PROCESSED)->count();

        $draftTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $draftTrend[] = MarkImportBatch::where('status', MarkImportBatch::STATUS_DRAFT)
                ->whereDate('created_at', $date)
                ->count();
        }

        return [
            Stat::make('Draft Batches', $draft)
                ->description('Awaiting validation')
                ->icon('heroicon-o-document')
                ->color('gray')
                ->chart($draftTrend),

            Stat::make('Validated Batches', $validated)
                ->description('Ready for locking')
                ->icon('heroicon-o-check-circle')
                ->color('info'),

            Stat::make('Locked Batches', $locked)
                ->description('Awaiting processing')
                ->icon('heroicon-o-lock-closed')
                ->color('warning'),

            Stat::make('Processed Batches', $processed)
                ->description('Completed')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
