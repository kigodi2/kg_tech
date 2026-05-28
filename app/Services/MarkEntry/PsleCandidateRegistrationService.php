<?php

namespace App\Services\MarkEntry;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\DistrictCouncil;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;
use App\Models\MarkEntryAssignment;
use App\Models\Region;
use App\Models\School;
use App\Models\User;
use App\Services\PsleActivityLogger;
use App\Support\PsleUserScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PsleCandidateRegistrationService
{
    private const TEMPLATE_COLUMNS = ['candidate_number', 'PReM_No', 'pupil_name', 'sex', 'school_code'];

    public function __construct(private PsleActivityLogger $activityLogger)
    {
    }

    public function examYear(?int $examYearId = null): ExamYear
    {
        $query = ExamYear::query();
        $examYear = $examYearId ? $query->find($examYearId) : null;

        return $examYear ?: ExamYear::where('is_active', true)->firstOrFail();
    }

    public function psleExamType(): ExamType
    {
        return ExamType::where('code', 'PSLE')->firstOrFail();
    }

    public function regions(User $user, ?int $examYearId = null): Collection
    {
        $query = Region::query()
            ->whereHas('schools', function ($schoolQuery) use ($user, $examYearId) {
                $this->applySchoolScope($schoolQuery, $user, $examYearId);
                $schoolQuery->where('education_level', 'PRIMARY');
            })
            ->orderBy('name');

        return $query->get(['id', 'name']);
    }

    public function councils(User $user, int $regionId, ?int $examYearId = null): Collection
    {
        return DistrictCouncil::query()
            ->where('region_id', $regionId)
            ->whereHas('schools', function ($schoolQuery) use ($user, $examYearId) {
                $this->applySchoolScope($schoolQuery, $user, $examYearId);
                $schoolQuery->where('education_level', 'PRIMARY');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'region_id']);
    }

    public function schools(User $user, array $filters): Collection
    {
        $examYearId = $filters['exam_year_id'] ?? null;
        $search = trim((string) ($filters['q'] ?? ''));

        $query = School::query()
            ->with(['council.region', 'district', 'region'])
            ->where('education_level', 'PRIMARY')
            ->when($filters['region_id'] ?? null, fn ($q, $regionId) => $q->where(function ($scope) use ($regionId) {
                $scope->where('region_id', $regionId)
                    ->orWhereHas('council', fn ($council) => $council->where('region_id', $regionId));
            }))
            ->when($filters['council_id'] ?? null, fn ($q, $councilId) => $q->where('council_id', $councilId));

        $this->applySchoolScope($query, $user, $examYearId ? (int) $examYearId : null);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        } elseif (empty($filters['selected_id']) && empty($filters['region_id']) && empty($filters['council_id'])) {
            return collect();
        }

        if (!empty($filters['selected_id'])) {
            $query->whereKey($filters['selected_id']);
        }

        return $query->orderBy('code')
            ->limit(30)
            ->get(['id', 'code', 'name', 'region_id', 'council_id', 'district_id', 'school_type', 'education_level']);
    }

    public function candidates(User $user, array $filters)
    {
        $examYear = $this->examYear(isset($filters['exam_year_id']) ? (int) $filters['exam_year_id'] : null);
        $examType = $this->psleExamType();
        $search = trim((string) ($filters['q'] ?? ''));

        $query = Candidate::query()
            ->with(['school.council', 'school.region'])
            ->where(function ($candidateQuery) use ($examType) {
                $candidateQuery->where('exam_type', 'PSLE')
                    ->orWhereHas('examRegistrations', fn ($registrationQuery) => $registrationQuery->where('exam_type_id', $examType->id));
            })
            ->whereHas('examRegistrations', function ($registrationQuery) use ($examType, $examYear) {
                $registrationQuery->where('exam_type_id', $examType->id)
                    ->where('exam_year_id', $examYear->id);
            })
            ->when($filters['school_id'] ?? null, fn ($q, $schoolId) => $q->where('school_id', $schoolId))
            ->when($filters['council_id'] ?? null, fn ($q, $councilId) => $q->whereHas('school', fn ($school) => $school->where('council_id', $councilId)))
            ->when($filters['region_id'] ?? null, fn ($q, $regionId) => $q->whereHas('school', fn ($school) => $school
                ->where('region_id', $regionId)
                ->orWhereHas('council', fn ($council) => $council->where('region_id', $regionId))));

        PsleUserScope::applyToCandidateSchools($query, $user);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('candidate_id', 'like', "%{$search}%")
                    ->orWhere('prem_no', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('school', fn ($school) => $school->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        return $query->orderBy('candidate_id')->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function summary(User $user, array $filters): array
    {
        $candidates = $this->candidates($user, array_merge($filters, ['per_page' => 1]));
        $schoolsQuery = School::query()->where('education_level', 'PRIMARY');
        $this->applySchoolScope($schoolsQuery, $user, isset($filters['exam_year_id']) ? (int) $filters['exam_year_id'] : null);

        return [
            'registered_candidates' => $candidates->total(),
            'synced_schools' => $schoolsQuery->count(),
        ];
    }

    public function authorizeSchool(User $user, int $schoolId, ?int $examYearId = null): School
    {
        $query = School::query()
            ->with('council')
            ->whereKey($schoolId)
            ->where('education_level', 'PRIMARY');
        $this->applySchoolScope($query, $user, $examYearId);

        $school = $query->first();
        if (!$school) {
            abort(response()->json([
                'success' => false,
                'message' => 'You are not authorized to register candidates for this school.',
                'errors' => [],
            ], 403));
        }

        return $school;
    }

    public function validateRegistrationWindow(ExamYear $examYear): void
    {
        if ($examYear->is_locked) {
            abort(response()->json([
                'success' => false,
                'message' => "This school's PSLE candidate registration has already been locked. New registration is no longer allowed unless reopened by an administrator.",
                'errors' => [],
            ], 423));
        }
    }

    /**
     * Find an existing candidate based on 3-tier matching priority:
     * A. candidate_id + exam_year_id + exam_type_id
     * B. prem_no + exam_year_id + exam_type_id (where prem_no is not empty)
     * C. school_id + normalized candidate name + sex + exam_year_id + exam_type_id
     * 
     * If not registered yet, also fall back to global matches to prevent duplicates.
     */
    public function findExistingCandidate(array $payload, int $examYearId, int $examTypeId): ?Candidate
    {
        // A. candidate_id + exam_year_id + exam_type_id (via registrations)
        if (!empty($payload['candidate_id'])) {
            $cand = Candidate::where('candidate_id', $payload['candidate_id'])
                ->whereHas('examRegistrations', function ($q) use ($examYearId, $examTypeId) {
                    $q->where('exam_year_id', $examYearId)
                      ->where('exam_type_id', $examTypeId);
                })->first();
            if ($cand) return $cand;
            
            // Global unique candidate_id check
            $candGlobal = Candidate::where('candidate_id', $payload['candidate_id'])->first();
            if ($candGlobal) return $candGlobal;
        }

        // B. prem_no + exam_year_id + exam_type_id where prem_no is not empty
        if (!empty($payload['prem_no'])) {
            $cand = Candidate::where('prem_no', $payload['prem_no'])
                ->whereHas('examRegistrations', function ($q) use ($examYearId, $examTypeId) {
                    $q->where('exam_year_id', $examYearId)
                      ->where('exam_type_id', $examTypeId);
                })->first();
            if ($cand) return $cand;
            
            // Global unique prem_no check
            $candGlobal = Candidate::where('prem_no', $payload['prem_no'])->first();
            if ($candGlobal) return $candGlobal;
        }

        // C. school_id + normalized candidate name + sex + exam_year_id + exam_type_id as fallback
        $schoolId = $payload['school_id'] ?? null;
        if (!$schoolId && !empty($payload['school_code'])) {
            $schoolId = School::where('code', $payload['school_code'])->value('id');
        }

        if ($schoolId && !empty($payload['full_name']) && !empty($payload['gender'])) {
            $normalizedName = strtolower(preg_replace('/\s+/', ' ', trim($payload['full_name'])));
            
            $candidates = Candidate::where('school_id', $schoolId)
                ->where('gender', $payload['gender'])
                ->whereHas('examRegistrations', function ($q) use ($examYearId, $examTypeId) {
                    $q->where('exam_year_id', $examYearId)
                      ->where('exam_type_id', $examTypeId);
                })->get();
            foreach ($candidates as $c) {
                if (strtolower(preg_replace('/\s+/', ' ', trim($c->full_name))) === $normalizedName) {
                    return $c;
                }
            }

            // Global check for same school & name & gender
            $candidatesGlobal = Candidate::where('school_id', $schoolId)
                ->where('gender', $payload['gender'])
                ->get();
            foreach ($candidatesGlobal as $c) {
                if (strtolower(preg_replace('/\s+/', ' ', trim($c->full_name))) === $normalizedName) {
                    return $c;
                }
            }
        }

        return null;
    }

    public function createOrUpdateCandidate(User $user, array $data, ?Candidate $candidate = null, bool $replaceExisting = false): array
    {
        $examYear = $this->examYear((int) ($data['exam_year_id'] ?? 0));
        $this->validateRegistrationWindow($examYear);
        $school = $this->authorizeSchool($user, (int) $data['school_id'], $examYear->id);
        $examType = $this->psleExamType();

        $payload = $this->normalizeCandidatePayload($data);
        $errors = $this->validateCandidatePayload($payload, $school, $candidate, $replaceExisting);
        if ($errors) {
            abort(response()->json([
                'success' => false,
                'message' => reset($errors),
                'errors' => $errors,
            ], 422));
        }

        $result = DB::transaction(function () use ($candidate, $payload, $school, $examType, $examYear, $replaceExisting) {
            $existing = $candidate ?: $this->findExistingCandidate([
                'candidate_id' => $payload['candidate_id'],
                'prem_no' => $payload['prem_no'],
                'school_id' => $school->id,
                'full_name' => $payload['full_name'],
                'gender' => $payload['gender'],
            ], $examYear->id, $examType->id);

            $mode = $existing ? 'updated' : 'inserted';
            $old = $existing ? $existing->only(['candidate_id', 'prem_no', 'full_name', 'gender', 'school_id', 'status']) : null;

            if (!$existing) {
                $existing = new Candidate();
            }

            $existing->fill([
                'school_id' => $school->id,
                'candidate_id' => $payload['candidate_id'],
                'prem_no' => $payload['prem_no'] ?: null, // Cast empty to NULL
                'full_name' => $payload['full_name'],
                'gender' => $payload['gender'],
                'candidate_type' => 'SCHOOL',
                'exam_type' => 'PSLE',
                'status' => 'registered',
                'is_active' => true,
            ]);
            $existing->save();
            $this->registerForPsle($existing, $examType, $examYear);

            return ['candidate' => $existing, 'mode' => $mode, 'old' => $old];
        });

        $this->audit('psle_candidate_' . $result['mode'], $user, $school, $examYear, [
            'candidate_id' => $result['candidate']->id,
            'candidate_number' => $result['candidate']->candidate_id,
            'old' => $result['old'],
            'new' => $result['candidate']->only(['candidate_id', 'prem_no', 'full_name', 'gender', 'school_id', 'status']),
        ]);

        return $result;
    }

    public function deleteCandidate(User $user, Candidate $candidate, int $examYearId): void
    {
        $examYear = $this->examYear($examYearId);
        $this->validateRegistrationWindow($examYear);
        $school = $this->authorizeSchool($user, (int) $candidate->school_id, $examYear->id);

        $old = $candidate->only(['candidate_id', 'prem_no', 'full_name', 'gender', 'school_id', 'status']);
        $candidate->delete();

        $this->audit('psle_candidate_deleted', $user, $school, $examYear, ['old' => $old]);
    }

    public function templateResponse(User $user, int $examYearId, ?int $schoolId = null)
    {
        $examYear = $this->examYear($examYearId);
        $this->validateRegistrationWindow($examYear);
        $school = $schoolId ? $this->authorizeSchool($user, $schoolId, $examYear->id) : null;

        $filename = $school
            ? sprintf(
                'PSLE_%s_%s_%s_CANDIDATE_REGISTRATION_TEMPLATE.csv',
                $examYear->year_label,
                $school->code,
                Str::slug($school->name, '_')
            )
            : sprintf('psle-pupil-import-template-%s.csv', now()->toDateString());

        $this->audit('psle_candidate_template_downloaded', $user, $school, $examYear, [
            'filename' => $filename,
            'columns' => self::TEMPLATE_COLUMNS,
        ]);

        return response()->streamDownload(function () use ($school) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::TEMPLATE_COLUMNS);
            $schoolCode = $school?->code ?: 'PS0404006';
            fputcsv($handle, [
                $schoolCode . '-0001',
                '20201520092',
                'ASHERI JOSHUA CHAULA',
                'M',
                $schoolCode,
            ]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function previewBulk(User $user, UploadedFile $file, array $data): array
    {
        $examYear = $this->examYear((int) ($data['exam_year_id'] ?? 0));
        $this->validateRegistrationWindow($examYear);
        $mode = $this->normalizeDuplicateMode((string) ($data['on_exists_mode'] ?? $data['duplicate_mode'] ?? 'skip'));
        $school = !empty($data['school_id'])
            ? $this->authorizeSchool($user, (int) $data['school_id'], $examYear->id)
            : null;

        $preview = $this->readAndValidateRows($file, $user, $school, $examYear, $mode);

        $this->audit('psle_candidate_bulk_previewed', $user, $school, $examYear, [
            'file_name' => $file->getClientOriginalName(),
            'summary' => $preview['summary'],
        ]);

        return $preview;
    }

    public function importBulk(User $user, UploadedFile $file, array $data): array
    {
        $examYear = $this->examYear((int) ($data['exam_year_id'] ?? 0));
        $this->validateRegistrationWindow($examYear);
        $mode = $this->normalizeDuplicateMode((string) ($data['on_exists_mode'] ?? $data['duplicate_mode'] ?? 'skip'));
        $selectedSchool = !empty($data['school_id'])
            ? $this->authorizeSchool($user, (int) $data['school_id'], $examYear->id)
            : null;
        $examType = $this->psleExamType();
        $preview = $this->readAndValidateRows($file, $user, $selectedSchool, $examYear, $mode);

        if ($mode === 'stop' && ($preview['summary']['already_existing'] ?? 0) > 0) {
            return [
                'success' => false,
                'message' => 'Import stopped because duplicate candidate numbers were found.',
                'summary' => ['duplicates' => $preview['summary']['already_existing']],
            ];
        }

        $summary = [
            'total_rows' => $preview['summary']['total_rows'],
            'inserted' => 0,
            'updated' => 0,
            'skipped' => $preview['summary']['invalid_rows'] + $preview['summary']['rows_to_skip'],
            'invalid' => $preview['summary']['invalid_rows'],
        ];
        $auditSchool = $selectedSchool;

        DB::transaction(function () use ($preview, $examType, $examYear, &$summary, &$auditSchool) {
            foreach ($preview['rows'] as $row) {
                if (!$row['valid'] || $row['action'] === 'skip') {
                    continue;
                }

                $existing = $this->findExistingCandidate([
                    'candidate_id' => $row['candidate_id'],
                    'prem_no' => $row['prem_no'],
                    'school_code' => $row['school_code'],
                    'full_name' => $row['full_name'],
                    'gender' => $row['gender'],
                ], $examYear->id, $examType->id);
                
                $candidate = $existing ?: new Candidate();
                $school = School::where('code', $row['school_code'])->firstOrFail();
                $auditSchool = $auditSchool ?: $school;
                $candidate->fill([
                    'school_id' => $school->id,
                    'candidate_id' => $row['candidate_id'],
                    'prem_no' => $row['prem_no'] ?: null, // Cast empty to NULL
                    'full_name' => $row['full_name'],
                    'gender' => $row['gender'],
                    'candidate_type' => 'SCHOOL',
                    'exam_type' => 'PSLE',
                    'status' => 'registered',
                    'is_active' => true,
                ]);
                $candidate->save();
                $this->registerForPsle($candidate, $examType, $examYear);

                $existing ? $summary['updated']++ : $summary['inserted']++;
            }
        });

        $this->audit('psle_candidate_bulk_imported', $user, $auditSchool, $examYear, [
            'file_name' => $file->getClientOriginalName(),
            'duplicate_handling_option' => $mode,
            'summary' => $summary,
        ], $summary['inserted'] + $summary['updated']);

        return [
            'success' => true,
            'message' => 'PSLE pupil import completed successfully.',
            'summary' => $summary,
        ];
    }

    private function applySchoolScope($query, User $user, ?int $examYearId = null): void
    {
        $examTypeId = ExamType::where('code', 'PSLE')->value('id');
        $assignmentSchoolIds = collect();

        if (!PsleUserScope::hasGlobalAccess($user) && $examTypeId) {
            $assignmentSchoolIds = MarkEntryAssignment::query()
                ->where('assigned_to', $user->id)
                ->where('exam_type_id', $examTypeId)
                ->when($examYearId, fn ($q) => $q->where('exam_year_id', $examYearId))
                ->whereIn('status', ['assigned', 'active', 'in_progress', 'submitted'])
                ->pluck('school_id')
                ->filter()
                ->unique()
                ->values();
        }

        if ($assignmentSchoolIds->isNotEmpty()) {
            $query->whereIn('id', $assignmentSchoolIds);
            return;
        }

        PsleUserScope::applyToSchools($query, $user);
    }

    private function normalizeCandidatePayload(array $data): array
    {
        return [
            'candidate_id' => strtoupper(trim((string) ($data['candidate_id'] ?? $data['candidate_number'] ?? ''))),
            'prem_no' => trim((string) ($data['prem_no'] ?? '')),
            'full_name' => trim((string) ($data['full_name'] ?? $data['pupil_name'] ?? '')),
            'gender' => strtoupper(trim((string) ($data['gender'] ?? $data['sex'] ?? ''))),
        ];
    }

    private function validateCandidatePayload(array $payload, School $school, ?Candidate $candidate = null, bool $replaceExisting = false): array
    {
        $errors = [];

        if (!preg_match($this->indexNumberPatternForSchool($school), $payload['candidate_id'])) {
            $errors['candidate_id'] = "Candidate Number must use this school's centre number, for example {$school->code}-0001.";
        }

        if (!preg_match('/^[0-9]{11}$/', $payload['prem_no'])) {
            $errors['prem_no'] = 'PReM No must be exactly 11 digits.';
        }

        if ($payload['full_name'] === '') {
            $errors['full_name'] = 'Pupil Name is required.';
        }

        if (!in_array($payload['gender'], ['M', 'F'], true)) {
            $errors['gender'] = 'Sex must be M or F.';
        }

        $existingByIndex = Candidate::where('candidate_id', $payload['candidate_id'])->first();
        if ($existingByIndex && (!$candidate || $existingByIndex->id !== $candidate->id)) {
            if ((int) $existingByIndex->school_id !== (int) $school->id) {
                $errors['candidate_id'] = 'This candidate number is already assigned to another school centre.';
            } elseif (!$replaceExisting) {
                $errors['candidate_id'] = 'This candidate number is already registered for the selected school and exam year.';
            }
        }

        $existingByPrem = Candidate::where('prem_no', $payload['prem_no'])->first();
        if ($existingByPrem && (!$candidate || $existingByPrem->id !== $candidate->id) && (!$existingByIndex || $existingByPrem->id !== $existingByIndex->id)) {
            $errors['prem_no'] = "PReM No is already assigned to candidate {$existingByPrem->candidate_id}.";
        }

        return $errors;
    }

    private function readAndValidateRows(UploadedFile $file, User $user, ?School $selectedSchool, ExamYear $examYear, string $mode): array
    {
        $rows = $this->readCsv($file);
        if (empty($rows)) {
            return [
                'rows' => [],
                'summary' => $this->previewSummary([], 0),
                'message' => 'The uploaded file is empty.',
            ];
        }

        $header = array_map(fn ($value) => trim((string) $value), array_shift($rows));
        
        $mappedIndices = [
            'candidate_number' => null,
            'PReM_No' => null,
            'pupil_name' => null,
            'sex' => null,
            'school_code' => null,
        ];

        foreach ($header as $index => $rawColName) {
            $colName = strtolower(str_replace(['_', ' '], '', trim($rawColName)));
            
            // Match candidate_number
            if (in_array($colName, ['candidatenumber', 'candidateno', 'indexnumber', 'indexno'], true)) {
                $mappedIndices['candidate_number'] = $index;
            }
            // Match PReM_No
            elseif (in_array($colName, ['premno', 'premnumber'], true)) {
                $mappedIndices['PReM_No'] = $index;
            }
            // Match pupil_name
            elseif (in_array($colName, ['pupilname', 'fullname', 'candidatename'], true)) {
                $mappedIndices['pupil_name'] = $index;
            }
            // Match sex
            elseif (in_array($colName, ['sex', 'gender'], true)) {
                $mappedIndices['sex'] = $index;
            }
            // Match school_code
            elseif (in_array($colName, ['schoolcode', 'schoolid', 'centrenumber', 'centreno'], true)) {
                $mappedIndices['school_code'] = $index;
            }
        }

        $missing = [];
        foreach ($mappedIndices as $key => $index) {
            if ($index === null) {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            abort(response()->json([
                'success' => false,
                'message' => 'This file has invalid columns or is missing required columns. Please download a fresh template and try again.',
                'errors' => [
                    'columns' => self::TEMPLATE_COLUMNS,
                    'missing' => $missing,
                ],
            ], 422));
        }

        $seenCandidateNumbers = [];
        $seenPremNumbers = [];
        $previewRows = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            if ($this->blankRow($row)) {
                continue;
            }

            $candidateNumber = isset($row[$mappedIndices['candidate_number']]) ? trim((string) $row[$mappedIndices['candidate_number']]) : '';
            $premNo = isset($row[$mappedIndices['PReM_No']]) ? trim((string) $row[$mappedIndices['PReM_No']]) : '';
            $pupilName = isset($row[$mappedIndices['pupil_name']]) ? trim((string) $row[$mappedIndices['pupil_name']]) : '';
            $sex = isset($row[$mappedIndices['sex']]) ? trim((string) $row[$mappedIndices['sex']]) : '';
            $schoolCode = isset($row[$mappedIndices['school_code']]) ? trim((string) $row[$mappedIndices['school_code']]) : '';

            $schoolCode = strtoupper(trim((string) $schoolCode));
            $school = $schoolCode !== '' ? School::where('code', $schoolCode)->with('council')->first() : null;
            $payload = $this->normalizeCandidatePayload([
                'candidate_number' => $candidateNumber,
                'prem_no' => $premNo,
                'pupil_name' => $pupilName,
                'sex' => $sex,
            ]);

            $errors = [];
            if ($schoolCode === '') {
                $errors['school_code'] = 'school_code is required.';
            } elseif (!$school) {
                $errors['school_code'] = 'The school code in this row does not exist in the PSLE Schools & Centres list.';
            } else {
                if ($selectedSchool && (int) $selectedSchool->id !== (int) $school->id) {
                    $errors['school_code'] = 'school_code does not match the selected primary school.';
                } else {
                    $scopedQuery = School::query()->whereKey($school->id);
                    $this->applySchoolScope($scopedQuery, $user, $examYear->id);
                    if (!$scopedQuery->exists()) {
                        $errors['school_code'] = 'You are not authorized to register candidates for this school.';
                    }
                }
                $errors = array_merge($errors, $this->validateCandidatePayload($payload, $school, null, in_array($mode, ['skip', 'replace'], true)));
            }
            if (isset($seenCandidateNumbers[$payload['candidate_id']])) {
                $errors['duplicate_candidate_id'] = "Duplicate Candidate Number also appears on row {$seenCandidateNumbers[$payload['candidate_id']]}.";
            }
            if ($payload['candidate_id'] !== '') {
                $seenCandidateNumbers[$payload['candidate_id']] = $rowNumber;
            }
            if (isset($seenPremNumbers[$payload['prem_no']])) {
                $errors['duplicate_prem_no'] = "Duplicate PReM No also appears on row {$seenPremNumbers[$payload['prem_no']]}.";
            }
            if ($payload['prem_no'] !== '') {
                $seenPremNumbers[$payload['prem_no']] = $rowNumber;
            }

            $existing = $this->findExistingCandidate([
                'candidate_id' => $payload['candidate_id'],
                'prem_no' => $payload['prem_no'],
                'school_id' => $school?->id,
                'full_name' => $payload['full_name'],
                'gender' => $payload['gender'],
            ], $examYear->id, $this->psleExamType()->id);
            
            $action = 'insert';
            if ($existing) {
                $action = match ($mode) {
                    'replace' => 'replace',
                    'stop' => 'stop',
                    default => 'skip',
                };
                if ($mode === 'stop') {
                    $errors['candidate_id'] = 'candidate_id already exists; stop-on-duplicates mode prevents import';
                }
            }
            $previewRows[] = [
                'row_number' => $rowNumber,
                'candidate_id' => $payload['candidate_id'],
                'prem_no' => $payload['prem_no'],
                'full_name' => $payload['full_name'],
                'pupil_name' => $payload['full_name'],
                'gender' => $payload['gender'],
                'sex' => $payload['gender'],
                'school_code' => $schoolCode,
                'school_name' => $school?->name,
                'council' => $school?->council?->name,
                'valid' => empty($errors),
                'status' => empty($errors) ? strtoupper($action === 'insert' ? 'NEW' : $action) : 'ERROR',
                'action' => empty($errors) ? $action : 'invalid',
                'message' => empty($errors) ? $this->candidateImportPreviewMessage($action) : implode(' ', array_values($errors)),
                'existing' => (bool) $existing,
            ];
        }

        return [
            'rows' => $previewRows,
            'summary' => $this->previewSummary($previewRows, count($rows)),
            'message' => 'Upload preview completed.',
        ];
    }

    private function previewSummary(array $rows, int $totalRows): array
    {
        $valid = collect($rows)->where('valid', true);
        $invalid = collect($rows)->where('valid', false);

        return [
            'total_rows' => $totalRows,
            'valid_rows' => $valid->count(),
            'invalid_rows' => $invalid->count(),
            'duplicate_rows' => $invalid->filter(fn ($row) => str_contains($row['message'], 'Duplicate'))->count(),
            'already_existing' => $valid->where('existing', true)->count(),
            'existing_candidates' => $valid->where('existing', true)->count(),
            'rows_to_insert' => $valid->where('action', 'insert')->count(),
            'rows_to_replace' => $valid->where('action', 'replace')->count(),
            'rows_to_skip' => $valid->where('action', 'skip')->count(),
            'ready_to_insert' => $valid->where('action', 'insert')->count(),
            'ready_to_update' => $valid->where('action', 'replace')->count(),
        ];
    }

    private function normalizeDuplicateMode(string $mode): string
    {
        return in_array($mode, ['skip', 'replace', 'stop'], true) ? $mode : 'skip';
    }

    private function candidateImportPreviewMessage(string $action): string
    {
        return match ($action) {
            'replace' => 'Existing pupil will be replaced during commit.',
            'skip' => 'Existing pupil will be skipped during commit.',
            default => 'Ready to import.',
        };
    }

    private function cleanUtf8(string $value): string
    {
        // Remove UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $value = preg_replace("/^$bom/", '', $value);
        
        // Detect encoding and convert
        $encoding = mb_detect_encoding($value, 'UTF-8, ISO-8859-1, Windows-1252', true);
        if ($encoding !== 'UTF-8') {
            $value = mb_convert_encoding($value, 'UTF-8', $encoding ?: 'ISO-8859-1');
        }
        
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    private function readCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            return [];
        }
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(function ($value) {
                if ($value === null || $value === '') {
                    return '';
                }
                return $this->cleanUtf8((string) $value);
            }, $row);
        }
        fclose($handle);

        return $rows;
    }

    private function blankRow(array $row): bool
    {
        return collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty();
    }

    private function registerForPsle(Candidate $candidate, ExamType $examType, ExamYear $examYear): void
    {
        CandidateExamRegistration::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
            ],
            [
                'year' => (int) $examYear->year_label,
                'registration_number' => 'PSLE-' . $candidate->candidate_id,
                'status' => 'APPROVED',
            ]
        );
    }

    private function indexNumberPatternForSchool(School $school): string
    {
        return '/^' . preg_quote((string) $school->code, '/') . '-[0-9]{4}$/';
    }

    private function audit(string $event, User $user, ?School $school, ExamYear $examYear, array $metadata = [], int $affectedCandidates = 1): void
    {
        try {
            $this->activityLogger->log([
                'exam_year_id' => $examYear->id,
                'region_id' => $school?->region_id ?: $school?->council?->region_id,
                'district_id' => $school?->district_id,
                'school_id' => $school?->id,
                'user_id' => $user->id,
                'event_type' => $event,
                'title' => Str::headline(str_replace('psle_', '', $event)),
                'description' => 'PSLE candidate registration activity recorded.',
                'affected_candidates' => $affectedCandidates,
                'metadata' => $metadata,
            ]);

            GovernanceAuditLog::log($event, userId: $user->id, data: array_merge($metadata, [
                'exam_year_id' => $examYear->id,
                'exam_year' => $examYear->year_label,
                'school_id' => $school?->id,
                'school_name' => $school?->name,
                'centre_number' => $school?->code,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]));
        } catch (\Throwable $e) {
            Log::warning('PSLE candidate registration audit failed.', [
                'event' => $event,
                'user_id' => $user->id,
                'school_id' => $school?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
