<?php

namespace App\Services\ExamFormatValidation;

class NectaFormatRulebook
{
    public function getOfficialSubjects(string $examType): array
    {
        $examType = strtoupper(trim($examType));

        if ($examType === 'FTNA') {
            return $this->mergeCatalogSubjects(
                config('ftna.official_subjects_general_2022', []),
                config('ftna.official_subjects', [])
            );
        }

        return config(strtolower($examType) . '.official_subjects', []);
    }

    protected function mergeCatalogSubjects(array ...$catalogs): array
    {
        $merged = [];

        foreach ($catalogs as $catalog) {
            foreach ($catalog as $entry) {
                $code = strtoupper((string) ($entry['code'] ?? ''));

                if ($code === '' || isset($merged[$code])) {
                    continue;
                }

                $entry['code'] = $code;
                $merged[$code] = $entry;
            }
        }

        return array_values($merged);
    }

    public function getRules(string $examType, ?string $subjectCode = null): array
    {
        $examType = strtoupper(trim($examType));
        $subjectCode = $subjectCode !== null ? trim($subjectCode) : null;

        $config = config('necta_format_rules', []);
        $common = $config['common'] ?? [];
        $examRules = $config['exam_types'][$examType] ?? [];
        $subjectRules = [];
        $catalogEntry = [];

        if ($subjectCode !== null && isset($examRules['subjects'][$subjectCode])) {
            $subjectRules = $examRules['subjects'][$subjectCode];
        }

        if ($subjectCode !== null && empty($subjectRules)) {
            $subjectRules = $this->buildDetailedFallbackSubjectRules($examType, $subjectCode);
        }

        if ($subjectCode !== null) {
            $catalogEntry = $this->findCatalogEntry($examType, $subjectCode);
        }

        return [
            'version' => $config['version'] ?? null,
            'exam_type' => $examType,
            'subject_code' => $subjectCode,
            'common' => $common,
            'exam' => $examRules,
            'subject' => $subjectRules,
            'catalog_subject' => $catalogEntry,
        ];
    }

    public function summarize(string $examType, ?string $subjectCode = null): array
    {
        $rules = $this->getRules($examType, $subjectCode);
        $summary = [
            'version' => $rules['version'],
            'exam_type' => $rules['exam_type'],
            'subject_code' => $rules['subject_code'],
            'guide_title' => $rules['exam']['guide_title'] ?? null,
            'edition' => $rules['exam']['edition'] ?? null,
            'assessment_model' => $rules['exam']['assessment_model'] ?? null,
            'common_sections' => $rules['common']['booklet_sections'] ?? [],
            'validation_focus' => $rules['exam']['validation_focus'] ?? [],
            'practical_controls' => $rules['exam']['common_practical_controls'] ?? [],
        ];

        if (!empty($rules['subject'])) {
            $summary['subject_name'] = $rules['subject']['name'] ?? null;
            $summary['papers'] = $rules['subject']['papers'] ?? [];
            $summary['profile_status'] = 'detailed';
        } elseif (!empty($rules['catalog_subject'])) {
            $catalog = $rules['catalog_subject'];
            $summary['subject_name'] = $catalog['name'] ?? null;
            $summary['catalog_subject'] = $catalog;
            $summary['profile_status'] = 'catalog_only';
            $summary['papers'] = $this->buildCatalogFallbackPapers($rules['exam_type'], $catalog);
        }

        return $summary;
    }

    protected function findCatalogEntry(string $examType, string $subjectCode): array
    {
        $catalog = $this->getOfficialSubjects($examType);

        foreach ($catalog as $entry) {
            if (($entry['code'] ?? null) === $subjectCode) {
                return $entry;
            }
        }

        return [];
    }

    protected function buildDetailedFallbackSubjectRules(string $examType, string $subjectCode): array
    {
        if ($examType === 'FTNA') {
            return $this->buildFtnaDetailedSubjectRules($subjectCode);
        }

        if ($examType === 'CSEE') {
            return $this->buildCseeDetailedSubjectRules($subjectCode);
        }

        if ($examType === 'ACSEE') {
            return $this->buildAcseeDetailedSubjectRules($subjectCode);
        }

        return [];
    }

