<?php

namespace App\Services\MarkEntry\Questions;

use App\Models\Subject;

class CseeOfficialRubricCatalog
{
    private const STANDARD_11 = [
        1 => 10.0,
        2 => 6.0,
        3 => 9.0,
        4 => 9.0,
        5 => 9.0,
        6 => 9.0,
        7 => 9.0,
        8 => 9.0,
        9 => 15.0,
        10 => 15.0,
        11 => 15.0,
    ];

    private const MATH_14 = [
        1 => 6.0,
        2 => 6.0,
        3 => 6.0,
        4 => 6.0,
        5 => 6.0,
        6 => 6.0,
        7 => 6.0,
        8 => 6.0,
        9 => 6.0,
        10 => 6.0,
        11 => 10.0,
        12 => 10.0,
        13 => 10.0,
        14 => 10.0,
    ];

    private const BOOK_KEEPING_9 = [
        1 => 10.0,
        2 => 5.0,
        3 => 10.0,
        4 => 10.0,
        5 => 10.0,
        6 => 10.0,
        7 => 15.0,
        8 => 15.0,
        9 => 15.0,
    ];

    private const DRAWING_8 = [
        1 => 10.0,
        2 => 10.0,
        3 => 10.0,
        4 => 10.0,
        5 => 10.0,
        6 => 10.0,
        7 => 10.0,
        8 => 30.0,
    ];

    private const ENGINEERING_DRAWING_6 = [
        1 => 10.0,
        2 => 20.0,
        3 => 20.0,
        4 => 20.0,
        5 => 20.0,
        6 => 30.0,
    ];

    private const BIBLE_KNOWLEDGE_13 = [
        1 => 20.0,
        2 => 5.0,
        3 => 9.0,
        4 => 9.0,
        5 => 9.0,
        6 => 9.0,
        7 => 9.0,
        8 => 9.0,
        9 => 9.0,
        10 => 9.0,
        11 => 15.0,
        12 => 15.0,
        13 => 15.0,
    ];

    private const PRACTICAL_TWO_BY_TWENTY_FIVE = [
        1 => 25.0,
        2 => 25.0,
    ];

    private const MUSIC_PRACTICAL_10 = [
        1 => 10.0,
        2 => 10.0,
        3 => 10.0,
        4 => 10.0,
        5 => 10.0,
        6 => 10.0,
        7 => 10.0,
        8 => 10.0,
        9 => 10.0,
        10 => 10.0,
    ];

    private const ICS_PRACTICAL_3 = [
        1 => 25.0,
        2 => 25.0,
        3 => 25.0,
    ];

    private const FINE_ART_PRACTICAL_4 = [
        1 => 50.0,
        2 => 50.0,
        3 => 50.0,
        4 => 50.0,
    ];

    private const FOOD_PRACTICAL_3 = [
        1 => 100.0,
        2 => 100.0,
        3 => 100.0,
    ];

    private const TEXTILES_PRACTICAL_AND_COURSEWORK = [
        1 => 75.0,
        2 => 25.0,
    ];

    private const TEMPLATE_BY_CODE = [
        '011' => ['single', 'standard_11'],
        '012' => ['single', 'standard_11'],
        '013' => ['single', 'standard_11'],
        '014' => ['single', 'bible_knowledge_13'],
        '015' => ['single', 'standard_11'],
        '016' => ['multi', 'fine_art'],
        '017' => ['multi', 'music'],
        '018' => ['single', 'standard_11'],
        '019' => ['single', 'standard_11'],
        '021' => ['single', 'standard_11'],
        '022' => ['single', 'standard_11'],
        '023' => ['single', 'standard_11'],
        '024' => ['single', 'standard_11'],
        '025' => ['single', 'standard_11'],
        '026' => ['single', 'standard_11'],
        '031' => ['multi', 'science_practical_2'],
        '032' => ['multi', 'science_practical_2'],
        '033' => ['multi', 'science_practical_2'],
        '034' => ['single', 'standard_11'],
        '035' => ['single', 'standard_11'],
        '036' => ['multi', 'ics'],
        '041' => ['single', 'math_14'],
        '042' => ['single', 'math_14'],
        '051' => ['multi', 'food'],
        '052' => ['multi', 'textiles'],
        '061' => ['single', 'standard_11'],
        '062' => ['single', 'book_keeping_9'],
        '071' => ['single', 'standard_11'],
        '072' => ['single', 'drawing_8'],
        '073' => ['single', 'standard_11'],
        '074' => ['single', 'standard_11'],
        '080' => ['single', 'standard_11'],
        '081' => ['single', 'standard_11'],
        '082' => ['single', 'drawing_8'],
        '083' => ['single', 'drawing_8'],
        '087' => ['single', 'standard_11'],
        '088' => ['single', 'standard_11'],
        '091' => ['single', 'engineering_drawing_6'],
    ];

