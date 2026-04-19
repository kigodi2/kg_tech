<?php

namespace Tests\Unit\Services;

use App\Services\ExamFormatValidation\ExamFormatValidator;
use App\Services\ExamFormatValidation\NectaFormatRulebook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class ExamFormatValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_exam_format_against_necta_template_passes_when_matches()
    {
        // Create a temporary PDF-like file for testing
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION ACSEE INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION ACSEE INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        // Point the template config to the same temporary file so comparison succeeds
        Config::set('acsee.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock);

        $results = $validator->validateExamFormat($uploadedFile, 'ACSEE');

        $this->assertTrue($results['is_valid']);
        $this->assertEquals(100, $results['template_comparison']['compliance_score']);
        $this->assertEmpty($results['errors']);

        // Cleanup temporary file
        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_warns_when_necta_template_missing()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION ACSEE INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION ACSEE INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        // Set a non-existing template path to simulate missing official PDF
        Config::set('acsee.formats_pdf_path', '/non/existing/path/acsee_formats.pdf');

        $validator = new ExamFormatValidator($parserMock);

        $results = $validator->validateExamFormat($uploadedFile, 'ACSEE');

        $this->assertTrue($results['is_valid']);
        $this->assertNotEmpty($results['template_comparison']['recommendations']);
        $this->assertStringContainsString('NECTA format template is not available for comparison', $results['template_comparison']['recommendations'][0]);

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_includes_subject_rulebook_guidance()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION FTNA 022 ENGLISH LANGUAGE INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION FTNA 022 ENGLISH LANGUAGE INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('ftna.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'FTNA', '022');

        $this->assertSame('022', $results['metadata']['subject_code']);
        $this->assertSame('English Language', $results['rulebook']['subject_name']);
        $this->assertNotEmpty($results['recommendations']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, 'Rulebook paper profile: 022/1');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_uses_catalog_fallback_for_known_subject_without_detailed_profile()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION ACSEE 113 GEOGRAPHY INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION ACSEE 113 GEOGRAPHY INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('acsee.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'ACSEE', '113');

        $this->assertSame('catalog_only', $results['rulebook']['profile_status']);
        $this->assertSame('GEOGRAPHY', $results['rulebook']['subject_name']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, 'Official NECTA booklet lists this subject');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_builds_detailed_ftna_tourism_profile_from_rule_family()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION FTNA 412 TOURISM INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION FTNA 412 TOURISM INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('ftna.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'FTNA', '412');

        $this->assertSame('detailed', $results['rulebook']['profile_status']);
        $this->assertSame('TOURISM', $results['rulebook']['subject_name']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, 'Rulebook paper profile: 412/1, theory paper');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_builds_detailed_ftna_practical_profile_from_rule_family()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION FTNA 842 GRAPHIC DESIGN INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION FTNA 842 GRAPHIC DESIGN INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('ftna.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'FTNA', '842');

        $this->assertSame('detailed', $results['rulebook']['profile_status']);
        $this->assertSame('GRAPHIC DESIGN', $results['rulebook']['subject_name']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, 'process assessment') || str_contains($message, 'final product assessment');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_builds_detailed_csee_standard_profile_from_rule_family()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION CSEE 011 CIVICS INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION CSEE 011 CIVICS INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('csee.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'CSEE', '011');

        $this->assertSame('detailed', $results['rulebook']['profile_status']);
        $this->assertSame('CIVICS', $results['rulebook']['subject_name']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, 'Rulebook paper profile: 011/1, theory paper');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_builds_detailed_csee_science_practical_profile_from_rule_family()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION CSEE 031 PHYSICS INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION CSEE 031 PHYSICS INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('csee.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'CSEE', '031');

        $this->assertSame('detailed', $results['rulebook']['profile_status']);
        $this->assertSame('PHYSICS', $results['rulebook']['subject_name']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, '031/2') || str_contains($message, 'actual practical');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_builds_detailed_acsee_science_profile_from_rule_family()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents($tempFilePath, "%PDF-1.4\nNECTA EXAMINATION ACSEE 131 PHYSICS INSTRUCTIONS SUBJECT CODE\nPAGE 1/1\n");

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION ACSEE 131 PHYSICS INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('acsee.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'ACSEE', '131');

        $this->assertSame('detailed', $results['rulebook']['profile_status']);
        $this->assertSame('PHYSICS', $results['rulebook']['subject_name']);
        $this->assertTrue(collect($results['recommendations'])->contains(function (string $message) {
            return str_contains($message, '131/3') || str_contains($message, 'actual practical');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_includes_document_metadata_inspector_details()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test');
        file_put_contents(
            $tempFilePath,
            "%PDF-1.4\n" .
            "1 0 obj\n" .
            "<< /Title (Sample Paper) /Author (Jane Doe) /Creator (Microsoft Word) /Producer (Microsoft Print to PDF) /CreationDate (D:20260401120000) >>\n" .
            "endobj\n" .
            "NECTA EXAMINATION CSEE 011 CIVICS INSTRUCTIONS SUBJECT CODE PAGE 1/1\n"
        );

        $uploadedFile = new UploadedFile($tempFilePath, 'sample.pdf', 'application/pdf', null, true);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getText')->willReturn('NECTA EXAMINATION CSEE 011 CIVICS INSTRUCTIONS SUBJECT CODE PAGE 1/1');

        $documentMock = $this->createMock(Document::class);
        $documentMock->method('getPages')->willReturn([$pageMock]);

        $parserMock = $this->createMock(Parser::class);
        $parserMock->method('parseFile')->willReturn($documentMock);

        Config::set('csee.formats_pdf_path', $tempFilePath);

        $validator = new ExamFormatValidator($parserMock, new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'CSEE', '011');

        $this->assertSame('Jane Doe', $results['document_inspector']['fields']['author']);
        $this->assertSame('Microsoft Word', $results['document_inspector']['fields']['creator']);
        $this->assertSame('sample.pdf', $results['metadata']['original_filename']);
        $this->assertSame('pdf', $results['metadata']['document_extension']);
        $this->assertNotEmpty($results['metadata']['sha256_fingerprint']);
        $this->assertStringContainsString('template comparison', strtolower($results['metadata']['validation_basis']));
        $this->assertTrue(collect($results['document_inspector']['environment_clues'])->contains(function (string $message) {
            return str_contains($message, 'Microsoft Word');
        }));

        @unlink($tempFilePath);
    }

    public function test_validate_exam_format_supports_docx_documents()
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'exam_format_test') . '.docx';

        $zip = new \ZipArchive();
        $zip->open($tempFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>');
        $zip->addFromString(
            'word/document.xml',
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' .
            '<w:body><w:p><w:r><w:t>NECTA EXAMINATION CSEE 011 CIVICS INSTRUCTIONS SUBJECT CODE PAGE 1/1</w:t></w:r></w:p></w:body>' .
            '</w:document>'
        );
        $zip->addFromString(
            'docProps/core.xml',
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/">' .
            '<dc:title>Sample DOCX</dc:title><dc:creator>John Doe</dc:creator><cp:lastModifiedBy>Mary Editor</cp:lastModifiedBy><dcterms:created>2026-04-01T10:00:00Z</dcterms:created>' .
            '</cp:coreProperties>'
        );
        $zip->addFromString(
            'docProps/app.xml',
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">' .
            '<Application>Microsoft Office Word</Application><Pages>2</Pages><Company>ProSmart</Company>' .
            '</Properties>'
        );
        $zip->close();

        $uploadedFile = new UploadedFile(
            $tempFilePath,
            'sample.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $validator = new ExamFormatValidator($this->createMock(Parser::class), new NectaFormatRulebook());

        $results = $validator->validateExamFormat($uploadedFile, 'CSEE', '011');

        $this->assertSame('DOCX', $results['metadata']['document_format']);
        $this->assertSame('DOCX', $results['document_inspector']['format']);
        $this->assertSame('John Doe', $results['document_inspector']['fields']['author']);
        $this->assertSame('sample.docx', $results['metadata']['original_filename']);
        $this->assertSame('docx', $results['metadata']['document_extension']);
        $this->assertStringContainsString('docx text extraction', strtolower($results['metadata']['validation_basis']));
        $this->assertTrue(collect($results['document_inspector']['environment_clues'])->contains(function (string $message) {
            return str_contains($message, 'Microsoft Word');
        }));
        $this->assertFalse($results['template_comparison']['template_available']);

        @unlink($tempFilePath);
    }
}
