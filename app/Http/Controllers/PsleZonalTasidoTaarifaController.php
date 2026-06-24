<?php

namespace App\Http\Controllers;

use App\Models\GovernanceAuditLog;
use App\Models\SystemSetting;
use App\Services\Results\TasidoMockTaarifaDataService;
use App\Services\Results\PsleZonalTasidoTaarifaPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PsleZonalTasidoTaarifaController extends Controller
{
    public function __construct(
        protected TasidoMockTaarifaDataService $dataService,
        protected PsleZonalTasidoTaarifaPdfService $pdfService
    ) {}

    public function show(Request $request)
    {
        $examYearValue = $this->activeYear();

        if (!$this->isAdminUser()) {
            $this->checkPublicationStatus($examYearValue);
        }

        // Fetch persisted settings from system_settings
        $savedSettings = SystemSetting::getSetting('psle_tasido_report_settings', []);
        
        // Merge request parameters/overrides with saved settings
        $overrides = array_merge($savedSettings, $request->only([
            'report_title', 'cover_title', 'subtitle', 'office_heading',
            'secretariat', 'exam_dates', 'main_heading', 'font_family',
            'orientation', 'margin_top', 'margin_bottom', 'margin_left',
            'margin_right', 'show_logo', 'reo_name', 'rto_name', 'rso_name',
            'exam_coordinator_name', 'marking_center', 'moderation_region',
            'production_days', 'marking_days', 'markers_count', 'students_assistants_count',
            'budget_amount', 'risso_machine_count', 'risso_machine_value',
            'exam_start_date', 'exam_end_date', 'collaborating_regions',
            'prepared_by_title', 'approved_by_title'
        ]));

        try {
            $reportData = $this->dataService->getReportData($examYearValue, $overrides);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        // Audit Logging
        $snapshotId = $reportData['meta']['snapshot_id'] ?? null;
        GovernanceAuditLog::log('result_book_viewed', auth()->id(), auth()->id(), [
            'zonal' => true,
            'region_name' => 'TASIDO',
            'exam_year' => $examYearValue,
            'snapshot_id' => $snapshotId,
            'type' => 'tasido_report'
        ]);

        if ($request->query('show_settings') === '1') {
            return view('evaluations.psle.zonalwise.taarifa-tasido.settings', [
                'examYear' => $examYearValue,
                'settings' => $overrides,
            ]);
        }

        return view('evaluations.psle.zonalwise.taarifa-tasido.show', [
            'examYear' => $examYearValue,
            'data' => $reportData,
            'inputs' => array_merge($reportData['operational'], $overrides),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $examYearValue = $this->activeYear();
        
        // Ensure admin or authorized user
        abort_unless($this->isAdminUser(), 403, 'Unauthorized');

        $settings = $request->only([
            'report_title', 'cover_title', 'subtitle', 'office_heading',
            'secretariat', 'exam_dates', 'main_heading', 'font_family',
            'orientation', 'margin_top', 'margin_bottom', 'margin_left',
            'margin_right', 'show_logo', 'reo_name', 'rto_name', 'rso_name',
            'exam_coordinator_name', 'marking_center', 'moderation_region',
            'production_days', 'marking_days', 'markers_count', 'students_assistants_count',
            'budget_amount', 'risso_machine_count', 'risso_machine_value',
            'exam_start_date', 'exam_end_date', 'collaborating_regions',
            'prepared_by_title', 'approved_by_title'
        ]);

        SystemSetting::setSetting('psle_tasido_report_settings', $settings, 'json', 'Settings for TAARIFA MOCK DRS VII 2026 TASIDO');

        return redirect()->route('evaluations.psle.zonalwise.taarifa-tasido')
            ->with('success', 'Mipangilio imehifadhiwa kikamilifu.');
    }

    public function pdf(Request $request)
    {
        $examYearValue = $this->activeYear();

        if (!$this->isAdminUser()) {
            $this->checkPublicationStatus($examYearValue);
        }

        // Load saved settings and merge with request overrides
        $savedSettings = SystemSetting::getSetting('psle_tasido_report_settings', []);
        $overrides = array_merge($savedSettings, $request->only([
            'report_title', 'cover_title', 'subtitle', 'office_heading',
            'secretariat', 'exam_dates', 'main_heading', 'font_family',
            'orientation', 'margin_top', 'margin_bottom', 'margin_left',
            'margin_right', 'show_logo', 'reo_name', 'rto_name', 'rso_name',
            'exam_coordinator_name', 'marking_center', 'moderation_region',
            'production_days', 'marking_days', 'markers_count', 'students_assistants_count',
            'budget_amount', 'risso_machine_count', 'risso_machine_value',
            'exam_start_date', 'exam_end_date', 'collaborating_regions',
            'prepared_by_title', 'approved_by_title'
        ]));

        try {
            $reportData = $this->dataService->getReportData($examYearValue, $overrides);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        // Generate PDF
        $tempPath = tempnam(sys_get_temp_dir(), 'tasido_taarifa_') . '.pdf';
        $this->pdfService->generate($reportData, $tempPath);

        // Audit Logging
        $snapshotId = $reportData['meta']['snapshot_id'] ?? null;
        GovernanceAuditLog::log('result_book_downloaded', auth()->id(), auth()->id(), [
            'zonal' => true,
            'region_name' => 'TASIDO',
            'exam_year' => $examYearValue,
            'snapshot_id' => $snapshotId,
            'overrides_applied' => array_keys($overrides),
            'type' => 'tasido_report_pdf'
        ]);

        $filename = 'Taarifa_Mock_Drs_VII_2026_TASIDO.pdf';

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

    protected function isAdminUser(): bool
    {
        if (!auth()->check()) {
            \Illuminate\Support\Facades\Log::warning('TASIDO Report Admin check failed: User not logged in.');
            return false;
        }
        $user = auth()->user();
        $hasAdminAccess = (bool) $user->is_admin
            || (method_exists($user, 'isAdmin') && $user->isAdmin())
            || in_array(strtolower($user->email ?? ''), ['aggreykigodi@gmail.com', 'agreykigodi@gmail.com'], true);

        if (!$hasAdminAccess) {
            \Illuminate\Support\Facades\Log::warning('TASIDO Report Admin check failed for user: ' . $user->email . ' (ID: ' . $user->id . ', Role: ' . ($user->portal_role ?? 'none') . ', is_admin: ' . ($user->is_admin ? 'true' : 'false') . ')');
        } else {
            \Illuminate\Support\Facades\Log::info('TASIDO Report Admin check succeeded for user: ' . $user->email);
        }

        return $hasAdminAccess;
    }
}
