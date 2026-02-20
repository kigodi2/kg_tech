<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BulkImport;
use App\Models\MarkImportBatch;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ImportFailuresWidget extends Widget
{
  protected static string $view = 'filament.admin.widgets.import-failures';

  protected static ?int $sort = 4;

  protected int | string | array $columnSpan = 'full';

  /**
   * Get bulk import failures from the last 7 days
   */
  public function get7DayFailures(): int
  {
    return BulkImport::where('status', 'failed')
      ->where('created_at', '>=', Carbon::now()->subDays(7))
      ->count();
  }

  /**
   * Get mark import batches with errors from the last 7 days
   */
  public function getBatchesWithErrors(): int
  {
    return MarkImportBatch::where('error_records', '>', 0)
      ->where('updated_at', '>=', Carbon::now()->subDays(7))
      ->count();
  }

  /**
   * Get top 5 failing schools (by failed import count in last 7 days)
   */
  public function getTopFailingSchools(): Collection
  {
    return BulkImport::with('school')
      ->where('status', 'failed')
      ->where('created_at', '>=', Carbon::now()->subDays(7))
      ->groupBy('school_id')
      ->selectRaw('school_id, COUNT(*) as failure_count')
      ->orderByDesc('failure_count')
      ->limit(5)
      ->get()
      ->map(function ($import) {
        return [
          'school' => $import->school?->name ?? 'Unknown',
          'count' => $import->failure_count,
        ];
      });
  }

  /**
   * Get top 5 failing districts (by failed import count in last 7 days)
   */
  public function getTopFailingDistricts(): Collection
  {
    return BulkImport::with('district')
      ->where('status', 'failed')
      ->where('created_at', '>=', Carbon::now()->subDays(7))
      ->groupBy('district_id')
      ->selectRaw('district_id, COUNT(*) as failure_count')
      ->orderByDesc('failure_count')
      ->limit(5)
      ->get()
      ->map(function ($import) {
        return [
          'district' => $import->district?->name ?? 'Unknown',
          'count' => $import->failure_count,
        ];
      });
  }

  /**
   * Get yesterday's failures for trend
   */
  public function getYesterdayFailures(): int
  {
    return BulkImport::where('status', 'failed')
      ->whereDate('created_at', Carbon::yesterday())
      ->count();
  }

  /**
   * Get today's failures for trend
   */
  public function getTodayFailures(): int
  {
    return BulkImport::where('status', 'failed')
      ->whereDate('created_at', Carbon::today())
      ->count();
  }
}