    protected function buildFtnaDetailedSubjectRules(string $subjectCode): array
    {
        $catalog = $this->findCatalogEntry('FTNA', $subjectCode);

        if (empty($catalog)) {
            return [];
        }

        $name = $catalog['name'] ?? $subjectCode;

        $singleTheoryStandard = ['022', '023', '025', '026', '033', '034', '035', '043', '060', '396', '412'];
        $twoPaperDirectProduct = ['201', '205', '801', '804', '805', '806', '824', '827', '843', '861', '862', '881', '882'];
        $sportsPerformance = ['241', '242', '243'];
        $computerPracticalTwoQuestion = ['398', '841'];
        $agriProductionTaskBased = ['403', '404', '405', '406'];
        $foodPlanning = ['463', '464'];

        if (in_array($subjectCode, $singleTheoryStandard, true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_or_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'one theory paper with ten questions divided into sections A, B and C',
                            'all questions are compulsory',
                            'section A carries 15 marks, section B 70 marks, and section C 15 marks',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '065') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '065/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 40],
                            ['name' => 'C', 'question_types' => ['structured_case_study_or_project'], 'marks' => 45],
                        ],
                        'rules' => [
                            'one theory paper with nine questions in sections A, B and C',
                            'all questions are compulsory',
                            'section C is based on case studies and projects with three structured questions',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '397') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '397/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'duration_special_needs' => '3:30 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['structured'], 'marks' => 40],
                            ['name' => 'B', 'question_types' => ['structured'], 'marks' => 60],
                        ],
                        'rules' => [
                            'one theory paper with seven structured questions divided into sections A and B',
                            'all questions are compulsory',
                            'section A focuses on plane geometry, scale drawing, and pictorial drawing',
                            'section B focuses on orthographic projections and sectional views',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, ['201', '205'], true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'theory paper has ten questions in sections A, B and C',
                            'answer all questions in sections A and B and one question in section C',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'duration' => '3:00 hours',
                        'duration_special_needs' => '3:30 hours',
                        'total_marks' => 100,
                        'components' => [
                            ['name' => 'direct_performance_assessment', 'marks' => 60],
                            ['name' => 'product_assessment', 'marks' => 40],
                        ],
                        'rules' => [
                            'one practical question performed individually',
                            'assessed in two stages: direct performance assessment and product assessment',
                            '24 hours advance instruction applies',
                            'tools, equipment, and materials checklist issued at least three months before assessment',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '204') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '204/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'theory paper has ten questions in sections A, B and C',
                            'answer all questions in sections A and B and one question in section C',
                        ],
                    ],
                    [
                        'code' => '204/2',
                        'type' => 'practical',
                        'total_marks' => 100,
                        'components' => [
                            ['name' => 'planning_session', 'duration' => '2:00 hours'],
                            ['name' => 'practical_session', 'duration' => '3:00 hours'],
                            ['name' => 'direct_performance_assessment', 'marks' => 60],
                            ['name' => 'product_assessment', 'marks' => 40],
                        ],
                        'rules' => [
                            'practical paper is split into planning and practical sessions',
                            'planning session provides the task and constructed patterns',
                            'practical session is evaluated through direct performance and product assessment',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, $sportsPerformance, true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'total_marks' => 100,
                        'rules' => [
                            'practical paper consists of five questions',
                            'each student answers one question in 15 minutes',
                            'assessment criteria are defined in the assessment guide and assessment sheet',
                            '1 hour advance instruction applies',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '398' || $subjectCode === '841') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'duration' => '3:00 hours',
                        'duration_special_needs' => '3:30 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'practical paper consists of two questions',
                            'all practical questions are compulsory and attempted individually',
                            'each practical question carries 50 marks',
                            'soft copies and hard copies of final products are expected',
                            'hardware, software, and tools checklist is issued in advance',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, $agriProductionTaskBased, true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'duration' => '3:00 hours',
                        'duration_special_needs' => '3:30 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'practical paper consists of one task-based question',
                            'assessment uses both process assessment and product assessment',
                            'student performance is scored against an assessment sheet',
                            '3 hours advance instruction applies',
                            'apparatuses, chemicals, tools, equipment, and materials checklist is issued in advance',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, $foodPlanning, true)) {
            $planningMarks = $subjectCode === '463' ? 17 : 15;
            $performanceMarks = $subjectCode === '463' ? 47 : null;
            $serviceMarks = $subjectCode === '463' ? 36 : null;

            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'total_marks' => 100,
                        'rules' => array_values(array_filter([
                            'practical paper is split into planning and practical sessions on separate days',
                            'students choose one question from three via secret ballot',
                            'planning session is open-book and includes service/menu plan, logical progression, and shopping list',
                            $planningMarks ? 'planning session carries ' . $planningMarks . ' marks' : null,
                            $performanceMarks ? 'direct performance carries ' . $performanceMarks . ' marks' : null,
                            $serviceMarks ? 'product or service assessment carries ' . $serviceMarks . ' marks' : null,
                            'equipment and tools checklist is issued in advance',
                        ])),
                    ],
                ],
            ];
        }

        if ($subjectCode === '481') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '481/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => '481/2',
                        'type' => 'practical',
                        'total_marks' => 100,
                        'rules' => [
                            'one practical question for all students',
                            'paper is divided into planning and practical sessions',
                            'planning session lasts 1:30 hours and requires order of work plus materials/equipment',
                            'practical session lasts 1 hour and includes individual and group performance',
                            'checklist of two play synopses and materials is issued in advance',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '485') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '485/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => '485/2',
                        'type' => 'practical',
                        'total_marks' => 100,
                        'components' => [
                            ['name' => 'aural_session', 'marks' => 70, 'duration' => '2:00 hours'],
                            ['name' => 'recital_performance_session', 'marks' => 30, 'duration' => '2:30 hours'],
                        ],
                        'rules' => [
                            'practical paper has two sessions: aural and recital performance',
                            'aural session has seven questions and recital session has two questions',
                            'recital takes place the day after the aural session',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '487') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '487/1',
                        'type' => 'practical_only',
                        'duration' => '5:00 hours',
                        'duration_special_needs' => '5:50 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'one practical paper only',
                            'two questions, both compulsory',
                            'each question carries 50 marks',
                            '3 hours advance instruction applies',
                            'samples of materials required for the assessment are issued in advance',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, $twoPaperDirectProduct, true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'duration' => '3:00 hours',
                        'duration_special_needs' => '3:30 hours',
                        'total_marks' => 100,
                        'components' => [
                            ['name' => 'direct_performance_assessment', 'marks' => 60],
                            ['name' => 'product_assessment', 'marks' => 40],
                        ],
                        'rules' => [
                            'one practical question performed individually',
                            'practical paper is assessed in direct performance and product/final output stages',
                            'tools, facilities, or materials checklist is issued at least three months in advance',
                            'practical assessment guideline is referenced by NECTA',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '842') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '842/1',
                        'type' => 'theory',
                        'duration' => '2:30 hours',
                        'duration_special_needs' => '2:55 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'C', 'question_types' => ['structured_essay'], 'marks' => 15],
                        ],
                        'rules' => [
                            'all theory questions are compulsory',
                        ],
                    ],
                    [
                        'code' => '842/2',
                        'type' => 'practical',
                        'duration' => '3:00 hours',
                        'duration_special_needs' => '3:30 hours',
                        'total_marks' => 100,
                        'components' => [
                            ['name' => 'process_assessment', 'marks' => 60],
                            ['name' => 'final_product_assessment', 'marks' => 40],
                        ],
                        'rules' => [
                            'one practical question',
                            'process assessment covers technical steps and rough materials such as sketches and documentation',
                            'final product assessment uses both soft and hard copy outputs',
                            'materials checklist is issued in advance',
                        ],
                    ],
                ],
            ];
        }

        return [];
    }

    protected function buildCseeDetailedSubjectRules(string $subjectCode): array
    {
        $catalog = $this->findCatalogEntry('CSEE', $subjectCode);

        if (empty($catalog)) {
            return [];
        }

        $name = $catalog['name'] ?? $subjectCode;

        $standardTheory11 = ['011', '012', '013', '014', '015', '018', '019', '021', '022', '023', '024', '025', '026', '035', '061', '071', '074', '080', '083', '087', '091'];
        $theoryStructured11 = ['031', '032', '036', '051', '052', '072', '073', '081', '082', '088'];
        $sciencePractical = ['031', '032', '033', '034'];

        if ($subjectCode === '010') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '010/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['civics', 'languages'], 'marks' => 60],
                            ['name' => 'B', 'question_types' => ['subject_cluster_choice'], 'marks' => 40],
                        ],
                        'rules' => [
                            'one paper with sections A and B',
                            'section A is compulsory and contains twelve questions',
                            'section B has three parts and candidates confine themselves to one part',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, $standardTheory11, true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 30],
                        ],
                        'rules' => [
                            'one theory paper with 11 questions in sections A, B and C',
                            'answer all questions in sections A and B and two questions from section C',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '017') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '017/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 30],
                        ],
                        'rules' => [
                            'one theory paper with 11 questions in sections A, B and C',
                            'answer all questions in sections A and B and two questions from section C',
                        ],
                    ],
                    [
                        'code' => '017/2',
                        'type' => 'practical_or_aural',
                        'duration' => '2:00 hours',
                        'rules' => [
                            'second paper exists for music and differs from the standard theory-only format',
                            'detailed music practical and aural rubric extraction is still pending',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '016') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '016/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['essay_or_structured'], 'marks' => 30],
                        ],
                        'rules' => [
                            'one theory paper with 11 questions in sections A, B and C',
                            'answer all questions in sections A and B and two questions from section C',
                        ],
                    ],
                    [
                        'code' => '016/2',
                        'type' => 'practical',
                        'duration' => '5:00 hours',
                        'rules' => [
                            'practical paper has sections A and B',
                            '3 hours advance instruction applies for materials and arrangement',
                            'actual practical paper has a checklist for required materials and equipment',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, $sciencePractical, true)) {
            $practicalRules = [
                'two papers: theory and actual practical',
                'practical paper has two questions and candidates answer all',
                'checklist of apparatuses, chemicals, specimens, equipment, or materials is issued in advance',
            ];

            if ($subjectCode === '031') {
                $practicalRules[] = '3 hours advance instruction for laboratory arrangements applies';
            }

            if ($subjectCode === '032') {
                $practicalRules[] = 'non-programmable calculators are allowed in theory and practical papers';
            }

            if ($subjectCode === '033') {
                $practicalRules[] = 'actual practical paper may have more than one alternative depending on candidate numbers';
            }

            if ($subjectCode === '034') {
                $practicalRules[] = 'practical paper lasts 2½ hours and relies on apparatuses, chemicals, and specimens';
            }

            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['structured_or_essay'], 'marks' => 30],
                        ],
                        'rules' => [
                            'answer all questions in sections A and B and two questions from section C',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'practical',
                        'duration' => '2:30 hours',
                        'total_marks' => 50,
                        'rules' => $practicalRules,
                    ],
                ],
            ];
        }

        if ($subjectCode === '036') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '036/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 30],
                        ],
                        'rules' => [
                            'answer all questions in sections A and B and two questions from section C',
                        ],
                    ],
                    [
                        'code' => '036/2',
                        'type' => 'practical',
                        'total_marks' => 50,
                        'rules' => [
                            'practical paper has three questions and candidates answer two',
                            'softcopies are saved on three CDs using examination numbers and submitted with hardcopies',
                            'hardware, software, and tools checklist is issued in advance',
                            'there is no 3 hours advance instruction for this paper',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, ['041', '042'], true)) {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => $subjectCode === '041' ? 60 : 60],
                            ['name' => 'B', 'question_types' => ['structured'], 'marks' => 40],
                        ],
                        'rules' => [
                            'one paper with sections A and B',
                            'section A contains ten short-answer questions',
                            'section B contains four structured questions',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '051') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '051/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 30],
                        ],
                        'rules' => [
                            'answer all questions in sections A and B and two from section C',
                        ],
                    ],
                    [
                        'code' => '051/2',
                        'type' => 'practical',
                        'total_marks' => 100,
                        'rules' => [
                            'practical paper is split into planning session and practical session',
                            'planning session is open-book and allows recipe books',
                            'three practical questions are provided and candidates answer one',
                            'candidate selects a question by secret ballot',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '052') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '052/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 16],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 54],
                            ['name' => 'C', 'question_types' => ['structured'], 'marks' => 30],
                        ],
                        'rules' => [
                            'answer all questions in sections A and B and two from section C',
                        ],
                    ],
                    [
                        'code' => '052/2',
                        'type' => 'practical_and_coursework',
                        'rules' => [
                            'second paper has two components: practical examination and coursework',
                            'practical examination has one question for 75 marks',
                            'checklist for sewing equipment, fabrics, fastenings, and trimmings is issued in advance',
                            '3 hours advance instruction for room arrangements applies',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '062') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '062/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 40],
                            ['name' => 'C', 'question_types' => ['problem_solving_or_recording_transactions'], 'marks' => 45],
                        ],
                        'rules' => [
                            'one paper with nine questions',
                            'all questions in all sections are compulsory',
                            'non-programmable calculators are allowed',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '071') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '071/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['multiple_choice'], 'marks' => 10],
                            ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 60],
                            ['name' => 'C', 'question_types' => ['structured'], 'marks' => 30],
                        ],
                        'rules' => [
                            'one paper with eight questions',
                            'all questions are compulsory',
                        ],
                    ],
                ],
            ];
        }

        return [];
    }

    protected function buildAcseeDetailedSubjectRules(string $subjectCode): array
    {
        $catalog = $this->findCatalogEntry('ACSEE', $subjectCode);

        if (empty($catalog)) {
            return [];
        }

        $name = $catalog['name'] ?? $subjectCode;

        if ($subjectCode === '111') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '111/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'one paper with seven essay-type questions',
                            'five questions are answered in total',
                            'question one is compulsory',
                            'each question carries 20 marks',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, ['112', '152'], true)) {
            $calculatorNote = $subjectCode === '152'
                ? 'non-programmable calculator is allowed in the examination room'
                : null;

            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => array_values(array_filter([
                            'paper consists of seven essay questions',
                            'candidates answer five questions',
                            'question one is compulsory',
                            'each question carries 20 marks',
                            $calculatorNote,
                        ])),
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => array_values(array_filter([
                            'paper consists of seven essay questions',
                            'candidates answer five questions',
                            'question one is compulsory',
                            'each question carries 20 marks',
                            $calculatorNote,
                        ])),
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, ['114', '115', '125', '155'], true)) {
            $paperCode = function (string $suffix) use ($subjectCode): string {
                return $subjectCode . '/' . $suffix;
            };

            if ($subjectCode === '155') {
                return [
                    'name' => $name,
                    'papers' => [
                        [
                            'code' => $paperCode('1'),
                            'type' => 'theory',
                            'duration' => '3:00 hours',
                            'total_marks' => 100,
                            'sections' => [
                                ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 60],
                                ['name' => 'B', 'question_types' => ['essay'], 'marks' => 40],
                            ],
                            'rules' => [
                                'nine questions in sections A and B',
                                'answer eight questions in total',
                                'section A has six short answer questions and section B has three essay questions',
                            ],
                        ],
                        [
                            'code' => $paperCode('2'),
                            'type' => 'theory',
                            'duration' => '3:00 hours',
                            'total_marks' => 100,
                            'sections' => [
                                ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 60],
                                ['name' => 'B', 'question_types' => ['essay'], 'marks' => 40],
                            ],
                            'rules' => [
                                'nine questions in sections A and B',
                                'answer eight questions in total',
                            ],
                        ],
                        [
                            'code' => $paperCode('3'),
                            'type' => 'practical',
                            'duration' => '3:20 hours',
                            'total_marks' => 50,
                            'rules' => [
                                'practical paper has three questions',
                                'question 1 carries 20 marks while questions 2 and 3 carry 15 marks each',
                                'all practical questions are compulsory',
                                'checklist of apparatuses, equipment, chemicals, and material samples is issued in advance',
                                '24 hours advance instruction applies for arrangements',
                            ],
                        ],
                    ],
                ];
            }

            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $paperCode('1'),
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 60],
                            ['name' => 'B', 'question_types' => ['essay'], 'marks' => 40],
                        ],
                        'rules' => [
                            'paper has nine questions in sections A and B',
                            'answer eight questions in total',
                            'all section A questions are compulsory and two section B questions are chosen',
                        ],
                    ],
                    [
                        'code' => $paperCode('2'),
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 60],
                            ['name' => 'B', 'question_types' => ['essay'], 'marks' => 40],
                        ],
                        'rules' => [
                            'paper has nine questions in sections A and B',
                            'answer eight questions in total',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '122') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '122/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 40],
                            ['name' => 'B', 'question_types' => ['essay'], 'marks' => 60],
                        ],
                        'rules' => [
                            'paper has eight questions in sections A and B',
                            'answer seven questions',
                            'all section A questions are compulsory',
                            'in section B, three questions are answered and two are compulsory',
                        ],
                    ],
                    [
                        'code' => '122/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 40],
                            ['name' => 'B', 'question_types' => ['essay'], 'marks' => 60],
                        ],
                        'rules' => [
                            'paper has eight questions in sections A and B',
                            'answer seven questions',
                            'paper includes prescribed reading texts',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '123') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '123/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'French Language examination consists of two papers',
                            'paper 1 has ten questions in sections A and B',
                            'section B consists of essay or structured questions',
                        ],
                    ],
                    [
                        'code' => '123/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'second French Language paper exists and should be checked against the official rubric',
                        ],
                    ],
                ],
            ];
        }

        if (in_array($subjectCode, ['131', '132', '133', '134'], true)) {
            $practicalNote = [
                '131' => 'alternative practical papers are used depending on candidate numbers and 24 hours advance instruction applies',
                '132' => 'actual practical has alternative papers and 24 hours advance instruction applies',
                '133' => 'actual practical has alternative papers and 24 hours advance instruction applies',
                '134' => 'practical paper lasts 3:20 hours and allows non-programmable calculators',
            ][$subjectCode];

            $practicalChecklist = [
                '131' => 'checklist of apparatuses, equipment, and materials is issued in advance',
                '132' => 'checklist of apparatuses and chemicals is issued in advance',
                '133' => 'checklist of laboratory specimens, chemicals, apparatuses, and equipment is issued in advance',
                '134' => 'checklist of apparatuses, tools, equipment, materials, and specimens is issued in advance',
            ][$subjectCode];

            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => $subjectCode . '/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'B', 'question_types' => ['structured'], 'marks' => 30],
                        ],
                        'rules' => [
                            'paper 1 has ten questions in sections A and B',
                            'answer nine questions in total',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'paper 2 is a theory paper',
                            'paper 2 consists of six questions',
                            'five questions are answered for a total of 100 marks',
                        ],
                    ],
                    [
                        'code' => $subjectCode . '/3',
                        'type' => 'practical',
                        'duration' => '3:20 hours',
                        'total_marks' => 50,
                        'rules' => [
                            'paper 3 is an actual practical paper',
                            'paper 3 consists of three questions',
                            'question 1 carries 20 marks and questions 2 and 3 carry 15 marks each',
                            $practicalChecklist,
                            $practicalNote,
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '136') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '136/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 70],
                            ['name' => 'B', 'question_types' => ['essay_or_structured'], 'marks' => 30],
                        ],
                        'rules' => [
                            'paper has ten questions in sections A and B',
                            'answer nine questions in total',
                        ],
                    ],
                    [
                        'code' => '136/2',
                        'type' => 'practical',
                        'duration' => '3:00 hours',
                        'total_marks' => 50,
                        'rules' => [
                            'practical paper has three questions and candidates answer two',
                            'question one on C++ programming is compulsory',
                            'there is no 24 hours advance instruction',
                            'softcopies and printed hard copies are both required',
                            'checklist of instruments and installed programmes is issued in advance',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '141') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '141/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'one paper with ten short answer or structured questions',
                            'all questions are compulsory',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '142') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '142/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'rules' => [
                            'paper 1 has ten structured questions',
                            'all questions are compulsory',
                            'each question carries 10 marks',
                        ],
                    ],
                    [
                        'code' => '142/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['problem_solving'], 'marks' => 60],
                            ['name' => 'B', 'question_types' => ['extended_questions'], 'marks' => 40],
                        ],
                        'rules' => [
                            'paper 2 has eight questions in sections A and B',
                            'answer six questions in total',
                            'all calculations and steps must be shown clearly',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '151') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '151/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 20],
                            ['name' => 'B', 'question_types' => ['structured'], 'marks' => 40],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 40],
                        ],
                        'rules' => [
                            'paper has eight questions in sections A, B and C',
                            'answer six questions in total',
                            'paper 1 is Economic Theory',
                        ],
                    ],
                    [
                        'code' => '151/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 20],
                            ['name' => 'B', 'question_types' => ['structured'], 'marks' => 40],
                            ['name' => 'C', 'question_types' => ['essay'], 'marks' => 40],
                        ],
                        'rules' => [
                            'paper has eight questions in sections A, B and C',
                            'answer six questions in total',
                            'paper 2 is Economic Development',
                        ],
                    ],
                ],
            ];
        }

        if ($subjectCode === '153') {
            return [
                'name' => $name,
                'papers' => [
                    [
                        'code' => '153/1',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 40],
                            ['name' => 'B', 'question_types' => ['structured_or_problem_solving'], 'marks' => 60],
                        ],
                        'rules' => [
                            'paper has eight questions in sections A and B',
                            'answer seven questions in total',
                            'all calculation steps should be shown clearly',
                            'non-programmable calculators are allowed',
                        ],
                    ],
                    [
                        'code' => '153/2',
                        'type' => 'theory',
                        'duration' => '3:00 hours',
                        'total_marks' => 100,
                        'sections' => [
                            ['name' => 'A', 'question_types' => ['short_answer'], 'marks' => 40],
                            ['name' => 'B', 'question_types' => ['structured_or_problem_solving'], 'marks' => 60],
                        ],
                        'rules' => [
                            'paper has eight questions in sections A and B',
                            'answer seven questions in total',
                            'non-programmable calculators are allowed',
                        ],
                    ],
                ],
            ];
        }

        return [];
    }

    protected function buildCatalogFallbackPapers(string $examType, array $catalog): array
    {
        $papers = [];
        $writtenPapers = (int) ($catalog['written_papers'] ?? 0);

        if ($writtenPapers > 0) {
            for ($i = 1; $i <= $writtenPapers; $i++) {
                $papers[] = [
                    'code' => ($catalog['code'] ?? 'UNKNOWN') . '/' . $i,
                    'type' => 'paper_profile_pending',
                    'rules' => [
                        'Official NECTA booklet lists this subject; detailed rubric extraction is still pending.',
                    ],
                ];
            }

            return $papers;
        }

        $papers[] = [
            'code' => ($catalog['code'] ?? 'UNKNOWN'),
            'type' => 'catalog_entry',
            'rules' => [
                'Official NECTA booklet lists this subject; detailed rubric extraction is still pending.',
            ],
        ];

        if (!empty($catalog['source_page'])) {
            $papers[0]['rules'][] = 'Use the official format booklet source page ' . $catalog['source_page'] . ' for manual review.';
        }

        if ($examType === 'FTNA') {
            $papers[0]['rules'][] = 'Treat this as a vocational-stream subject and confirm whether the booklet prescribes theory, practical, direct performance, or product assessment.';
        }

        return $papers;
    }
}
