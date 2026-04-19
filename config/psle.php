<?php

return [
    'timetable' => [
        'source_dir' => env('PSLE_TIMETABLE_SOURCE_DIR', '/home/prosmart-technologies/Music/ZONAL MEETING/ZONAL TIMETABLES'),
        'source_tex' => env('PSLE_TIMETABLE_SOURCE_TEX', 'RATIBA YA MTIHANI WA UTAMILIFU (MOCK) DARASA LA SABA KANDA MEI 2026.tex'),
        'source_pdf' => env('PSLE_TIMETABLE_SOURCE_PDF', 'RATIBA YA MTIHANI WA UTAMILIFU (MOCK) DARASA LA SABA KANDA MEI 2026.pdf'),
        'download_filename' => env('PSLE_TIMETABLE_DOWNLOAD_FILENAME', 'psle_zonal_timetable_may_2026_a3_portrait.pdf'),
    ],

    'official_subjects' => [
        [
            'code' => 'PSLE-01',
            'name' => 'KISWAHILI',
            'category' => 'ARTS',
            'subject_group_label' => 'Language and Literacy',
            'written_papers' => 1,
            'max_marks' => 50,
            'description' => 'NECTA official PSLE subject: KISWAHILI.',
        ],
        [
            'code' => 'PSLE-02',
            'name' => 'ENGLISH LANGUAGE',
            'category' => 'ARTS',
            'subject_group_label' => 'Language and Literacy',
            'written_papers' => 1,
            'max_marks' => 50,
            'description' => 'NECTA official PSLE subject: ENGLISH LANGUAGE.',
        ],
        [
            'code' => 'PSLE-03',
            'name' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
            'category' => 'BUSINESS',
            'subject_group_label' => 'Social Studies and General Learning',
            'written_papers' => 1,
            'max_marks' => 50,
            'description' => 'NECTA official PSLE subject: SOCIAL STUDIES AND VOCATIONAL SKILLS.',
        ],
        [
            'code' => 'PSLE-04',
            'name' => 'MATHEMATICS',
            'category' => 'SCIENCE',
            'subject_group_label' => 'Mathematics and Science',
            'written_papers' => 1,
            'max_marks' => 50,
            'description' => 'NECTA official PSLE subject: MATHEMATICS.',
        ],
        [
            'code' => 'PSLE-05',
            'name' => 'SCIENCE AND TECHNOLOGY',
            'category' => 'SCIENCE',
            'subject_group_label' => 'Mathematics and Science',
            'written_papers' => 1,
            'max_marks' => 50,
            'description' => 'NECTA official PSLE subject: SCIENCE AND TECHNOLOGY.',
        ],
        [
            'code' => 'PSLE-06',
            'name' => 'CIVIC AND MORAL EDUCATION',
            'category' => 'BUSINESS',
            'subject_group_label' => 'Social Studies and General Learning',
            'written_papers' => 1,
            'max_marks' => 50,
            'description' => 'NECTA official PSLE subject: CIVIC AND MORAL EDUCATION.',
        ],
    ],
];