    private const TEMPLATE_CHOICE_GROUPS = [
        'standard_11' => [
            ['question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from Q9-Q11'],
        ],
        'bible_knowledge_13' => [
            ['question_numbers' => [11, 12, 13], 'limit' => 2, 'label' => 'Answer any 2 from Q11-Q13'],
        ],
        'book_keeping_9' => [
            ['question_numbers' => [7, 8, 9], 'limit' => 2, 'label' => 'Answer any 2 from Q7-Q9'],
        ],
        'science_practical_2' => [
            ['paper_code' => 'P1', 'question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from P1 Q9-P1 Q11'],
        ],
        'fine_art' => [
            ['paper_code' => 'P1', 'question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from P1 Q9-P1 Q11'],
            ['paper_code' => 'P2', 'question_numbers' => [1, 2], 'limit' => 1, 'label' => 'Answer 1 from Paper 2 Section A'],
            ['paper_code' => 'P2', 'question_numbers' => [3, 4], 'limit' => 1, 'label' => 'Answer 1 from Paper 2 Section B'],
        ],
        'music' => [
            ['paper_code' => 'P1', 'question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from P1 Q9-P1 Q11'],
        ],
        'ics' => [
            ['paper_code' => 'P1', 'question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from P1 Q9-P1 Q11'],
            ['paper_code' => 'P2', 'question_numbers' => [1, 2, 3], 'limit' => 2, 'label' => 'Answer any 2 practical questions'],
        ],
        'food' => [
            ['paper_code' => 'P1', 'question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from P1 Q9-P1 Q11'],
            ['paper_code' => 'P2', 'question_numbers' => [1, 2, 3], 'limit' => 1, 'label' => 'Answer 1 practical question'],
        ],
        'textiles' => [
            ['paper_code' => 'P1', 'question_numbers' => [9, 10, 11], 'limit' => 2, 'label' => 'Answer any 2 from P1 Q9-P1 Q11'],
        ],
    ];

    public function resolve(Subject $subject): ?array
    {
        $code = strtoupper((string) $subject->code);
        $config = self::TEMPLATE_BY_CODE[$code] ?? null;

        if (!$config) {
            return null;
        }

        [$mode, $template] = $config;

        $structure = $mode === 'multi'
            ? $this->multiPaperStructure($template, $code)
            : $this->singlePaperStructure($template, $code);

        if (!$structure) {
            return null;
        }

        return array_merge($structure, [
            'status' => 'configured',
            'label' => "Loaded from CSEE_FORMATS_2022.pdf rubric for subject {$code}.",
        ]);
    }

    private function singlePaperStructure(string $template, string $subjectCode): ?array
    {
        $questions = $this->templateQuestions($template);

        if (!$questions) {
            return null;
        }

        return $this->buildStructure([
            [
                'paper_code' => 'P1',
                'paper_label' => "{$subjectCode} Paper 1",
                'questions' => $questions,
                'max_mark_total' => 100.0,
            ],
        ], 'sum', self::TEMPLATE_CHOICE_GROUPS[$template] ?? []);
    }

    private function multiPaperStructure(string $template, string $subjectCode): ?array
    {
        $papers = match ($template) {
            'science_practical_2' => [
                [
                    'paper_code' => 'P1',
                    'paper_label' => "{$subjectCode} Paper 1 Theory",
                    'questions' => self::STANDARD_11,
                    'max_mark_total' => 100.0,
                ],
                [
                    'paper_code' => 'P2',
                    'paper_label' => "{$subjectCode} Paper 2 Practical",
                    'questions' => self::PRACTICAL_TWO_BY_TWENTY_FIVE,
                    'max_mark_total' => 50.0,
                ],
            ],
            'fine_art' => [
                [
                    'paper_code' => 'P1',
                    'paper_label' => "{$subjectCode} Paper 1 Theory",
                    'questions' => self::STANDARD_11,
                    'max_mark_total' => 100.0,
                ],
                [
                    'paper_code' => 'P2',
                    'paper_label' => "{$subjectCode} Paper 2 Practical",
                    'questions' => self::FINE_ART_PRACTICAL_4,
                    'max_mark_total' => 100.0,
                ],
            ],
            'music' => [
                [
                    'paper_code' => 'P1',
                    'paper_label' => "{$subjectCode} Paper 1 Theory",
                    'questions' => self::STANDARD_11,
                    'max_mark_total' => 100.0,
                ],
                [
                    'paper_code' => 'P2',
                    'paper_label' => "{$subjectCode} Paper 2 Practical",
                    'questions' => self::MUSIC_PRACTICAL_10,
                    'max_mark_total' => 100.0,
                ],
            ],
            'ics' => [
                [
                    'paper_code' => 'P1',
                    'paper_label' => "{$subjectCode} Paper 1 Theory",
                    'questions' => self::STANDARD_11,
                    'max_mark_total' => 100.0,
                ],
                [
                    'paper_code' => 'P2',
                    'paper_label' => "{$subjectCode} Paper 2 Practical",
                    'questions' => self::ICS_PRACTICAL_3,
                    'max_mark_total' => 50.0,
                ],
            ],
            'food' => [
                [
                    'paper_code' => 'P1',
                    'paper_label' => "{$subjectCode} Paper 1 Theory",
                    'questions' => self::STANDARD_11,
                    'max_mark_total' => 100.0,
                ],
                [
                    'paper_code' => 'P2',
                    'paper_label' => "{$subjectCode} Paper 2 Practical",
                    'questions' => self::FOOD_PRACTICAL_3,
                    'max_mark_total' => 100.0,
                ],
            ],
            'textiles' => [
                [
                    'paper_code' => 'P1',
                    'paper_label' => "{$subjectCode} Paper 1 Theory",
                    'questions' => self::STANDARD_11,
                    'max_mark_total' => 100.0,
                ],
                [
                    'paper_code' => 'P2',
                    'paper_label' => "{$subjectCode} Paper 2 Practical and Coursework",
                    'questions' => self::TEXTILES_PRACTICAL_AND_COURSEWORK,
                    'display_labels' => [
                        1 => 'P2 Practical',
                        2 => 'P2 Coursework',
                    ],
                    'max_mark_total' => 100.0,
                ],
            ],
            default => null,
        };

        if (!$papers) {
            return null;
        }

        return $this->buildStructure($papers, 'normalize_to_100', self::TEMPLATE_CHOICE_GROUPS[$template] ?? []);
    }

