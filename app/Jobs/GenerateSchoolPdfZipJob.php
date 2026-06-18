<?php

namespace App\Jobs;

use App\Models\ReportJob;
use App\Services\MarkEntry\Reporting\ReportScoresheetPdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Throwable;

class GenerateSchoolPdfZipJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public function __construct(public int $reportJobId)
    {
    }

    public function handle(ReportScoresheetPdfService $scoresheetService): void
    {
        $reportJob = ReportJob::findOrFail($this->reportJobId);
        $reportJob->update(['status' => ReportJob::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $params = $reportJob->parameters ?? [];
            $scope = (string) ($params['scope'] ?? 'school');
            $mode = (string) ($params['mode'] ?? 'approved');
            $examYearId = (int) $params['exam_year_id'];

            $result = match ($scope) {
                'district' => $scoresheetService->generateDistrictZip($examYearId, (int) $params['district_id'], $mode),
                'region' => $scoresheetService->generateRegionZip($examYearId, (int) $params['region_id'], $mode),
                default => $scoresheetService->generateSchoolZip($examYearId, (int) $params['school_id'], $mode),
            };

            $targetDir = storage_path('app/exports');
            File::ensureDirectoryExists($targetDir);

            $targetPath = $targetDir . DIRECTORY_SEPARATOR . now()->format('Ymd_His') . '_' . basename($result['filename']);
            File::move($result['file_path'], $targetPath);

            $reportJob->update([
                'status' => ReportJob::STATUS_COMPLETED,
                'file_path' => 'exports/' . basename($targetPath),
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
