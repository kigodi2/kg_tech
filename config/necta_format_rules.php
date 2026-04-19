<?php

return [
    'version' => '2026-04-01',

    'common' => [
        'booklet_sections' => [
            'introduction',
            'general_objectives',
            'general_competences',
            'rubric',
            'competences_or_content_to_be_assessed',
            'table_of_specifications',
        ],
        'validation_focus' => [
            'first_page_identity' => [
                'necta',
                'exam title',
                'subject code',
                'subject name',
            ],
            'rubric_signals' => [
                'paper count',
                'duration',
                'sections',
                'question types',
                'mark allocation',
                'compulsory/optional instructions',
            ],
        ],
    ],

    'exam_types' => [
        'FTNA' => [
            'guide_title' => 'Formats for the Form Two National Assessment (FTNA) Vocational Stream',
            'edition' => 'September 2025',
            'assessment_model' => 'competency_based',
            'syllabus_basis' => 'TIE 2023 vocational stream syllabuses',
            'validation_focus' => [
                'confirm subject identity and stream alignment',
                'check that the paper reflects the subject rubric rather than a generic school template',
                'distinguish theory papers from direct performance, product, and practical assessments',
                'verify that assessed competences are consistent with the subject-specific table of specifications',
            ],
            'common_practical_controls' => [
                'tool, equipment, and material checklists issued in advance',
                '24 hours advance instruction where the rubric requires it',
                'product conformity and accuracy assessment for practical subjects',
                'practical assessment guideline referenced by NECTA',
            ],
            'subjects' => [
                '022' => [
                    'name' => 'English Language',
                    'papers' => [
                        [
                            'code' => '022/1',
                            'type' => 'theory',
                            'duration' => '2:30 hours',
                            'duration_special_needs' => '2:55 hours',
                            'total_marks' => 100,
                            'sections' => [
                                ['name' => 'A', 'question_types' => ['multiple_choice', 'matching'], 'marks' => 15],
                                ['name' => 'B', 'question_types' => ['short_answer'], 'marks' => 70],
                                ['name' => 'C', 'question_types' => ['essay_or_structured_or_composition'], 'marks' => 15],
                            ],
                            'rules' => [
                                'one theory paper with ten questions',
                                'all questions are compulsory',
                                'paper should visibly separate sections A, B, and C',
                            ],
                        ],
                    ],
                ],
                '205' => [
                    'name' => 'Leather Goods and Footwear',
                    'papers' => [
                        [
                            'code' => '205/1',
                            'type' => 'theory',
                            'rules' => [
                                'theory paper tied to workshop safety, maintenance, design, and production competences',
                                'table of specifications is competence-weighted rather than purely topic-weighted',
                            ],
                        ],
                        [
                            'code' => '205/2',
                            'type' => 'practical',
                            'total_marks' => 100,
                            'components' => [
                                ['name' => 'direct_performance_assessment', 'marks' => 60],
                                ['name' => 'product_assessment', 'marks' => 40],
                            ],
                            'rules' => [
                                'student performs layout, cutting, skiving, assembly, and stitching from a given task/pattern',
                                'finished product is checked against measurements and conformity to specification',
                                'checklist is expected three months in advance',
                                '24 hours advance instruction applies',
                            ],
                        ],
                    ],
                ],
                '806' => [
                    'name' => 'Painting and Signwriting',
                    'papers' => [
                        [
                            'code' => '806/1',
                            'type' => 'theory',
                            'rules' => [
                                'theory paper covers workshop safety, paint selection, structure painting, signwriting, estimating/costing, and plate spraying',
                            ],
                        ],
                        [
                            'code' => '806/2',
                            'type' => 'practical',
                            'rules' => [
                                'practical tasks should evidence paint formulation, colour matching, surface preparation, signwriting, and spraying competences',
                                'practical guideline and advance logistics are part of the official administration model',
                            ],
                        ],
                    ],
                ],
            ],
        ],

        'CSEE' => [
            'guide_title' => 'Certificate of Secondary Education Examination Formats',
            'edition' => 'October 2022',
            'assessment_model' => 'competency_based',
            'validation_focus' => [
                'confirm subject-specific paper structure and sectioning',
                'check that the exam reflects rubric and topic weightings from the table of specifications',
                'distinguish theory papers from practical papers and their submission procedures',
            ],
            'common_practical_controls' => [
                'hardware/software/material checklists are issued before practical papers',
                'some practical papers require hardcopy plus softcopy submission',
                'practical papers may have no advance instruction even where materials are pre-announced',
            ],
            'subjects' => [
                '010' => [
                    'name' => 'Qualifying Test',
                    'papers' => [
                        [
                            'code' => '010/1',
                            'type' => 'theory',
                            'duration' => '3:00 hours',
                            'total_marks' => 100,
                            'rules' => [
                                'one paper with sections A and B',
                                'section A is compulsory and spans civics and languages',
                                'section B is choice-based across grouped subject clusters',
                            ],
                        ],
                    ],
                ],
                '036' => [
                    'name' => 'Information and Computer Studies',
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
                                'all questions in sections A and B are compulsory',
                                'two questions are chosen from section C',
                            ],
                        ],
                        [
                            'code' => '036/2',
                            'type' => 'practical',
                            'total_marks' => 50,
                            'rules' => [
                                'three practical questions, answer two',
                                'softcopies saved using candidate examination numbers and submitted with hardcopies',
                                'no three-hour advance instruction for this practical paper',
                            ],
                        ],
                    ],
                ],
            ],
        ],

        'ACSEE' => [
            'guide_title' => 'Advanced Certificate of Secondary Education Examination Formats',
            'edition' => 'Revised Edition July 2019',
            'assessment_model' => 'competency_based_higher_order',
            'validation_focus' => [
                'check that the paper targets higher-order thinking and competence-based assessment',
                'confirm that sections are organised by question type where prescribed',
                'apply practical-paper controls where the subject includes practical work',
            ],
            'common_practical_controls' => [
                'required-material checklists are issued ahead of practical papers',
                'softcopy and hardcopy handling rules may apply to computer-based practicals',
            ],
            'subjects' => [
                '111' => [
                    'name' => 'General Studies',
                    'papers' => [
                        [
                            'code' => '111/1',
                            'type' => 'theory',
                            'duration' => '3:00 hours',
                            'total_marks' => 100,
                            'rules' => [
                                'one paper with seven essay-type questions',
                                'five questions answered in total',
                                'question one is compulsory',
                                'each question carries twenty marks',
                            ],
                        ],
                    ],
                ],
                '136' => [
                    'name' => 'Computer Science',
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
                                'seven short-answer questions in section A, all compulsory',
                                'three essay/structured questions in section B, answer two',
                            ],
                        ],
                        [
                            'code' => '136/2',
                            'type' => 'practical',
                            'duration' => '3:00 hours',
                            'total_marks' => 50,
                            'rules' => [
                                'three practical questions, answer two',
                                'question one on C++ programming is compulsory',
                                'there is no 24 hours advance instruction',
                                'softcopies and printed hard copies are both expected',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
