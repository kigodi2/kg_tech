<?php

namespace App\Http\Controllers\Api\Results;

use App\Http\Controllers\Controller;
use App\Models\CandidateResult;
use App\Models\ExamType;
use App\Models\FinalOutlierResolution;
use App\Models\GovernanceAuditLog;
use App\Services\Results\AcseeOutliersFpdfService;
use App\Services\Results\Outliers\FinalOutliersService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcseeOutliersApiController extends Controller
{
    /*
     * Outliers Data Sources (RESULTS FINAL MODULE)
     * - subject_marks (FINAL), final_grades (FINAL), candidate_results (FINAL), result_processes (FINAL metadata)
     * - No raw/import staging tables are read in this controller.
     * - Endpoints are read-only; only export actions append governance audit log entries.
     */

    public function __construct(
        private readonly FinalOutliersService $service,
        private readonly AcseeOutliersFpdfService $fpdfService
    )
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->summary($request->user(), $request->all()),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Failed to load outliers summary.'], 500);
        }
    }

    public function candidates(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        try {
            $p = $this->service->candidates($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => $p->items(),
                'meta' => [
                    'total' => $p->total(),
                    'per_page' => $p->perPage(),
                    'current_page' => $p->currentPage(),
                    'last_page' => $p->lastPage(),
                    'from' => $p->firstItem() ?? 0,
                    'to' => $p->lastItem() ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Failed to load candidate outliers.'], 500);
        }
    }

    public function schools(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);
        try {
            $p = $this->service->schools($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => $p->items(),
                'meta' => [
                    'total' => $p->total(),
                    'per_page' => $p->perPage(),
                    'current_page' => $p->currentPage(),
                    'last_page' => $p->lastPage(),
                    'from' => $p->firstItem() ?? 0,
                    'to' => $p->lastItem() ?? 0,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Failed to load school outliers.'], 500);
        }
    }

    public function subjects(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->subjects($request->user(), $request->all()),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Failed to load subject distributions.'], 500);
        }
    }

    public function exportPdf(Request $request)
    {
        return $this->exportDelimited($request, 'pdf');
    }

    public function exportXlsx(Request $request)
    {
        return $this->exportDelimited($request, 'xlsx');
    }

    public function approveAll(Request $request): JsonResponse
    {
        $this->authorize('viewResults', CandidateResult::class);

        $validated = $request->validate([
            'exam_year_id' => 'nullable|integer',
            'region_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'council_id' => 'nullable|integer',
            'school_id' => 'nullable|integer',
            'q' => 'nullable|string|max:255',
            'active_tab' => 'nullable|in:candidates,schools,subjects,missing',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $filters = [
                'exam_year_id' => $validated['exam_year_id'] ?? null,
                'region_id' => $validated['region_id'] ?? null,
                'district_id' => $validated['district_id'] ?? null,
                'council_id' => $validated['council_id'] ?? null,
                'school_id' => $validated['school_id'] ?? null,
                'q' => $validated['q'] ?? null,
            ];
            $activeTab = (string) ($validated['active_tab'] ?? 'candidates');
            $examTypeId = (int) ExamType::query()->where('code', 'ACSEE')->value('id');
            $year = $this->resolveYearFromExamYearId($validated['exam_year_id'] ?? null);

            $resolved = 0;
            $skipped = 0;
            $failed = 0;
            $reasons = [];

            DB::beginTransaction();
            try {
                if ($activeTab === 'candidates') {
                    $rows = $this->collectCandidateRows($request, $filters);
                    if (empty($rows)) {
                        $skipped = 0;
                    } else {
                        $now = Carbon::now();
                        foreach ($rows as $row) {
                            $candidateId = (int) ($row['candidate_id'] ?? 0);
                            $subjectId = (int) ($row['subject_id'] ?? 0);
                            if ($candidateId <= 0 || $subjectId <= 0) {
                                $skipped++;
                                continue;
                            }
                            FinalOutlierResolution::updateOrCreate(
                                [
                                    'exam_type_id' => $examTypeId,
                                    'year' => $year,
                                    'resolution_key' => "candidate:{$candidateId}:{$subjectId}",
                                ],
                                [
                                    'tab' => 'candidates',
                                    'candidate_id' => $candidateId,
                                    'subject_id' => $subjectId,
                                    'school_id' => isset($row['school_id']) ? (int) $row['school_id'] : null,
                                    'resolved_by' => auth()->id(),
                                    'resolved_at' => $now,
                                    'note' => $validated['note'] ?? null,
                                ]
                            );
                            $resolved++;
                        }
                    }
                } elseif ($activeTab === 'schools') {
                    $rows = $this->collectSchoolRows($request, $filters);
                    $now = Carbon::now();
                    foreach ($rows as $row) {
                        $schoolId = (int) ($row['school_id'] ?? 0);
                        if ($schoolId <= 0) {
                            $skipped++;
                            continue;
                        }
                        FinalOutlierResolution::updateOrCreate(
                            [
                                'exam_type_id' => $examTypeId,
                                'year' => $year,
                                'resolution_key' => "school:{$schoolId}",
                            ],
                            [
                                'tab' => 'schools',
                                'school_id' => $schoolId,
                                'resolved_by' => auth()->id(),
                                'resolved_at' => $now,
                                'note' => $validated['note'] ?? null,
                            ]
                        );
                        $resolved++;
                    }
                } else {
                    $reasons[] = 'Approve All is supported for Candidate Outliers and School Outliers tabs.';
                    $skipped = 0;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            $summary = $this->service->summary($request->user(), $filters);
            $candidateTotal = (int) ($summary['flagged_candidates'] ?? 0);
            $flaggedSchools = (int) ($summary['flagged_schools'] ?? 0);
            $missingWithheld = $summary['missing_withheld'] ?? [];
            $missingCount = (int) (($missingWithheld['INC'] ?? 0) + ($missingWithheld['ABS'] ?? 0) + ($missingWithheld['X'] ?? 0));
            $matched = $candidateTotal + $flaggedSchools + $missingCount;

            GovernanceAuditLog::create([
                'admin_id' => auth()->id(),
                'user_id' => auth()->id(),
                'action' => GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                'data' => [
                    'event' => 'results_outliers_approve_all',
                    'exam_type' => 'ACSEE',
                    'filters' => $filters,
                    'matched' => $matched,
                    'flagged_candidates' => $candidateTotal,
                    'flagged_schools' => $flaggedSchools,
                    'missing_withheld' => $missingWithheld,
                    'note' => $validated['note'] ?? null,
                    'active_tab' => $activeTab,
                    'review_acknowledgement' => false,
                    'non_mutating' => false,
                    'ip' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => $resolved > 0 ? 'Final outliers flags approved successfully.' : 'No actionable rows were approved.',
                'stats' => [
                    'matched' => $matched,
                    'resolved' => $resolved,
                    'skipped' => $skipped,
                    'failed' => $failed,
                    'flagged_candidates' => $candidateTotal,
                    'flagged_schools' => $flaggedSchools,
                    'missing_withheld' => $missingWithheld,
                ],
                'reasons' => $reasons,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve all final outlier flags.',
            ], 500);
        }
    }

    private function exportDelimited(Request $request, string $format)
    {
        $this->authorize('exportResults', CandidateResult::class);

        $rows = $this->service->candidates($request->user(), array_merge($request->all(), ['per_page' => 1000]))->items();

        try {
            GovernanceAuditLog::create([
                'admin_id' => auth()->id(),
                'user_id' => auth()->id(),
                // Must use allowed enum values on governance_audit_logs.action.
                'action' => 'import_completed',
                'data' => [
                    'event' => 'results_outliers_export_' . $format,
                    'exam_type' => 'ACSEE',
                    'filters' => $request->all(),
                    'rows' => count($rows),
                    'export_format' => $format,
                    'non_mutating' => true,
                    'ip' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($format === 'pdf') {
            $filename = 'acsee-final-outliers-' . now()->format('Ymd-His') . '.pdf';
            $tempPath = tempnam(sys_get_temp_dir(), 'acsee_outliers_');
            if ($tempPath === false) {
                abort(500, 'Unable to prepare PDF export file.');
            }
            $pdfPath = $tempPath . '.pdf';
            @rename($tempPath, $pdfPath);

            $this->fpdfService->generate(
                $rows,
                $request->all(),
                now(),
                auth()->user()?->name ?? 'System',
                $pdfPath
            );

            return response()->download($pdfPath, $filename)->deleteFileAfterSend(true);
        }

        $filename = 'acsee-final-outliers-' . now()->format('Ymd-His') . '.csv';
        $header = ['Index Number', 'Candidate Name', 'School', 'Subject', 'Mark', 'Z Score', 'Flag', 'Division', 'GPA'];
        $csv = implode(',', $header) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', [
                $this->csvSafe($row['index_number'] ?? ''),
                $this->csvSafe($row['candidate_name'] ?? ''),
                $this->csvSafe($row['school_name'] ?? ''),
                $this->csvSafe($row['subject_name'] ?? ''),
                $this->csvSafe((string) ($row['mark'] ?? '')),
                $this->csvSafe((string) ($row['z_score'] ?? '')),
                $this->csvSafe($row['flag'] ?? ''),
                $this->csvSafe((string) ($row['division'] ?? '')),
                $this->csvSafe((string) ($row['gpa'] ?? '')),
            ]) . "\n";
        }
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function csvSafe(string $value): string
    {
        $escaped = str_replace('"', '""', $value);
        return '"' . $escaped . '"';
    }

    private function collectCandidateRows(Request $request, array $filters): array
    {
        $page = 1;
        $lastPage = 1;
        $rows = [];

        do {
            $paginator = $this->service->candidates($request->user(), array_merge($filters, [
                'page' => $page,
                'per_page' => 100,
            ]));
            foreach ($paginator->items() as $item) {
                $rows[] = $item;
            }
            $lastPage = max(1, (int) $paginator->lastPage());
            $page++;
        } while ($page <= $lastPage);

        return $rows;
    }

    private function collectSchoolRows(Request $request, array $filters): array
    {
        $page = 1;
        $lastPage = 1;
        $rows = [];

        do {
            $paginator = $this->service->schools($request->user(), array_merge($filters, [
                'page' => $page,
                'per_page' => 100,
            ]));
            foreach ($paginator->items() as $item) {
                if (!empty($item['is_flagged'])) {
                    $rows[] = $item;
                }
            }
            $lastPage = max(1, (int) $paginator->lastPage());
            $page++;
        } while ($page <= $lastPage);

        return $rows;
    }

    private function resolveYearFromExamYearId(?int $examYearId): int
    {
        if (!$examYearId) {
            $active = \App\Models\ExamYear::query()->where('is_active', true)->first();
            return (int) ($active?->year_label ?? now()->year);
        }

        $examYear = \App\Models\ExamYear::query()->find($examYearId);
        return (int) ($examYear?->year_label ?? now()->year);
    }
}
