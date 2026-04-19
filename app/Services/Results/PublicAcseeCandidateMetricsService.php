<?php

namespace App\Services\Results;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;
use App\Models\SubjectMarks;
use Illuminate\Support\Collection;

class PublicAcseeCandidateMetricsService
{
    public function __construct(
        private readonly NectaGradingService $gradingService
    ) {
    }

    public function computeForCandidateIds(
        Collection|array $candidateIds,
        ExamType $examTypeModel,
        int $examYear,
        ?Collection $storedFinalRows = null,
        ?Collection $storedStatusRows = null
    ): Collection {
        $candidateIds = collect($candidateIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($candidateIds->isEmpty()) {
            return collect();
        }

        $storedFinalRows = $storedFinalRows ?? collect();
        $storedStatusRows = $storedStatusRows ?? collect();

        $candidates = Candidate::query()
            ->whereIn('id', $candidateIds)
            ->get(['id', 'candidate_type']);

        $candidateTypeById = $candidates
            ->keyBy('id')
            ->map(fn (Candidate $candidate) => strtoupper((string) ($candidate->candidate_type ?? '')));

        $subjectSelectionsByCandidate = CandidateSubjectSelection::query()
            ->whereIn('candidate_id', $candidateIds)
            ->where('exam_type_id', $examTypeModel->id)
            ->where('year', $examYear)
            ->where('is_active', true)
            ->with('subject')
            ->orderBy('subject_id')
            ->get()
            ->groupBy('candidate_id');

        $marksByCandidate = SubjectMarks::query()
            ->whereIn('candidate_id', $candidateIds)
            ->where('exam_type_id', $examTypeModel->id)
            ->where('year', $examYear)
            ->with('subject')
            ->orderByDesc('id')
            ->get()
            ->groupBy('candidate_id')
            ->map(function ($rows) {
                return $rows
                    ->groupBy('subject_id')
                    ->map(fn ($subjectRows) => $this->pickPreferredMarkForSubjectRows($subjectRows))
                    ->filter();
            });

        return $candidateIds->mapWithKeys(function (int $candidateId) use (
            $candidateTypeById,
            $subjectSelectionsByCandidate,
            $marksByCandidate,
            $storedFinalRows,
            $storedStatusRows
        ) {
            $candidateMarkMap = $marksByCandidate->get($candidateId, collect());
            $allocatedSubjects = $subjectSelectionsByCandidate->get($candidateId, collect());
            $isPrivateCandidate = ($candidateTypeById->get($candidateId) ?? '') === 'PRIVATE';

            if ($allocatedSubjects->isNotEmpty()) {
                $subjectsForResults = $allocatedSubjects->map(function ($selection) use ($candidateMarkMap) {
                    $mark = $candidateMarkMap->get($selection->subject_id);

                    return [
                        'subject' => $selection->subject,
                        'mark' => $mark,
                        'subject_id' => (int) $selection->subject_id,
                        'is_principal' => (bool) ($selection->is_principal ?? false),
                    ];
                });
            } elseif ($isPrivateCandidate) {
                $subjectsForResults = $candidateMarkMap
                    ->filter(function ($mark) {
                        if (!$mark) {
                            return false;
                        }

                        $status = strtoupper((string) ($mark->subject_status ?? ''));

                        return $mark->marks_obtained !== null
                            || $mark->paper_1 !== null
                            || $mark->paper_2 !== null
                            || $mark->paper_3 !== null
                            || in_array($status, ['ABS', 'INC', 'X'], true);
                    })
                    ->map(function ($mark) {
                        return [
                            'subject' => $mark->subject,
                            'mark' => $mark,
                            'subject_id' => (int) $mark->subject_id,
                            'is_principal' => false,
                        ];
                    })
                    ->values();
            } else {
                $subjectsForResults = $candidateMarkMap
                    ->map(function ($mark) {
                        return [
                            'subject' => $mark->subject,
                            'mark' => $mark,
                            'subject_id' => (int) $mark->subject_id,
                            'is_principal' => false,
                        ];
                    })
                    ->values();
            }

            $subjectsForResults = collect($subjectsForResults)
                ->sortBy(function (array $row) {
                    $subject = $row['subject'] ?? null;
                    $code = strtoupper(trim((string) ($subject?->code ?? '')));
                    $name = strtoupper(trim((string) ($subject?->name ?? '')));
                    $label = strtoupper($this->formatNectaSubjectLabel($subject?->code, $subject?->name));
                    $isGeneralStudies = $code === '111'
                        || $name === 'GENERAL STUDIES'
                        || $label === 'G/STUDIES';

                    return sprintf(
                        '%d_%s_%s',
                        $isGeneralStudies ? 0 : 1,
                        $code !== '' ? $code : 'ZZZ',
                        $name
                    );
                })
                ->values();

            $totalMarks = 0.0;
            $marksCount = 0;
            $subjectResults = [];
            $hasAnyEnteredMark = false;
            $hasIncompleteSubject = false;
            $allAbsentOrX = $subjectsForResults->isNotEmpty();
            $subjectGrades = [];
            $coreSubjectIds = $subjectsForResults
                ->filter(fn ($row) => !empty($row['is_principal']) && !empty($row['subject_id']))
                ->pluck('subject_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            foreach ($subjectsForResults as $subjectRow) {
                $subject = $subjectRow['subject'];
                $mark = $subjectRow['mark'];
                $subjectId = (int) ($subjectRow['subject_id'] ?? 0);
                $isPrincipal = (bool) ($subjectRow['is_principal'] ?? false);
                $subjectName = $subject?->name ?? '-';
                $subjectLabel = $this->formatNectaSubjectLabel($subject?->code, $subjectName);
                $subjectStatus = strtoupper((string) ($mark?->subject_status ?? ''));

                if (!in_array($subjectStatus, ['ABS', 'X'], true)) {
                    $allAbsentOrX = false;
                }

                if (
                    $mark
                    && (
                        $mark->marks_obtained !== null
                        || $mark->paper_1 !== null
                        || $mark->paper_2 !== null
                        || $mark->paper_3 !== null
                        || $subjectStatus === 'INC'
                    )
                ) {
                    $hasAnyEnteredMark = true;
                }

                $requiredPapers = $this->requiredPaperCodes($subject);
                $missingRequiredPaper = false;
                if (!$mark) {
                    $missingRequiredPaper = true;
                } else {
                    foreach ($requiredPapers as $paperCode) {
                        if ($mark->{$paperCode} === null) {
                            $missingRequiredPaper = true;
                            break;
                        }
                    }
                }

                if ($missingRequiredPaper || $subjectStatus === 'INC') {
                    $hasIncompleteSubject = true;
                    if ($isPrivateCandidate && !$mark && !$hasAnyEnteredMark) {
                        $subjectResults[] = $subjectLabel . "=ABS 'ABS'";
                    } else {
                        $subjectResults[] = $subjectLabel . "=INC 'INC'";
                    }
                    continue;
                }

                if (in_array($subjectStatus, ['ABS', 'X'], true)) {
                    $subjectResults[] = $subjectLabel . '=' . $subjectStatus . " '" . $subjectStatus . "'";
                    continue;
                }

                $subjectNormalized = $mark?->marks_obtained;
                $subjectGrade = $subjectNormalized !== null
                    ? $this->gradingService->calculateGrade((float) $subjectNormalized)
                    : ($mark?->grade ?? null);

                $subjectResults[] = $subjectLabel . '=' . ($subjectNormalized !== null ? $subjectNormalized : '-')
                    . ($subjectGrade ? " '" . $subjectGrade . "'" : '');

                if ($this->shouldIncludeInAggtAndGpa($subjectName) && $subjectGrade && $mark && $mark->marks_obtained !== null) {
                    $subjectGrades[] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'grade' => $subjectGrade,
                        'points' => $this->gradingService->getGradePoints($subjectGrade),
                    ];
                }

                if ($mark && $mark->marks_obtained !== null) {
                    $marksCount++;
                    $totalMarks += (float) ($mark->marks_obtained ?? 0);
                }
            }

            $candidateStatus = 'COMPLETE';
            if (!$hasAnyEnteredMark) {
                $candidateStatus = 'ABS';
            } elseif ($hasIncompleteSubject) {
                $candidateStatus = 'INC';
            } elseif ($allAbsentOrX) {
                $candidateStatus = 'ABS';
            }

            $effectiveCoreSubjectIds = !empty($coreSubjectIds)
                ? $coreSubjectIds
                : collect($subjectGrades)
                    ->pluck('subject_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

            $aggtPoints = $this->gradingService->calculateAggtFromSubjectGrades($subjectGrades, $effectiveCoreSubjectIds);
            $principalPassCount = $this->gradingService->countPrincipalPassesFromSubjectGrades($subjectGrades, $effectiveCoreSubjectIds);
            $totalPoints = $aggtPoints ?? 0;

            $coreSet = collect($effectiveCoreSubjectIds)->map(fn ($id) => (int) $id)->values()->all();
            $gpaRows = collect($subjectGrades)
                ->filter(fn (array $row) => in_array((int) ($row['subject_id'] ?? 0), $coreSet, true))
                ->sortBy(fn (array $row) => (float) ($row['points'] ?? 99))
                ->take(3)
                ->values();

            $gpaSubjectCount = $gpaRows->count();
            $gpa = ($candidateStatus === 'COMPLETE' && $gpaSubjectCount > 0)
                ? round(((float) $gpaRows->sum(fn (array $row) => (float) ($row['points'] ?? 0))) / $gpaSubjectCount, 4)
                : 0.0;
            $gpaPointsSum = (float) $gpaRows->sum(fn (array $row) => (float) ($row['points'] ?? 0));
            $gpaInfo = $this->gradingService->getGpaCompetence($gpa);
            $usedStoredFallback = false;

            $division = '0';
            $divisionNumeric = 0;
            if ($candidateStatus === 'COMPLETE' && $marksCount > 0 && $aggtPoints !== null) {
                $divisionInfo = $this->gradingService->calculateDivisionWithEligibility(
                    (float) $totalPoints,
                    (int) $principalPassCount
                );
                $divisionNumeric = (int) ($divisionInfo['division'] ?? 0);
                $division = $this->mapDivisionNumberToLabel($divisionNumeric);
            }

            $storedFinal = $storedFinalRows->get($candidateId);
            if ($storedFinal && !$hasAnyEnteredMark) {
                $usedStoredFallback = true;
                $candidateStatus = $this->resolveStoredStatus($candidateId, $storedStatusRows, $storedFinalRows);
                $storedBreakdown = is_array($storedFinal->grading_breakdown ?? null)
                    ? $storedFinal->grading_breakdown
                    : json_decode((string) ($storedFinal->grading_breakdown ?? ''), true);

                $aggtPoints = data_get($storedBreakdown, 'aggt_points');
                $principalPassCount = (int) data_get($storedBreakdown, 'principal_passes', $principalPassCount);
                $totalPoints = (float) (data_get($storedBreakdown, 'aggt_points') ?? $totalPoints ?? 0);
                $storedGpa = $storedFinal->gpa;
                $gpa = !is_null($storedGpa) && $storedGpa !== '' ? (float) $storedGpa : 0.0;
                $gpaInfo = $this->gradingService->getGpaCompetence($gpa);

                if ($candidateStatus === 'COMPLETE') {
                    $divisionNumeric = (int) ($storedFinal->division ?? 0);
                    $division = $this->mapDivisionNumberToLabel($divisionNumeric);
                } elseif ($candidateStatus === 'INC') {
                    $divisionNumeric = 0;
                    $division = 'INC';
                } else {
                    $divisionNumeric = 0;
                    $division = 'ABS';
                }
            }

            return [
                $candidateId => [
                    'totalMarks' => $totalMarks,
                    'average' => $marksCount > 0 ? $totalMarks / $marksCount : 0.0,
                    'gpa' => $gpa,
                    'gpaInfo' => $gpaInfo,
                    'division' => $division,
                    'division_numeric' => $divisionNumeric,
                    'aggt_points' => $aggtPoints,
                    'principal_passes' => $principalPassCount,
                    'totalPoints' => $totalPoints,
                    'gpaPointsSum' => $gpaPointsSum,
                    'gpaSubjectCount' => $gpaSubjectCount,
                    'subjectResultsStr' => $allAbsentOrX ? 'ABS' : implode(', ', $subjectResults),
                    'candidateStatus' => $candidateStatus,
                    'latestMarks' => $candidateMarkMap->values(),
                    'usedStoredFallback' => $usedStoredFallback,
                ],
            ];
        });
    }

    private function resolveStoredStatus(int $candidateId, Collection $storedStatusRows, Collection $storedFinalRows): string
    {
        $stored = strtoupper(trim((string) ($storedStatusRows->get($candidateId)->result_status ?? '')));
        if (in_array($stored, ['COMPLETE', 'INC', 'ABS'], true)) {
            return $stored;
        }

        $final = $storedFinalRows->get($candidateId);
        $decoded = is_array($final?->grading_breakdown ?? null)
            ? $final->grading_breakdown
            : json_decode((string) ($final?->grading_breakdown ?? ''), true);
        $irregular = strtoupper(trim((string) data_get($decoded, 'irregular_overall_status', '')));

        if (in_array($irregular, ['ABS', 'X'], true)) {
            return 'ABS';
        }
        if ($irregular !== '') {
            return 'INC';
        }

        $aggtPoints = data_get($decoded, 'aggt_points');
        $principalPasses = (int) data_get($decoded, 'principal_passes', 0);
        $gpaSubjectsCount = (int) data_get($decoded, 'gpa_subjects_count', 0);

        if ($aggtPoints === null && $principalPasses === 0 && $gpaSubjectsCount === 0) {
            return 'INC';
        }

        return 'COMPLETE';
    }

    private function requiredPaperCodes($subject): array
    {
        if (!$subject) {
            return ['paper_1'];
        }

        $codes = [];
        $written = max(1, min(2, (int) ($subject->written_papers ?? 1)));
        for ($i = 1; $i <= $written; $i++) {
            $codes[] = "paper_{$i}";
        }
        if (!empty($subject->has_practical)) {
            $codes[] = 'paper_3';
        }

        return array_values(array_unique($codes));
    }

    private function shouldIncludeInAggtAndGpa(string $subjectName): bool
    {
        $normalized = strtoupper(trim($subjectName));

        return !in_array($normalized, ['GENERAL STUDIES', 'BASIC APPLIED MATHEMATICS'], true);
    }

    private function pickPreferredMarkForSubjectRows($subjectRows): ?SubjectMarks
    {
        $rows = collect($subjectRows)->sortByDesc('id')->values();
        if ($rows->isEmpty()) {
            return null;
        }

        $subject = $rows->first()?->subject;
        $requiredPapers = $this->requiredPaperCodes($subject);
        $hasPositiveByPaper = [];
        foreach ($requiredPapers as $paperCode) {
            $hasPositiveByPaper[$paperCode] = $rows->contains(function ($mark) use ($paperCode) {
                $v = $mark->{$paperCode} ?? null;

                return $v !== null && (float) $v > 0;
            });
        }

        $preferred = $rows->first(function ($mark) use ($requiredPapers, $hasPositiveByPaper) {
            if (!$mark) {
                return false;
            }

            $status = strtoupper((string) ($mark->subject_status ?? ''));
            if ($status === 'INC') {
                return false;
            }

            foreach ($requiredPapers as $paperCode) {
                $value = $mark->{$paperCode} ?? null;
                if ($value === null) {
                    return false;
                }
                if (($hasPositiveByPaper[$paperCode] ?? false) && (float) $value <= 0) {
                    return false;
                }
            }

            return true;
        });

        return $preferred ?: $rows->first();
    }

    private function formatNectaSubjectLabel(?string $subjectCode, ?string $subjectName): string
    {
        $aliases = (array) config('necta_subject_aliases.acsee', []);
        $code = trim((string) ($subjectCode ?? ''));
        if ($code !== '' && isset($aliases[$code])) {
            return (string) $aliases[$code];
        }

        return strtoupper(trim((string) ($subjectName ?? 'SUBJECT')));
    }

    private function mapDivisionNumberToLabel(int $division): string
    {
        return match ($division) {
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            default => '0',
        };
    }
}
