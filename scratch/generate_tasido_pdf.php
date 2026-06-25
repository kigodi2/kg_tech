<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Results\TasidoMockTaarifaDataService;
use App\Services\Results\PsleZonalTasidoTaarifaPdfService;
use App\Models\SystemSetting;

$dataService = app(TasidoMockTaarifaDataService::class);
$pdfService = app(PsleZonalTasidoTaarifaPdfService::class);

$savedSettings = SystemSetting::getSetting('psle_tasido_report_settings', []);
$savedSettings['emblem_path'] = public_path('images/emblem.png');

$candidates = [
    'images/emblem.png',
    'images/tanzania-emblem.png',
    'assets/images/emblem.png',
    'assets/img/emblem.png',
];
foreach ($candidates as $candidate) {
    if (file_exists(public_path($candidate))) {
        $savedSettings['emblem_path'] = public_path($candidate);
        break;
    }
}

try {
    $reportData = $dataService->getReportData(2026, $savedSettings);
    
    // Inject mock data for Table 7 to verify our landscape and double header fixes
    $reportData['table7'] = [
        [
            'sn' => 1,
            'region' => 'DODOMA',
            'council' => 'DODOMA MC',
            'school' => 'KIGONGO PRIMARY SCHOOL WITH AN EXTREMELY LONG NAME THAT SHOULD TRUNCATE',
            'registered' => 120,
            'sat' => 118,
            'a' => 50,
            'b' => 40,
            'c' => 20,
            'pass_ac' => 110,
            'pass_pct' => 93.22,
            'average_marks' => 245.81,
            'competence' => 'VIZURI SANA'
        ],
        [
            'sn' => 2,
            'region' => 'IRINGA',
            'council' => 'IRINGA MC',
            'school' => 'SHULE NYINGINE YA JINA LEFU AMBALO PIA INATAKIWA IPUNGUZWE SANA ILI KUZUIA OVERLAP',
            'registered' => 150,
            'sat' => 150,
            'a' => 80,
            'b' => 50,
            'c' => 15,
            'pass_ac' => 145,
            'pass_pct' => 96.67,
            'average_marks' => 240.22,
            'competence' => 'VIZURI SANA'
        ]
    ];

    // Inject mock data for Table 8 to verify landscape and double header fixes
    $reportData['table8'] = [
        [
            'sn' => 1,
            'region' => 'TABORA',
            'council' => 'TABORA MC',
            'school' => 'TABORA BOYS ACADEMY WITH AN EXTREMELY WIDE AND LONG NAME THAT NESTLES IN LANDSCAPE',
            'registered' => 90,
            'sat' => 90,
            'a' => 60,
            'b' => 20,
            'c' => 10,
            'pass_ac' => 90,
            'pass_pct' => 100.00,
            'average_marks' => 230.15,
            'competence' => 'VIZURI SANA'
        ]
    ];
    
    // Inject mock data for Table 9 to verify our layout fixes
    $reportData['table9'] = [
        [
            'sn' => 1,
            'region' => 'DODOMA',
            'council' => 'DODOMA MC',
            'school' => 'KIGONGO PRIMARY SCHOOL WITH AN EXTREMELY LONG NAME THAT SHOULD TRUNCATE',
            'ownership' => 'GOVERNMENT',
            'sat_m' => 50,
            'sat_f' => 60,
            'sat' => 110,
            'a' => 10,
            'b' => 20,
            'c' => 30,
            'pass_ac' => 60,
            'pass_pct' => 54.54,
            'fail_de' => 50,
            'fail_pct' => 45.45,
            'average_marks' => 120.50
        ],
        [
            'sn' => 2,
            'region' => 'IRINGA',
            'council' => 'IRINGA MC',
            'school' => 'SHULE NYINGINE YA JINA LEFU AMBALO PIA INATAKIWA IPUNGUZWE SANA ILI KUZUIA OVERLAP',
            'ownership' => 'NON-GOVERNMENT',
            'sat_m' => 40,
            'sat_f' => 45,
            'sat' => 85,
            'a' => 5,
            'b' => 15,
            'c' => 25,
            'pass_ac' => 45,
            'pass_pct' => 52.94,
            'fail_de' => 40,
            'fail_pct' => 47.06,
            'average_marks' => 115.00
        ],
        [
            'sn' => 3,
            'region' => 'SINGIDA',
            'council' => 'SINGIDA MC',
            'school' => 'SHORT NAME',
            'ownership' => 'NON-GOVERNMENT',
            'sat_m' => 10,
            'sat_f' => 10,
            'sat' => 20,
            'a' => 1,
            'b' => 2,
            'c' => 3,
            'pass_ac' => 6,
            'pass_pct' => 30.00,
            'fail_de' => 14,
            'fail_pct' => 70.00,
            'average_marks' => 90.00
        ]
    ];
    
    $reportData['table12'] = [
        [
            'sn' => 1,
            'subject' => 'KISWAHILI',
            'schools_count' => 120,
            'a' => 45,
            'b' => 50,
            'c' => 20,
            'd' => 5,
            'e' => 0,
            'pass_ac' => 115,
            'pass_pct' => 95.83,
            'fail_de' => 5,
            'fail_pct' => 4.17,
            'average_marks' => 42.15,
            'competence' => 'BORA'
        ],
        [
            'sn' => 2,
            'subject' => 'CIVIC AND MORAL EDUCATION',
            'schools_count' => 120,
            'a' => 10,
            'b' => 25,
            'c' => 40,
            'd' => 35,
            'e' => 10,
            'pass_ac' => 75,
            'pass_pct' => 62.50,
            'fail_de' => 45,
            'fail_pct' => 37.50,
            'average_marks' => 36.70,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 3,
            'subject' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
            'schools_count' => 120,
            'a' => 10,
            'b' => 25,
            'c' => 40,
            'd' => 35,
            'e' => 10,
            'pass_ac' => 75,
            'pass_pct' => 62.50,
            'fail_de' => 45,
            'fail_pct' => 37.50,
            'average_marks' => 32.88,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 4,
            'subject' => 'SCIENCE AND TECHNOLOGY',
            'schools_count' => 120,
            'a' => 10,
            'b' => 25,
            'c' => 40,
            'd' => 35,
            'e' => 10,
            'pass_ac' => 75,
            'pass_pct' => 62.50,
            'fail_de' => 45,
            'fail_pct' => 37.50,
            'average_marks' => 27.40,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 5,
            'subject' => 'ENGLISH LANGUAGE',
            'schools_count' => 120,
            'a' => 10,
            'b' => 25,
            'c' => 40,
            'd' => 35,
            'e' => 10,
            'pass_ac' => 75,
            'pass_pct' => 62.50,
            'fail_de' => 45,
            'fail_pct' => 37.50,
            'average_marks' => 23.97,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 6,
            'subject' => 'MATHEMATICS',
            'schools_count' => 120,
            'a' => 10,
            'b' => 25,
            'c' => 40,
            'd' => 35,
            'e' => 10,
            'pass_ac' => 75,
            'pass_pct' => 62.50,
            'fail_de' => 45,
            'fail_pct' => 37.50,
            'average_marks' => 21.86,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 7,
            'subject' => 'ENGLISH WEIRDLY LONG SUBJECT NAME FOR TESTING DYNAMIC TRUNCATION IN SOMO COLUMN',
            'schools_count' => 120,
            'a' => 10,
            'b' => 25,
            'c' => 40,
            'd' => 35,
            'e' => 10,
            'pass_ac' => 75,
            'pass_pct' => 62.50,
            'fail_de' => 45,
            'fail_pct' => 37.50,
            'average_marks' => 8.50,
            'competence' => 'NZURI'
        ]
    ];
    $reportData['table12_gov'] = [
        [
            'sn' => 1,
            'subject' => 'KISWAHILI',
            'schools_count' => 280,
            'a' => 800,
            'b' => 1000,
            'c' => 500,
            'd' => 200,
            'e' => 50,
            'pass_ac' => 2300,
            'pass_pct' => 90.20,
            'fail_de' => 250,
            'fail_pct' => 9.80,
            'average_marks' => 38.45,
            'competence' => 'BORA'
        ],
        [
            'sn' => 2,
            'subject' => 'CIVIC AND MORAL EDUCATION',
            'schools_count' => 280,
            'a' => 200,
            'b' => 600,
            'c' => 800,
            'd' => 700,
            'e' => 250,
            'pass_ac' => 1600,
            'pass_pct' => 62.75,
            'fail_de' => 950,
            'fail_pct' => 37.25,
            'average_marks' => 29.10,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 3,
            'subject' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
            'schools_count' => 280,
            'a' => 150,
            'b' => 500,
            'c' => 900,
            'd' => 700,
            'e' => 300,
            'pass_ac' => 1550,
            'pass_pct' => 60.78,
            'fail_de' => 1000,
            'fail_pct' => 39.22,
            'average_marks' => 26.50,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 4,
            'subject' => 'SCIENCE AND TECHNOLOGY',
            'schools_count' => 280,
            'a' => 100,
            'b' => 400,
            'c' => 800,
            'd' => 800,
            'e' => 450,
            'pass_ac' => 1300,
            'pass_pct' => 50.98,
            'fail_de' => 1250,
            'fail_pct' => 49.02,
            'average_marks' => 21.80,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 5,
            'subject' => 'ENGLISH LANGUAGE',
            'schools_count' => 280,
            'a' => 50,
            'b' => 300,
            'c' => 700,
            'd' => 900,
            'e' => 600,
            'pass_ac' => 1050,
            'pass_pct' => 41.18,
            'fail_de' => 1500,
            'fail_pct' => 58.82,
            'average_marks' => 16.40,
            'competence' => 'NZURI'
        ],
        [
            'sn' => 6,
            'subject' => 'MATHEMATICS',
            'schools_count' => 280,
            'a' => 40,
            'b' => 200,
            'c' => 600,
            'd' => 1000,
            'e' => 710,
            'pass_ac' => 840,
            'pass_pct' => 32.94,
            'fail_de' => 1710,
            'fail_pct' => 67.06,
            'average_marks' => 13.95,
            'competence' => 'NZURI'
        ]
    ];
    $reportData['narratives']['ufaulu_masomo_serikali'] = "Hali ya ufaulu kwa shule za Serikali inaonesha mwenendo wa ufaulu kwa kila somo katika Mtihani wa Utamilifu wa Darasa la Saba mwaka 2026. Katika matokeo haya, watahiniwa wa shule za Serikali wamefanya vizuri zaidi katika somo la KISWAHILI ambapo asilimia ya waliofeli ni 9.80%. Aidha, changamoto kubwa zaidi imeonekana katika somo la HISABATI ambapo asilimia ya waliofeli ni 67.06%, kama inavyooneshwa kwenye Jedwali Na. 12: Ufaulu Kikanda kwa Masomo (shule za serikali).";
    $reportData['narratives']['ufaulu_masomo_binafsi'] = "Hali ya ufaulu kwa shule zisizo za Serikali pia hairidhishi kama ilivyo kwa shule za Serikali hususan kwa somo la ENGLISH WEIRDLY LONG SUBJECT NAME FOR TESTING DYNAMIC TRUNCATION IN SOMO COLUMN. Katika Mtihani wa Utamilifu uliofanyika, watahiniwa katika shule zisizo za Serikali wamefeli somo hilo kwa 37.50% na somo la MAARIFA YA JAMII NA STADI ZA KAZI kwa asilimia 37.50% kama inavyoonesha kwenye jedwali. Jedwali Na. 13: Ufaulu Kikanda kwa Masomo (shule za binafsi)";

    // Inject mock data for Table 4 to verify our Portrait two-row grouped headers
    $reportData['table4'] = [
        [
            'sn' => 1,
            'region' => 'DODOMA',
            'schools_count' => 15,
            'a' => 120,
            'b' => 230,
            'c' => 450,
            'd' => 100,
            'e' => 10,
            'pass_ac' => 800,
            'pass_pct' => 87.91,
            'fail_de' => 110,
            'fail_pct' => 12.09,
            'average_marks' => 219.52,
            'competence' => 'MAHIRI'
        ],
        [
            'sn' => 2,
            'region' => 'IRINGA',
            'schools_count' => 12,
            'a' => 80,
            'b' => 150,
            'c' => 300,
            'd' => 200,
            'e' => 50,
            'pass_ac' => 530,
            'pass_pct' => 67.95,
            'fail_de' => 250,
            'fail_pct' => 32.05,
            'average_marks' => 147.05,
            'competence' => 'INARIDHISHA'
        ]
    ];

    // Inject mock data for Table 5
    $reportData['table5'] = [
        [
            'sn' => 1,
            'region' => 'TABORA',
            'schools_count' => 8,
            'a' => 90,
            'b' => 180,
            'c' => 220,
            'd' => 50,
            'e' => 5,
            'pass_ac' => 490,
            'pass_pct' => 89.91,
            'fail_de' => 55,
            'fail_pct' => 10.09,
            'average_marks' => 185.19,
            'competence' => 'MAHIRI'
        ],
        [
            'sn' => 2,
            'region' => 'SINGIDA',
            'schools_count' => 10,
            'a' => 40,
            'b' => 100,
            'c' => 150,
            'd' => 180,
            'e' => 70,
            'pass_ac' => 290,
            'pass_pct' => 53.70,
            'fail_de' => 250,
            'fail_pct' => 46.30,
            'average_marks' => 136.34,
            'competence' => 'INARIDHISHA'
        ]
    ];

    // Inject mock data for Table 6
    $reportData['table6'] = [
        [
            'sn' => 1,
            'region' => 'DODOMA',
            'council' => 'DODOMA MC',
            'a' => 450,
            'b' => 800,
            'c' => 1200,
            'd' => 300,
            'e' => 50,
            'pass_ac' => 2450,
            'pass_pct' => 87.50,
            'd_e' => 350,
            'fail_pct' => 12.50,
            'average_marks' => 212.45
        ],
        [
            'sn' => 2,
            'region' => 'IRINGA',
            'council' => 'IRINGA MC',
            'a' => 300,
            'b' => 600,
            'c' => 900,
            'd' => 400,
            'e' => 100,
            'pass_ac' => 1800,
            'pass_pct' => 78.26,
            'd_e' => 500,
            'fail_pct' => 21.74,
            'average_marks' => 189.12
        ]
    ];
    
    $outputPath = __DIR__ . '/test_report.pdf';
    if (file_exists($outputPath)) {
        unlink($outputPath);
    }
    $pdfService->generate($reportData, $outputPath);
    echo "PDF generated successfully at: " . $outputPath . "\n";
} catch (\Exception $e) {
    echo "Error generating PDF: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
