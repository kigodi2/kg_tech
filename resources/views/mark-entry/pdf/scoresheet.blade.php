<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $school->code }} - {{ $subject->code }} - ACSEE {{ $examYear->year_label }}</title>
    <style>
        /**
         * ACSEE SCORESHEET PDF TEMPLATE
         * 
         * Specifications:
         * - A4 portrait (210mm × 297mm)
         * - Page margins: 20mm (enforced via @page)
         * - Internal padding: 6mm (breathing room inside content)
         * - Font: DejaVu Sans, 11pt (global, PDF-safe)
         * - SN column: REMOVED
         * - Title: Line-by-line (not inline grid)
         * - Page-break safe (headers repeat, rows intact)
         * 
         * Usable width: 210mm - 40mm (margins) - 12mm (padding) = 158mm
         */

        /* ===== PAGE & MARGIN RULES ===== */
        @page {
            size: A4 portrait;
            margin: 20mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Maiandra GD', 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
            line-height: 1.3;
            padding: 6mm;
        }

        /* ===== HEADER SECTION (LINE-BY-LINE) ===== */
        .pdf-header {
            margin-bottom: 10mm;
            padding-bottom: 4mm;
            border-bottom: 1.5pt solid #000;
        }

        .pdf-header-line {
            margin-bottom: 2pt;
            font-size: 11pt;
            line-height: 1.4;
        }

        .pdf-header-label {
            font-weight: bold;
            display: inline-block;
            width: 85pt;
            margin-right: 0;
        }

        .pdf-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 6pt;
            letter-spacing: 0.5pt;
        }

        .pdf-subtitle {
            text-align: center;
            font-size: 11pt;
            font-weight: 600;
            color: #333;
            margin-bottom: 6pt;
        }

        /* ===== TABLE (NO SN COLUMN) ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4pt;
            margin-bottom: 8pt;
            font-size: 11pt;
        }

        thead {
            display: table-header-group;
            background-color: #2c3e50;
            color: #fff;
        }

        thead th {
            background-color: #2c3e50;
            color: #fff;
            border: 1pt solid #000;
            padding: 4pt 3pt;
            text-align: center;
            vertical-align: middle;
            font-weight: 600;
            font-size: 10pt;
            height: 18pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            page-break-inside: avoid;
        }

        tbody {
            page-break-inside: auto;
        }

        tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        tbody td {
            border: 1pt solid #999;
            padding: 3pt 2pt;
            text-align: center;
            vertical-align: middle;
            font-size: 11pt;
            height: 15pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
            page-break-inside: avoid;
        }

        /* Alternating row colors */
        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        /* ===== COLUMN WIDTHS (NO SN) =====
         * 
         * Usable width: 158mm (A4 - margins - padding)
         * 
         * Distribution:
         * - INDEX: 50mm (31.6%)
         * - SEX: 18mm (11.4%)
         * - COMB: 18mm (11.4%)
         * - PAPERS: 72mm (45.6%)  [split equally among papers]
         */

        .col-index {
            width: 50mm;
            text-align: center;
            font-weight: 500;
        }

        .col-sex {
            width: 18mm;
            text-align: center;
        }

        .col-comb {
            width: 18mm;
            text-align: center;
        }

        .col-paper {
            width: auto;
            text-align: center;
            font-weight: 500;
        }

        /* ===== PAGE NUMBER ===== */
        .page-number {
            text-align: right;
            font-size: 9pt;
            color: #aaa;
            margin-bottom: 3pt;
        }

        /* ===== FOOTER ===== */
        .pdf-footer {
            font-size: 9pt;
            color: #666;
            border-top: 1pt solid #ccc;
            padding-top: 3pt;
            margin-top: 6pt;
            display: flex;
            justify-content: space-between;
            align-items: center;
            page-break-inside: avoid;
            page-break-before: avoid;
        }

        .footer-left {
            text-align: left;
            flex: 1;
        }

        .footer-center {
            text-align: center;
            flex: 1;
            font-size: 9pt;
            color: #666;
        }

        .footer-right {
            text-align: right;
            flex: 1;
        }

        /* ===== SIGNATURE SECTION ===== */
        .signature-section {
            margin-top: 4pt;
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .signature-field {
            display: table-cell;
            width: 60mm;
            padding-right: 12mm;
            vertical-align: top;
        }

        .signature-line {
            border-bottom: 1pt solid #000;
            height: 18pt;
            margin-bottom: 2pt;
        }

        .signature-label {
            font-size: 9pt;
            color: #666;
        }

        /* ===== WATERMARK ===== */
        .watermark {
            position: fixed;
            top: 100mm;
            left: 50%;
            transform: translate(-50%, 0) rotate(-45deg);
            font-size: 84pt;
            font-weight: bold;
            color: rgba(200, 200, 200, 0.07);
            white-space: nowrap;
            z-index: -1;
            pointer-events: none;
            letter-spacing: -2pt;
        }

    </style>
</head>
<body>
    {{-- Watermark --}}
    <div class="watermark">IRMS – CONFIDENTIAL</div>

    @php
        $pageNum = 1;
        $rowsPerPage = 26;
        $totalRows = count($candidates);
        $totalPages = ceil($totalRows / $rowsPerPage);
        
        // Calculate paper columns
        $totalPapers = $paperStructure['written_papers'];
        if ($paperStructure['has_practical']) $totalPapers++;
        if ($paperStructure['has_project']) $totalPapers++;
        
        $paperColumnWidth = round(72 / $totalPapers, 1); // mm
    @endphp

    {{-- HEADER SECTION (FIRST PAGE ONLY) --}}
    <div>
        <div class="pdf-title">ACSEE SCORESHEET</div>
        <div class="pdf-subtitle">{{ $subject->code }} – {{ $subject->name }}</div>

        <div class="pdf-header">
            <div class="pdf-header-line">
                <span class="pdf-header-label">School:</span>
                {{ $school->code }} – {{ $school->name }}
            </div>
            <div class="pdf-header-line">
                <span class="pdf-header-label">Year:</span>
                {{ $examYear->year_label }}
            </div>
            <div class="pdf-header-line">
                <span class="pdf-header-label">Region:</span>
                {{ $school->region->name ?? 'N/A' }}
            </div>
            <div class="pdf-header-line">
                <span class="pdf-header-label">District:</span>
                {{ $school->district->name ?? 'N/A' }}
            </div>
            <div class="pdf-header-line">
                <span class="pdf-header-label">Generated:</span>
                {{ $timestamp->format('d M Y H:i') }}
            </div>
        </div>
    </div>

    {{-- SINGLE TABLE SPANNING ALL PAGES --}}
    <table>
        <thead>
            <tr>
                <th class="col-index">INDEX NUMBER</th>
                <th class="col-sex">SEX</th>
                <th class="col-comb">COMB</th>

                {{-- Written Papers --}}
                @for ($i = 1; $i <= $paperStructure['written_papers']; $i++)
                    <th class="col-paper" style="width: {{ $paperColumnWidth }}mm;">P{{ $i }}</th>
                @endfor

                {{-- Practical (if applicable) --}}
                @if ($paperStructure['has_practical'])
                    <th class="col-paper" style="width: {{ $paperColumnWidth }}mm;">PRAC</th>
                @endif

                {{-- Project (if applicable) --}}
                @if ($paperStructure['has_project'])
                    <th class="col-paper" style="width: {{ $paperColumnWidth }}mm;">PROJ</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($candidates as $registration)
                <tr>
                    <td class="col-index">{{ $registration->candidate->candidate_id }}</td>
                    <td class="col-sex">{{ strtoupper($registration->candidate->gender[0] ?? 'U') }}</td>
                    <td class="col-comb">{{ $registration->candidate->combination ?? '—' }}</td>

                    {{-- Empty mark cells for papers --}}
                    @for ($i = 1; $i <= $paperStructure['written_papers']; $i++)
                        <td class="col-paper" style="width: {{ $paperColumnWidth }}mm;"></td>
                    @endfor

                    @if ($paperStructure['has_practical'])
                        <td class="col-paper" style="width: {{ $paperColumnWidth }}mm;"></td>
                    @endif

                    @if ($paperStructure['has_project'])
                        <td class="col-paper" style="width: {{ $paperColumnWidth }}mm;"></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- FOOTER & SIGNATURE (LAST PAGE ONLY) --}}
    <div class="pdf-footer">
        <div class="footer-left">Total: {{ $totalRows }} candidates</div>
        <div class="footer-center">Page <span class="page-num"></span> of {{ $totalPages }}</div>
        <div class="footer-right">IRMS © {{ date('Y') }}</div>
    </div>

    <div class="signature-section">
        <div class="signature-field">
            <div class="signature-line"></div>
            <div class="signature-label">Invigilator Signature</div>
        </div>
        <div class="signature-field">
            <div class="signature-line"></div>
            <div class="signature-label">Date</div>
        </div>
    </div>
</body>
</html>
