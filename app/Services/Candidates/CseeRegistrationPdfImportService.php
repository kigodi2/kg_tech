<?php

namespace App\Services\Candidates;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectMarks;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class CseeRegistrationPdfImportService
{
    public function __construct(
        private readonly CseeCandidateSubjectService $subjectService
    ) {
    }

    public function validatePdf(UploadedFile $file, ?string $examYear = null): array
    {
        return $this->buildReport($this->parsePdfFile($file), $examYear, false);
    }

    public function validatePdfBatch(array $files, ?string $examYear = null): array
    {
        $reports = [];

        foreach ($files as $index => $file) {
            try {
                if (!$file instanceof UploadedFile) {
                    throw new \RuntimeException('Invalid PDF upload payload.');
                }

                $report = $this->validatePdf($file, $examYear);
                $report['source_file_name'] = $file->getClientOriginalName();
                $reports[] = $report;
            } catch (\Throwable $e) {
                $reports[] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'total_rows' => 0,
                    'create_count' => 0,
                    'update_count' => 0,
                    'skip_count' => 0,
                    'error_count' => 1,
                    'warning_count' => 0,
                    'can_import' => false,
                    'errors' => [],
                    'warnings' => [],
                    'rows' => [],
                    'summary' => [
                        'school_code' => null,
                        'school_name' => null,
                        'exam_year' => $examYear,
                    ],
                    'commit_payload' => null,
                    'source_file_name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : ('file_' . ($index + 1)),
                ];
            }
        }

