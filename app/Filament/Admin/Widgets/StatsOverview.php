<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Candidate;
use App\Models\BulkImport;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeYear = ExamYear::where('is_active', true)->first();
        $lockedYears = ExamYear::where('is_locked', true)->count();
        $totalSchools = School::count();
        $pendingImports = BulkImport::where('status', 'pending')->count();

        return [
            Stat::make('Active Exam Year', $activeYear?->year_label ?? 'None')
                ->description('Currently active')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Total Schools', $totalSchools)
                ->description('Registered schools')
                ->icon('heroicon-o-building-office-2')
                ->color('info'),

            Stat::make('Locked Years', $lockedYears)
                ->description('Published & locked')
                ->icon('heroicon-o-lock-closed')
                ->color('danger'),

            Stat::make('Pending Imports', $pendingImports)
                ->description('Awaiting processing')
                ->icon('heroicon-o-inbox-stack')
                ->color('warning'),
        ];
    }
}
