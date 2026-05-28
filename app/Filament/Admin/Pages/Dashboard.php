<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\BatchesPendingReviewWidget;
use App\Filament\Admin\Widgets\BulkImportStats;
use App\Filament\Admin\Widgets\ExamYearOverview;
use App\Filament\Admin\Widgets\ImportFailuresWidget;
use App\Filament\Admin\Widgets\RecentAuditLogsWidget;
use App\Filament\Admin\Widgets\SecurityAlertsWidget;
use App\Filament\Admin\Widgets\StatsOverview;
use App\Filament\Admin\Widgets\SystemActivityPulseWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function mount(): void
    {
        $this->redirect('/admin/dashboard');
    }

    public function getWidgets(): array
    {
        return [
            SystemActivityPulseWidget::class,
            SecurityAlertsWidget::class,
            StatsOverview::class,
            BatchesPendingReviewWidget::class,
            BulkImportStats::class,
            ExamYearOverview::class,
            ImportFailuresWidget::class,
            RecentAuditLogsWidget::class,
        ];
    }
}
