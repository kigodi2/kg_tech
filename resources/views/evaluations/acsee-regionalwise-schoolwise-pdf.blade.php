<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ACSEE Regional Schoolwise Evaluation</title>
    <style>
        @page { size: A3 landscape; margin: 7mm; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #082b6a; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .header-table td { vertical-align: middle; }
        .logo-cell { width: 70px; text-align: center; }
        .logo { width: 56px; height: auto; }
        .title-wrap { text-align: center; }
        .title-1 { font-size: 12px; font-weight: 700; }
        .title-2 { font-size: 12px; font-weight: 700; color: #b7791f; }
        .title-3 { font-size: 9px; }
        .title-4 { font-size: 12px; font-weight: 700; color: #b7791f; margin-top: 2px; }
        .schoolwise-table { width: 100%; border-collapse: collapse; background: #d7ddd2; table-layout: auto; }
        .schoolwise-table th, .schoolwise-table td {
            border: 1px solid #1f2937;
            padding: 2px;
            font-size: 7px;
            line-height: 1.1;
            text-align: center;
            white-space: nowrap;
        }
        .schoolwise-table thead th { background: #ecec98; color: #062a72; font-weight: 700; }
        .schoolwise-table td.txt-left { text-align: left; }
        .schoolwise-table .council-col,
        .schoolwise-table .school-col,
        .schoolwise-table td.txt-left {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            text-align: left;
        }
        .schoolwise-table .council-col { width: 120px; }
        .schoolwise-table .school-col { width: 260px; }
        .schoolwise-table tfoot td { background: #ecec98; font-weight: 700; color: #062a72; }
        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr, td, th { page-break-inside: avoid; }
        table { page-break-inside: auto; }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/emblem.png') }}" alt="Logo Left" class="logo">
            </td>
            <td>
                <div class="title-wrap">
                    <div class="title-1">PRIME MINISTER'S OFFICE</div>
                    <div class="title-2">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</div>
                    <div class="title-3">TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA</div>
                    <div class="title-4">FORM SIX ZONAL JOINT MOCK EVALUATION RESULTS - FEBRUARY, {{ $examYearValue }} - {{ strtoupper($region->name) }}</div>
                </div>
            </td>
            <td class="logo-cell">
                <img src="{{ public_path('images/emblem.png') }}" alt="Logo Right" class="logo">
            </td>
        </tr>
    </table>

    <table class="schoolwise-table">
        <colgroup>
            <col class="council-col">
            <col class="school-col">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="3" class="council-col">COUNCIL</th>
                <th rowspan="3" class="school-col">SCHOOL</th>
                <th colspan="3">REGISTERED</th>
                <th colspan="4">ABSENT</th>
                <th colspan="4">SAT</th>
                <th colspan="4">INC</th>
                <th colspan="28">DIVISION</th>
                <th rowspan="3">GPA</th>
                <th rowspan="3">POS</th>
            </tr>
            <tr>
                <th rowspan="2">M</th>
                <th rowspan="2">F</th>
                <th rowspan="2">T</th>

                <th rowspan="2">M</th>
                <th rowspan="2">F</th>
                <th rowspan="2">T</th>
                <th rowspan="2">%</th>

                <th rowspan="2">M</th>
                <th rowspan="2">F</th>
                <th rowspan="2">T</th>
                <th rowspan="2">%</th>

                <th rowspan="2">M</th>
                <th rowspan="2">F</th>
                <th rowspan="2">T</th>
                <th rowspan="2">%</th>

                <th colspan="4">I</th>
                <th colspan="4">II</th>
                <th colspan="4">III</th>
                <th colspan="4">I - III</th>
                <th colspan="4">IV</th>
                <th colspan="4">I - IV</th>
                <th colspan="4">0</th>
            </tr>
            <tr>
                <th>M</th><th>F</th><th>T</th><th>%</th>
                <th>M</th><th>F</th><th>T</th><th>%</th>
                <th>M</th><th>F</th><th>T</th><th>%</th>
                <th>M</th><th>F</th><th>T</th><th>%</th>
                <th>M</th><th>F</th><th>T</th><th>%</th>
                <th>M</th><th>F</th><th>T</th><th>%</th>
                <th>M</th><th>F</th><th>T</th><th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="txt-left">{{ $row['council'] }}</td>
                    <td class="txt-left">{{ $row['school'] }}</td>
                    <td>{{ $row['registered']['m'] }}</td><td>{{ $row['registered']['f'] }}</td><td>{{ $row['registered']['t'] }}</td>
                    <td>{{ $row['absent']['m'] }}</td><td>{{ $row['absent']['f'] }}</td><td>{{ $row['absent']['t'] }}</td><td>{{ number_format($row['absent']['pct'], 0) }}</td>
                    <td>{{ $row['sat']['m'] }}</td><td>{{ $row['sat']['f'] }}</td><td>{{ $row['sat']['t'] }}</td><td>{{ number_format($row['sat']['pct'], 0) }}</td>
                    <td>{{ $row['inc']['m'] }}</td><td>{{ $row['inc']['f'] }}</td><td>{{ $row['inc']['t'] }}</td><td>{{ number_format($row['inc']['pct'], 0) }}</td>
                    <td>{{ $row['division']['i']['m'] }}</td><td>{{ $row['division']['i']['f'] }}</td><td>{{ $row['division']['i']['t'] }}</td><td>{{ number_format($row['division']['i']['pct'], 0) }}</td>
                    <td>{{ $row['division']['ii']['m'] }}</td><td>{{ $row['division']['ii']['f'] }}</td><td>{{ $row['division']['ii']['t'] }}</td><td>{{ number_format($row['division']['ii']['pct'], 0) }}</td>
                    <td>{{ $row['division']['iii']['m'] }}</td><td>{{ $row['division']['iii']['f'] }}</td><td>{{ $row['division']['iii']['t'] }}</td><td>{{ number_format($row['division']['iii']['pct'], 0) }}</td>
                    <td>{{ $row['division']['i_iii']['m'] }}</td><td>{{ $row['division']['i_iii']['f'] }}</td><td>{{ $row['division']['i_iii']['t'] }}</td><td>{{ number_format($row['division']['i_iii']['pct'], 0) }}</td>
                    <td>{{ $row['division']['iv']['m'] }}</td><td>{{ $row['division']['iv']['f'] }}</td><td>{{ $row['division']['iv']['t'] }}</td><td>{{ number_format($row['division']['iv']['pct'], 0) }}</td>
                    <td>{{ $row['division']['i_iv']['m'] }}</td><td>{{ $row['division']['i_iv']['f'] }}</td><td>{{ $row['division']['i_iv']['t'] }}</td><td>{{ number_format($row['division']['i_iv']['pct'], 0) }}</td>
                    <td>{{ $row['division']['zero']['m'] }}</td><td>{{ $row['division']['zero']['f'] }}</td><td>{{ $row['division']['zero']['t'] }}</td><td>{{ number_format($row['division']['zero']['pct'], 0) }}</td>
                    <td>{{ is_null($row['gpa']) ? '-' : number_format($row['gpa'], 4) }}</td>
                    <td>{{ $row['pos'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="txt-left">TOTAL</td>
                <td>{{ $total['registered']['m'] }}</td><td>{{ $total['registered']['f'] }}</td><td>{{ $total['registered']['t'] }}</td>
                <td>{{ $total['absent']['m'] }}</td><td>{{ $total['absent']['f'] }}</td><td>{{ $total['absent']['t'] }}</td><td>{{ number_format($total['absent']['pct'], 1) }}</td>
                <td>{{ $total['sat']['m'] }}</td><td>{{ $total['sat']['f'] }}</td><td>{{ $total['sat']['t'] }}</td><td>{{ number_format($total['sat']['pct'], 1) }}</td>
                <td>{{ $total['inc']['m'] }}</td><td>{{ $total['inc']['f'] }}</td><td>{{ $total['inc']['t'] }}</td><td>{{ number_format($total['inc']['pct'], 1) }}</td>
                <td>{{ $total['division']['i']['m'] }}</td><td>{{ $total['division']['i']['f'] }}</td><td>{{ $total['division']['i']['t'] }}</td><td>{{ number_format($total['division']['i']['pct'], 2) }}</td>
                <td>{{ $total['division']['ii']['m'] }}</td><td>{{ $total['division']['ii']['f'] }}</td><td>{{ $total['division']['ii']['t'] }}</td><td>{{ number_format($total['division']['ii']['pct'], 2) }}</td>
                <td>{{ $total['division']['iii']['m'] }}</td><td>{{ $total['division']['iii']['f'] }}</td><td>{{ $total['division']['iii']['t'] }}</td><td>{{ number_format($total['division']['iii']['pct'], 2) }}</td>
                <td>{{ $total['division']['i_iii']['m'] }}</td><td>{{ $total['division']['i_iii']['f'] }}</td><td>{{ $total['division']['i_iii']['t'] }}</td><td>{{ number_format($total['division']['i_iii']['pct'], 2) }}</td>
                <td>{{ $total['division']['iv']['m'] }}</td><td>{{ $total['division']['iv']['f'] }}</td><td>{{ $total['division']['iv']['t'] }}</td><td>{{ number_format($total['division']['iv']['pct'], 2) }}</td>
                <td>{{ $total['division']['i_iv']['m'] }}</td><td>{{ $total['division']['i_iv']['f'] }}</td><td>{{ $total['division']['i_iv']['t'] }}</td><td>{{ number_format($total['division']['i_iv']['pct'], 2) }}</td>
                <td>{{ $total['division']['zero']['m'] }}</td><td>{{ $total['division']['zero']['f'] }}</td><td>{{ $total['division']['zero']['t'] }}</td><td>{{ number_format($total['division']['zero']['pct'], 2) }}</td>
                <td></td><td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
