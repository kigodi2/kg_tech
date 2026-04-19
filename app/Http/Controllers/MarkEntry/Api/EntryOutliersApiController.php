<?php

namespace App\Http\Controllers\MarkEntry\Api;

use App\Http\Controllers\Controller;
use App\Services\MarkEntry\Outliers\EntryOutliersService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EntryOutliersApiController extends Controller
{
    /*
     * Outliers Data Sources (ENTRY MODULE)
     * - Uses entry-stage sources only: mark_import_batches, raw_marks, mark_import_run_errors.
     * - Does not read final results tables for this QA endpoint.
     * - Read-only endpoints; no mark/result mutations.
     */

    public function __construct(private readonly EntryOutliersService $service)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->summary($request->user(), $request->all()),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $paginator = $this->service->list($request->user(), $request->all());

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
            ],
        ]);
    }

    public function tabStats(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->tabStatusSummary($request->user(), $request->all()),
        ]);
    }

    public function candidate(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->candidateDetails($request->user(), $id, $request->all()),
        ]);
    }

    public function batch(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->batchDetails($request->user(), $id, $request->all()),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->all();
        $rows = collect();
        $page = 1;
        $lastPage = 1;
        $perPage = 100;

        do {
            $paginator = $this->service->list($request->user(), array_merge($filters, [
                'page' => $page,
                'per_page' => $perPage,
            ]));
            $rows = $rows->concat($paginator->items());
            $lastPage = max(1, (int) $paginator->lastPage());
            $page++;
        } while ($page <= $lastPage);

        $tab = strtolower((string) ($filters['tab'] ?? 'integrity'));
        $tabLabel = match ($tab) {
            'missing' => 'Missing Required Paper Marks',
            'swings' => 'Extreme Score Swings',
            default => 'Data Integrity Flags',
        };

        $filename = 'entry-outliers-qa-' . $tab . '-' . now()->format('Ymd-His') . '.pdf';

        $pdf = Pdf::loadView('exports.entry-outliers-pdf', [
            'rows' => $rows->values()->all(),
            'filters' => $filters,
            'tab' => $tab,
            'tabLabel' => $tabLabel,
            'generatedAt' => now(),
            'generatedBy' => auth()->user()?->name ?? 'System',
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
