<?php

namespace App\Jobs;

use App\Models\ExamYear;
use App\Models\ReportJob;
use App\Services\MarkEntry\Reporting\ReportAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Throwable;

class GenerateReportPdfJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public int $reportJobId)
    {
    }

    public function handle(ReportAnalyticsService $analyticsService): void
    {
        $reportJob = ReportJob::findOrFail($this->reportJobId);
        $reportJob->update(['status' => ReportJob::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $params = $reportJob->parameters ?? [];
            $examYearId = (int) $params['exam_year_id'];
            $districtId = isset($params['district_id']) ? (int) $params['district_id'] : null;
            $examYear = ExamYear::findOrFail($examYearId);

            $data = $analyticsService->getSummaryReport($examYearId, $districtId);
            $pdf = Pdf::loadView('mark-entry.pdf.summary-report', array_merge($data, [
                'examYear' => $examYear,
            ]))
                ->setPaper('a4', 'portrait')
                ->setOption('enable-local-file-access', true);

            $targetDir = storage_path('app/reports');
            File::ensureDirectoryExists($targetDir);

            $filename = sprintf('summary_report_%s_%s_%s.pdf', $examYear->year_label, $reportJob->id, now()->format('Ymd_His'));
            $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
            File::put($targetPath, $pdf->output());

            $reportJob->update([
                'status' => ReportJob::STATUS_COMPLETED,
                'file_path' => 'reports/' . $filename,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $reportJob->update([
                'status' => ReportJob::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
