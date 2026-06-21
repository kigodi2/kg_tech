<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\GovernanceAuditLog;
use App\Services\Results\RegionalResultBookDataService;
use App\Services\Results\PsleRegionalResultBookPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PsleRegionalResultBookController extends Controller
{
    public function __construct(
        protected RegionalResultBookDataService $dataService,
        protected PsleRegionalResultBookPdfService $pdfService
    ) {}

    public function show(Request $request, $id)
    {
        $region = Region::findOrFail($id);
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        // Get preview with current inputs or defaults
        $overrides = $request->only([
            'reo_name', 'rto_name', 'rso_name', 'exam_coordinator_name',
            'marking_center', 'moderation_region', 'production_days',
            'marking_days', 'markers_count', 'students_assistants_count',
            'budget_amount', 'risso_machine_count', 'risso_machine_value',
            'exam_start_date', 'exam_end_date', 'collaborating_regions',
            'prepared_by_title', 'approved_by_title'
        ]);

        try {
            $reportData = $this->dataService->getReportData($region, $examYearValue, $overrides);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return view('evaluations.psle.regionalwise.result-book.show', [
            'region' => $region,
            'examYear' => $examYearValue,
            'data' => $reportData,
            'inputs' => array_merge($reportData['operational'], $overrides),
        ]);
    }

    public function pdf(Request $request, $id)
    {
        $region = Region::findOrFail($id);
        $examYearValue = $this->activeYear();

        if (!(auth()->check() && auth()->user()->is_admin)) {
            $this->checkPublicationStatus($examYearValue);
        }

        $overrides = $request->only([
            'reo_name', 'rto_name', 'rso_name', 'exam_coordinator_name',
            'marking_center', 'moderation_region', 'production_days',
            'marking_days', 'markers_count', 'students_assistants_count',
            'budget_amount', 'risso_machine_count', 'risso_machine_value',
            'exam_start_date', 'exam_end_date', 'collaborating_regions',
            'prepared_by_title', 'approved_by_title'
        ]);

        try {
            $reportData = $this->dataService->getReportData($region, $examYearValue, $overrides);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        // Generate PDF
        $tempPath = tempnam(sys_get_temp_dir(), 'result_book_') . '.pdf';
        $this->pdfService->generate($region, $reportData, $tempPath);

        // Audit Logging
        $snapshotId = $reportData['meta']['snapshot_id'] ?? null;
        GovernanceAuditLog::log('result_book_downloaded', auth()->id(), auth()->id(), [
            'region_id' => $region->id,
            'region_name' => $region->name,
            'exam_year' => $examYearValue,
            'snapshot_id' => $snapshotId,
            'overrides_applied' => array_keys($overrides),
        ]);

        $filename = 'Kitabu_cha_Matokeo_PSLE_' . str_replace(' ', '_', $region->name) . '_' . $examYearValue . '.pdf';

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    protected function activeYear(): int
    {
        $activeYear = DB::table('exam_years')->where('is_active', true)->first();
        return $activeYear ? (int) $activeYear->year_label : (int) date('Y');
    }

    private function checkPublicationStatus(int $examYear): void
    {
        $hasActiveCorrection = DB::table('school_result_correction_batches')
            ->where('exam_year', $examYear)
            ->where('exam_type', 'PSLE')
            ->whereIn('status', ['open', 'corrected', 'recalculated'])
            ->exists();

        if ($hasActiveCorrection) {
            abort(403, "Results are temporarily under correction. Please check again later.");
        }

        $publication = DB::table('psle_result_publications as prp')
            ->join('result_snapshots as rs', 'rs.id', '=', 'prp.snapshot_id')
            ->where('prp.exam_year_id', function ($query) use ($examYear) {
                $query->select('id')->from('exam_years')->where('year_label', $examYear)->limit(1);
            })
            ->where('prp.status', 'published')
            ->where('rs.is_active', true)
            ->where('rs.is_rolled_back', false)
            ->exists();

        if (!$publication) {
            abort(403, "Results for {$examYear} are not yet published.");
        }
    }
}
