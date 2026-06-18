<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DAO Schools Report</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 25mm 15mm;
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
        
        .sub-header {
            color: #08276d;
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 12px;
            line-height: 1.4;
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
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
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

        footer {
            position: fixed;
            bottom: -20mm;
            left: 0;
            right: 0;
            height: 10mm;
            border-top: 1px solid #000;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .footer-table td {
            border: none;
            padding: 0;
            font-size: 8px; /* small font */
            font-weight: bold;
        }
        .page-number:after {
            content: "Page " counter(page) " of " counter(pages);
        }

    </style>
</head>
<body>

    @php
        $emblemData = '';
        $paths = [
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
    @endphp

    <footer>
        <!-- Empty footer just to draw the border-top line -->
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
                    <div>STANDARD SEVEN MOCK REGISTRATION - MAY, 2026</div>
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

        <div class="sub-header">
            <div>REGION: {{ strtoupper($district->region->name ?? 'UNKNOWN') }}</div>
            <div>DISTRICT: {{ strtoupper($district->name ?? 'UNKNOWN') }}</div>
            <div>TOTAL SCHOOLS: {{ number_format($schools->count()) }}</div>
        </div>

        @if(!empty($search))
            <div style="margin-bottom: 10px; font-size: 10px;">
                <strong>Search Filter:</strong> {{ $search }}
            </div>
        @endif

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;">CENTRE NO.</th>
                    <th style="width: 45%;">SCHOOL CENTRE NAME</th>
                    <th style="width: 15%;">OWNERSHIP</th>
                    <th style="width: 10%;" class="text-center">CANDIDATES</th>
                    <th style="width: 15%;" class="text-center">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr>
                        <td>{{ $school->code }}</td>
                        <td>{{ $school->name }}</td>
                        <td>{{ $school->ownership ?? 'N/A' }}</td>
                        <td style="text-align: center;">{{ number_format($school->candidates_count) }}</td>
                        <td style="text-align: center;">REGISTERED</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; font-style: italic; padding: 15px;">No schools found for this report.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Barcode Section placed safely at the end of the content -->
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
            $left_text = "STANDARD SEVEN MOCK, TASIDO 2026";
            
            // Margins are 15mm left/right, which is ~42.5 points
            $margin_x = 42.5; 
            
            // Footer border-top is located at approx height - 42.5 points
            // So we place the text slightly below that line
            $y = $pdf->get_height() - 33; 
            
            $right_width = $fontMetrics->get_text_width("Page 0 of 0", $font, $size);
            $right_x = $pdf->get_width() - $right_width - $margin_x; 
            
            $pdf->page_text($margin_x, $y, $left_text, $font, $size, array(0,0,0));
            $pdf->page_text($right_x, $y, $text, $font, $size, array(0,0,0));
        }
    </script>

</body>
</html>
