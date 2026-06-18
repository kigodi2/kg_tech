<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CAL - {{ $subject->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 30mm 15mm; /* increased bottom margin for footer */
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11px;
            color: #000;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 5px;
        }
        .header-table td {
            border: none;
            padding: 0;
        }
        .header-text {
            text-align: center;
            color: #08276d;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.4;
        }
        
        .color-bar {
            width: 100%;
            height: 3px;
            margin-top: 5px;
            margin-bottom: 8px;
            display: table;
        }
        .color-bar > div {
            display: table-cell;
        }
        
        .sub-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8px; /* Reduced to prevent text wrapping */
            font-weight: bold;
            color: #08276d;
        }
        .sub-header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
            white-space: nowrap; /* Avoid text wrapping */
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
        }
        .text-center { text-align: center !important; }
        .text-right { text-align: right !important; }
        
        footer {
            position: fixed;
            bottom: -25mm;
            left: 0;
            right: 0;
            height: 15mm;
            padding-top: 5px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 5px; /* Space between text and rule */
        }
        .footer-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
            white-space: nowrap;
        }
        
        .barcode-container {
            margin-top: 30px;
            text-align: center;
            page-break-inside: avoid;
        }
        .barcode-bars {
            display: inline-block;
            white-space: nowrap;
            height: 17px;
            font-size: 0;
            line-height: 0;
            margin-bottom: 2px;
        }
        .barcode-bars span {
            display: inline-block;
            height: 100%;
        }
        .barcode-text {
            font-size: 9px;
            font-weight: bold;
            color: #0b1d3a;
        }

    </style>
</head>
<body>

    @php
        if (!isset($emblemData)) {
            $emblemData = '';
            $paths = [
                base_path('images/emblem.png'),
                public_path('images/emblem.png'),
                base_path('../public_html/images/emblem.png'),
                base_path('../../public_html/images/emblem.png')
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $emblemData = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
                    break;
                }
            }
        }
    @endphp

    <footer>
        <table class="footer-table">
            <tr>
                <td style="width: 40%; text-align: left;">HEADTEACHER: _____________________________</td>
                <td style="width: 30%; text-align: center;">DATE: ___________________</td>
                <td style="width: 30%; text-align: right;">SIGNATURE: ___________________</td>
            </tr>
        </table>
        <div style="border-top: 1px solid #000; padding-top: 5px;"></div>
    </footer>

    <main>
        <table class="header-table">
            <tr>
                <td style="width: 15%; text-align: left; vertical-align: middle;">
                    @if($emblemData)
                        <img src="{{ $emblemData }}" style="width: 55px; height: 55px;">
                    @endif
                </td>
                <td style="width: 70%;" class="header-text">
                    <div>PRIME MINISTER'S OFFICE</div>
                    <div>REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</div>
                    <div>ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)</div>
                    <div>STANDARD SEVEN MOCK EXAMINATION - MAY, 2026</div>
                    <div style="margin-top: 5px;">SUBJECT COLLECTIVE ATTENDANCE LIST (CAL)</div>
                </td>
                <td style="width: 15%; text-align: right; vertical-align: middle;">
                    @if($emblemData)
                        <img src="{{ $emblemData }}" style="width: 55px; height: 55px;">
                    @endif
                </td>
            </tr>
        </table>
        
        <div style="border-top: 1px solid #000; margin-top: 2px;"></div>

        <div class="color-bar">
            <div style="width: 25%; background-color: #00a651;"></div>
            <div style="width: 25%; background-color: #f5d000;"></div>
            <div style="width: 25%; background-color: #000000;"></div>
            <div style="width: 25%; background-color: #0b2f5b;"></div>
        </div>

        <table class="sub-header-table">
            <tr>
                <td style="text-align: left;">REGION: {{ strtoupper($school->region->name ?? 'UNKNOWN') }}</td>
                <td style="text-align: center;">DISTRICT: {{ strtoupper($school->district->name ?? 'UNKNOWN') }}</td>
                <td style="text-align: center;">SCHOOL: {{ strtoupper($school->name) }} ({{ $school->code }})</td>
                <td style="text-align: right;">SUBJECT: {{ strtoupper($subject->name) }}</td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;" class="text-center">CNO</th>
                    <th style="width: 15%;" class="text-center">PReM</th>
                    <th style="width: 35%;">CANDIDATE NAME</th>
                    <th style="width: 5%;" class="text-center">SEX</th>
                    <th style="width: 10%;" class="text-center">MARKS</th>
                    <th style="width: 20%;" class="text-center">SIGNATURE</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates as $candidate)
                    <tr>
                        <td class="text-center">{{ $candidate->candidate_id }}</td>
                        <td class="text-center">{{ $candidate->prem_no ?? '---' }}</td>
                        <td>{{ strtoupper($candidate->full_name) }}</td>
                        <td class="text-center">{{ $candidate->gender }}</td>
                        <td class="text-center"></td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="font-style: italic; padding: 15px;">No candidates registered.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(isset($barcodeBars) && isset($barcodePayload))
        <div class="barcode-container">
            <div class="barcode-bars">
                @foreach($barcodeBars as $bar)
                    <span style="width: {{ $bar['width'] }}px; background-color: {{ $bar['color'] }};"></span>
                @endforeach
            </div>
            <div class="barcode-text">{{ $barcodePayload }}</div>
        </div>
        @endif

    </main>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->get_font("Times New Roman", "bold");
            $left_text = "STANDARD SEVEN MOCK, TASIDO 2026 - CAL";
            
            // Margins are 15mm left/right, which is ~42.5 points
            $margin_x = 42.5; 
            
            // Footer border-top is located at approx height - 42.5 points
            // However, our footer is slightly larger now. 
            $y = $pdf->get_height() - 33; 
            
            $right_width = $fontMetrics->get_text_width("Page 0 of 0", $font, $size);
            $right_x = $pdf->get_width() - $right_width - $margin_x; 
            
            $pdf->page_text($margin_x, $y, $left_text, $font, $size, array(0,0,0));
            $pdf->page_text($right_x, $y, $text, $font, $size, array(0,0,0));
        }
    </script>

</body>
</html>
