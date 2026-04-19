<?php

namespace App\Services\MarkEntry\Questions;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\QuestionMarkEntry;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionMarkEntryService
{
    public function __construct(
        private RegionalCandidateAccessService $accessService,
        private QuestionStructureResolver $structureResolver
    ) {
    }

    public function pageData(string $examCode, User $user, ?string $candidateNo = null, ?int $subjectId = null): array
    {
        $examType = $this->resolveExamType($examCode);
        $examYear = ExamYear::activeOrFail();

        $payload = [
            'examType' => $examType,
            'examYear' => $examYear,
            'subjects' => Subject::query()
                ->where('exam_type_id', $examType->id)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'candidateNo' => $candidateNo,
            'selectedSubjectId' => $subjectId,
            'loaded' => false,
            'loadedEntry' => null,
            'structure' => null,
            'scores' => [],
            'canEdit' => true,
            'candidate' => null,
        ];

        if (!$candidateNo || !$subjectId) {
            return $payload;
        }

        $context = $this->resolveContext($examCode, $user, $candidateNo, $subjectId);
        $entry = $this->findEntry($context['examType'], $context['examYear'], $context['candidate'], $context['subject']);
        $structure = $this->structureResolver->resolve($context['examType'], $context['subject']);
        $scores = $this->scoreMap($structure['questions'], $entry);
        $canEdit = !$entry || $entry->status !== QuestionMarkEntry::STATUS_SUBMITTED || $this->accessService->canEditSubmitted($user);

        return array_merge($payload, [
            'loaded' => true,
            'loadedEntry' => $entry,
            'structure' => $structure,
            'scores' => $scores,
            'canEdit' => $canEdit,
            'candidate' => $context['candidate'],
            'selectedSubjectId' => $context['subject']->id,
        ]);
    }

    public function save(string $examCode, User $user, array $payload): QuestionMarkEntry
    {
        $context = $this->resolveContext(
            $examCode,
            $user,
            (string) $payload['candidate_no'],
            (int) $payload['subject_id']
        );

        $entry = $this->findEntry($context['examType'], $context['examYear'], $context['candidate'], $context['subject']);
        if ($entry && $entry->status === QuestionMarkEntry::STATUS_SUBMITTED && !$this->accessService->canEditSubmitted($user)) {
            throw ValidationException::withMessages([
                'candidate_no' => 'This entry has already been submitted and can only be edited by an authorized administrator.',
            ]);
        }

        $structure = $this->structureResolver->resolve($context['examType'], $context['subject']);
        $action = (string) ($payload['entry_action'] ?? 'draft');
        $submitted = $action === 'submit';
        $scores = $this->validatedScores($structure['questions'], $payload['scores'] ?? [], $submitted);
        $total = $this->calculateTotal($structure, $scores);

        return DB::transaction(function () use ($context, $user, $entry, $scores, $total, $submitted) {
            $entry = QuestionMarkEntry::query()->updateOrCreate(
                [
                    'exam_type_id' => $context['examType']->id,
                    'exam_year_id' => $context['examYear']->id,
                    'candidate_id' => $context['candidate']->id,
                    'subject_id' => $context['subject']->id,
                ],
                [
                    'exam_type' => $context['examType']->code,
                    'candidate_no' => (string) $context['candidate']->candidate_id,
                    'school_id' => $context['candidate']->school_id,
                    'region_id' => $context['candidate']->school?->region_id,
                    'entered_by' => $user->id,
                    'status' => $submitted ? QuestionMarkEntry::STATUS_SUBMITTED : QuestionMarkEntry::STATUS_DRAFT,
                    'total' => $total,
                    'submitted_at' => $submitted ? now() : null,
                ]
            );

            foreach ($scores as $questionNo => $scoreMeta) {
                $entry->items()->updateOrCreate(
                    ['question_no' => $questionNo],
                    [
                        'max_mark' => $scoreMeta['max_mark'],
                        'score' => $scoreMeta['score'],
                    ]
                );
            }

            return $entry->load(['items', 'candidate.school.region', 'subject']);
        });
    }

    public function resolveExamType(string $examCode): ExamType
    {
        return ExamType::query()
            ->where('code', strtoupper($examCode))
            ->firstOrFail();
    }

    private function resolveContext(string $examCode, User $user, string $candidateNo, int $subjectId): array
    {
        $examType = $this->resolveExamType($examCode);
        $examYear = ExamYear::activeOrFail();
        $subject = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->findOrFail($subjectId);

        $candidate = $this->candidateQuery($examType, $examYear, trim($candidateNo))->first();

        if (!$candidate) {
            throw ValidationException::withMessages([
                'candidate_no' => "Candidate number {$candidateNo} was not found for {$examType->code} in active year {$examYear->year_label}.",
            ]);
        }

        if (!$this->accessService->canAccessCandidate($user, $candidate)) {
            throw new AuthorizationException('You are not allowed to access candidates outside your assigned scope.');
        }

        if (!$this->candidateCanTakeSubject($candidate, $examType, $examYear, $subject)) {
            throw ValidationException::withMessages([
                'subject_id' => 'The candidate is not registered for the selected subject in the active exam context.',
            ]);
        }

        return compact('examType', 'examYear', 'subject', 'candidate');
    }

    private function candidateQuery(ExamType $examType, ExamYear $examYear, string $candidateNo)
    {
        return Candidate::query()
            ->with(['school.region', 'school.district'])
            ->where(function ($query) use ($examType, $candidateNo) {
                $query->where('candidate_id', $candidateNo);

                if ($examType->code === 'PSLE') {
                    $query->orWhere('prem_no', $candidateNo);
                }
            })
            ->whereHas('examRegistrations', function ($query) use ($examType, $examYear) {
                $query->where('exam_type_id', $examType->id)
                    ->where('exam_year_id', $examYear->id);
            });
    }

