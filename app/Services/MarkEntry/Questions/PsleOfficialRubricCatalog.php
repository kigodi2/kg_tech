<?php

namespace App\Services\MarkEntry\Questions;

use App\Models\Subject;

class PsleOfficialRubricCatalog
{
    private const KISWAHILI = [
        1 => 5.0,
        2 => 10.0,
        3 => 5.0,
        4 => 10.0,
        5 => 10.0,
        6 => 10.0,
    ];

    private const ENGLISH = [
        1 => 5.0,
        2 => 5.0,
        3 => 5.0,
        4 => 5.0,
        5 => 10.0,
        6 => 10.0,
        7 => 10.0,
    ];

    private const SOCIAL_STUDIES_AND_VOCATIONAL_SKILLS = [
        1 => 15.0,
        2 => 5.0,
        3 => 6.0,
        4 => 6.0,
        5 => 4.0,
        6 => 4.0,
        7 => 10.0,
    ];

    private const MATHEMATICS = [
        1 => 10.0,
        2 => 6.0,
        3 => 6.0,
        4 => 6.0,
        5 => 6.0,
        6 => 6.0,
        7 => 4.0,
        8 => 6.0,
    ];

    private const SCIENCE_AND_TECHNOLOGY = [
        1 => 10.0,
        2 => 5.0,
        3 => 5.0,
        4 => 6.0,
        5 => 6.0,
        6 => 8.0,
        7 => 6.0,
        8 => 4.0,
    ];

    private const CIVIC_AND_MORAL_EDUCATION = [
        1 => 10.0,
        2 => 5.0,
        3 => 5.0,
        4 => 10.0,
        5 => 10.0,
        6 => 10.0,
    ];

    private const TEMPLATE_BY_CODE = [
        'PSLE-01' => 'kiswahili',
        'PSLE-02' => 'english',
        'PSLE-03' => 'social_studies_and_vocational_skills',
        'PSLE-04' => 'mathematics',
        'PSLE-05' => 'science_and_technology',
        'PSLE-06' => 'civic_and_moral_education',
    ];

    public function resolve(Subject $subject): ?array
    {
        $code = strtoupper((string) $subject->code);
        $template = self::TEMPLATE_BY_CODE[$code] ?? null;

        if (!$template) {
            return null;
        }

        $questions = $this->templateQuestions($template);
        if (!$questions) {
            return null;
        }

        $questionRows = collect($questions)->map(
            fn (float $maxMark, int $questionNo) => [
                'question_no' => $questionNo,
                'question_index' => $questionNo,
                'display_label' => 'Q' . $questionNo,
                'max_mark' => $maxMark,
                'paper_code' => 'P1',
                'paper_label' => null,
            ]
        )->values()->all();

        return [
            'status' => 'configured',
            'label' => "Loaded from PSLE_FORMAT_ENGLISH_2024.pdf rubric for {$subject->name}.",
            'questions' => $questionRows,
            'papers' => [[
                'paper_code' => 'P1',
                'paper_label' => $subject->name,
                'question_numbers' => array_keys($questions),
                'max_mark_total' => 50.0,
            ]],
            'choice_groups' => [],
            'aggregation' => 'sum',
            'total_label' => 'Final total follows the official PSLE rubric and is out of 50 marks.',
        ];
    }

    private function templateQuestions(string $template): ?array
    {
        return match ($template) {
            'kiswahili' => self::KISWAHILI,
            'english' => self::ENGLISH,
            'social_studies_and_vocational_skills' => self::SOCIAL_STUDIES_AND_VOCATIONAL_SKILLS,
            'mathematics' => self::MATHEMATICS,
            'science_and_technology' => self::SCIENCE_AND_TECHNOLOGY,
            'civic_and_moral_education' => self::CIVIC_AND_MORAL_EDUCATION,
            default => null,
        };
    }
}
