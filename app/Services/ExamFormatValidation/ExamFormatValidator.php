<?php

namespace App\Services\ExamFormatValidation;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;
use Exception;
use ZipArchive;
use SimpleXMLElement;

class ExamFormatValidator
{
    protected Parser $pdfParser;
    protected NectaFormatRulebook $rulebook;

    public function __construct(Parser $pdfParser = null, ?NectaFormatRulebook $rulebook = null)
    {
        $this->pdfParser = $pdfParser ?? new Parser();
        $this->rulebook = $rulebook ?? new NectaFormatRulebook();
    }

    /**
     * Validate an uploaded exam document against NECTA format requirements.
     */
    public function validateExamFormat(UploadedFile $file, string $examType, ?string $subjectCode = null): array
    {
        $ruleSummary = $this->rulebook->summarize($examType, $subjectCode);
        $extension = strtolower($file->getClientOriginalExtension());
        $format = $extension === 'docx' ? 'DOCX' : 'PDF';
        $results = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => [],
            'metadata' => [
                'original_filename' => $file->getClientOriginalName(),
                'document_extension' => $extension,
                'sha256_fingerprint' => hash_file('sha256', $file->getPathname()) ?: null,
                'inspected_at' => now()->toIso8601String(),
                'exam_type' => strtoupper($examType),
                'subject_code' => $subjectCode,
                'rulebook_version' => $ruleSummary['version'] ?? null,
                'document_format' => $format,
            ],
            'document_inspector' => [
                'format' => $format,
                'fields' => [],
                'environment_clues' => [],
            ],
            'rulebook' => $ruleSummary,
        ];

        try {
            if ($format === 'DOCX') {
                $this->validateDocxDocument($file, $examType, $subjectCode, $results, $ruleSummary);
            } else {
                $this->validatePdfDocument($file, $examType, $subjectCode, $results, $ruleSummary);
            }

        } catch (Exception $e) {
            $results['is_valid'] = false;
            $results['errors'][] = 'Failed to parse ' . $format . ': ' . $e->getMessage();
        }