    private function candidateCanTakeSubject(Candidate $candidate, ExamType $examType, ExamYear $examYear, Subject $subject): bool
    {
        $selectionQuery = CandidateSubjectSelection::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id);

        if ($selectionQuery->exists()) {
            return (clone $selectionQuery)->where('subject_id', $subject->id)->exists();
        }

        return CandidateExamRegistration::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->exists();
    }

    private function findEntry(ExamType $examType, ExamYear $examYear, Candidate $candidate, Subject $subject): ?QuestionMarkEntry
    {
        return QuestionMarkEntry::query()
            ->with('items')
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->where('candidate_id', $candidate->id)
            ->where('subject_id', $subject->id)
            ->first();
    }

    private function scoreMap(array $questions, ?QuestionMarkEntry $entry): array
    {
        $existing = $entry
            ? $entry->items->keyBy(fn ($item) => (int) $item->question_no)
            : collect();

        $scores = [];

        foreach ($questions as $question) {
            $questionNo = (int) $question['question_no'];
            $scores[$questionNo] = $existing->has($questionNo)
                ? (string) $existing[$questionNo]->score
                : null;
        }

        return $scores;
    }

    private function validatedScores(array $questions, array $submittedScores, bool $submitted): array
    {
        $validated = [];

        foreach ($questions as $question) {
            $questionNo = (int) $question['question_no'];
            $maxMark = round((float) $question['max_mark'], 2);
            $rawScore = $submittedScores[$questionNo] ?? null;
            $score = $rawScore === '' || $rawScore === null ? null : round((float) $rawScore, 2);
            $label = $this->questionLabel($question);

            if ($submitted && $score === null) {
                throw ValidationException::withMessages([
                    "scores.{$questionNo}" => "{$label} is required before submission.",
                ]);
            }

            if ($score !== null && $score < 0) {
                throw ValidationException::withMessages([
                    "scores.{$questionNo}" => "{$label} cannot be below 0.",
                ]);
            }

            if ($score !== null && $score > $maxMark) {
                throw ValidationException::withMessages([
                    "scores.{$questionNo}" => "{$label} cannot exceed {$maxMark}.",
                ]);
            }

            $validated[$questionNo] = [
                'score' => $score,
                'max_mark' => $maxMark,
            ];
        }

        $this->validateChoiceGroups($validated, $questions, $submitted);

        return $validated;
    }

    private function calculateTotal(array $structure, array $scores): float
    {
        $aggregation = $structure['aggregation'] ?? 'sum';

        if ($aggregation === 'normalize_to_100') {
            $paperTotals = collect($structure['papers'] ?? [])
                ->map(function (array $paper) use ($scores) {
                    return [
                        'score' => (float) collect($paper['question_numbers'] ?? [])
                            ->sum(fn (int $questionNo) => (float) ($scores[$questionNo]['score'] ?? 0)),
                        'max' => (float) ($paper['max_mark_total'] ?? 0),
                    ];
                });

            $weightedSum = $paperTotals->sum('score');
            $weightedMax = $paperTotals->sum('max');

            if ($weightedMax <= 0) {
                return 0.0;
            }

            return round(($weightedSum / $weightedMax) * 100, 2);
        }

        if ($aggregation === 'average_paper_totals') {
            $paperTotals = collect($structure['papers'] ?? [])
                ->map(function (array $paper) use ($scores) {
                    return collect($paper['question_numbers'] ?? [])
                        ->sum(fn (int $questionNo) => (float) ($scores[$questionNo]['score'] ?? 0));
                })
                ->filter(fn (float $total) => $total >= 0)
                ->values();

            if ($paperTotals->isEmpty()) {
                return 0.0;
            }

            return round($paperTotals->avg(), 2);
        }

        return round((float) collect($scores)->sum(fn (array $scoreMeta) => $scoreMeta['score'] ?? 0), 2);
    }

    private function questionLabel(array $question): string
    {
        return (string) ($question['display_label'] ?? ('Q' . ((int) ($question['question_no'] ?? 0))));
    }

    private function validateChoiceGroups(array $validatedScores, array $questions, bool $submitted): void
    {
        $questionMeta = collect($questions)->keyBy(fn (array $question) => (int) $question['question_no']);
        $groups = collect($questions)
            ->filter(fn (array $question) => !empty($question['choice_group']))
            ->groupBy(fn (array $question) => (string) $question['choice_group']);

        foreach ($groups as $groupKey => $groupQuestions) {
            $limit = (int) ($groupQuestions->first()['choice_limit'] ?? 0);
            if ($limit <= 0) {
                continue;
            }

            $label = (string) ($groupQuestions->first()['choice_label'] ?? 'Choice group');
            $entered = $groupQuestions
                ->filter(function (array $question) use ($validatedScores) {
                    $questionNo = (int) $question['question_no'];
                    return array_key_exists($questionNo, $validatedScores) && $validatedScores[$questionNo]['score'] !== null;
                })
                ->map(fn (array $question) => $this->questionLabel($question))
                ->values();

            if ($entered->count() > $limit) {
                throw ValidationException::withMessages([
                    'scores' => "{$label}. Only {$limit} question(s) can be entered: " . $entered->implode(', ') . '.',
                ]);
            }

            if ($submitted && $entered->count() !== $limit) {
                throw ValidationException::withMessages([
                    'scores' => "{$label}. Enter marks for exactly {$limit} question(s) before submission.",
                ]);
            }
        }
    }
}
