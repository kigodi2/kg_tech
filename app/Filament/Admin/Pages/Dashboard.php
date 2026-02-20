<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\StatsOverviewWidget;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\BulkImport;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\StatsOverview::class,
            \App\Filament\Admin\Widgets\ExamYearOverview::class,
            \App\Filament\Admin\Widgets\BatchesPendingReviewWidget::class,
            \App\Filament\Admin\Widgets\ImportFailuresWidget::class,
            \App\Filament\Admin\Widgets\SecurityAlertsWidget::class,
            \App\Filament\Admin\Widgets\RecentAuditLogsWidget::class,
        ];
    }
}
