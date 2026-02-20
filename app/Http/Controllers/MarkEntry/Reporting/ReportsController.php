<?php

namespace App\Http\Controllers\MarkEntry\Reporting;

use App\Http\Controllers\Controller;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\ReportExport;
use App\Services\MarkEntry\Reporting\ReportAnalyticsService;
use App\Services\MarkEntry\Reporting\ReportCsvExportService;
use App\Services\MarkEntry\Reporting\ReportScoresheetPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    public function __construct(
        private ReportScoresheetPdfService $scoresheetService,
        private ReportCsvExportService $csvExportService,
        private ReportAnalyticsService $analyticsService,
    ) {}

    // ==================== SCORESHEET PDF ====================

    /**
     * Generate filled scoresheet PDF for a single subject
     */
    public function scoresheetPdf(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'school_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $data = $this->scoresheetService->generateFilledScoresheet(
                $request->exam_year_id,
                $request->school_id,
                $request->subject_id,
                $mode
            );

            ReportExport::log('scoresheet_pdf', 'single_subject', [
                'exam_year_id' => $request->exam_year_id,
                'school_id' => $request->school_id,
                'subject_id' => $request->subject_id,
                'mode' => $mode,
            ]);

            $viewName = $mode === 'all' ? 'mark-entry.pdf.filled-scoresheet-draft' : 'mark-entry.pdf.filled-scoresheet';
            $pdf = Pdf::loadView($viewName, $data)
                ->setPaper('a4', 'landscape')
                ->setOption('enable-local-file-access', true);

            $filename = sprintf('%s_%s_%s_filled_scoresheet.pdf',
                $data['school']->code,
                $data['subject']->code,
                $data['exam_year']->year_label
            );

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Scoresheet PDF generation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get subjects with marks for scoresheet generation
     */
    public function scoresheetSubjects(Request $request)
    {
        $request->validate([
            'school_id' => 'required|integer',
            'exam_year_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        $subjects = $this->scoresheetService->getSubjectsWithMarks(
            $request->school_id,
            $request->exam_year_id,
            $mode
        );

        return response()->json(['data' => $subjects]);
    }

    /**
     * Generate filled scoresheet ZIP for all subjects at a school
     */
    public function scoresheetSchoolZip(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'school_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $result = $this->scoresheetService->generateSchoolZip(
                $request->exam_year_id,
                $request->school_id,
                $mode
            );

            ReportExport::log('scoresheet_pdf', 'school_zip', [
                'exam_year_id' => $request->exam_year_id,
                'school_id' => $request->school_id,
            ]);

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('School scoresheet ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Generate filled scoresheet ZIP for all schools in a district
     */
    public function scoresheetDistrictZip(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'district_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $result = $this->scoresheetService->generateDistrictZip(
                $request->exam_year_id,
                $request->district_id,
                $mode
            );

            ReportExport::log('scoresheet_pdf', 'district_zip', [
                'exam_year_id' => $request->exam_year_id,
                'district_id' => $request->district_id,
            ], 'district');

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('District scoresheet ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Generate filled scoresheet ZIP for all schools in a region
     */
    public function scoresheetRegionZip(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'region_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $result = $this->scoresheetService->generateRegionZip(
                $request->exam_year_id,
                $request->region_id,
                $mode
            );

            ReportExport::log('scoresheet_pdf', 'region_zip', [
                'exam_year_id' => $request->exam_year_id,
                'region_id' => $request->region_id,
            ], 'region');

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Region scoresheet ZIP failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ==================== CSV EXPORT ====================

    /**
     * Export marks CSV for a single school + subject
     */
    public function csvExportSchoolSubject(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'school_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $result = $this->csvExportService->exportSchoolSubjectCsv(
                $request->school_id,
                $request->subject_id,
                $request->exam_year_id,
                $mode
            );

            ReportExport::log('csv_export', 'school_subject', [
                'exam_year_id' => $request->exam_year_id,
                'school_id' => $request->school_id,
                'subject_id' => $request->subject_id,
            ]);

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Export all subjects for a school as ZIP
     */
    public function csvExportSchoolZip(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'school_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $result = $this->csvExportService->exportSchoolZip(
                $request->school_id,
                $request->exam_year_id,
                $mode
            );

            ReportExport::log('csv_export', 'school_zip', [
                'exam_year_id' => $request->exam_year_id,
                'school_id' => $request->school_id,
            ]);

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Export all schools in a district as ZIP
     */
    public function csvExportDistrictZip(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'district_id' => 'required|integer',
            'mode' => 'nullable|in:approved,all',
        ]);

        $mode = $request->input('mode', 'approved');

        try {
            $result = $this->csvExportService->exportDistrictZip(
                $request->district_id,
                $request->exam_year_id,
                $mode
            );

            ReportExport::log('csv_export', 'district_zip', [
                'exam_year_id' => $request->exam_year_id,
                'district_id' => $request->district_id,
            ], 'district');

            return response()->download($result['file_path'], $result['filename'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ==================== ANALYTICS ====================

    /**
     * Get completion rates
     */
    public function analyticsCompletion(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'district_id' => 'nullable|integer',
        ]);

        $data = $this->analyticsService->getCompletionRates(
            $request->exam_year_id,
            $request->district_id
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get mark distribution for a subject
     */
    public function analyticsDistribution(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'subject_id' => 'required|integer',
        ]);

        $data = $this->analyticsService->getMarkDistribution(
            $request->exam_year_id,
            $request->subject_id
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get anomalies
     */
    public function analyticsAnomalies(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'subject_id' => 'nullable|integer',
        ]);

        $data = $this->analyticsService->getAnomalies(
            $request->exam_year_id,
            $request->subject_id,
            $request->integer('limit', 20)
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Get missing marks heatmap
     */
    public function analyticsHeatmap(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'district_id' => 'nullable|integer',
        ]);

        $data = $this->analyticsService->getMissingMarksHeatmap(
            $request->exam_year_id,
            $request->district_id
        );

        return response()->json(['data' => $data]);
    }

    // ==================== SUMMARY REPORT ====================

    /**
     * Get summary report data
     */
    public function summaryReport(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'district_id' => 'nullable|integer',
        ]);

        $data = $this->analyticsService->getSummaryReport(
            $request->exam_year_id,
            $request->district_id
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Export summary report as PDF
     */
    public function summaryReportPdf(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'district_id' => 'nullable|integer',
        ]);

        try {
            $data = $this->analyticsService->getSummaryReport(
                $request->exam_year_id,
                $request->district_id
            );

            $examYear = \App\Models\ExamYear::findOrFail($request->exam_year_id);

            ReportExport::log('summary_report', 'pdf_export', [
                'exam_year_id' => $request->exam_year_id,
                'district_id' => $request->district_id,
            ]);

            $pdf = Pdf::loadView('mark-entry.pdf.summary-report', array_merge($data, [
                'examYear' => $examYear,
            ]))
                ->setPaper('a4', 'portrait')
                ->setOption('enable-local-file-access', true);

            $filename = sprintf('ACSEE_Summary_Report_%s_%s.pdf', $examYear->year_label, now()->format('Ymd'));

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ==================== DIAGNOSTICS ====================

    /**
     * Diagnostics endpoint to debug marks visibility issues
     */
    public function diagnostics(Request $request)
    {
        $request->validate([
            'exam_year_id' => 'required|integer',
            'school_id' => 'required|integer',
            'subject_id' => 'nullable|integer',
        ]);

        $examYear = ExamYear::findOrFail($request->exam_year_id);
        $yearValue = (int) $examYear->year_label;

        $query = MarkImportBatch::where('exam_year', $yearValue)
            ->where('school_id', $request->school_id);

        if ($request->subject_id) {
            $query->where('subject_id', $request->subject_id);
        }

        $batches = $query->get();

        $statusCounts = $batches->groupBy('status')->map->count();
        $totalMarks = RawMark::whereIn('mark_import_batch_id', $batches->pluck('id'))->count();
        $errorFreeMarks = RawMark::whereIn('mark_import_batch_id', $batches->pluck('id'))->where('has_errors', false)->count();
        $errorMarks = RawMark::whereIn('mark_import_batch_id', $batches->pluck('id'))->where('has_errors', true)->count();

        return response()->json([
            'data' => [
                'exam_year_id' => $request->exam_year_id,
                'exam_year_label' => $examYear->year_label,
                'school_id' => $request->school_id,
                'subject_id' => $request->subject_id,
                'total_batches' => $batches->count(),
                'status_counts' => $statusCounts,
                'total_marks_imported' => $totalMarks,
                'error_free_marks' => $errorFreeMarks,
                'error_marks' => $errorMarks,
                'latest_batch' => $batches->sortByDesc('created_at')->first()?->only(['id', 'batch_code', 'status', 'total_records', 'created_at']),
                'batches' => $batches->map(fn($b) => [
                    'id' => $b->id,
                    'subject_id' => $b->subject_id,
                    'status' => $b->status,
                    'total_records' => $b->total_records,
                    'error_records' => $b->error_records,
                    'imported_at' => $b->imported_at,
                ]),
            ],
        ]);
    }
}
