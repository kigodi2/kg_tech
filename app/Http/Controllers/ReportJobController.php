<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReportPdfJob;
use App\Jobs\GenerateSchoolPdfZipJob;
use App\Models\ReportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ReportJobController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_type' => ['required', Rule::in(['mark_entry_scoresheet_zip', 'mark_entry_summary_pdf'])],
            'scope' => ['nullable', Rule::in(['school', 'district', 'region'])],
            'exam_year_id' => ['required', 'integer', 'exists:exam_years,id'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'mode' => ['nullable', Rule::in(['approved', 'all'])],
        ]);

        $reportJob = ReportJob::create([
            'user_id' => $request->user()?->id,
            'report_type' => $validated['report_type'],
            'status' => ReportJob::STATUS_PENDING,
            'parameters' => $validated,
        ]);

        if ($validated['report_type'] === 'mark_entry_scoresheet_zip') {
            GenerateSchoolPdfZipJob::dispatch($reportJob->id);
        } else {
            GenerateReportPdfJob::dispatch($reportJob->id);
        }

        return response()->json([
            'ok' => true,
            'report_job_id' => $reportJob->id,
            'status' => $reportJob->status,
            'status_url' => route('report-jobs.show', $reportJob),
        ], 202);
    }

    public function show(Request $request, ReportJob $reportJob)
    {
        $this->authorizeReportJob($request, $reportJob);

        return response()->json([
            'id' => $reportJob->id,
            'report_type' => $reportJob->report_type,
            'status' => $reportJob->status,
            'file_path' => $reportJob->file_path,
            'download_url' => $reportJob->status === ReportJob::STATUS_COMPLETED
                ? route('report-jobs.download', $reportJob)
                : null,
            'error_message' => $reportJob->error_message,
            'created_at' => $reportJob->created_at?->toIso8601String(),
            'updated_at' => $reportJob->updated_at?->toIso8601String(),
        ]);
    }

    public function download(Request $request, ReportJob $reportJob)
    {
        $this->authorizeReportJob($request, $reportJob);

        abort_unless($reportJob->status === ReportJob::STATUS_COMPLETED && $reportJob->file_path, 404);
        abort_unless(Storage::disk('local')->exists($reportJob->file_path), 404);

        return Storage::disk('local')->download($reportJob->file_path);
    }

    private function authorizeReportJob(Request $request, ReportJob $reportJob): void
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && (int) $reportJob->user_id !== (int) $user->id)) {
            abort(403);
        }
    }
}
