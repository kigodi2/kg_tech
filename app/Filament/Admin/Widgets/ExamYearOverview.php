<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\ExamYear;
use Illuminate\Support\Collection;

class ExamYearOverview extends Widget
{
    protected static string $view = 'filament.admin.widgets.exam-year-overview';

    protected static ?int $sort = 2;

    public function getExamYears(): Collection
    {
        return ExamYear::orderByDesc('created_at')->limit(10)->get();
    }

    public function getActiveYear(): ?ExamYear
    {
        return ExamYear::where('is_active', true)->first();
    }

    public function getLockedCount(): int
    {
        return ExamYear::where('is_locked', true)->count();
    }
}