        return $this->aggregateBatchReports($reports, false);
    }

    public function commitPdf(UploadedFile $file, ?string $examYear = null): array
    {
        return $this->buildReport($this->parsePdfFile($file), $examYear, true);
    }

    public function commitPdfBatch(array $files, ?string $examYear = null): array
    {
        $validation = $this->validatePdfBatch($files, $examYear);
        $payloads = collect($validation['schools'] ?? [])
            ->filter(fn (array $school) => !empty($school['can_import']) && !empty($school['commit_payload']))
            ->map(fn (array $school) => $school['commit_payload'])
            ->values()
            ->all();

        return $this->commitParsedPayloadBatch($payloads, $examYear);
    }

    public function validateParsedPayload(array $parsed, ?string $examYear = null): array
    {
        return $this->buildReport($parsed, $examYear, false);
    }

    public function commitParsedPayload(array $parsed, ?string $examYear = null): array
    {
        return $this->buildReport($parsed, $examYear, true);
    }

    public function validateParsedPayloadBatch(array $parsedPayloads, ?string $examYear = null): array
    {
        $reports = [];

        foreach ($parsedPayloads as $index => $parsedPayload) {
            try {
                $report = $this->validateParsedPayload($parsedPayload, $examYear);
                $report['source_file_name'] = $parsedPayload['source_file_name'] ?? ('school_' . ($index + 1) . '.pdf');
                $reports[] = $report;
            } catch (\Throwable $e) {
                $reports[] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'total_rows' => 0,
                    'create_count' => 0,
                    'update_count' => 0,
                    'skip_count' => 0,
                    'error_count' => 1,
                    'warning_count' => 0,
                    'can_import' => false,
                    'errors' => [],
                    'warnings' => [],
                    'rows' => [],
                    'summary' => [
                        'school_code' => $parsedPayload['school_code'] ?? null,
                        'school_name' => $parsedPayload['school_name'] ?? null,
                        'exam_year' => $examYear ?: ($parsedPayload['exam_year'] ?? null),
                    ],
                    'commit_payload' => null,
                    'source_file_name' => $parsedPayload['source_file_name'] ?? ('school_' . ($index + 1) . '.pdf'),
                ];
            }
        }

        return $this->aggregateBatchReports($reports, false);
    }

    public function commitParsedPayloadBatch(array $parsedPayloads, ?string $examYear = null): array
    {
        $reports = [];

        foreach ($parsedPayloads as $index => $parsedPayload) {
            try {
                $report = $this->commitParsedPayload($parsedPayload, $examYear);
                $report['source_file_name'] = $parsedPayload['source_file_name'] ?? ('school_' . ($index + 1) . '.pdf');
                $reports[] = $report;
            } catch (\Throwable $e) {
                $reports[] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                    'total_rows' => 0,
                    'create_count' => 0,
                    'update_count' => 0,
                    'skip_count' => 0,
                    'error_count' => 1,
                    'warning_count' => 0,
                    'can_import' => false,
                    'errors' => [],
                    'warnings' => [],
                    'rows' => [],
                    'summary' => [
                        'school_code' => $parsedPayload['school_code'] ?? null,
                        'school_name' => $parsedPayload['school_name'] ?? null,
                        'exam_year' => $examYear ?: ($parsedPayload['exam_year'] ?? null),
                    ],
                    'commit_payload' => null,
                    'source_file_name' => $parsedPayload['source_file_name'] ?? ('school_' . ($index + 1) . '.pdf'),
                ];
            }
        }

        return $this->aggregateBatchReports($reports, true);
    }

    public function parseLayoutText(string $text): array
    {
        $lines = preg_split("/\\r\\n|\\n|\\r/", $text) ?: [];

        $parsed = [
            'exam_year' => null,
            'school_code' => null,
            'school_name' => null,
            'rows' => [],
        ];

        $currentHeader = null;

        foreach ($lines as $line) {
            $line = $this->sanitizeExtractedText(rtrim((string) $line, "\r"));

            if (preg_match("/CSEE\\s+(\\d{4})\\s*:\\s*([A-Z0-9]{5})\\s*-\\s*(.+)$/i", trim($line), $matches)) {
                $parsed['exam_year'] = $matches[1];
                $parsed['school_code'] = strtoupper(trim($this->sanitizeExtractedText($matches[2])));
                $parsed['school_name'] = trim($this->sanitizeExtractedText($matches[3]));
                continue;
            }

            if (str_contains($line, 'CANDIDATE') && str_contains($line, 'FULL NAME')) {
                preg_match_all('/\b\d{3}\b/', $line, $matches, PREG_OFFSET_CAPTURE);
                $codes = $matches[0] ?? [];

                if (!empty($codes)) {
                    $currentHeader = [
                        'codes' => array_map(fn ($item) => [
                            'code' => (string) $item[0],
                            'offset' => (int) $item[1],
                        ], $codes),
                    ];
                }
                continue;
            }

            if (!$currentHeader || !preg_match('/^\s*([A-Z0-9]{5}-\d{4})\b/i', $line, $candidateMatch)) {
                continue;
            }

            $row = $this->parseCandidateLine($line, $currentHeader['codes']);
            if (!$row) {
                continue;
            }

            $row['candidate_id'] = strtoupper($row['candidate_id']);
            $row['school_code'] = strtoupper(substr($row['candidate_id'], 0, 5));
            $parsed['rows'][] = $row;
        }

        return $parsed;
    }

    private function buildReport(array $parsed, ?string $examYearInput, bool $commit): array
    {
        if (empty($parsed['school_code']) || empty($parsed['school_name'])) {
            throw new \RuntimeException('The PDF header could not be recognized. Upload the NECTA registration printout that shows "CSEE YEAR : SCHOOLCODE - SCHOOL NAME".');
        }

        if (empty($parsed['rows'])) {
            throw new \RuntimeException('No candidate rows were detected in the registration PDF. Make sure the file is a text-based NECTA registration printout, not a scanned image PDF.');
        }

        $examType = ExamType::query()->where('code', 'CSEE')->firstOrFail();
        $examYearValue = $examYearInput ?: ($parsed['exam_year'] ?? null) ?: optional(ExamYear::query()->where('is_active', true)->first())->year_label;
        $examYear = ExamYear::query()->where('year_label', (string) $examYearValue)->first();

        if (!$examYear) {
            throw new \RuntimeException('No matching CSEE exam year was found for the uploaded registration PDF.');
        }

        $this->ensureOfficialCseeSubjectsSeeded($examType);

        $subjectMap = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->where('is_active', true)
            ->get(['id', 'code', 'name'])
            ->keyBy(fn (Subject $subject) => strtoupper((string) $subject->code));

        $rows = [];
        $errors = [];
        $createCount = 0;
        $updateCount = 0;
        $skipCount = 0;

        $schoolMap = School::query()
            ->whereIn('code', collect($parsed['rows'])
                ->map(fn (array $row) => strtoupper((string) ($row['school_code'] ?? $parsed['school_code'] ?? '')))
                ->filter()
                ->unique()
                ->all())
            ->get()
            ->keyBy(fn (School $school) => strtoupper((string) $school->code));

        $candidateMap = Candidate::query()
            ->whereIn('candidate_id', collect($parsed['rows'])->pluck('candidate_id')->all())
            ->get()
            ->keyBy(fn (Candidate $candidate) => strtoupper((string) $candidate->candidate_id));

        $existingCandidateIds = $candidateMap->pluck('id')->map(fn ($id) => (int) $id)->all();

        $existingSelectionsByCandidateId = CandidateSubjectSelection::query()
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->when(
                !empty($existingCandidateIds),
                fn ($query) => $query->whereIn('candidate_id', $existingCandidateIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->get(['candidate_id', 'subject_id'])
            ->groupBy('candidate_id')
            ->map(fn (Collection $group) => $group->pluck('subject_id')->map(fn ($id) => (int) $id)->all());

        $markedSubjectIdsByCandidateId = SubjectMarks::query()
            ->where('exam_type_id', $examType->id)
            ->when(
                !empty($existingCandidateIds),
                fn ($query) => $query->whereIn('candidate_id', $existingCandidateIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->get(['candidate_id', 'subject_id'])
            ->groupBy('candidate_id')
            ->map(fn (Collection $group) => $group->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all());

        $existingRegistrations = CandidateExamRegistration::query()
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->when(
                !empty($existingCandidateIds),
                fn ($query) => $query->whereIn('candidate_id', $existingCandidateIds),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->pluck('candidate_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $queuedRegistrationUpserts = [];
        $queuedSelectionUpserts = [];
        $queuedSelectionDeletes = [];
        $now = Carbon::now();

        $rowProcessor = function () use (
            &$rows,
            &$errors,
            &$createCount,
            &$updateCount,
            &$skipCount,
            &$candidateMap,
            &$existingSelectionsByCandidateId,
            &$existingRegistrations,
            &$queuedRegistrationUpserts,
            &$queuedSelectionUpserts,
            &$queuedSelectionDeletes,
            $parsed,
            $subjectMap,
            $schoolMap,
            $markedSubjectIdsByCandidateId,
            $examYear,
            $examType,
            $commit,
            $now
        ) {
            foreach ($parsed['rows'] as $index => $row) {
                $rowNumber = $index + 1;
                $messages = [];
                $status = 'ready';

                $schoolCode = strtoupper((string) ($row['school_code'] ?? $parsed['school_code'] ?? ''));
                $school = $schoolMap->get($schoolCode);

                if (!$school) {
                    $status = 'error';
                    $messages[] = "No centre matched school code '{$schoolCode}'.";
                }

                $subjectCodes = array_values(array_unique(array_map(
                    fn ($code) => strtoupper((string) $code),
                    $row['subject_codes'] ?? []
                )));

                if (empty($subjectCodes)) {
                    $status = 'error';
                    $messages[] = 'No registered subjects were detected for this candidate.';
                }

                $subjectIds = [];
                $missingCodes = [];
                foreach ($subjectCodes as $subjectCode) {
                    $subject = $subjectMap->get($subjectCode);
                    if (!$subject) {
                        $missingCodes[] = $subjectCode;
                        continue;
                    }

                    $subjectIds[] = (int) $subject->id;
                }

                if (!empty($missingCodes)) {
                    $status = 'error';
                    $messages[] = 'Unknown CSEE subject code(s): ' . implode(', ', $missingCodes) . '.';
                }

                if (count($subjectIds) > 10) {
                    $status = 'error';
                    $messages[] = 'NECTA allows a maximum of 10 CSEE subjects per candidate.';
                }

                $candidate = $candidateMap->get($row['candidate_id']);
                $action = $candidate ? 'update' : 'create';

                if ($status !== 'error' && $candidate) {
                    $existingSubjectIds = $existingSelectionsByCandidateId->get((int) $candidate->id, []);
                    $removedSubjectIds = array_values(array_diff($existingSubjectIds, $subjectIds));
                    $markedSubjectIds = $markedSubjectIdsByCandidateId->get((int) $candidate->id, []);

                    if (!empty(array_intersect($removedSubjectIds, $markedSubjectIds))) {
                        $status = 'error';
                        $messages[] = 'Cannot remove subjects that already have marks recorded.';
                    }
                }

                if ($candidate) {
                    $messages[] = 'Candidate exists and subject registration will be synchronized from the PDF.';
                } else {
                    $messages[] = 'Candidate will be created from the registration PDF.';
                }

                if ($status !== 'error' && $candidate) {
                    if ($candidate->full_name !== $row['full_name']) {
                        $messages[] = 'Candidate name will be refreshed from the PDF.';
                    }

                    if (strtoupper((string) $candidate->gender) !== strtoupper((string) $row['gender'])) {
                        $messages[] = 'Candidate sex will be refreshed from the PDF.';
                    }
                }

                if ($status === 'error') {
                    $errors[] = [
                        'row_number' => $rowNumber,
                        'candidate_id' => $row['candidate_id'],
                        'full_name' => $row['full_name'],
                        'primary_error' => $messages[0] ?? 'Registration PDF row failed validation.',
                        'error_messages' => $messages,
                    ];
                    $skipCount++;
                } elseif ($commit) {
                    $candidate = $this->upsertCandidateFromPdfRow($candidate, $row, $school);
                    $candidateMap->put($row['candidate_id'], $candidate);
                    $existingRegistrations->put((int) $candidate->id, true);

                    $registrationRow = [
                        'candidate_id' => (int) $candidate->id,
                        'exam_type_id' => (int) $examType->id,
                        'exam_year_id' => (int) $examYear->id,
                        'year' => (int) $examYear->year_label,
                        'registration_number' => 'REG-' . uniqid(),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ];
                    if (Schema::hasColumn('candidate_exam_registrations', 'is_active')) {
                        $registrationRow['is_active'] = true;
                    }
                    if (Schema::hasColumn('candidate_exam_registrations', 'is_verified')) {
                        $registrationRow['is_verified'] = false;
                    }
                    $queuedRegistrationUpserts[(string) $candidate->id] = $registrationRow;

                    $existingSubjectIds = $existingSelectionsByCandidateId->get((int) $candidate->id, []);
                    $removedSubjectIds = array_values(array_diff($existingSubjectIds, $subjectIds));
                    if (!empty($removedSubjectIds)) {
                        $queuedSelectionDeletes[(string) $candidate->id] = [
                            'candidate_id' => (int) $candidate->id,
                            'subject_ids' => $removedSubjectIds,
                        ];
                    }

                    $existingSelectionsByCandidateId->put((int) $candidate->id, $subjectIds);

                    foreach ($subjectIds as $subjectId) {
                        $queuedSelectionUpserts[$candidate->id . ':' . $subjectId] = [
                            'candidate_id' => (int) $candidate->id,
                            'exam_type_id' => (int) $examType->id,
                            'exam_year_id' => (int) $examYear->id,
                            'subject_id' => (int) $subjectId,
                            'year' => (int) $examYear->year_label,
                            'is_active' => true,
                            'is_principal' => false,
                            'source' => 'pdf-registration',
                            'created_by' => $this->resolveActorId(),
                            'updated_at' => $now,
                            'created_at' => $now,
                        ];
                    }

                    if ($action === 'create') {
                        $createCount++;
                    } else {
                        $updateCount++;
                    }
                    $status = $action;
                } else {
                    if ($action === 'create') {
                        $createCount++;
                    } else {
                        $updateCount++;
                    }
                    $status = $action;
                }

                $rows[] = [
                    'row_number' => $rowNumber,
                    'candidate_id' => $row['candidate_id'],
                    'full_name' => $row['full_name'],
                    'gender' => $row['gender'],
                    'status' => $status,
                    'messages' => $messages,
                    'subject_codes' => $subjectCodes,
                    'subject_count' => count($subjectCodes),
                ];
            }
        };

        if ($commit) {
            DB::transaction(function () use (
                $rowProcessor,
                &$queuedRegistrationUpserts,
                &$queuedSelectionUpserts,
                &$queuedSelectionDeletes,
                $examType,
                $examYear
            ) {
                $rowProcessor();

                if (!empty($queuedRegistrationUpserts)) {
                    $updateColumns = ['year', 'registration_number', 'updated_at'];
                    if (Schema::hasColumn('candidate_exam_registrations', 'is_active')) {
                        $updateColumns[] = 'is_active';
                    }
                    if (Schema::hasColumn('candidate_exam_registrations', 'is_verified')) {
                        $updateColumns[] = 'is_verified';
                    }

                    DB::table('candidate_exam_registrations')->upsert(
                        array_values($queuedRegistrationUpserts),
                        ['candidate_id', 'exam_type_id', 'exam_year_id'],
                        $updateColumns
                    );
                }

                foreach ($queuedSelectionDeletes as $deleteSet) {
                    DB::table('candidate_subject_selections')
                        ->where('candidate_id', $deleteSet['candidate_id'])
                        ->where('exam_type_id', $examType->id)
                        ->where('exam_year_id', $examYear->id)
                        ->whereIn('subject_id', $deleteSet['subject_ids'])
                        ->delete();
                }

                if (!empty($queuedSelectionUpserts)) {
                    if (DB::connection()->getDriverName() === 'sqlite') {
                        foreach ($queuedSelectionUpserts as $selectionRow) {
                            DB::table('candidate_subject_selections')->updateOrInsert(
                                [
                                    'candidate_id' => $selectionRow['candidate_id'],
                                    'exam_type_id' => $selectionRow['exam_type_id'],
                                    'exam_year_id' => $selectionRow['exam_year_id'],
                                    'subject_id' => $selectionRow['subject_id'],
                                ],
                                [
                                    'year' => $selectionRow['year'],
                                    'is_active' => $selectionRow['is_active'],
                                    'is_principal' => $selectionRow['is_principal'],
                                    'source' => $selectionRow['source'],
                                    'created_by' => $selectionRow['created_by'],
                                    'updated_at' => $selectionRow['updated_at'],
                                    'created_at' => $selectionRow['created_at'],
                                ]
                            );
                        }
                    } else {
                        DB::table('candidate_subject_selections')->upsert(
                            array_values($queuedSelectionUpserts),
                            ['candidate_id', 'exam_type_id', 'exam_year_id', 'subject_id'],
                            ['year', 'is_active', 'is_principal', 'source', 'created_by', 'updated_at']
                        );
                    }
                }
            });
        } else {
            $rowProcessor();
        }

        return [
            'success' => true,
            'message' => $commit
                ? 'CSEE registration PDF imported successfully.'
                : 'CSEE registration PDF validated successfully.',
            'total_rows' => count($parsed['rows']),
            'create_count' => $createCount,
            'update_count' => $updateCount,
            'skip_count' => $skipCount,
            'error_count' => count($errors),
            'warning_count' => 0,
            'can_import' => count($parsed['rows']) > 0 && count($errors) === 0,
            'errors' => $errors,
            'warnings' => [],
            'rows' => $rows,
            'commit_payload' => [
                'exam_year' => (string) $examYear->year_label,
                'school_code' => $parsed['school_code'],
                'school_name' => $parsed['school_name'],
                'rows' => collect($parsed['rows'])->map(fn (array $row) => [
                    'candidate_id' => $row['candidate_id'],
                    'gender' => $row['gender'],
                    'full_name' => $row['full_name'],
                    'subject_codes' => array_values(array_unique(array_map('strtoupper', $row['subject_codes'] ?? []))),
                    'school_code' => strtoupper((string) ($row['school_code'] ?? $parsed['school_code'] ?? '')),
                ])->values()->all(),
            ],
            'summary' => [
                'school_code' => $parsed['school_code'],
                'school_name' => $parsed['school_name'],
                'exam_year' => (string) $examYear->year_label,
            ],
        ];
    }

    private function parsePdfFile(UploadedFile $file): array
    {
        $process = new Process([
            'pdftotext',
            '-layout',
            $file->getRealPath(),
            '-',
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Unable to read the registration PDF. Make sure it is a text-based NECTA registration printout.');
        }

        $text = trim($process->getOutput());
        if ($text === '') {
            throw new \RuntimeException('The uploaded registration PDF did not contain extractable text.');
        }

        $parsed = $this->parseLayoutText($text);
        
        $pageTexts = preg_split("/\f/", $text) ?: [];
        $visualSubjectRowsPerPage = $this->extractSubjectRowsFromRenderedPdf($file->getRealPath(), $pageTexts);

        $candidates = $parsed['rows'];
        $offset = 0;
        $mergedRows = [];

        foreach ($pageTexts as $pageIndex => $pageText) {
            $candidateCount = $this->countCandidateRowsInPageText($pageText);
            if ($candidateCount === 0) {
                continue;
            }

            $pageCandidates = array_slice($candidates, $offset, $candidateCount);
            $offset += $candidateCount;

            $visualRows = $visualSubjectRowsPerPage[$pageIndex] ?? [];
            if (!empty($visualRows) && count($visualRows) === count($pageCandidates)) {
                foreach ($pageCandidates as $idx => $candidate) {
                    $visualCodes = $visualRows[$idx]['subject_codes'] ?? [];
                    if (!empty($visualCodes)) {
                        $pageCandidates[$idx]['subject_codes'] = $visualCodes;
                    }
                }
            }

            foreach ($pageCandidates as $candidate) {
                $mergedRows[] = $candidate;
            }
        }

        $parsed['rows'] = $mergedRows;

        return $parsed;
    }

    private function extractSubjectRowsFromRenderedPdf(string $pdfPath, array $pageTexts): array
    {
        if (!function_exists('imagecreatefrompng')) {
            return [];
        }

        $tempDir = sys_get_temp_dir() . '/csee_pdf_' . uniqid();
        if (!@mkdir($tempDir) && !is_dir($tempDir)) {
            return [];
        }

        try {
            $prefix = $tempDir . '/page';
            $render = new Process([
                'pdftoppm',
                '-png',
                $pdfPath,
                $prefix,
            ]);
            $render->setTimeout(120);
            $render->run();

            if (!$render->isSuccessful()) {
                return [];
            }

            $pageImages = glob($prefix . '-*.png') ?: [];
            sort($pageImages, SORT_NATURAL);

            $result = [];

            foreach ($pageImages as $pageIndex => $imagePath) {
                $pageText = $pageTexts[$pageIndex] ?? '';
                $codes = $this->extractSubjectCodesFromPageText($pageText);
                $candidateCount = $this->countCandidateRowsInPageText($pageText);

                if (empty($codes) || $candidateCount === 0) {
                    $result[$pageIndex] = [];
                    continue;
                }

                $result[$pageIndex] = $this->extractSubjectRowsFromRenderedPage($imagePath, $codes, $candidateCount);
            }

            return $result;
        } finally {
            foreach (glob($tempDir . '/*') ?: [] as $path) {
                @unlink($path);
            }
            @rmdir($tempDir);
        }
    }

    private function extractSubjectCodesFromPageText(string $pageText): array
    {
        foreach (preg_split("/\r\n|\n|\r/", $pageText) ?: [] as $line) {
            if (str_contains($line, 'CANDIDATE') && str_contains($line, 'FULL NAME')) {
                preg_match_all('/\b\d{3}\b/', $line, $matches);
                return array_values(array_map('strval', $matches[0] ?? []));
            }
        }

        return [];
    }

    private function countCandidateRowsInPageText(string $pageText): int
    {
        $count = 0;
        foreach (preg_split("/\r\n|\n|\r/", $pageText) ?: [] as $line) {
            if (preg_match('/^\s*[A-Z0-9]{5}-\d{4}\b/i', $line)) {
                $count++;
            }
        }

        return $count;
    }

    private function extractSubjectRowsFromRenderedPage(string $imagePath, array $subjectCodes, int $candidateCount): array
    {
        $image = @imagecreatefrompng($imagePath);
        if (!$image) {
            return [];
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);

            $xLines = $this->detectVerticalGridLines($image, $width, $height, $candidateCount);
            $yLines = $this->detectHorizontalGridLines($image, $width, $height);

            if (count($xLines) < count($subjectCodes) + 1 || count($yLines) < $candidateCount + 2) {
                return [];
            }

            $subjectBoundaries = $this->locateSubjectColumnBoundaries($xLines, count($subjectCodes));
            if (empty($subjectBoundaries)) {
                return [];
            }

            $rowBoundaries = array_slice($yLines, 1, $candidateCount + 1);
            if (count($rowBoundaries) !== $candidateCount + 1) {
                return [];
            }

            $rows = [];
            for ($rowIndex = 0; $rowIndex < $candidateCount; $rowIndex++) {
                $y1 = $rowBoundaries[$rowIndex] ?? null;
                $y2 = $rowBoundaries[$rowIndex + 1] ?? null;
                if ($y1 === null || $y2 === null) {
                    break;
                }

                $rowCodes = [];
                foreach ($subjectCodes as $index => $code) {
                    $x1 = $subjectBoundaries[$index];
                    $x2 = $subjectBoundaries[$index + 1];
                    if ($this->cellContainsTick($image, $x1, $y1, $x2, $y2)) {
                        $rowCodes[] = (string) $code;
                    }
                }

                $rows[] = [
                    'subject_codes' => $rowCodes,
                ];
            }

            return $rows;
        } finally {
            imagedestroy($image);
        }
    }

    private function detectVerticalGridLines($image, int $width, int $height, int $candidateCount): array
    {
        $hits = [];
        $threshold = max(50, min(300, $candidateCount * 12));
        for ($x = 0; $x < $width; $x++) {
            $dark = 0;
            for ($y = 250; $y < min($height, 1100); $y++) {
                if ($this->isDarkPixel($image, $x, $y)) {
                    $dark++;
                }
            }
            if ($dark > $threshold) {
                $hits[] = $x;
            }
        }

        return $this->clusterLineHits($hits);
    }

    private function detectHorizontalGridLines($image, int $width, int $height): array
    {
        $hits = [];
        for ($y = 0; $y < $height; $y++) {
            $dark = 0;
            for ($x = 0; $x < $width; $x++) {
                if ($this->isDarkPixel($image, $x, $y)) {
                    $dark++;
                }
            }
            if ($dark > 900) {
                $hits[] = $y;
            }
        }

        return $this->clusterLineHits($hits);
    }

    private function locateSubjectColumnBoundaries(array $xLines, int $subjectCount): array
    {
        $needed = $subjectCount + 1;
        if (count($xLines) < $needed) {
            return [];
        }

        $bestWindow = [];
        $bestScore = null;

        for ($start = 0; $start <= count($xLines) - $needed; $start++) {
            $window = array_slice($xLines, $start, $needed);
            $widths = [];

            for ($i = 0; $i < count($window) - 1; $i++) {
                $widths[] = $window[$i + 1] - $window[$i];
            }

            if (empty($widths)) {
                continue;
            }

            $average = array_sum($widths) / count($widths);
            if ($average < 20 || $average > 80) {
                continue;
            }

            $variance = array_sum(array_map(
                fn (float $width) => ($width - $average) ** 2,
                $widths
            )) / count($widths);

            $score = $variance;
            if ($bestScore === null || $score < $bestScore) {
                $bestScore = $score;
                $bestWindow = $window;
            }
        }

        return $bestWindow;
    }

    private function clusterLineHits(array $hits): array
    {
        $clusters = [];
        foreach ($hits as $hit) {
            if (empty($clusters) || ($hit - end($clusters[count($clusters) - 1])) > 2) {
                $clusters[] = [$hit];
            } else {
                $clusters[count($clusters) - 1][] = $hit;
            }
        }

        return array_map(
            fn (array $cluster) => (int) round(array_sum($cluster) / max(count($cluster), 1)),
            $clusters
        );
    }

    private function cellContainsTick($image, int $x1, int $y1, int $x2, int $y2): bool
    {
        $darkPixels = 0;
        for ($x = $x1 + 4; $x < $x2 - 4; $x++) {
            for ($y = $y1 + 4; $y < $y2 - 4; $y++) {
                if ($this->isDarkPixel($image, $x, $y, 180)) {
                    $darkPixels++;
                }
            }
        }

        return $darkPixels > 25;
    }

    private function isDarkPixel($image, int $x, int $y, int $threshold = 80): bool
    {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

        return $luma < $threshold;
    }

    private function parseCandidateLine(string $line, array $codeOffsets): ?array
    {
        $subjectAreaStart = (int) ($codeOffsets[0]['offset'] ?? 0);
        if ($subjectAreaStart <= 0) {
            return null;
        }

        $identitySegment = rtrim(substr($line, 0, $subjectAreaStart));
        if (!preg_match('/^\s*([A-Z0-9]{5}-\d{4})\s+([MF])\s+(.*?)\s*$/i', $identitySegment, $matches)) {
            return null;
        }

        $normalizedLine = str_replace(['✔', '✓', '√'], '*', $line);
        preg_match_all('/\*/', $normalizedLine, $tickMatches, PREG_OFFSET_CAPTURE);
        $ticks = $tickMatches[0] ?? [];

        $columnRanges = [];
        $lastIndex = count($codeOffsets) - 1;
        foreach ($codeOffsets as $index => $codeOffset) {
            $currentOffset = (int) $codeOffset['offset'];
            $previousOffset = $index > 0 ? (int) $codeOffsets[$index - 1]['offset'] : null;
            $nextOffset = $index < $lastIndex ? (int) $codeOffsets[$index + 1]['offset'] : null;

            $start = $previousOffset === null
                ? max(0, $currentOffset - 2)
                : ((int) floor(($previousOffset + $currentOffset) / 2) + 1);

            $end = $nextOffset === null
                ? ($currentOffset + 5)
                : (int) floor(($currentOffset + $nextOffset) / 2);

            $columnRanges[] = [
                'code' => (string) $codeOffset['code'],
                'start' => $start,
                'end' => $end,
            ];
        }

        $assignedCodes = [];
        foreach ($ticks as $tick) {
            $tickOffset = (int) $tick[1];
            $matchedCode = null;

            foreach ($columnRanges as $columnRange) {
                if ($tickOffset >= $columnRange['start'] && $tickOffset <= $columnRange['end']) {
                    $matchedCode = $columnRange['code'];
                    break;
                }
            }

            if ($matchedCode !== null) {
                $assignedCodes[] = $matchedCode;
            }
        }

        $subjectCodes = array_values(array_unique($assignedCodes));

        return [
            'candidate_id' => strtoupper(trim($this->sanitizeExtractedText($matches[1]))),
            'gender' => strtoupper(trim($this->sanitizeExtractedText($matches[2]))),
            'full_name' => trim($this->sanitizeExtractedText($matches[3])),
            'subject_codes' => $subjectCodes,
        ];
    }

    private function sanitizeExtractedText(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $normalized = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($normalized === false) {
            $normalized = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        // Remove control characters and stray replacement glyphs that can appear in extracted PDF rows.
        $normalized = preg_replace('/[\x00-\x1F\x7F]/u', '', $normalized) ?? $normalized;
        $normalized = str_replace("\u{FFFD}", '', $normalized);

        return $normalized;
    }

    private function upsertCandidateFromPdfRow(?Candidate $candidate, array $row, School $school): Candidate
    {
        $payload = [
            'school_id' => $school->id,
            'candidate_id' => $row['candidate_id'],
            'full_name' => $row['full_name'],
            'gender' => $row['gender'],
            'exam_type' => 'CSEE',
            'candidate_type' => 'SCHOOL',
            'combination' => null,
        ];

        if ($candidate) {
            $candidate->update($payload);
        } else {
            $candidate = Candidate::query()->create($payload);
        }

        return $candidate->fresh();
    }

    private function resolveActorId(): ?int
    {
        $userId = auth()->id();

        return $userId ? (int) $userId : null;
    }

    private function ensureOfficialCseeSubjectsSeeded(ExamType $examType): void
    {
        $existingCount = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->count();

        if ($existingCount > 0) {
            return;
        }

        $catalog = collect(config('csee.official_subjects', []));
        if ($catalog->isEmpty()) {
            return;
        }

        foreach ($catalog as $entry) {
            Subject::query()->updateOrCreate(
                [
                    'exam_type_id' => $examType->id,
                    'code' => $entry['code'],
                ],
                [
                    'name' => $entry['name'],
                    'category' => $entry['category'] ?? 'ARTS',
                    'subject_group_label' => $entry['subject_group_label'] ?? $this->defaultCseeSubjectGroupLabel($entry['category'] ?? null),
                    'written_papers' => (int) ($entry['written_papers'] ?? 1),
                    'paper_pattern_label' => 'Official booklet on file. Structured paper extraction pending.',
                    'has_practical' => false,
                    'has_project' => false,
                    'description' => sprintf(
                        'NECTA CSEE official subject from the October 2022 examination formats booklet (section %s, page %s).',
                        $entry['code'] ?? 'N/A',
                        $entry['source_page'] ?? 'N/A'
                    ),
                    'max_marks' => 100,
                    'is_active' => true,
                ]
            );
        }
    }

    private function defaultCseeSubjectGroupLabel(?string $category): string
    {
        return match (strtoupper((string) $category)) {
            'SCIENCE' => 'Science Subjects',
            'BUSINESS' => 'Business, Commerce & Technical Subjects',
            default => 'Humanities, Languages & Skills Subjects',
        };
    }

    private function aggregateBatchReports(array $reports, bool $committed): array
    {
        $schools = collect($reports)->map(function (array $report) {
            $summary = $report['summary'] ?? [];

            return [
                'source_file_name' => $report['source_file_name'] ?? null,
                'school_code' => $summary['school_code'] ?? null,
                'school_name' => $summary['school_name'] ?? null,
                'exam_year' => $summary['exam_year'] ?? null,
                'total_rows' => (int) ($report['total_rows'] ?? 0),
                'create_count' => (int) ($report['create_count'] ?? 0),
                'update_count' => (int) ($report['update_count'] ?? 0),
                'skip_count' => (int) ($report['skip_count'] ?? 0),
                'error_count' => (int) ($report['error_count'] ?? 0),
                'can_import' => (bool) ($report['can_import'] ?? false),
                'success' => (bool) ($report['success'] ?? false),
                'message' => (string) ($report['message'] ?? ''),
                'errors' => $report['errors'] ?? [],
                'rows' => $report['rows'] ?? [],
                'commit_payload' => $report['commit_payload'] ?? null,
            ];
        })->values();

        $importableSchools = $schools->where('can_import', true)->count();
        $failedSchools = $schools->where('can_import', false)->count();

        return [
            'success' => $committed ? $importableSchools > 0 && $failedSchools === 0 : true,
            'message' => $committed
                ? 'CSEE registration PDFs imported successfully.'
                : 'CSEE registration PDFs validated successfully.',
            'total_files' => $schools->count(),
            'importable_school_count' => $importableSchools,
            'failed_school_count' => $failedSchools,
            'total_rows' => $schools->sum('total_rows'),
            'create_count' => $schools->sum('create_count'),
            'update_count' => $schools->sum('update_count'),
            'skip_count' => $schools->sum('skip_count'),
            'error_count' => $schools->sum('error_count'),
            'warning_count' => 0,
            'can_import' => $importableSchools > 0,
            'schools' => $schools->all(),
            'errors' => $schools
                ->flatMap(function (array $school) {
                    return collect($school['errors'] ?? [])->map(function (array $error) use ($school) {
                        $error['source_file_name'] = $school['source_file_name'] ?? null;
                        $error['school_code'] = $school['school_code'] ?? null;

                        return $error;
                    });
                })
                ->values()
                ->all(),
            'summary' => [
                'exam_year' => $schools->pluck('exam_year')->filter()->unique()->implode(', '),
            ],
            'commit_payloads' => $schools
                ->pluck('commit_payload')
                ->filter()
                ->values()
                ->all(),
        ];
    }
}
