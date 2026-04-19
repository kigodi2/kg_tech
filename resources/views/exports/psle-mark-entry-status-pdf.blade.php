<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PSLE Mark Entry Status</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #0f172a; }
        h1, h2, p { margin: 0; }
        .title { text-align: center; margin-bottom: 10px; }
        .title h1 { font-size: 16px; }
        .title h2 { font-size: 13px; margin-top: 4px; }
        .summary { margin: 8px 0 10px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th, td { border: 1px solid #666; padding: 4px 5px; }
        th { background: #003366; color: white; text-align: center; }
        td { background: #fffde7; }
        .left { text-align: left; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="title">
        <h1>STANDARD SEVEN ZONAL JOINT MOCK EVALUATION RESULTS - MAY, {{ $examYearValue }}</h1>
        <h2>{{ strtoupper($region->name) }} - {{ strtoupper($evaluationLabel) }}</h2>
    </div>

    <div class="summary">
        <p>REGION: {{ strtoupper($region->name) }}</p>
        <p>TOTAL SCHOOLS: {{ $summary['schools'] ?? number_format($rows->count()) }} | TOTAL REGISTERED CANDIDATES: {{ number_format((int) $rows->sum('registered')) }}</p>
        <p>TOTAL EXPECTED SCRIPTS: {{ $summary['total_scripts'] ?? number_format((int) $rows->sum('total')) }} | TOTAL MARKED SCRIPTS: {{ $summary['marked_scripts'] ?? number_format((int) $rows->sum('marked_scripts')) }} | TOTAL PENDING SCRIPTS: {{ $summary['pending_scripts'] ?? number_format((int) $rows->sum('pending_scripts')) }}</p>
        <p>OVERALL COMPLETION RATE: {{ $summary['completion'] ?? number_format((float) ($rows->sum('total') > 0 ? ($rows->sum('marked_scripts') / $rows->sum('total')) * 100 : 0), 1) }}%</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>S/N</th>
                <th class="left">SCHOOL</th>
                <th>REGIST</th>
                <th>LAST ACTIVITY</th>
                <th>KISW</th>
                <th>ENG</th>
                <th>SST</th>
                <th>MATH</th>
                <th>SCI</th>
                <th>CME</th>
                <th>TOTAL</th>
                <th>MARKED SCRIPTS</th>
                <th>MARKED %</th>
                <th>PENDING SCRIPTS</th>
                <th>PENDING %</th>
                <th>COMPLETION %</th>
                <th class="left">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="left">{{ $row['school'] }}</td>
                    <td class="center">{{ $row['registered'] }}</td>
                    <td class="center">{{ $row['last_activity_date'] ?? '-' }}</td>
                    <td class="center">{{ $row['kisw'] }}</td>
                    <td class="center">{{ $row['eng'] }}</td>
                    <td class="center">{{ $row['sst'] }}</td>
                    <td class="center">{{ $row['math'] }}</td>
                    <td class="center">{{ $row['sci'] }}</td>
                    <td class="center">{{ $row['cme'] }}</td>
                    <td class="center">{{ $row['total'] }}</td>
                    <td class="center">{{ $row['marked_scripts'] }}</td>
                    <td class="center">{{ number_format((float) $row['marked_pct'], 1) }}</td>
                    <td class="center">{{ $row['pending_scripts'] }}</td>
                    <td class="center">{{ number_format((float) $row['pending_pct'], 1) }}</td>
                    <td class="center">{{ number_format((float) $row['completion'], 1) }}</td>
                    <td class="left">{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