        return $results;
    }

    protected function validatePdfDocument(UploadedFile $file, string $examType, ?string $subjectCode, array &$results, array $ruleSummary): void
    {
        $results['document_inspector'] = $this->inspectPdfMetadata($file->getPathname());

        $pdf = $this->pdfParser->parseFile($file->getPathname());
        $pages = $pdf->getPages();

        $results['metadata'] = [
            ...$results['metadata'],
            'page_count' => count($pages),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'validation_basis' => 'Official PDF template comparison plus text and metadata checks',
        ];

        $this->validateBasicDocumentRequirements($file, ['pdf'], ['application/pdf', 'application/x-pdf'], $results);
        $this->validateContentStructure($pages, $examType, $results, $subjectCode);
        $this->appendRulebookGuidance($results, $ruleSummary);
        $this->validateFormatCompliance($file->getPathname(), $pages, $examType, $results);
    }

    protected function validateDocxDocument(UploadedFile $file, string $examType, ?string $subjectCode, array &$results, array $ruleSummary): void
    {
        $docx = $this->extractDocxContents($file->getPathname());
        $results['document_inspector'] = $docx['document_inspector'];
        $results['metadata'] = [
            ...$results['metadata'],
            'page_count' => $docx['page_count'],
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'validation_basis' => 'DOCX text extraction and metadata checks aligned to the official PDF guide',
        ];

        $this->validateBasicDocumentRequirements(
            $file,
            ['docx'],
            [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
            $results
        );

        $pages = $this->buildLogicalPagesFromText($docx['text']);
        $this->validateContentStructure($pages, $examType, $results, $subjectCode);
        $this->appendRulebookGuidance($results, $ruleSummary);

        $results['template_comparison'] = [
            'template_available' => false,
            'compliance_score' => null,
            'matched_elements' => [],
            'missing_elements' => [],
            'recommendations' => [
                'NECTA template comparison is currently based on official PDF guides; DOCX submissions are validated using extracted text and document metadata.',
            ],
        ];
        $results['warnings'][] = 'DOCX submission detected; template comparison is limited to text and metadata checks because the official NECTA format guides are distributed as PDFs.';
    }

    protected function inspectPdfMetadata(string $filePath): array
    {
        $inspector = [
            'format' => 'PDF',
            'fields' => [],
            'environment_clues' => [],
        ];

        $raw = @file_get_contents($filePath);

        if ($raw === false || $raw === '') {
            $inspector['environment_clues'][] = 'Document metadata could not be read from the uploaded PDF.';
            return $inspector;
        }

        $mappings = [
            'Title' => 'title',
            'Author' => 'author',
            'Subject' => 'subject',
            'Keywords' => 'keywords',
            'Creator' => 'creator',
            'Producer' => 'producer',
            'CreationDate' => 'create_date',
            'ModDate' => 'modify_date',
        ];

        foreach ($mappings as $pdfKey => $resultKey) {
            $value = $this->extractPdfMetadataValue($raw, $pdfKey);
            if ($value !== null && $value !== '') {
                $inspector['fields'][$resultKey] = $value;
            }
        }

        if (preg_match('/%PDF-([0-9.]+)/', $raw, $match) === 1) {
            $inspector['fields']['pdf_version'] = $match[1];
        }

        $inspector['fields']['metadata_status'] = count($inspector['fields']) > 0
            ? 'Document metadata is present and was inspected.'
            : 'No embedded PDF metadata fields were detected.';

        $inspector['environment_clues'] = $this->inferPdfEnvironmentClues($inspector['fields']);

        return $inspector;
    }

    protected function extractPdfMetadataValue(string $raw, string $key): ?string
    {
        $pattern = sprintf('/\/%s\s*\((.*?)\)/s', preg_quote($key, '/'));

        if (preg_match($pattern, $raw, $match) !== 1) {
            return null;
        }

        $value = preg_replace('/\\\\([()\\\\])/', '$1', $match[1]);
        $value = preg_replace('/\s+/', ' ', (string) $value);

        return trim((string) $value);
    }

    protected function inferPdfEnvironmentClues(array $fields): array
    {
        $creator = strtolower((string) ($fields['creator'] ?? ''));
        $producer = strtolower((string) ($fields['producer'] ?? ''));
        $combined = trim($creator . ' ' . $producer);
        $clues = [];

        if (str_contains($combined, 'microsoft') || str_contains($combined, 'word')) {
            $clues[] = 'Likely created or exported using Microsoft Word.';
        }

        if (str_contains($combined, 'microsoft print to pdf') || str_contains($combined, 'windows')) {
            $clues[] = 'Likely processed on Windows.';
        }

        if (str_contains($combined, 'quartz pdfcontext') || str_contains($combined, 'mac')) {
            $clues[] = 'Likely processed on macOS.';
        }

        if (str_contains($combined, 'libreoffice')) {
            $clues[] = 'Likely created using LibreOffice.';
        }

        if (str_contains($combined, 'wps')) {
            $clues[] = 'Likely created using WPS Office.';
        }

        if (str_contains($combined, 'adobe')) {
            $clues[] = 'Adobe software appears in the document production metadata.';
        }

        if ($clues === []) {
            $clues[] = 'No strong device or authoring-environment clue was found in the PDF metadata.';
        }

        return $clues;
    }

    protected function validateBasicDocumentRequirements(UploadedFile $file, array $allowedExtensions, array $allowedMimeTypes, array &$results): void
    {
        if ($file->getSize() > 10 * 1024 * 1024) {
            $results['is_valid'] = false;
            $results['errors'][] = 'File size exceeds maximum allowed size of 10MB';
        }

        if (!in_array((string) $file->getMimeType(), $allowedMimeTypes, true)) {
            $results['is_valid'] = false;
            $results['errors'][] = 'File must be a valid ' . strtoupper(implode(' or ', $allowedExtensions)) . ' document';
        }

        if (!in_array(strtolower($file->getClientOriginalExtension()), $allowedExtensions, true)) {
            $results['is_valid'] = false;
            $results['errors'][] = 'File must have one of these extensions: ' . implode(', ', array_map(fn ($extension) => '.' . $extension, $allowedExtensions));
        }
    }

    protected function extractDocxContents(string $filePath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath) !== true) {
            throw new Exception('Unable to open DOCX package.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $coreXml = $zip->getFromName('docProps/core.xml');
        $appXml = $zip->getFromName('docProps/app.xml');
        $zip->close();

        $text = $this->extractTextFromDocxXml((string) $documentXml);
        $coreData = $this->parseDocxCoreXml((string) $coreXml);
        $appData = $this->parseDocxAppXml((string) $appXml);

        $fields = array_filter([
            'title' => $coreData['title'] ?? null,
            'subject' => $coreData['subject'] ?? null,
            'author' => $coreData['creator'] ?? null,
            'last_modified_by' => $coreData['lastModifiedBy'] ?? null,
            'description' => $coreData['description'] ?? null,
            'keywords' => $coreData['keywords'] ?? null,
            'created' => $coreData['created'] ?? null,
            'modified' => $coreData['modified'] ?? null,
            'application' => $appData['Application'] ?? null,
            'app_version' => $appData['AppVersion'] ?? null,
            'company' => $appData['Company'] ?? null,
            'template' => $appData['Template'] ?? null,
            'pages' => $appData['Pages'] ?? null,
            'words' => $appData['Words'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $fields['metadata_status'] = count($fields) > 0
            ? 'Document metadata is present and was inspected.'
            : 'No embedded DOCX metadata fields were detected.';

        return [
            'text' => $text,
            'page_count' => isset($appData['Pages']) && is_numeric($appData['Pages']) ? (int) $appData['Pages'] : max(1, (int) ceil(max(strlen($text), 1) / 3500)),
            'document_inspector' => [
                'format' => 'DOCX',
                'fields' => $fields,
                'environment_clues' => $this->inferDocxEnvironmentClues($coreData, $appData),
            ],
        ];
    }

    protected function extractTextFromDocxXml(string $xmlString): string
    {
        if ($xmlString === '') {
            return '';
        }

        $xml = @simplexml_load_string($xmlString);

        if (! $xml instanceof SimpleXMLElement) {
            return trim(strip_tags($xmlString));
        }

        $namespaces = $xml->getNamespaces(true);
        $body = isset($namespaces['w']) ? $xml->children($namespaces['w'])->body : $xml->body;

        if (! $body) {
            return trim(strip_tags($xmlString));
        }

        $paragraphs = [];
        foreach ($body->children($namespaces['w'] ?? null)->p ?? [] as $paragraph) {
            $textRuns = [];
            foreach ($paragraph->children($namespaces['w'] ?? null)->r ?? [] as $run) {
                $text = (string) ($run->children($namespaces['w'] ?? null)->t ?? '');
                if ($text !== '') {
                    $textRuns[] = $text;
                }
            }
            $paragraphText = trim(implode(' ', $textRuns));
            if ($paragraphText !== '') {
                $paragraphs[] = $paragraphText;
            }
        }

        return implode("\n", $paragraphs);
    }

    protected function parseDocxCoreXml(string $xmlString): array
    {
        $xml = @simplexml_load_string($xmlString);
        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $namespaces = $xml->getNamespaces(true);

        return [
            'title' => isset($namespaces['dc']) ? (string) $xml->children($namespaces['dc'])->title : null,
            'subject' => isset($namespaces['dc']) ? (string) $xml->children($namespaces['dc'])->subject : null,
            'creator' => isset($namespaces['dc']) ? (string) $xml->children($namespaces['dc'])->creator : null,
            'description' => isset($namespaces['dc']) ? (string) $xml->children($namespaces['dc'])->description : null,
            'keywords' => isset($namespaces['cp']) ? (string) $xml->children($namespaces['cp'])->keywords : null,
            'lastModifiedBy' => isset($namespaces['cp']) ? (string) $xml->children($namespaces['cp'])->lastModifiedBy : null,
            'created' => isset($namespaces['dcterms']) ? (string) $xml->children($namespaces['dcterms'])->created : null,
            'modified' => isset($namespaces['dcterms']) ? (string) $xml->children($namespaces['dcterms'])->modified : null,
        ];
    }

    protected function parseDocxAppXml(string $xmlString): array
    {
        $xml = @simplexml_load_string($xmlString);
        if (! $xml instanceof SimpleXMLElement) {
            return [];
        }

        $result = [];
        foreach ($xml->children() as $child) {
            $result[$child->getName()] = (string) $child;
        }

        return $result;
    }

    protected function inferDocxEnvironmentClues(array $coreData, array $appData): array
    {
        $application = strtolower((string) ($appData['Application'] ?? ''));
        $author = (string) ($coreData['creator'] ?? '');
        $modifier = (string) ($coreData['lastModifiedBy'] ?? '');
        $company = (string) ($appData['Company'] ?? '');
        $clues = [];

        if (str_contains($application, 'microsoft office word') || str_contains($application, 'word')) {
            $clues[] = 'Likely created or edited using Microsoft Word.';
        }

        if (str_contains($application, 'libreoffice')) {
            $clues[] = 'Likely edited using LibreOffice.';
        }

        if (str_contains($application, 'wps')) {
            $clues[] = 'Likely edited using WPS Office.';
        }

        if ($company !== '') {
            $clues[] = 'Organization or company metadata found: ' . $company . '.';
        }

        if ($author !== '') {
            $clues[] = 'Stored author name exists: ' . $author . '.';
        }

        if ($modifier !== '' && $modifier !== $author) {
            $clues[] = 'Document appears to have been edited by another user: ' . $modifier . '.';
        }

        if ($clues === []) {
            $clues[] = 'No strong device or authoring-environment clue was found in the DOCX metadata.';
        }

        return $clues;
    }

    protected function buildLogicalPagesFromText(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $chunks = preg_split('/\n\s*\n/', $text);
        $pages = array_values(array_filter(array_map('trim', $chunks), fn ($chunk) => $chunk !== ''));

        if ($pages === []) {
            $pages = [preg_replace('/\s+/', ' ', $text)];
        }

        return array_map(function (string $pageText) {
            return new class($pageText) {
                public function __construct(private string $text)
                {
                }

                public function getText(): string
                {
                    return $this->text;
                }
            };
        }, $pages);
    }

    /**
     * Validate content structure based on exam type
     */
    protected function validateContentStructure(array $pages, string $examType, array &$results, ?string $subjectCode = null): void
    {
        $pageCount = count($pages);

        // Exam-specific page count validation
        switch ($examType) {
            case 'ACSEE':
                if ($pageCount < 1 || $pageCount > 20) {
                    $results['warnings'][] = 'ACSEE exam papers typically have 1-20 pages';
                }
                break;
            case 'CSEE':
                if ($pageCount < 1 || $pageCount > 15) {
                    $results['warnings'][] = 'CSEE exam papers typically have 1-15 pages';
                }
                break;
            case 'FTNA':
                if ($pageCount < 1 || $pageCount > 10) {
                    $results['warnings'][] = 'FTNA exam papers typically have 1-10 pages';
                }
                break;
        }

        // Check for required elements on first page
        if (!empty($pages)) {
            $firstPageText = $pages[0]->getText();
            $this->validateFirstPageElements($firstPageText, $examType, $results, $subjectCode);
        }
    }

    /**
     * Validate first page required elements
     */
    protected function validateFirstPageElements(string $text, string $examType, array &$results, ?string $subjectCode = null): void
    {
        $text = strtolower($text);

        // Check for NECTA branding
        if (strpos($text, 'necta') === false) {
            $results['warnings'][] = 'NECTA branding not found on first page';
        }

        // Check for exam type
        if (strpos($text, strtolower($examType)) === false) {
            $results['warnings'][] = "Exam type '{$examType}' not clearly indicated";
        }

        if ($subjectCode !== null && strpos($text, strtolower($subjectCode)) === false) {
            $results['warnings'][] = "Subject code '{$subjectCode}' not clearly indicated on first page";
        }

        // Check for subject information
        $subjectKeywords = ['subject', 'paper', 'code'];
        $hasSubjectInfo = false;
        foreach ($subjectKeywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                $hasSubjectInfo = true;
                break;
            }
        }
        if (!$hasSubjectInfo) {
            $results['warnings'][] = 'Subject information not found on first page';
        }
    }

    /**
     * Add machine-readable guidance derived from the studied NECTA format guides.
     */
    protected function appendRulebookGuidance(array &$results, array $ruleSummary): void
    {
        if (!empty($ruleSummary['common_sections'])) {
            $results['recommendations'][] = 'Expected NECTA format sections: ' . implode(', ', $ruleSummary['common_sections']) . '.';
        }

        foreach ($ruleSummary['validation_focus'] ?? [] as $focus) {
            $results['recommendations'][] = 'Validation focus: ' . $focus . '.';
        }

        foreach ($ruleSummary['practical_controls'] ?? [] as $control) {
            $results['recommendations'][] = 'Practical control: ' . $control . '.';
        }

        foreach ($ruleSummary['papers'] ?? [] as $paper) {
            $details = [];

            if (!empty($paper['code'])) {
                $details[] = $paper['code'];
            }

            if (!empty($paper['type'])) {
                $details[] = $paper['type'] . ' paper';
            }

            if (!empty($paper['duration'])) {
                $details[] = 'duration ' . $paper['duration'];
            }

            if (!empty($paper['total_marks'])) {
                $details[] = 'total ' . $paper['total_marks'] . ' marks';
            }

            if (!empty($details)) {
                $results['recommendations'][] = 'Rulebook paper profile: ' . implode(', ', $details) . '.';
            }

            foreach ($paper['rules'] ?? [] as $rule) {
                $results['recommendations'][] = 'Rulebook expectation: ' . $rule . '.';
            }
        }
    }

    /**
     * Validate format compliance against NECTA standards
     */
    protected function validateFormatCompliance(string $submittedPdfPath, array $pages, string $examType, array &$results): void
    {
        // Compare with an official NECTA format template for the selected exam type
        $comparison = $this->compareWithNectaFormat($submittedPdfPath, $examType);
        $results['template_comparison'] = $comparison;

        if (!empty($comparison['missing_elements'])) {
            foreach ($comparison['missing_elements'] as $missing) {
                $results['errors'][] = "NECTA format check failed: {$missing}";
            }
        }

        if (!empty($comparison['template_available']) && $comparison['template_available'] === true) {
            if ($comparison['compliance_score'] < 80) {
                $results['errors'][] = "Exam paper compliance score ({$comparison['compliance_score']}%) is below the required threshold (80%)";
            }
        } else {
            $results['warnings'][] = 'NECTA format template unavailable; compliance is evaluated using basic structural checks only.';
        }

        // Keep the submission invalid if any template errors are critical
        if (!empty($results['errors'])) {
            $results['is_valid'] = false;
        }

        // Structural validation (page-level checks)
        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1;
            $text = $page->getText();

            // Check for proper page numbering
            if (!preg_match('/page\s*' . $pageNumber . '/i', $text) &&
                !preg_match('/' . $pageNumber . '\s*\/\s*\d+/', $text)) {
                $results['warnings'][] = "Page {$pageNumber} may not have proper page numbering";
            }

            // Check for minimum content
            if (strlen(trim($text)) < 100) {
                $results['warnings'][] = "Page {$pageNumber} appears to have very little content";
            }

            // Check for exam-type-specific page text patterns
            $lowerText = strtolower($text);
            if ($pageNumber === 1) {
                $requiredFirstPageKeywords = ['necta', 'examination', strtolower($examType), 'instructions'];
                foreach ($requiredFirstPageKeywords as $keyword) {
                    if (strpos($lowerText, $keyword) === false) {
                        $results['warnings'][] = "First page might be missing required heading: {$keyword}";
                    }
                }
            }
        }
    }

    /**
     * Compare PDF structure against NECTA format template
     */
    public function compareWithNectaFormat(string $submittedPdfPath, string $examType): array
    {
        $results = [
            'compliance_score' => 0,
            'matched_elements' => [],
            'missing_elements' => [],
            'recommendations' => []
        ];

        // Get the format PDF path for this exam type
        $formatPdfPath = $this->getFormatPdfPath($examType);

        if (!file_exists($formatPdfPath)) {
            $results['template_available'] = false;
            $results['recommendations'][] = 'NECTA format template is not available for comparison; only basic structural checks were executed.';
            return $results;
        }

        $results['template_available'] = true;

        try {
            // Parse both PDFs
            $submittedPdf = $this->pdfParser->parseFile($submittedPdfPath);
            $formatPdf = $this->pdfParser->parseFile($formatPdfPath);

            // Compare basic structure
            $this->comparePdfStructure($submittedPdf, $formatPdf, $results);

            // Calculate compliance score (0-100)
            $results['compliance_score'] = $this->calculateComplianceScore($results);

        } catch (Exception $e) {
            $results['missing_elements'][] = 'Failed to compare with NECTA format: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Get the format PDF path for a specific exam type
     */
    protected function getFormatPdfPath(string $examType): string
    {
        switch ($examType) {
            case 'ACSEE':
                return config('acsee.formats_pdf_path');
            case 'CSEE':
                return config('csee.formats_pdf_path');
            case 'FTNA':
                // FTNA has both general and vocational stream formats.
                $paths = config('ftna.formats_pdf_paths', []);
                if (isset($paths['general']) && file_exists($paths['general'])) {
                    return $paths['general'];
                }
                if (isset($paths['vocational']) && file_exists($paths['vocational'])) {
                    return $paths['vocational'];
                }
                return config('ftna.formats_pdf_path');
            default:
                return '';
        }
    }

    /**
     * Compare PDF structures
     */
    protected function comparePdfStructure($submittedPdf, $formatPdf, array &$results): void
    {
        $submittedPages = $submittedPdf->getPages();
        $formatPages = $formatPdf->getPages();

        // Compare page count
        if (count($submittedPages) !== count($formatPages)) {
            $results['missing_elements'][] = 'Page count does not match NECTA format';
        } else {
            $results['matched_elements'][] = 'Page count matches NECTA format';
        }

        // Compare text patterns (simplified)
        $formatText = strtolower($formatPages[0]->getText());
        $submittedText = strtolower($submittedPages[0]->getText());

        $requiredElements = ['necta', 'examination', 'instructions'];

        foreach ($requiredElements as $element) {
            if (strpos($formatText, $element) !== false && strpos($submittedText, $element) !== false) {
                $results['matched_elements'][] = "Contains required element: {$element}";
            } else {
                $results['missing_elements'][] = "Missing required element: {$element}";
            }
        }
    }

    /**
     * Calculate compliance score
     */
    protected function calculateComplianceScore(array $results): int
    {
        $matched = count($results['matched_elements']);
        $missing = count($results['missing_elements']);

        if ($matched + $missing === 0) {
            return 100;
        }

        return (int) (($matched / ($matched + $missing)) * 100);
    }
}
