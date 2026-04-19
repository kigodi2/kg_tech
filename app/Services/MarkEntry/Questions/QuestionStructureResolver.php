<?php

namespace App\Services\MarkEntry\Questions;

use App\Models\ExamDevelopment\SubjectFormat;
use App\Models\ExamType;
use App\Models\Subject;
use Illuminate\Support\Collection;

class QuestionStructureResolver
{
    public function __construct(
        private CseeOfficialRubricCatalog $cseeOfficialRubricCatalog,
        private PsleOfficialRubricCatalog $psleOfficialRubricCatalog
    ) {
    }

    public function resolve(ExamType $examType, Subject $subject): array
    {
        $format = SubjectFormat::query()
            ->where('exam_type_id', $examType->id)
            ->where('subject_id', $subject->id)
            ->where('is_active', true)
            ->with(['papers.sections.rules'])
            ->latest('id')
            ->first();

        if ($format) {
            $questions = $this->questionsFromFormat($format);

            if ($questions->isNotEmpty()) {
                $choiceGroups = $this->choiceGroupsFromFormat($format, $questions);
                $questions = $this->applyChoiceGroupsToQuestions($questions, $choiceGroups);
                $papers = $format->papers
                    ->sortBy('display_order')
                    ->map(function ($paper) use ($questions) {
                        $paperQuestionNumbers = $questions
                            ->filter(fn (array $question) => ($question['paper_code'] ?? null) === $paper->paper_code)
                            ->pluck('question_no')
                            ->values()
                            ->all();

                        return [
                            'paper_code' => $paper->paper_code,
                            'paper_label' => $paper->paper_name,
                            'question_numbers' => $paperQuestionNumbers,
                            'max_mark_total' => round((float) ($paper->total_marks ?? 0), 2),
                        ];
                    })
                    ->filter(fn (array $paper) => !empty($paper['question_numbers']))
                    ->values()
                    ->all();

                return [
                    'source' => 'subject_format',
                    'label' => 'Loaded from configured subject format',
                    'questions' => $questions->values()->all(),
                    'papers' => $papers,
                    'choice_groups' => $choiceGroups,
                    'aggregation' => count($papers) > 1 ? 'average_paper_totals' : 'sum',
                    'total_label' => count($papers) > 1
                        ? 'Final total is the average of paper totals, matching existing IRMS multi-paper handling.'
                        : 'Total is the sum of the question scores.',
                ];
            }
        }

        if ($examType->code === 'CSEE') {
            $officialRubric = $this->cseeOfficialRubricCatalog->resolve($subject);

            if ($officialRubric && !empty($officialRubric['questions'])) {
                return [
                    'source' => 'csee_official_pdf',
                    'label' => $officialRubric['label'],
                    'questions' => $officialRubric['questions'],
                    'papers' => $officialRubric['papers'] ?? [],
                    'choice_groups' => $officialRubric['choice_groups'] ?? [],
                    'aggregation' => $officialRubric['aggregation'] ?? 'sum',
                    'total_label' => $officialRubric['total_label'] ?? 'Total is the sum of the question scores.',
                ];
            }
        }

        if ($examType->code === 'PSLE') {
            $officialRubric = $this->psleOfficialRubricCatalog->resolve($subject);

            if ($officialRubric && !empty($officialRubric['questions'])) {
                return [
                    'source' => 'psle_official_pdf',
                    'label' => $officialRubric['label'],
                    'questions' => $officialRubric['questions'],
                    'papers' => $officialRubric['papers'] ?? [],
                    'choice_groups' => $officialRubric['choice_groups'] ?? [],
                    'aggregation' => $officialRubric['aggregation'] ?? 'sum',
                    'total_label' => $officialRubric['total_label'] ?? 'Total is the sum of the question scores.',
                ];
            }
        }

        $maxMark = (float) ($subject->max_marks ?: 100);

        return [
            'source' => 'default_subject_total',
            'label' => 'Using a safe fallback because no question format is configured yet for this subject.',
            'questions' => [[
                'question_no' => 1,
                'question_index' => 1,
                'display_label' => 'Q1',
                'max_mark' => round($maxMark, 2),
                'paper_code' => 'P1',
                'paper_label' => null,
            ]],
            'papers' => [[
                'paper_code' => 'P1',
                'paper_label' => 'Paper 1',
                'question_numbers' => [1],
                'max_mark_total' => round($maxMark, 2),
            ]],
            'choice_groups' => [],
            'aggregation' => 'sum',
            'total_label' => 'Total is the sum of the question scores.',
        ];
    }

