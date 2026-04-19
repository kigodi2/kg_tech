<?php

namespace App\Console\Commands;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\ResultProcess;
use App\Models\ResultSnapshot;
use App\Services\Results\PublicAcseeCandidateMetricsService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncAcseeStoredFinalGrades extends Command
{
    protected $signature = 'results:sync-acsee-stored-finals
        {--exam-year= : Optional numeric year label (defaults to active year)}
        {--school-id= : Restrict to one school ID}
        {--process-id= : Limit to a specific result process}
        {--snapshot-id= : Limit to a specific snapshot}
        {--chunk=500 : Chunk size for write operations}
        {--dry-run : Preview mismatches without writing}';

    protected $description = 'Sync ACSEE stored final_grades and candidate_results from the live public-results calculation path.';

    public function __construct(
        private readonly PublicAcseeCandidateMetricsService $candidateMetricsService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $acsee = ExamType::query()->where('code', 'ACSEE')->first();
        if (!$acsee) {
            $this->error('ACSEE exam type not found.');
            return self::FAILURE;
        }

        $year = $this->resolveYear();
        if ($year === null) {
            return self::FAILURE;
        }

        $schoolId = $this->option('school-id');
        $chunkSize = max(100, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $activeYear = ExamYear::query()
            ->where('year_label', (string) $year)
            ->orWhere('year', (string) $year)
            ->first();

        $registrationRows = $this->loadRegistrationRows($acsee->id, $year, $activeYear?->id, $schoolId);
        if ($registrationRows->isEmpty()) {
            $this->warn('No ACSEE candidate registrations matched the selected scope.');
            return self::SUCCESS;
        }

        $candidateIds = $registrationRows->pluck('candidate_id')->unique()->values();
        $scope = $this->resolveScope($acsee->id, $year, $activeYear?->id);
        $storedFinalRows = $this->loadScopedFinalRows($acsee->id, $year, $candidateIds, $scope, $schoolId);
        $storedStatusRows = $this->loadScopedStatusRows($acsee->id, $year, $candidateIds, $scope);
        $computedMetrics = $this->candidateMetricsService
            ->computeForCandidateIds($candidateIds, $acsee, $year, $storedFinalRows, $storedStatusRows);

        if ($computedMetrics->isEmpty()) {
            $this->warn('No computed candidate metrics were produced for the selected scope.');
            return self::SUCCESS;
        }

        $finalGradeTargets = $this->findFinalGradeTargets($acsee->id, $year, $candidateIds, $scope, $schoolId);
        $candidateResultTargets = $this->findCandidateResultTargets($acsee->id, $year, $candidateIds, $scope);

        $finalUpdates = [];
        $resultUpdates = [];
        $stats = [
            'candidates' => $candidateIds->count(),
            'final_grade_rows_scanned' => $finalGradeTargets->count(),
            'candidate_result_rows_scanned' => $candidateResultTargets->count(),
            'final_grade_updates' => 0,
            'candidate_result_updates' => 0,
            'used_stored_fallback' => 0,
        ];

        foreach ($computedMetrics as $candidateId => $metrics) {
            if (!empty($metrics['usedStoredFallback'])) {
                $stats['used_stored_fallback']++;
            }

            $finalRow = $finalGradeTargets->get((int) $candidateId);
            if ($finalRow) {
                $existingBreakdown = $this->decodeBreakdown($finalRow->grading_breakdown ?? null);
                $newBreakdown = $this->mergeBreakdown($existingBreakdown, $metrics);
                $newDivision = (int) ($metrics['division_numeric'] ?? 0);
                $newGpa = isset($metrics['gpa']) ? (float) $metrics['gpa'] : null;

                $existingDivision = (int) ($finalRow->division ?? 0);
                $existingGpa = $finalRow->gpa !== null && $finalRow->gpa !== '' ? (float) $finalRow->gpa : null;
                $existingBreakdownJson = json_encode($existingBreakdown);
                $newBreakdownJson = json_encode($newBreakdown);

                if ($existingDivision !== $newDivision || $existingGpa !== $newGpa || $existingBreakdownJson !== $newBreakdownJson) {
                    $finalUpdates[(int) $finalRow->id] = [
                        'division' => $newDivision,
                        'gpa' => $newGpa,
                        'grading_breakdown' => $newBreakdown,
                    ];
                }
            }

            $resultRow = $candidateResultTargets->get((int) $candidateId);
            if ($resultRow) {
                $payload = [];
                $newStatus = (string) ($metrics['candidateStatus'] ?? 'ABS');

                if (Schema::hasColumn('candidate_results', 'result_status')) {
                    $existingStatus = strtoupper(trim((string) ($resultRow->result_status ?? '')));
                    if ($existingStatus !== $newStatus) {
                        $payload['result_status'] = $newStatus;
                    }
                }

                if (Schema::hasColumn('candidate_results', 'division')) {
                    $newDivision = (int) ($metrics['division_numeric'] ?? 0);
                    $existingDivision = (int) ($resultRow->division ?? 0);
                    if ($existingDivision !== $newDivision) {
                        $payload['division'] = $newDivision;
                    }
                }

                if (!empty($payload)) {
                    $resultUpdates[(int) $resultRow->id] = $payload;
                }
            }
        }

        $stats['final_grade_updates'] = count($finalUpdates);
        $stats['candidate_result_updates'] = count($resultUpdates);

        $this->info('ACSEE stored finals sync summary');
        $this->line('Exam year: ' . $year);
        $this->line('School scope: ' . ($schoolId !== null && $schoolId !== '' ? (string) $schoolId : 'ALL'));
        $this->line('Selection scope: ' . $this->describeScope($scope));
        $this->line('Candidates: ' . $stats['candidates']);
        $this->line('Final grade rows to update: ' . $stats['final_grade_updates']);
        $this->line('Candidate result rows to update: ' . $stats['candidate_result_updates']);
        $this->line('Candidates using stored fallback (no live marks): ' . $stats['used_stored_fallback']);
        $this->newLine();

        $preview = collect($finalUpdates)
            ->take(10)
            ->map(function (array $payload, int $rowId) use ($finalGradeTargets) {
                $row = $finalGradeTargets->firstWhere('id', $rowId);
                return [
                    'FinalGrade ID' => $rowId,
                    'Candidate ID' => $row->candidate_id ?? '-',
                    'Old Div' => (int) ($row->division ?? 0),
                    'New Div' => (int) ($payload['division'] ?? 0),
                    'Old GPA' => $row->gpa ?? '-',
                    'New GPA' => $payload['gpa'] ?? '-',
                ];
            })
            ->values()
            ->all();

        if (!empty($preview)) {
            $this->table(['FinalGrade ID', 'Candidate ID', 'Old Div', 'New Div', 'Old GPA', 'New GPA'], $preview);
        }

        if ($dryRun) {
            $this->warn('Dry run only. No rows were updated.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($finalUpdates, $resultUpdates, $chunkSize) {
            foreach (collect($finalUpdates)->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $rowId => $payload) {
                    DB::table('final_grades')
                        ->where('id', (int) $rowId)
                        ->update([
                            'division' => (int) $payload['division'],
                            'gpa' => $payload['gpa'],
                            'grading_breakdown' => json_encode($payload['grading_breakdown']),
                            'updated_at' => now(),
                        ]);
                }
            }

            foreach (collect($resultUpdates)->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $rowId => $payload) {
                    $payload['updated_at'] = now();
                    DB::table('candidate_results')
                        ->where('id', (int) $rowId)
                        ->update($payload);
                }
            }
        });

        $this->info('Stored ACSEE finals sync applied successfully.');
        return self::SUCCESS;
    }

    private function resolveYear(): ?int
    {
        $yearOption = $this->option('exam-year');
        if ($yearOption !== null && $yearOption !== '') {
            return (int) $yearOption;
        }

        $activeYear = ExamYear::query()->active()->first();
        if ($activeYear) {
            return (int) $activeYear->year_label;
        }

        $this->error('No active exam year found. Pass --exam-year explicitly.');
        return null;
    }

    private function loadRegistrationRows(int $examTypeId, int $year, ?int $activeYearId, $schoolId): Collection
    {
        $query = DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->where('cer.exam_type_id', $examTypeId)
            ->where(function ($q) use ($activeYearId, $year) {
                $q->where('cer.year', $year);
                if ($activeYearId) {
                    $q->orWhere('cer.exam_year_id', $activeYearId);
                }
            })
            ->when($schoolId !== null && $schoolId !== '', fn ($q) => $q->where('s.id', (int) $schoolId))
            ->select('cer.candidate_id', 's.id as school_id')
            ->get();

        return $query;
    }

    private function resolveScope(int $examTypeId, int $year, ?int $activeYearId): array
    {
        $snapshotIdOption = $this->option('snapshot-id');
        if ($snapshotIdOption !== null && $snapshotIdOption !== '') {
            return ['snapshot_id' => (int) $snapshotIdOption, 'process_id' => null, 'mode' => 'snapshot'];
        }

        $processIdOption = $this->option('process-id');
        if ($processIdOption !== null && $processIdOption !== '') {
            return ['snapshot_id' => null, 'process_id' => (int) $processIdOption, 'mode' => 'process'];
        }

        $activeSnapshot = ResultSnapshot::query()
            ->where('exam_year_id', $activeYearId)
            ->where('is_active', true)
            ->first();

        if ($activeSnapshot && Schema::hasColumn('final_grades', 'snapshot_id')) {
            $hasSnapshotRows = DB::table('final_grades')
                ->where('exam_type_id', $examTypeId)
                ->where('year', $year)
                ->where('snapshot_id', $activeSnapshot->id)
                ->exists();

            if ($hasSnapshotRows) {
                return ['snapshot_id' => (int) $activeSnapshot->id, 'process_id' => null, 'mode' => 'snapshot'];
            }
        }

        $latestProcessId = ResultProcess::query()
            ->where('exam_type_id', $examTypeId)
            ->where('exam_year_id', $activeYearId)
            ->where('status', 'completed')
            ->latest('id')
            ->value('id');

        if ($latestProcessId) {
            return ['snapshot_id' => null, 'process_id' => (int) $latestProcessId, 'mode' => 'process'];
        }

        return ['snapshot_id' => null, 'process_id' => null, 'mode' => 'latest'];
    }

    private function loadScopedFinalRows(int $examTypeId, int $year, Collection $candidateIds, array $scope, $schoolId): Collection
    {
        $query = DB::table('final_grades as fg')
            ->join('candidates as c', 'c.id', '=', 'fg.candidate_id')
            ->when($schoolId !== null && $schoolId !== '', fn ($q) => $q->where('c.school_id', (int) $schoolId))
            ->where('fg.exam_type_id', $examTypeId)
            ->where('fg.year', $year)
            ->whereIn('fg.candidate_id', $candidateIds);

        if ($scope['mode'] === 'snapshot') {
            $query->where('fg.snapshot_id', $scope['snapshot_id']);
        } elseif ($scope['mode'] === 'process') {
            $query->where('fg.process_id', $scope['process_id'])
                ->whereNull('fg.snapshot_id');
        }

        $rows = $query
            ->orderByDesc('fg.id')
            ->get(['fg.id', 'fg.candidate_id', 'fg.gpa', 'fg.division', 'fg.grading_breakdown'])
            ->unique('candidate_id')
            ->keyBy('candidate_id');

        $missingCandidateIds = $candidateIds->diff($rows->keys())->values();
        if ($missingCandidateIds->isNotEmpty()) {
            $fallbackRows = DB::table('final_grades as fg')
                ->join('candidates as c', 'c.id', '=', 'fg.candidate_id')
                ->when($schoolId !== null && $schoolId !== '', fn ($q) => $q->where('c.school_id', (int) $schoolId))
                ->where('fg.exam_type_id', $examTypeId)
                ->where('fg.year', $year)
                ->whereIn('fg.candidate_id', $missingCandidateIds)
                ->orderByDesc('fg.id')
                ->get(['fg.id', 'fg.candidate_id', 'fg.gpa', 'fg.division', 'fg.grading_breakdown'])
                ->unique('candidate_id')
                ->keyBy('candidate_id');

            $rows = $rows->union($fallbackRows);
        }

        return $rows;
    }

    private function loadScopedStatusRows(int $examTypeId, int $year, Collection $candidateIds, array $scope): Collection
    {
        if (!Schema::hasColumn('candidate_results', 'result_status')) {
            return collect();
        }

        $query = DB::table('candidate_results as cr')
            ->where('cr.exam_type_id', $examTypeId)
            ->where('cr.year', $year)
            ->whereIn('cr.candidate_id', $candidateIds);

        if ($scope['mode'] === 'snapshot' && Schema::hasColumn('candidate_results', 'snapshot_id')) {
            $query->where('cr.snapshot_id', $scope['snapshot_id']);
        } elseif ($scope['mode'] === 'process') {
            $query->where('cr.process_id', $scope['process_id']);
            if (Schema::hasColumn('candidate_results', 'snapshot_id')) {
                $query->whereNull('cr.snapshot_id');
            }
        }

        $rows = $query
            ->orderByDesc('cr.id')
            ->get(['cr.id', 'cr.candidate_id', 'cr.result_status'])
            ->unique('candidate_id')
            ->keyBy('candidate_id');

        $missingCandidateIds = $candidateIds->diff($rows->keys())->values();
        if ($missingCandidateIds->isNotEmpty()) {
            $fallbackRows = DB::table('candidate_results as cr')
                ->where('cr.exam_type_id', $examTypeId)
                ->where('cr.year', $year)
                ->whereIn('cr.candidate_id', $missingCandidateIds)
                ->orderByDesc('cr.id')
                ->get(['cr.id', 'cr.candidate_id', 'cr.result_status'])
                ->unique('candidate_id')
                ->keyBy('candidate_id');

            $rows = $rows->union($fallbackRows);
        }

        return $rows;
    }

    private function findFinalGradeTargets(int $examTypeId, int $year, Collection $candidateIds, array $scope, $schoolId): Collection
    {
        return $this->loadScopedFinalRows($examTypeId, $year, $candidateIds, $scope, $schoolId);
    }

    private function findCandidateResultTargets(int $examTypeId, int $year, Collection $candidateIds, array $scope): Collection
    {
        $select = ['cr.id', 'cr.candidate_id'];
        if (Schema::hasColumn('candidate_results', 'result_status')) {
            $select[] = 'cr.result_status';
        }
        if (Schema::hasColumn('candidate_results', 'division')) {
            $select[] = 'cr.division';
        }

        $query = DB::table('candidate_results as cr')
            ->where('cr.exam_type_id', $examTypeId)
            ->where('cr.year', $year)
            ->whereIn('cr.candidate_id', $candidateIds);

        if ($scope['mode'] === 'snapshot' && Schema::hasColumn('candidate_results', 'snapshot_id')) {
            $query->where('cr.snapshot_id', $scope['snapshot_id']);
        } elseif ($scope['mode'] === 'process') {
            $query->where('cr.process_id', $scope['process_id']);
            if (Schema::hasColumn('candidate_results', 'snapshot_id')) {
                $query->whereNull('cr.snapshot_id');
            }
        }

        $rows = $query->orderByDesc('cr.id')->get($select)->unique('candidate_id')->keyBy('candidate_id');
        $missingCandidateIds = $candidateIds->diff($rows->keys())->values();
        if ($missingCandidateIds->isNotEmpty()) {
            $fallbackRows = DB::table('candidate_results as cr')
                ->where('cr.exam_type_id', $examTypeId)
                ->where('cr.year', $year)
                ->whereIn('cr.candidate_id', $missingCandidateIds)
                ->orderByDesc('cr.id')
                ->get($select)
                ->unique('candidate_id')
                ->keyBy('candidate_id');

            $rows = $rows->union($fallbackRows);
        }

        return $rows;
    }

    private function mergeBreakdown(array $existing, array $metrics): array
    {
        $status = strtoupper((string) ($metrics['candidateStatus'] ?? 'ABS'));
        $existing['aggt_points'] = $status === 'COMPLETE' ? ($metrics['aggt_points'] ?? $metrics['totalPoints'] ?? null) : null;
        $existing['principal_passes'] = $status === 'COMPLETE' ? (int) ($metrics['principal_passes'] ?? 0) : 0;
        $existing['gpa_subjects_count'] = $status === 'COMPLETE' ? (int) ($metrics['gpaSubjectCount'] ?? 0) : 0;
        $existing['division'] = (int) ($metrics['division_numeric'] ?? 0);
        $existing['irregular_overall_status'] = match ($status) {
            'ABS' => 'ABS',
            'INC' => 'INC',
            default => '',
        };

        return $existing;
    }

    private function decodeBreakdown($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function describeScope(array $scope): string
    {
        return match ($scope['mode']) {
            'snapshot' => 'snapshot_id=' . $scope['snapshot_id'],
            'process' => 'process_id=' . $scope['process_id'],
            default => 'latest-per-candidate fallback',
        };
    }
}
