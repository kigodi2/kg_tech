<?php

return [
    'formats_pdf_path' => env('ACSEE_FORMATS_PDF_PATH', base_path('ACSEE_FORMATS_2026.pdf')),
    'formats_pdf_filename' => env('ACSEE_FORMATS_PDF_FILENAME', 'acsee_formats_2026.pdf'),

    'official_links' => [
        'results_directory' => 'https://www.necta.go.tz/results/view/acsee',
        'publications' => 'https://www.necta.go.tz/publications/all',
    ],

    'exam_types' => [
        'ACSEE' => 'Advanced Certificate of Secondary Education Examination'
    ],

    'official_subjects' => [
        ['code' => '111', 'name' => 'GENERAL STUDIES', 'source_page' => 1],
        ['code' => '112', 'name' => 'HISTORY', 'source_page' => 3],
        ['code' => '113', 'name' => 'GEOGRAPHY', 'source_page' => 7],
        ['code' => '114', 'name' => 'DIVINITY', 'source_page' => 12],
        ['code' => '115', 'name' => 'ISLAMIC KNOWLEDGE', 'source_page' => 17],
        ['code' => '121', 'name' => 'KISWAHILI', 'source_page' => 22],
        ['code' => '122', 'name' => 'ENGLISH LANGUAGE', 'source_page' => 27],
        ['code' => '123', 'name' => 'FRENCH LANGUAGE', 'source_page' => 32],
        ['code' => '125', 'name' => 'ARABIC LANGUAGE', 'source_page' => 36],
        ['code' => '131', 'name' => 'PHYSICS', 'source_page' => 40],
        ['code' => '132', 'name' => 'CHEMISTRY', 'source_page' => 45],
        ['code' => '133', 'name' => 'BIOLOGY', 'source_page' => 50],
        ['code' => '134', 'name' => 'AGRICULTURE', 'source_page' => 55],
        ['code' => '136', 'name' => 'COMPUTER SCIENCE', 'source_page' => 61],
        ['code' => '141', 'name' => 'BASIC APPLIED MATHEMATICS', 'source_page' => 66],
        ['code' => '142', 'name' => 'ADVANCED MATHEMATICS', 'source_page' => 69],
        ['code' => '151', 'name' => 'ECONOMICS', 'source_page' => 73],
        ['code' => '152', 'name' => 'COMMERCE', 'source_page' => 78],
        ['code' => '153', 'name' => 'ACCOUNTANCY', 'source_page' => 82],
        ['code' => '155', 'name' => 'FOOD AND HUMAN NUTRITION', 'source_page' => 87],
    ],
];
