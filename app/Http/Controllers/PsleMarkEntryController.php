<?php

namespace App\Http\Controllers;

use App\Services\MarkEntry\PsleMarkEntryService;
use App\Services\MarkEntry\PsleScoresheetFpdfService;
use Illuminate\Http\Request;

class PsleMarkEntryController extends Controller
{
    public function __construct(
        private PsleMarkEntryService $service,
        private PsleScoresheetFpdfService $scoresheetService
    ) {
    }

    public function singleValidate(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        return response()->json(
            $this->service->validateSingleCsv(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                (int) $validated['subject_id']
            )
        );
    }

    public function singleCommit(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        return response()->json(
            $this->service->commitSingleCsv(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                (int) $validated['subject_id'],
                auth()->id() ?? 1
            )
        );
    }

    public function schoolValidateZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        return response()->json(
            $this->service->validateSchoolZip(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['school_id']
            )
        );
    }

    public function schoolCommitZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        return response()->json(
            $this->service->commitSchoolZip(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                auth()->id() ?? 1
            )
        );
    }

    public function districtValidateZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        return response()->json(
            $this->service->validateDistrictZip(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['district_id']
            )
        );
    }

    public function districtCommitZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'file' => ['required', 'file', 'mimes:zip'],
        ]);

        return response()->json(
            $this->service->commitDistrictZip(
                $request->file('file'),
                (string) $validated['exam_year'],
                (int) $validated['district_id'],
                auth()->id() ?? 1
            )
        );
    }

    public function recentBatches()
    {
        return response()->json([
            'data' => $this->service->recentBatches(),
        ]);
    }

    public function lifecycleDashboard(Request $request)
    {
        return response()->json([
            'data' => $this->service->lifecycleDashboard($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }

    public function reportsSummary(Request $request)
    {
        return response()->json([
            'data' => $this->service->reportsSummary($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }

    public function reportsExport(Request $request)
    {
        $rows = $this->service->reportsExportRows($request->only([
            'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
        ]));

        $handle = fopen('php://temp', 'r+');
        if (!empty($rows)) {
            fputcsv($handle, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
        } else {
            fputcsv($handle, ['Message']);
            fputcsv($handle, ['No PSLE mark-entry rows matched the current scope.']);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="psle_mark_entry_report.csv"',
        ]);
    }

    public function scoresheetSubjects(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'mode' => ['nullable', 'in:approved,all'],
        ]);

        return response()->json([
            'data' => $this->scoresheetService
                ->getSubjectsWithMarks(
                    (string) $validated['exam_year'],
                    (int) $validated['school_id'],
                    (string) ($validated['mode'] ?? 'approved')
                )
                ->values()
                ->all(),
        ]);
    }

    public function scoresheetPdf(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'mode' => ['nullable', 'in:approved,all'],
            'layout' => ['nullable', 'in:formal,condensed'],
        ]);

        try {
            $result = $this->scoresheetService->generateSingle(
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                (int) $validated['subject_id'],
                (string) ($validated['mode'] ?? 'approved'),
                (string) ($validated['layout'] ?? 'formal'),
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function enteredMarksPdf(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'mode' => ['nullable', 'in:approved'],
        ]);

        try {
            $result = $this->scoresheetService->generateEnteredSingle(
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                (int) $validated['subject_id'],
                'approved',
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function scoresheetSchoolZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'mode' => ['nullable', 'in:approved,all'],
            'layout' => ['nullable', 'in:formal,condensed'],
        ]);

        try {
            $result = $this->scoresheetService->generateSchoolZip(
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                (string) ($validated['mode'] ?? 'approved'),
                (string) ($validated['layout'] ?? 'formal'),
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function scoresheetDistrictZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'mode' => ['nullable', 'in:approved,all'],
            'layout' => ['nullable', 'in:formal,condensed'],
        ]);

        try {
            $result = $this->scoresheetService->generateDistrictZip(
                (string) $validated['exam_year'],
                (int) $validated['district_id'],
                (string) ($validated['mode'] ?? 'approved'),
                (string) ($validated['layout'] ?? 'formal'),
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function scoresheetRegionZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'mode' => ['nullable', 'in:approved,all'],
            'layout' => ['nullable', 'in:formal,condensed'],
        ]);

        try {
            $result = $this->scoresheetService->generateRegionZip(
                (string) $validated['exam_year'],
                (int) $validated['region_id'],
                (string) ($validated['mode'] ?? 'approved'),
                (string) ($validated['layout'] ?? 'formal'),
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function enteredMarksSchoolZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'mode' => ['nullable', 'in:approved'],
        ]);

        try {
            $result = $this->scoresheetService->generateEnteredSchoolZip(
                (string) $validated['exam_year'],
                (int) $validated['school_id'],
                'approved',
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function enteredMarksDistrictZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);

        try {
            $result = $this->scoresheetService->generateEnteredDistrictZip(
                (string) $validated['exam_year'],
                (int) $validated['district_id'],
                'locked',
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function enteredMarksRegionZip(Request $request)
    {
        $validated = $request->validate([
            'exam_year' => ['required', 'string'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ]);

        try {
            $result = $this->scoresheetService->generateEnteredRegionZip(
                (string) $validated['exam_year'],
                (int) $validated['region_id'],
                'locked',
                auth()->id()
            );

            return response()->download($result['file_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function auditSummary(Request $request)
    {
        return response()->json([
            'data' => $this->service->auditSummary($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }

    public function administrationSummary(Request $request)
    {
        return response()->json([
            'data' => $this->service->administrationSummary($request->only([
                'exam_year', 'region_id', 'district_id', 'school_id', 'subject_id',
            ])),
        ]);
    }

    public function submitBatch(Request $request, int $batchId)
    {
        return response()->json(
            $this->service->transitionBatch($batchId, 'submit', $request->user())
        );
    }

    public function approveBatch(Request $request, int $batchId)
    {
        $validated = $request->validate([
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json(
            $this->service->transitionBatch($batchId, 'approve', $request->user(), $validated['feedback'] ?? null)
        );
    }

    public function rejectBatch(Request $request, int $batchId)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        return response()->json(
            $this->service->transitionBatch($batchId, 'reject', $request->user(), $validated['reason'])
        );
    }

    public function lockBatch(Request $request, int $batchId)
    {
        return response()->json(
            $this->service->transitionBatch($batchId, 'lock', $request->user())
        );
    }

    public function unlockBatch(Request $request, int $batchId)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        return response()->json(
            $this->service->transitionBatch($batchId, 'unlock', $request->user(), $validated['reason'])
        );
    }
}