    private function questionsFromFormat(SubjectFormat $format): Collection
    {
        return $format->papers
            ->sortBy('display_order')
            ->flatMap(function ($paper) {
                return $paper->sections
                    ->sortBy('display_order')
                    ->flatMap(function ($section) use ($paper) {
                        return $section->rules
                            ->sortBy('display_order')
                            ->flatMap(function ($rule) use ($paper) {
                                $from = (int) ($rule->question_no_from ?: 0);
                                $to = (int) ($rule->question_no_to ?: $from);

                                if ($from <= 0 || $to < $from) {
                                    return [];
                                }

                                $count = max(1, ($to - $from) + 1);
                                $maxPerQuestion = $rule->marks_per_question !== null
                                    ? (float) $rule->marks_per_question
                                    : round(((float) $rule->total_marks) / $count, 2);

                                return collect(range($from, $to))->map(fn (int $number) => [
                                    'question_no' => $number,
                                    'question_index' => $number,
                                    'display_label' => $paper->paper_code && $paper->paper_code !== 'P1'
                                        ? "{$paper->paper_code} Q{$number}"
                                        : "Q{$number}",
                                    'max_mark' => $maxPerQuestion,
                                    'paper_code' => $paper->paper_code,
                                    'paper_label' => $paper->paper_name,
                                ]);
                            });
                    });
            });
    }

    private function choiceGroupsFromFormat(SubjectFormat $format, Collection $questions): array
    {
        $questionMap = $questions
            ->mapWithKeys(fn (array $question) => [
                ($question['paper_code'] ?? 'P1') . ':' . (int) $question['question_index'] => (int) $question['question_no'],
            ]);

        return $format->papers
            ->sortBy('display_order')
            ->flatMap(function ($paper) use ($questionMap) {
                return $paper->sections
                    ->sortBy('display_order')
                    ->flatMap(function ($section) use ($paper, $questionMap) {
                        return $section->rules
                            ->sortBy('display_order')
                            ->filter(fn ($rule) => $rule->answer_mode === 'fixed_count' && (int) ($rule->choice_count ?? 0) > 0)
                            ->map(function ($rule, $index) use ($paper, $questionMap) {
                                $from = (int) ($rule->question_no_from ?: 0);
                                $to = (int) ($rule->question_no_to ?: $from);

                                if ($from <= 0 || $to < $from) {
                                    return null;
                                }

                                $questionNumbers = collect(range($from, $to))
                                    ->map(fn (int $questionIndex) => $questionMap[$paper->paper_code . ':' . $questionIndex] ?? null)
                                    ->filter()
                                    ->values()
                                    ->all();

                                if (empty($questionNumbers)) {
                                    return null;
                                }

                                return [
                                    'group_key' => strtolower($paper->paper_code) . '_choice_' . $section->id . '_' . $index,
                                    'label' => "Answer any {$rule->choice_count} from {$paper->paper_name} Q{$from}-Q{$to}",
                                    'limit' => (int) $rule->choice_count,
                                    'question_numbers' => $questionNumbers,
                                ];
                            })
                            ->filter()
                            ->values();
                    });
            })
            ->values()
            ->all();
    }

    private function applyChoiceGroupsToQuestions(Collection $questions, array $choiceGroups): Collection
    {
        $groupMap = [];

        foreach ($choiceGroups as $group) {
            foreach ($group['question_numbers'] as $questionNo) {
                $groupMap[(int) $questionNo] = [
                    'choice_group' => $group['group_key'],
                    'choice_limit' => $group['limit'],
                    'choice_label' => $group['label'],
                ];
            }
        }

        return $questions->map(function (array $question) use ($groupMap) {
            $questionNo = (int) $question['question_no'];
            if (!isset($groupMap[$questionNo])) {
                return $question;
            }

            return array_merge($question, $groupMap[$questionNo]);
        });
    }
}
