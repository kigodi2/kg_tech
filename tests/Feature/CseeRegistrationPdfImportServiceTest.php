<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Services\Candidates\CseeRegistrationPdfImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CseeRegistrationPdfImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private CseeRegistrationPdfImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CseeRegistrationPdfImportService::class);

        $csee = ExamType::create(['code' => 'CSEE', 'name' => 'CSEE']);
        ExamYear::create(['year_label' => '2026', 'is_active' => true]);
        $region = Region::create(['code' => 'IR', 'name' => 'Iringa']);
        School::create(['code' => 'S0203', 'name' => "IRINGA GIRLS' SECONDARY SCHOOL", 'region_id' => $region->id]);
        School::create(['code' => 'S0108', 'name' => 'IFUNDA TECHNICAL SECONDARY SCHOOL', 'region_id' => $region->id]);

        foreach (config('csee.official_subjects', []) as $entry) {
            Subject::create([
                'exam_type_id' => $csee->id,
                'code' => $entry['code'],
                'name' => $entry['name'],
                'category' => $entry['category'] ?? 'ARTS',
                'is_active' => true,
            ]);
        }
    }

    public function test_it_parses_layout_text_and_previews_registration_sync(): void
    {
        $pdfPath = base_path("IRINGA GIRLS' SECONDARY SCHOOL.pdf");
        if (!file_exists($pdfPath)) {
            $this->markTestSkipped('IRINGA GIRLS registration PDF fixture is not available.');
        }

        $layoutText = shell_exec('pdftotext -layout ' . escapeshellarg($pdfPath) . ' -');
        if (!is_string($layoutText) || trim($layoutText) === '') {
            $this->markTestSkipped('pdftotext did not return extractable text for the IRINGA GIRLS PDF fixture.');
        }

        $parsed = $this->service->parseLayoutText($layoutText);

        $this->assertSame('2026', $parsed['exam_year']);
        $this->assertSame('S0203', $parsed['school_code']);
        $this->assertCount(78, $parsed['rows']);
        $this->assertSame(['011', '012', '013', '021', '022', '033', '041'], $parsed['rows'][0]['subject_codes']);
        $this->assertSame(['011', '012', '013', '021', '022', '033', '041'], $parsed['rows'][1]['subject_codes']);

        $report = $this->service->validateParsedPayload($parsed, '2026');

        $this->assertTrue($report['can_import']);
        $this->assertSame(78, $report['total_rows']);
        $this->assertSame(78, $report['create_count']);
        $this->assertSame(0, $report['error_count']);
    }

    public function test_it_creates_candidates_and_subject_selections_from_registration_pdf_payload(): void
    {
        $payload = [
            'exam_year' => '2026',
            'school_code' => 'S0203',
            'school_name' => "IRINGA GIRLS' SECONDARY SCHOOL",
            'rows' => [
                [
                    'candidate_id' => 'S0203-0001',
                    'gender' => 'F',
                    'full_name' => 'ADELINA PETER DEUS',
                    'school_code' => 'S0203',
                    'subject_codes' => ['011', '012', '013', '021', '022', '033', '041'],
                ],
                [
                    'candidate_id' => 'S0203-0002',
                    'gender' => 'F',
                    'full_name' => 'AGNES SAIMON KABAHILIZA',
                    'school_code' => 'S0203',
                    'subject_codes' => ['011', '012', '013', '021', '022', '033', '041'],
                ],
            ],
        ];

        $report = $this->service->commitParsedPayload(
            $payload,
            '2026'
        );

        $this->assertTrue($report['success']);
        $this->assertSame(2, Candidate::count());

        $firstCandidate = Candidate::where('candidate_id', 'S0203-0001')->firstOrFail();
        $secondCandidate = Candidate::where('candidate_id', 'S0203-0002')->firstOrFail();

        $this->assertSame(7, $firstCandidate->subjectSelections()->count());
        $this->assertSame(7, $secondCandidate->subjectSelections()->count());
    }

    public function test_it_reads_fixed_subject_columns_from_ifunda_pdf_without_over_allocating(): void
    {
        $pdfPath = base_path('IFUNDA TECHNICAL SECONDARY SCHOOL.pdf');
        if (!file_exists($pdfPath)) {
            $this->markTestSkipped('IFUNDA TECHNICAL registration PDF fixture is not available.');
        }

        $file = new \Illuminate\Http\UploadedFile(
            $pdfPath,
            'IFUNDA TECHNICAL SECONDARY SCHOOL.pdf',
            'application/pdf',
            null,
            true
        );

        $report = $this->service->validatePdf($file, '2026');

        $this->assertTrue($report['can_import']);
        $this->assertSame(131, $report['total_rows']);
        $this->assertSame(0, $report['error_count']);

        $rows = collect($report['rows'])->keyBy('candidate_id');

        $this->assertSame(
            ['011', '021', '022', '032', '033', '035', '041', '071', '072', '074'],
            $rows->get('S0108-0003')['subject_codes']
        );

        $this->assertLessThanOrEqual(10, $rows->get('S0108-0003')['subject_count']);
        $this->assertSame(
            ['011', '021', '022', '032', '033', '035', '041', '080', '082'],
            $rows->get('S0108-0001')['subject_codes']
        );
    }

    public function test_it_aggregates_multiple_school_payloads(): void
    {
        $region = Region::firstOrFail();
        School::create(['code' => 'S0204', 'name' => 'LUGALO SECONDARY SCHOOL', 'region_id' => $region->id]);

        $batch = [
            [
                'source_file_name' => 'IRINGA GIRLS.pdf',
                'exam_year' => '2026',
                'school_code' => 'S0203',
                'school_name' => "IRINGA GIRLS' SECONDARY SCHOOL",
                'rows' => [
                    [
                        'candidate_id' => 'S0203-0001',
                        'gender' => 'F',
                        'full_name' => 'ADELINA PETER DEUS',
                        'school_code' => 'S0203',
                        'subject_codes' => ['011', '012', '013', '021', '022', '033', '041'],
                    ],
                ],
            ],
            [
                'source_file_name' => 'LUGALO.pdf',
                'exam_year' => '2026',
                'school_code' => 'S0204',
                'school_name' => 'LUGALO SECONDARY SCHOOL',
                'rows' => [
                    [
                        'candidate_id' => 'S0204-0001',
                        'gender' => 'M',
                        'full_name' => 'JUMA MTEGA',
                        'school_code' => 'S0204',
                        'subject_codes' => ['011', '012', '013', '021', '022', '033', '041'],
                    ],
                ],
            ],
        ];

        $report = $this->service->validateParsedPayloadBatch($batch, '2026');

        $this->assertSame(2, $report['total_files']);
        $this->assertSame(2, $report['importable_school_count']);
        $this->assertSame(2, $report['total_rows']);
        $this->assertCount(2, $report['schools']);

        $commit = $this->service->commitParsedPayloadBatch($batch, '2026');

        $this->assertTrue($commit['success']);
        $this->assertSame(2, Candidate::count());
    }

}
