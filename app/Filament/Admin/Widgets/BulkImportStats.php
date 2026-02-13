<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\BulkImport;
use Illuminate\Support\Collection;

class BulkImportStats extends Widget
{
    protected static string $view = 'filament.admin.widgets.bulk-import-stats';

    protected static ?int $sort = 3;

    public function getRecentImports(): Collection
    {
        return BulkImport::with('createdBy', 'examYear')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function getImportStats(): array
    {
        return [
            'pending' => BulkImport::where('status', 'pending')->count(),
            'processing' => BulkImport::where('status', 'processing')->count(),
            'completed' => BulkImport::where('status', 'completed')->count(),
            'failed' => BulkImport::where('status', 'failed')->count(),
        ];
    }
}