    private function buildStructure(array $papers, string $aggregation, array $choiceGroups = []): array
    {
        $questions = [];
        $paperRows = [];
        $internalQuestionNo = 1;
        $questionMap = [];

        $singlePaper = count($papers) === 1;

        foreach ($papers as $paperIndex => $paper) {
            $questionKeys = [];
            $displayLabels = $paper['display_labels'] ?? [];

            foreach ($paper['questions'] as $paperQuestionNo => $maxMark) {
                $questionKeys[] = $internalQuestionNo;
                $questions[] = [
                    'question_no' => $internalQuestionNo,
                    'question_index' => $paperQuestionNo,
                    'display_label' => $displayLabels[$paperQuestionNo]
                        ?? ($singlePaper ? 'Q' . $paperQuestionNo : 'P' . ($paperIndex + 1) . ' Q' . $paperQuestionNo),
                    'max_mark' => $maxMark,
                    'paper_code' => $paper['paper_code'],
                    'paper_label' => $singlePaper ? null : $paper['paper_label'],
                ];
                $questionMap[$paper['paper_code']][$paperQuestionNo] = $internalQuestionNo;
                $internalQuestionNo++;
            }

            $paperRows[] = [
                'paper_code' => $paper['paper_code'],
                'paper_label' => $paper['paper_label'],
                'question_numbers' => $questionKeys,
                'max_mark_total' => round((float) ($paper['max_mark_total'] ?? array_sum($paper['questions'])), 2),
            ];
        }

        $normalizedChoiceGroups = collect($choiceGroups)
            ->map(function (array $group, int $index) use ($questionMap, $singlePaper) {
                $paperCode = (string) ($group['paper_code'] ?? ($singlePaper ? 'P1' : ''));
                $mappedQuestions = collect($group['question_numbers'] ?? [])
                    ->map(fn (int $paperQuestionNo) => $questionMap[$paperCode][$paperQuestionNo] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                if (empty($mappedQuestions)) {
                    return null;
                }

                return [
                    'group_key' => 'choice_group_' . ($index + 1),
                    'label' => (string) ($group['label'] ?? 'Choice group'),
                    'limit' => (int) ($group['limit'] ?? 0),
                    'question_numbers' => $mappedQuestions,
                ];
            })
            ->filter()
            ->values()
            ->all();

        foreach ($normalizedChoiceGroups as $group) {
            foreach ($group['question_numbers'] as $questionNo) {
                if (!isset($questions[$questionNo - 1])) {
                    continue;
                }

                $questions[$questionNo - 1]['choice_group'] = $group['group_key'];
                $questions[$questionNo - 1]['choice_limit'] = $group['limit'];
                $questions[$questionNo - 1]['choice_label'] = $group['label'];
            }
        }

        return [
            'questions' => $questions,
            'papers' => $paperRows,
            'choice_groups' => $normalizedChoiceGroups,
            'aggregation' => $aggregation,
            'total_label' => match ($aggregation) {
                'normalize_to_100' => 'Final total is normalized to 100 from the official paper totals for this subject.',
                'average_paper_totals' => 'Final total is the average of paper totals, matching existing IRMS multi-paper handling.',
                default => 'Total is the sum of the question scores.',
            },
        ];
    }

    private function templateQuestions(string $template): ?array
    {
        return match ($template) {
            'standard_11' => self::STANDARD_11,
            'math_14' => self::MATH_14,
            'book_keeping_9' => self::BOOK_KEEPING_9,
            'drawing_8' => self::DRAWING_8,
            'engineering_drawing_6' => self::ENGINEERING_DRAWING_6,
            'bible_knowledge_13' => self::BIBLE_KNOWLEDGE_13,
            default => null,
        };
    }
}
