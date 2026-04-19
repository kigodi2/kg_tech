<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ACSEE Summary Report - {{ $examYear->year_label }}</title>
    <style>
        @page { size: A4 portrait; margin: 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #000; background: #fff; padding: 6mm; }
        .title { text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 4pt; }
        .subtitle { text-align: center; font-size: 12pt; color: #333; margin-bottom: 12pt; }
        .section-title { font-size: 12pt; font-weight: bold; margin-top: 12pt; margin-bottom: 6pt; border-bottom: 1pt solid #333; padding-bottom: 3pt; }
        .stat-grid { display: table; width: 100%; margin-bottom: 8pt; }
        .stat-item { display: table-cell; width: 25%; text-align: center; padding: 6pt; border: 1pt solid #ddd; }
        .stat-label { font-size: 8pt; color: #666; margin-bottom: 2pt; }
        .stat-value { font-size: 16pt; font-weight: bold; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 6pt; font-size: 9pt; }
        th { background-color: #2c3e50; color: #fff; padding: 4pt; text-align: left; border: 1pt solid #000; font-size: 9pt; }
        td { padding: 3pt 4pt; border: 1pt solid #ccc; font-size: 9pt; }
        tr:nth-child(even) { background-color: #f5f5f5; }
        .footer { margin-top: 16pt; padding-top: 6pt; border-top: 1pt solid #ccc; font-size: 8pt; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="title">ACSEE MARK ENTRY SUMMARY REPORT</div>
    <div class="subtitle">Examination Year: {{ $examYear->year_label }} | Generated: {{ now()->format('d M Y H:i') }}</div>

    <div class="section-title">Completion Overview</div>
    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Total Batches</div>
            <div class="stat-value">{{ $completion['total_batches'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Approved</div>
            <div class="stat-value">{{ $completion['approved_batches'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Completion Rate</div>
            <div class="stat-value">{{ $completion['completion_rate'] }}%</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Data Quality</div>
            <div class="stat-value">{{ $completion['data_quality_rate'] }}%</div>
        </div>
    </div>
    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Schools with Marks</div>
            <div class="stat-value">{{ $completion['schools_with_marks'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Subjects with Marks</div>
            <div class="stat-value">{{ $completion['subjects_with_marks'] }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Total Marks</div>
            <div class="stat-value">{{ number_format($completion['total_marks']) }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Error-Free Marks</div>
            <div class="stat-value">{{ number_format($completion['error_free_marks']) }}</div>
        </div>
    </div>

    <div class="section-title">Status Snapshot</div>
    <div class="stat-grid">
        @foreach ($status_snapshot as $status => $count)
        <div class="stat-item">
            <div class="stat-label">{{ ucfirst($status) }}</div>
            <div class="stat-value">{{ $count }}</div>
        </div>
        @endforeach
    </div>

    <div class="section-title">Schools with Highest Error Rates</div>
    @if(count($worst_schools) > 0)
    <table>
        <thead>
            <tr>
                <th>School Code</th>
                <th>School Name</th>
                <th>Candidates</th>
                <th>Batches</th>
                <th>Total Records</th>
                <th>Errors</th>
                <th>Error Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($worst_schools as $school)
            <tr>
                <td>{{ $school['school_code'] }}</td>
                <td>{{ $school['school_name'] }}</td>
                <td style="text-align: center;">{{ $school['candidate_count'] ?? 0 }}</td>
                <td style="text-align: center;">{{ $school['batch_count'] }}</td>
                <td style="text-align: center;">{{ $school['total_records'] }}</td>
                <td style="text-align: center;">{{ $school['total_errors'] }}</td>
                <td style="text-align: center;">{{ $school['error_rate'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #666; margin-top: 4pt;">No schools with errors found.</p>
    @endif

    <div class="footer">
        IRMS – ACSEE Mark Entry System | Generated {{ now()->format('d M Y H:i:s') }} | © {{ date('Y') }}
    </div>
</body>
</html>
