<?php

namespace App\Services\MarkEntry;

use App\Models\Candidate;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\SystemEventLog;
use App\Models\User;
use App\Services\MarkImport\MarkImportService;
use App\Services\MarkImport\MarkRowLockingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class PsleMarkEntryService
{
    public function __construct(
        private MarkImportService $importService,
        private MarkRowLockingService $lockingService,
        private MarkBatchStateMachine $stateMachine
    ) {
    }

    public function validateSingleCsv(UploadedFile $file, string $examYearLabel, int $schoolId, int $subjectId): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::findOrFail($schoolId);
        $subject = $this->resolvePsleSubject($subjectId);
        $rows = $this->parseCsvUpload($file);

        return $this->validateCsvRecords($rows, $examYear, $school, $subject, $file->getClientOriginalName());
    }

    public function commitSingleCsv(UploadedFile $file, string $examYearLabel, int $schoolId, int $subjectId, int $userId): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::findOrFail($schoolId);
        $subject = $this->resolvePsleSubject($subjectId);
        $rows = $this->parseCsvUpload($file);
        $validation = $this->validateCsvRecords($rows, $examYear, $school, $subject, $file->getClientOriginalName());

        if (!($validation['can_commit'] ?? false)) {
            return $validation;
        }

        return DB::transaction(function () use ($validation, $examYear, $school, $subject, $file, $userId) {
            $batch = $this->createPsleBatch($examYear, $school, $subject, $userId, 'single_csv', $file->getClientOriginalName());
            $this->persistValidatedRows($batch, $validation['validated_rows'] ?? [], $userId);

            return [
                'success' => true,
                'message' => 'PSLE subject CSV committed successfully.',
                'batch' => $this->batchPayload($batch),
                'totals' => $validation['totals'],
            ];
        });
    }

    public function validateSchoolZip(UploadedFile $file, string $examYearLabel, int $schoolId): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::findOrFail($schoolId);

        return $this->validateZipUpload($file, $examYear, $school, null, 'school_zip');
    }

    public function commitSchoolZip(UploadedFile $file, string $examYearLabel, int $schoolId, int $userId): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::findOrFail($schoolId);
        $validation = $this->validateZipUpload($file, $examYear, $school, null, 'school_zip');

        if (!($validation['can_commit'] ?? false)) {
            return $validation;
        }

        return DB::transaction(function () use ($validation, $examYear, $file, $userId) {
            $batches = [];
            foreach ($validation['validated_groups'] ?? [] as $group) {
                $subject = Subject::findOrFail($group['subject_id']);
                $school = School::findOrFail($group['school_id']);
                $batch = $this->createPsleBatch($examYear, $school, $subject, $userId, 'school_zip', $file->getClientOriginalName());
                $this->persistValidatedRows($batch, $group['validated_rows'] ?? [], $userId);
                $batches[] = $this->batchPayload($batch);
            }

            return [
                'success' => true,
                'message' => 'PSLE school ZIP committed successfully.',
                'batches' => $batches,
                'totals' => $validation['totals'],
            ];
        });
    }

    public function validateDistrictZip(UploadedFile $file, string $examYearLabel, int $districtId): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $district = \App\Models\District::findOrFail($districtId);

        return $this->validateZipUpload($file, $examYear, null, $district, 'district_zip');
    }

    public function commitDistrictZip(UploadedFile $file, string $examYearLabel, int $districtId, int $userId): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $district = \App\Models\District::findOrFail($districtId);
        $validation = $this->validateZipUpload($file, $examYear, null, $district, 'district_zip');

        if (!($validation['can_commit'] ?? false)) {
            return $validation;
        }

        return DB::transaction(function () use ($validation, $examYear, $file, $userId) {
            $batches = [];
            foreach ($validation['validated_groups'] ?? [] as $group) {
                $subject = Subject::findOrFail($group['subject_id']);
                $school = School::findOrFail($group['school_id']);
                $batch = $this->createPsleBatch($examYear, $school, $subject, $userId, 'district_zip', $file->getClientOriginalName());
                $this->persistValidatedRows($batch, $group['validated_rows'] ?? [], $userId);
                $batches[] = $this->batchPayload($batch);
            }

            return [
                'success' => true,
                'message' => 'PSLE district ZIP committed successfully.',
                'batches' => $batches,
                'totals' => $validation['totals'],
            ];
        });
    }

    public function recentBatches(int $limit = 20): array
    {
        $psle = $this->resolvePsleExamType();

        return MarkImportBatch::query()
            ->where('exam_type_id', $psle->id)
            ->with(['school:id,name,code', 'district:id,name', 'subject:id,code,name'])
            ->latest('imported_at')
            ->limit($limit)
            ->get()
            ->map(fn (MarkImportBatch $batch) => $this->batchPayload($batch))
            ->all();
    }

    public function lifecycleDashboard(array $filters = []): array
    {
        $query = $this->basePsleBatchQuery($filters);
        $batches = (clone $query)
            ->latest('imported_at')
            ->limit(150)
            ->get();

        $summary = [
            'total' => $batches->count(),
            'validated' => $batches->where('status', MarkImportBatch::STATUS_VALIDATED)->count(),
            'submitted' => $batches->where('status', MarkImportBatch::STATUS_SUBMITTED)->count(),
            'approved' => $batches->where('status', MarkImportBatch::STATUS_APPROVED)->count(),
            'locked' => $batches->where('status', MarkImportBatch::STATUS_LOCKED)->count(),
            'rejected' => $batches->where('status', MarkImportBatch::STATUS_REJECTED)->count(),
            'rows' => (int) $batches->sum('total_records'),
            'schools' => $batches->pluck('school_id')->filter()->unique()->count(),
            'subjects' => $batches->pluck('subject_id')->filter()->unique()->count(),
            'warnings' => RawMark::query()
                ->whereIn('mark_import_batch_id', $batches->pluck('id'))
                ->where('has_warnings', true)
                ->count(),
        ];

        $subjectBreakdown = $batches
            ->groupBy(fn (MarkImportBatch $batch) => $batch->subject?->code ?? 'UNKNOWN')
            ->map(function (Collection $group, string $code) {
                $sample = $group->first();

                return [
                    'subject_code' => $code,
                    'subject_name' => $sample?->subject?->name ?? '-',
                    'batch_count' => $group->count(),
                    'rows' => (int) $group->sum('total_records'),
                    'locked' => $group->where('status', MarkImportBatch::STATUS_LOCKED)->count(),
                    'pending_review' => $group->where('status', MarkImportBatch::STATUS_SUBMITTED)->count(),
                ];
            })
            ->sortByDesc('rows')
            ->values()
            ->take(8)
            ->all();

        $schoolBreakdown = $batches
            ->groupBy(fn (MarkImportBatch $batch) => $batch->school_id)
            ->map(function (Collection $group) {
                $sample = $group->first();

                return [
                    'school_id' => $sample?->school_id,
                    'school_name' => $sample?->school?->name ?? '-',
                    'council_name' => $sample?->district?->name ?? '-',
                    'batch_count' => $group->count(),
                    'rows' => (int) $group->sum('total_records'),
                    'latest_time' => optional($group->max('imported_at') ?? $sample?->created_at)?->format('Y-m-d H:i:s'),
                ];
            })
            ->sortByDesc('rows')
            ->values()
            ->take(8)
            ->all();

        return [
            'summary' => $summary,
            'batches' => $batches->map(fn (MarkImportBatch $batch) => $this->batchPayload($batch))->all(),
            'subject_breakdown' => $subjectBreakdown,
            'school_breakdown' => $schoolBreakdown,
        ];
    }

    public function transitionBatch(int $batchId, string $action, User $user, ?string $note = null): array
    {
        $batch = $this->psleBatchOrFail($batchId);

        $result = match ($action) {
            'submit' => $this->stateMachine->submit($batch, $user),
            'approve' => $this->stateMachine->approve($batch, $user, $note),
            'reject' => $this->stateMachine->reject($batch, $user, (string) $note),
            'lock' => $this->stateMachine->lock($batch, $user),
            'unlock' => $this->stateMachine->unlock($batch, $user, (string) $note),
            default => throw new \InvalidArgumentException("Unsupported transition action [{$action}]"),
        };

        SystemEventLog::record(
            match ($action) {
                'submit' => SystemEventLog::CAT_SUBMISSION,
                'approve', 'reject' => SystemEventLog::CAT_MODERATION,
                'lock', 'unlock' => SystemEventLog::CAT_LOCKING,
                default => SystemEventLog::CAT_SYSTEM,
            },
            "psle_batch_{$action}",
            SystemEventLog::STATUS_SUCCESS,
            "PSLE batch {$batch->batch_code} {$action} completed.",
            [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'exam_type' => 'PSLE',
                'status' => $batch->fresh()->status,
                'note' => $note,
            ],
            actorUserId: $user->id
        );

        return [
            'success' => true,
            'message' => $result['message'] ?? 'Batch transition completed.',
            'batch' => $this->batchPayload($batch->fresh([
                'school:id,name,code',
                'district:id,name',
                'subject:id,code,name',
                'latestReview.reviewer:id,name',
            ])),
        ];
    }

    public function reportsSummary(array $filters = []): array
    {
        $batches = $this->basePsleBatchQuery($filters)->get();
        $batchIds = $batches->pluck('id');

        $statusRows = collect(MarkImportBatch::STATUSES)
            ->map(function ($label, $status) use ($batches) {
                return [
                    'status' => $status,
                    'label' => $label,
                    'batch_count' => $batches->where('status', $status)->count(),
                    'rows' => (int) $batches->where('status', $status)->sum('total_records'),
                ];
            })
            ->values()
            ->all();

        $subjectRows = $batches
            ->groupBy('subject_id')
            ->map(function (Collection $group) {
                $sample = $group->first();
                return [
                    'subject_code' => $sample?->subject?->code ?? '-',
                    'subject_name' => $sample?->subject?->name ?? '-',
                    'batches' => $group->count(),
                    'rows' => (int) $group->sum('total_records'),
                    'locked' => $group->where('status', MarkImportBatch::STATUS_LOCKED)->count(),
                ];
            })
            ->sortBy('subject_code')
            ->values()
            ->all();

        $schoolRows = $batches
            ->groupBy('school_id')
            ->map(function (Collection $group) {
                $sample = $group->first();
                return [
                    'school_code' => $sample?->school?->code ?? '-',
                    'school_name' => $sample?->school?->name ?? '-',
                    'council_name' => $sample?->district?->name ?? '-',
                    'batches' => $group->count(),
                    'rows' => (int) $group->sum('total_records'),
                    'locked' => $group->where('status', MarkImportBatch::STATUS_LOCKED)->count(),
                    'pending' => $group->whereIn('status', [MarkImportBatch::STATUS_VALIDATED, MarkImportBatch::STATUS_SUBMITTED, MarkImportBatch::STATUS_APPROVED])->count(),
                ];
            })
            ->sortBy('school_name')
            ->values()
            ->all();

        $rawWarnings = RawMark::query()
            ->whereIn('mark_import_batch_id', $batchIds)
            ->where('has_warnings', true)
            ->count();

        return [
            'summary' => [
                'batch_count' => $batches->count(),
                'row_count' => (int) $batches->sum('total_records'),
                'locked_count' => $batches->where('status', MarkImportBatch::STATUS_LOCKED)->count(),
                'warning_count' => $rawWarnings,
            ],
            'status_rows' => $statusRows,
            'subject_rows' => $subjectRows,
            'school_rows' => $schoolRows,
        ];
    }

    public function reportsExportRows(array $filters = []): array
    {
        return $this->basePsleBatchQuery($filters)
            ->latest('imported_at')
            ->get()
            ->map(function (MarkImportBatch $batch) {
                return [
                    'Batch Code' => $batch->batch_code,
                    'Exam Year' => $batch->exam_year,
                    'Council' => $batch->district?->name ?? '-',
                    'School Code' => $batch->school?->code ?? '-',
                    'Primary School' => $batch->school?->name ?? '-',
                    'Subject Code' => $batch->subject?->code ?? '-',
                    'Subject Name' => $batch->subject?->name ?? '-',
                    'Rows' => (int) ($batch->total_records ?? 0),
                    'Status' => strtoupper((string) $batch->status),
                    'Lifecycle State' => strtoupper((string) ($batch->lifecycle_state ?? $batch->status)),
                    'Imported At' => optional($batch->imported_at ?? $batch->created_at)?->format('Y-m-d H:i:s'),
                    'Locked At' => optional($batch->locked_at)?->format('Y-m-d H:i:s'),
                    'Review Feedback' => $batch->latestReview?->feedback ?? '',
                ];
            })
            ->all();
    }

    public function auditSummary(array $filters = []): array
    {
        $query = $this->basePsleBatchQuery($filters);
        $batches = (clone $query)->latest('imported_at')->limit(100)->get();
        $batchIds = $batches->pluck('id');

        $reviews = MarkModerationReview::query()
            ->with(['reviewer:id,name', 'batch:id,batch_code'])
            ->whereIn('mark_import_batch_id', $batchIds)
            ->latest('reviewed_at')
            ->limit(100)
            ->get()
            ->map(function (MarkModerationReview $review) {
                return [
                    'type' => 'review',
                    'time' => optional($review->reviewed_at ?? $review->created_at)?->format('Y-m-d H:i:s'),
                    'batch_code' => $review->batch?->batch_code ?? '-',
                    'action' => strtoupper((string) $review->status),
                    'actor' => $review->reviewer?->name ?? 'System',
                    'message' => $review->feedback ?: 'Moderation review recorded.',
                ];
            });

        $events = SystemEventLog::query()
            ->with('actor:id,name')
            ->where(function ($query) {
                $query->whereIn('category', [
                    SystemEventLog::CAT_IMPORT,
                    SystemEventLog::CAT_MODERATION,
                    SystemEventLog::CAT_SUBMISSION,
                    SystemEventLog::CAT_LOCKING,
                    SystemEventLog::CAT_EXPORT,
                    SystemEventLog::CAT_ADMIN,
                ])->orWhere('action', 'like', 'psle_batch_%');
            })
            ->latest('created_at')
            ->limit(120)
            ->get()
            ->filter(function (SystemEventLog $event) use ($batchIds) {
                $batchId = data_get($event->context, 'batch_id');
                return $batchId ? $batchIds->contains((int) $batchId) : false;
            })
            ->map(function (SystemEventLog $event) {
                return [
                    'type' => 'event',
                    'time' => optional($event->created_at)?->format('Y-m-d H:i:s'),
                    'batch_code' => data_get($event->context, 'batch_code', '-'),
                    'action' => strtoupper(str_replace('_', ' ', (string) $event->action)),
                    'actor' => $event->actor?->name ?? 'System',
                    'message' => $event->message,
                    'status' => $event->status,
                ];
            });

        $timeline = $reviews
            ->concat($events)
            ->sortByDesc('time')
            ->values()
            ->take(120)
            ->all();

        return [
            'summary' => [
                'events' => count($timeline),
                'reviews' => $reviews->count(),
                'imports' => $batches->count(),
                'locked' => $batches->where('status', MarkImportBatch::STATUS_LOCKED)->count(),
            ],
            'timeline' => $timeline,
        ];
    }

    public function administrationSummary(array $filters = []): array
    {
        $latestBatch = $this->basePsleBatchQuery($filters)->latest('imported_at')->first();
        $schools = School::query()->where('source_system', 'NECTA_PSLE_2025')->count();
        $subjects = Subject::query()->where('exam_type_id', $this->resolvePsleExamType()->id)->count();

        return [
            'settings' => [
                ['label' => 'Active intake modes', 'value' => 'Single CSV, School ZIP, District ZIP'],
                ['label' => 'Current exam year', 'value' => (string) (ExamYear::active()->value('year_label') ?? ExamYear::latest('year_label')->value('year_label') ?? '-')],
                ['label' => 'Official PSLE subjects', 'value' => (string) $subjects],
                ['label' => 'NECTA-synced schools', 'value' => (string) $schools],
                ['label' => 'Latest committed batch', 'value' => $latestBatch?->batch_code ?? 'No batch committed yet'],
            ],
            'governance' => [
                ['label' => 'Submission locking', 'value' => 'Enabled after moderation approval'],
                ['label' => 'Moderation path', 'value' => 'Validated -> Submitted -> Approved / Rejected -> Locked'],
                ['label' => 'Audit coverage', 'value' => 'Imports, moderation decisions, locking actions, and exports'],
                ['label' => 'Template rule', 'value' => 'Governed PSLE templates with candidate number and PReM No'],
            ],
        ];
    }

    private function basePsleBatchQuery(array $filters = [])
    {
        $psle = $this->resolvePsleExamType();

        return MarkImportBatch::query()
            ->where('exam_type_id', $psle->id)
            ->when(!empty($filters['exam_year']), fn ($query) => $query->where('exam_year', $filters['exam_year']))
            ->when(!empty($filters['region_id']), fn ($query) => $query->where('region_id', $filters['region_id']))
            ->when(!empty($filters['district_id']), fn ($query) => $query->where('district_id', $filters['district_id']))
            ->when(!empty($filters['school_id']), fn ($query) => $query->where('school_id', $filters['school_id']))
            ->when(!empty($filters['subject_id']), fn ($query) => $query->where('subject_id', $filters['subject_id']))
            ->with([
                'school:id,name,code',
                'district:id,name',
                'subject:id,code,name',
                'latestReview.reviewer:id,name',
            ]);
    }

    private function psleBatchOrFail(int $batchId): MarkImportBatch
    {
        return MarkImportBatch::query()
            ->where('id', $batchId)
            ->where('exam_type_id', $this->resolvePsleExamType()->id)
            ->firstOrFail();
    }

    private function validateZipUpload(UploadedFile $file, ExamYear $examYear, ?School $scopedSchool, $scopedDistrict, string $mode): array
    {
        $entries = $this->collectZipEntries($file->getRealPath());
        if (empty($entries)) {
            return [
                'success' => false,
                'status' => 'failed',
                'can_commit' => false,
                'totals' => ['total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 0, 'warnings' => 0],
                'preview' => [],
                'errors' => [['message' => 'No CSV files were found in the ZIP package.']],
                'warnings' => [],
                'validated_groups' => [],
            ];
        }

        $allErrors = [];
        $allWarnings = [];
        $preview = [];
        $validatedGroups = [];
        $totalRows = 0;
        $validRows = 0;
        $invalidRows = 0;

        foreach ($entries as $entry) {
            $rows = $this->parseCsvContent($entry['content']);
            if (count($rows) <= 1) {
                $allErrors[] = ['message' => "{$entry['filename']}: file is empty or has no data rows."];
                continue;
            }

            $header = $this->normalizeHeaderRow($rows[0]);
            if ($header !== $this->expectedHeaders()) {
                $allErrors[] = ['message' => "{$entry['filename']}: invalid CSV headers."];
                continue;
            }

            $dataRows = array_slice($rows, 1);
            $firstNonEmpty = collect($dataRows)->first(fn ($row) => !empty(array_filter($row, fn ($value) => trim((string) $value) !== '')));
            if (!$firstNonEmpty) {
                $allErrors[] = ['message' => "{$entry['filename']}: file has no usable data rows."];
                continue;
            }

            $schoolCode = strtoupper(trim((string) ($firstNonEmpty[4] ?? '')));
            $subjectCode = strtoupper(trim((string) ($firstNonEmpty[5] ?? '')));
            $school = School::where('code', $schoolCode)
                ->orWhere('registration_number', $schoolCode)
                ->first();
            $subject = Subject::query()
                ->where('code', $subjectCode)
                ->where('exam_type_id', $this->resolvePsleExamType()->id)
                ->first();

            if (!$school) {
                $allErrors[] = ['message' => "{$entry['filename']}: school_code {$schoolCode} was not found."];
                continue;
            }

            if (!$subject) {
                $allErrors[] = ['message' => "{$entry['filename']}: subject_code {$subjectCode} was not found in the PSLE catalog."];
                continue;
            }

            if ($scopedSchool && (int) $school->id !== (int) $scopedSchool->id) {
                $allErrors[] = ['message' => "{$entry['filename']}: school_code {$schoolCode} is outside the selected school scope."];
                continue;
            }

            if ($scopedDistrict && (int) $school->district_id !== (int) $scopedDistrict->id) {
                $allErrors[] = ['message' => "{$entry['filename']}: school_code {$schoolCode} is outside the selected council scope."];
                continue;
            }

            $validation = $this->validateCsvRecords($rows, $examYear, $school, $subject, $entry['filename']);
            $totalRows += $validation['totals']['total_rows'] ?? 0;
            $validRows += $validation['totals']['valid_rows'] ?? 0;
            $invalidRows += $validation['totals']['invalid_rows'] ?? 0;
            $allWarnings = array_merge($allWarnings, $validation['warnings'] ?? []);
            $allErrors = array_merge($allErrors, $validation['errors'] ?? []);
            $preview[] = [
                'line' => $entry['filename'],
                'candidate_number' => $school->code,
                'prem_no' => $subject->code,
                'pupil_name' => $mode === 'school_zip' ? $school->name : ($school->name . ' / ' . ($school->district?->name ?? '-')),
                'mark' => (string) ($validation['totals']['total_rows'] ?? 0),
            ];

            if (($validation['can_commit'] ?? false) && !empty($validation['validated_rows'])) {
                $validatedGroups[] = [
                    'school_id' => $school->id,
                    'subject_id' => $subject->id,
                    'validated_rows' => $validation['validated_rows'],
                ];
            }
        }

        $status = $invalidRows > 0 ? ($validRows > 0 ? 'partial' : 'failed') : 'completed';

        return [
            'success' => $status !== 'failed',
            'status' => $status,
            'can_commit' => !empty($validatedGroups) && $invalidRows === 0,
            'totals' => [
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'warnings' => count($allWarnings),
            ],
            'preview' => array_slice($preview, 0, 20),
            'errors' => $allErrors,
            'warnings' => $allWarnings,
            'validated_groups' => $validatedGroups,
        ];
    }

    private function validateCsvRecords(array $rows, ExamYear $examYear, School $school, Subject $subject, string $sourceName): array
    {
        $header = $this->normalizeHeaderRow($rows[0] ?? []);
        $expectedHeaders = $this->expectedHeaders();
        $bulkHeaders = $this->bulkImportHeaders();
        $errors = [];
        $warnings = [];
        $preview = [];
        $validatedRows = [];
        $seenCandidates = [];
        $psle = $this->resolvePsleExamType();
        $dataRows = array_slice($rows, 1);
        $invalidRowCount = 0;

        $mode = 'standard';
        if ($header === $expectedHeaders) {
            $mode = 'standard';
        } elseif ($header === $bulkHeaders) {
            $mode = 'bulk';
        } else {
            $hasRemovedBulkColumns = count(array_intersect($header, ['status', 'remarks'])) > 0;

            return [
                'success' => false,
                'status' => 'failed',
                'can_commit' => false,
                'totals' => ['total_rows' => 0, 'valid_rows' => 0, 'invalid_rows' => 1, 'warnings' => 0, 'duplicate_rows' => 0, 'not_found_rows' => 0, 'locked_rows' => 0, 'existing_rows' => 0],
                'preview' => [],
                'errors' => [[
                    'message' => $hasRemovedBulkColumns
                        ? 'This file has invalid columns. Please download a fresh template and try again. The required columns are CNO, PReM, Name, Sex, Mark.'
                        : "{$sourceName}: expected headers " . implode(', ', ['CNO', 'PReM', 'Name', 'Sex', 'Mark']) . '.'
                ]],
                'warnings' => [],
                'validated_rows' => [],
            ];
        }

        $duplicateRows = 0;
        $notFoundRows = 0;
        $lockedRows = 0;
        $existingRows = 0;

        foreach ($dataRows as $index => $row) {
            if (empty(array_filter($row, fn ($value) => trim((string) $value) !== ''))) {
                continue;
            }

            $line = $index + 2;
            
            if ($mode === 'bulk') {
                $candidateNumber = strtoupper(trim((string) ($row[0] ?? '')));
                $premNo = trim((string) ($row[1] ?? ''));
                $pupilName = trim((string) ($row[2] ?? ''));
                $sex = strtoupper(trim((string) ($row[3] ?? '')));
                $schoolCode = strtoupper((string) ($school->code ?? ''));
                $subjectCode = strtoupper((string) ($subject->code ?? ''));
                $markValue = trim((string) ($row[4] ?? ''));
            } else { // standard
                $candidateNumber = strtoupper(trim((string) ($row[0] ?? '')));
                $premNo = trim((string) ($row[1] ?? ''));
                $pupilName = '';
                $sex = strtoupper(trim((string) ($row[2] ?? '')));
                $schoolCode = strtoupper(trim((string) ($row[3] ?? '')));
                $subjectCode = strtoupper(trim((string) ($row[4] ?? '')));
                $markValue = trim((string) ($row[5] ?? ''));
            }

            $rowErrors = [];
            $rowWarnings = [];
            $existingMark = null;

            if ($candidateNumber === '') $rowErrors[] = "Line {$line}: candidate_number is required.";
            if ($mode !== 'bulk' && $sex === '') $rowErrors[] = "Line {$line}: sex is required.";
            if ($mode !== 'bulk' && $schoolCode === '') $rowErrors[] = "Line {$line}: school_code is required.";
            if ($mode !== 'bulk' && $subjectCode === '') $rowErrors[] = "Line {$line}: subject_code is required.";

            if ($candidateNumber !== '' && isset($seenCandidates[$candidateNumber])) {
                $rowErrors[] = "Line {$line}: duplicate candidate_number {$candidateNumber} in file.";
                $duplicateRows++;
            }
            $seenCandidates[$candidateNumber] = true;

            if ($schoolCode !== '' && $schoolCode !== strtoupper((string) ($school->code ?? ''))) {
                $rowErrors[] = "Line {$line}: school_code {$schoolCode} does not match selected school {$school->code}.";
            }

            if ($subjectCode !== '' && $subjectCode !== strtoupper((string) $subject->code)) {
                $rowErrors[] = "Line {$line}: subject_code {$subjectCode} does not match selected subject {$subject->code}.";
            }

            $candidate = Candidate::where('candidate_id', $candidateNumber)
                ->where('school_id', $school->id)
                ->first();
            if (!$candidate) {
                $rowErrors[] = "Line {$line}: candidate {$candidateNumber} was not found.";
                $notFoundRows++;
            } else {
                if ((int) $candidate->school_id !== (int) $school->id) {
                    $rowErrors[] = "Line {$line}: candidate {$candidateNumber} does not belong to school {$school->code}.";
                }

                $registration = $candidate->examRegistrations()
                    ->where('exam_type_id', $psle->id)
                    ->where(function ($query) use ($examYear) {
                        $query->where('exam_year_id', $examYear->id)
                            ->orWhere('year', (int) $examYear->year_label);
                    })
                    ->exists();

                if (!$registration) {
                    $rowErrors[] = "Line {$line}: candidate {$candidateNumber} is not registered for PSLE {$examYear->year_label}.";
                }

                // PSLE candidates are registered for all subjects, no CandidateSubjectSelection check required.
                if ($psle->code !== 'PSLE') {
                    $hasSelections = $candidate->subjectSelections()
                        ->where('exam_type_id', $psle->id)
                        ->where(function ($query) use ($examYear) {
                            $query->where('exam_year_id', $examYear->id)
                                ->orWhere('year', (int) $examYear->year_label);
                        })
                        ->count();

                    $subjectAllocated = $candidate->subjectSelections()
                        ->where('exam_type_id', $psle->id)
                        ->where('subject_id', $subject->id)
                        ->where(function ($query) use ($examYear) {
                            $query->where('exam_year_id', $examYear->id)
                                ->orWhere('year', (int) $examYear->year_label);
                        })
                        ->exists();

                    if ($hasSelections > 0 && !$subjectAllocated) {
                        $rowErrors[] = "Line {$line}: subject {$subject->code} is not allocated to candidate {$candidateNumber}.";
                    }
                }

                if ($premNo !== '' && strtoupper((string) ($candidate->prem_no ?? '')) !== strtoupper($premNo)) {
                    $rowWarnings[] = "Line {$line}: PReM No differs from registered pupil record.";
                }

                if ($pupilName !== '' && strtoupper($candidate->full_name ?? '') !== strtoupper($pupilName)) {
                    $rowWarnings[] = "Line {$line}: pupil_name differs from registered pupil record.";
                }

                if ($sex !== '' && strtoupper((string) $candidate->gender) !== strtoupper($sex)) {
                    $rowWarnings[] = "Line {$line}: sex differs from registered pupil record.";
                }

                $existingMark = RawMark::where('candidate_id', $candidate->id)
                    ->where('subject_id', $subject->id)
                    ->where('exam_year_id', $examYear->id)
                    ->first();
                if ($existingMark) {
                    $existingRows++;
                    if ($existingMark->is_locked) {
                        $rowErrors[] = "Line {$line}: candidate {$candidateNumber} has a locked mark record that cannot be overwritten.";
                        $lockedRows++;
                    } elseif ($existingMark->batch && $existingMark->batch->status !== 'draft') {
                        $rowErrors[] = "Line {$line}: candidate {$candidateNumber} has a mark record in a committed/approved batch ({$existingMark->batch->batch_code}) that cannot be overwritten.";
                        $lockedRows++;
                    }
                }
            }

            if ($markValue === '') {
                $rowWarnings[] = "Line {$line}: mark is blank. Start Entry treats this as a cleared mark.";
            } elseif (!is_numeric($markValue)) {
                $rowErrors[] = "Line {$line}: mark must be numeric.";
            } elseif ((float) $markValue < 0 || (float) $markValue > 50) {
                $rowErrors[] = "Line {$line}: mark must be between 0 and 50.";
            }

            $preview[] = [
                'line' => $line,
                'row' => $line,
                'candidate_number' => $candidateNumber,
                'cno' => $candidateNumber,
                'prem_no' => $premNo,
                'prem' => $premNo,
                'name' => $candidate ? $candidate->full_name : $pupilName,
                'full_name' => $candidate ? $candidate->full_name : $pupilName,
                'sex' => $sex,
                'mark' => $markValue,
                'valid' => empty($rowErrors),
                'errors' => $rowErrors,
                'warnings' => $rowWarnings,
                'message' => empty($rowErrors) ? (empty($rowWarnings) ? 'Ready to import.' : implode(' ', $rowWarnings)) : implode(' ', $rowErrors),
                'will_update' => isset($existingMark) && $existingMark,
            ];

            if (empty($rowErrors) && $candidate) {
                $validatedRows[] = [
                    'row_number' => $line,
                    'candidate_id' => $candidate->id,
                    'candidate_index_number' => $candidateNumber,
                    'full_name' => $candidate->full_name ?: $pupilName,
                    'paper_1_marks' => $markValue === '' ? null : (float) $markValue,
                    'subject_status' => $markValue === '' ? 'INC' : null,
                    'status_reason' => $markValue === '' ? 'Mark missing from uploaded PSLE file.' : null,
                    'has_warnings' => !empty($rowWarnings),
                    'warning_messages' => $rowWarnings,
                    'has_errors' => false,
                    'error_messages' => [],
                    'raw_data' => [
                        'candidate_number' => $candidateNumber,
                        'prem_no' => $premNo,
                        'sex' => $sex,
                        'school_code' => $schoolCode,
                        'subject_code' => $subjectCode,
                        'mark' => $markValue,
                    ],
                ];
            }

            if (!empty($rowErrors)) {
                $invalidRowCount++;
            }

            foreach ($rowErrors as $message) {
                $errors[] = ['message' => $message];
            }

            foreach ($rowWarnings as $message) {
                $warnings[] = $message;
            }
        }

        $validRows = count($validatedRows);
        $totalRows = count(array_filter($dataRows, fn ($row) => !empty(array_filter($row, fn ($value) => trim((string) $value) !== ''))));
        $invalidRows = $invalidRowCount;
        $status = $invalidRows > 0 ? ($validRows > 0 ? 'partial' : 'failed') : 'completed';

        return [
            'success' => $status !== 'failed',
            'status' => $status,
            'can_commit' => $validRows > 0 && ($mode === 'bulk' || $invalidRows === 0),
            'totals' => [
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'warnings' => count($warnings),
                'duplicate_rows' => $duplicateRows,
                'not_found_rows' => $notFoundRows,
                'locked_rows' => $lockedRows,
                'existing_rows' => $existingRows,
            ],
            'total_count' => $totalRows,
            'valid_count' => $validRows,
            'invalid_count' => $invalidRows,
            'preview' => array_slice($preview, 0, 20),
            'records' => array_slice($preview, 0, 100),
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_rows' => $validatedRows,
        ];
    }

    private function createPsleBatch(ExamYear $examYear, School $school, Subject $subject, int $userId, string $mode, string $filename): MarkImportBatch
    {
        $batch = $this->importService->createBatch((int) $examYear->year_label, (int) $school->id, (int) $subject->id, (string) $userId);
        $batch->update([
            'notes' => trim("PSLE {$mode} import: {$filename}"),
        ]);

        return $batch->fresh();
    }

    private function persistValidatedRows(MarkImportBatch $batch, array $validatedRows, int $userId): void
    {
        $timestamp = now();
        $candidateIds = collect($validatedRows)->pluck('candidate_id')->toArray();
        $existingMarks = DB::table('raw_marks')
            ->whereIn('candidate_id', $candidateIds)
            ->where('subject_id', $batch->subject_id)
            ->where('exam_year_id', $batch->exam_year_id)
            ->get()
            ->keyBy('candidate_id');

        foreach ($validatedRows as $row) {
            $existing = $existingMarks->get($row['candidate_id']);
            
            if ($existing) {
                if ($existing->is_locked) {
                    continue;
                }
                
                DB::table('raw_marks')
                    ->where('id', $existing->id)
                    ->update([
                        'mark_import_batch_id' => $batch->id,
                        'row_number' => $row['row_number'],
                        'paper_1_marks' => $row['paper_1_marks'],
                        'subject_status' => $row['subject_status'],
                        'status_reason' => $row['status_reason'],
                        'has_errors' => false,
                        'has_warnings' => $row['has_warnings'],
                        'warning_messages' => json_encode($row['warning_messages'] ?? []),
                        'error_messages' => json_encode([]),
                        'raw_data' => json_encode($row['raw_data'] ?? []),
                        'updated_at' => $timestamp,
                    ]);
            } else {
                DB::table('raw_marks')->insert([
                    'mark_import_batch_id' => $batch->id,
                    'candidate_id' => $row['candidate_id'],
                    'subject_id' => $batch->subject_id,
                    'exam_year_id' => $batch->exam_year_id,
                    'row_number' => $row['row_number'],
                    'candidate_index_number' => $row['candidate_index_number'],
                    'full_name' => $row['full_name'],
                    'paper_1_marks' => $row['paper_1_marks'],
                    'subject_status' => $row['subject_status'],
                    'status_reason' => $row['status_reason'],
                    'has_errors' => false,
                    'has_warnings' => $row['has_warnings'],
                    'warning_messages' => json_encode($row['warning_messages'] ?? []),
                    'error_messages' => json_encode([]),
                    'raw_data' => json_encode($row['raw_data'] ?? []),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }

        $batch->update([
            'total_records' => count($validatedRows),
            'valid_records' => count($validatedRows),
            'error_records' => 0,
            'status' => MarkImportBatch::STATUS_VALIDATED,
            'lifecycle_state' => 'awaiting_moderation',
            'validated_by' => $userId,
            'validated_at' => now(),
        ]);

        $this->lockingService->lockBatchRows($batch, $userId);
    }

    private function batchPayload(MarkImportBatch $batch): array
    {
        $batch->loadMissing(['school:id,name,code', 'district:id,name', 'subject:id,code,name']);
        $notes = strtoupper((string) ($batch->notes ?? ''));

        return [
            'id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'school_id' => $batch->school_id,
            'subject_id' => $batch->subject_id,
            'mode' => str_contains($notes, 'DISTRICT_ZIP') ? 'district_zip' : (str_contains($notes, 'SCHOOL_ZIP') ? 'school_zip' : 'single_csv'),
            'modeLabel' => str_contains($notes, 'DISTRICT_ZIP') ? 'District Bulk ZIP' : (str_contains($notes, 'SCHOOL_ZIP') ? 'School Bulk ZIP' : 'Single Subject CSV'),
            'fileName' => trim((string) preg_replace('/^PSLE\s+[A-Z_]+\s+IMPORT:\s*/i', '', (string) ($batch->notes ?? ''))),
            'scope' => collect([
                $batch->exam_year,
                $batch->district?->name,
                $batch->school?->name,
                $batch->subject?->code,
            ])->filter()->implode(' · '),
            'total_records' => (int) ($batch->total_records ?? 0),
            'valid_records' => (int) ($batch->valid_records ?? 0),
            'error_records' => (int) ($batch->error_records ?? 0),
            'status' => $batch->status,
            'lifecycle_state' => $batch->lifecycle_state ?: $batch->status,
            'time' => optional($batch->imported_at ?? $batch->created_at)?->format('Y-m-d H:i:s'),
            'school_name' => $batch->school?->name,
            'school_code' => $batch->school?->code,
            'district_name' => $batch->district?->name,
            'subject_code' => $batch->subject?->code,
            'subject_name' => $batch->subject?->name,
            'review_status' => $batch->latestReview?->status,
            'review_feedback' => $batch->latestReview?->feedback,
            'reviewed_by' => $batch->latestReview?->reviewer?->name,
            'submitted_at' => optional($batch->submitted_at)?->format('Y-m-d H:i:s'),
            'approved_at' => optional($batch->approved_at)?->format('Y-m-d H:i:s'),
            'locked_at' => optional($batch->locked_at)?->format('Y-m-d H:i:s'),
            'rejection_reason' => $batch->rejection_reason,
        ];
    }

    private function collectZipEntries(string $zipPath, string $prefix = ''): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return [];
        }

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!$name || str_ends_with($name, '/')) {
                continue;
            }
            if (str_starts_with($name, '__MACOSX/') || str_contains($name, '/__MACOSX/')) {
                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($extension, ['csv', 'txt'], true)) {
                $entries[] = [
                    'filename' => ltrim($prefix . $name, '/'),
                    'content' => (string) $zip->getFromIndex($i),
                ];
                continue;
            }

            if ($extension === 'zip') {
                $tmpPath = tempnam(sys_get_temp_dir(), 'psle_zip_');
                file_put_contents($tmpPath, (string) $zip->getFromIndex($i));
                $entries = array_merge($entries, $this->collectZipEntries($tmpPath, $prefix . pathinfo($name, PATHINFO_FILENAME) . '/'));
                @unlink($tmpPath);
            }
        }

        $zip->close();

        return $entries;
    }

    private function parseCsvUpload(UploadedFile $file): array
    {
        return $this->parseCsvContent((string) file_get_contents($file->getRealPath()));
    }

    private function parseCsvContent(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $lines = array_values(array_filter($lines, fn ($line) => trim((string) $line) !== ''));

        return array_map(fn ($line) => str_getcsv($line), $lines);
    }

    private function normalizeHeaderRow(array $row): array
    {
        return array_map(fn ($value) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))), $row);
    }

    private function expectedHeaders(): array
    {
        return ['candidate_number', 'prem_no', 'sex', 'school_code', 'subject_code', 'mark'];
    }

    private function legacyExpectedHeaders(): array
    {
        return ['candidate_number', 'prem_no', 'pupil_name', 'sex', 'school_code', 'subject_code', 'mark'];
    }

    private function bulkImportHeaders(): array
    {
        return ['cno', 'prem', 'name', 'sex', 'mark'];
    }

    private function resolvePsleExamType(): ExamType
    {
        return ExamType::where('code', 'PSLE')->firstOrFail();
    }

    private function resolvePsleSubject(int $subjectId): Subject
    {
        $psle = $this->resolvePsleExamType();

        return Subject::query()
            ->where('id', $subjectId)
            ->where('exam_type_id', $psle->id)
            ->firstOrFail();
    }

    private function resolveExamYear(string $examYearLabel): ExamYear
    {
        return ExamYear::where('year_label', (string) $examYearLabel)->firstOrFail();
    }
}
