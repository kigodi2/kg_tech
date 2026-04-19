<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @php
        $examYear = $examYear ?? $exam_year ?? null;
        $paperStructure = $paper_structure ?? ['written_papers' => 0, 'has_practical' => false, 'has_project' => false];
        $paperKeys = [];
        for ($i = 1; $i <= (int) ($paperStructure['written_papers'] ?? 0); $i++) { $paperKeys[] = "P{$i}"; }
        if (!empty($paperStructure['has_practical'])) { $paperKeys[] = 'PRAC'; }
        if (!empty($paperStructure['has_project'])) { $paperKeys[] = 'PROJ'; }
    @endphp
    <title>{{ $school->code }} - {{ $subject->code }} - ACSEE {{ optional($examYear)->year_label }} (Draft)</title>
    <style>
        @page { size: A4 portrait; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #000; background: #fff; line-height: 1.3; padding: 4mm; }
        .pdf-title { text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 4pt; }
        .pdf-subtitle { text-align: center; font-size: 11pt; font-weight: 600; color: #333; margin-bottom: 6pt; }
        .pdf-header { margin-bottom: 6mm; padding-bottom: 3mm; border-bottom: 1.5pt solid #000; }
        .pdf-header-line { margin-bottom: 2pt; font-size: 10pt; }
        .pdf-header-label { font-weight: bold; display: inline-block; width: 80pt; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 4pt; font-size: 9pt; }
        thead { display: table-header-group; background-color: #2c3e50; color: #fff; }
        thead th { background-color: #2c3e50; color: #fff; border: 1pt solid #000; padding: 3pt 2pt; text-align: center; font-weight: 600; font-size: 9pt; white-space: nowrap; }
        tbody tr { page-break-inside: avoid; }
        tbody td { border: 1pt solid #999; padding: 2pt 2pt; text-align: center; font-size: 9pt; height: 14pt; }
        tbody tr:nth-child(even) { background-color: #f5f5f5; }
        .col-index { width: 35mm; text-align: center; font-weight: 500; }
        .col-sex { width: 10mm; text-align: center; }
        .col-comb { width: 14mm; text-align: center; }
        .col-paper { text-align: center; font-weight: 500; }
        .col-total { width: 16mm; text-align: center; font-weight: bold; }
        .pdf-footer { font-size: 8pt; color: #666; border-top: 1pt solid #ccc; padding-top: 3pt; margin-top: 4pt; display: flex; justify-content: space-between; }
        .watermark { position: fixed; top: 80mm; left: 50%; transform: translate(-50%, 0) rotate(-45deg); font-size: 60pt; font-weight: bold; color: rgba(200,0,0,0.15); z-index: -1; pointer-events: none; }
        .draft-banner { background-color: #c0392b; color: #fff; text-align: center; padding: 6pt 4pt; font-size: 10pt; font-weight: bold; margin-bottom: 6pt; }
        .draft-footer-banner { background-color: #c0392b; color: #fff; text-align: center; padding: 4pt; font-size: 8pt; font-weight: bold; margin-top: 6pt; }
    </style>
</head>
<body>
    <div class="watermark">DRAFT — NOT APPROVED — FOR VERIFICATION ONLY</div>

    <div class="draft-banner">⚠️ DRAFT PREVIEW — These marks have NOT been approved or locked</div>

    <div class="pdf-title">ACSEE MARKS SCORESHEET</div>
    <div class="pdf-subtitle">{{ $subject->code }} – {{ $subject->name }}</div>

    <div class="pdf-header">
        <div class="pdf-header-line"><span class="pdf-header-label">School:</span> {{ $school->code }} – {{ $school->name }}</div>
        <div class="pdf-header-line"><span class="pdf-header-label">Year:</span> {{ optional($examYear)->year_label }}</div>
        <div class="pdf-header-line"><span class="pdf-header-label">Generated:</span> {{ $timestamp->format('d M Y H:i') }}</div>
        <div class="pdf-header-line"><span class="pdf-header-label">Total:</span> {{ $total_candidates }} candidates</div>
        <div class="pdf-header-line"><span class="pdf-header-label">Status:</span> <strong style="color: #c0392b;">DRAFT / UNVERIFIED</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-index">INDEX NO.</th>
                <th class="col-sex">SEX</th>
                <th class="col-comb">COMB</th>
                @foreach ($paperKeys as $key)
                    <th class="col-paper">{{ $key }}</th>
                @endforeach
                <th class="col-total">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($candidates as $candidate)
                <tr>
                    <td class="col-index">{{ $candidate['index_number'] }}</td>
                    <td class="col-sex">{{ $candidate['gender'] }}</td>
                    <td class="col-comb">{{ $candidate['combination'] }}</td>
                    @foreach ($paperKeys as $key)
                        <td class="col-paper">{{ $candidate['papers'][$key] ?? '—' }}</td>
                    @endforeach
                    <td class="col-total">{{ $candidate['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="draft-footer-banner">This document is for verification purposes only and should not be used as an official scoresheet</div>

    <div class="pdf-footer">
        <span>Total: {{ $total_candidates }} candidates</span>
        <span>Generated: {{ $timestamp->format('d M Y H:i') }}</span>
        <span>IRMS © {{ date('Y') }}</span>
    </div>
</body>
</html>
