<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>School Results - {{ $yearLabel }}</title>
    <style>
        @page {
            size: A3 portrait;
            margin: 8mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background: #b0e0e6;
        }

        .page { background: #b0e0e6; padding: 6px; }
        .header-wrap { margin-bottom: 8px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .logo-cell { width: 10%; text-align: center; color: #1e3a8a; font-weight: 700; font-size: 10px; }
        .center-header { width: 80%; text-align: center; color: #1e3a8a; font-weight: 700; line-height: 1.2; }
        .center-header .h1 { font-size: 14px; }
        .center-header .h2 { font-size: 13px; }
        .center-header .h3 { font-size: 12px; }
        .center-header .h4 { font-size: 12px; margin-top: 4px; }

        .meta {
            background: #fffde7;
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-size: 9px;
        }

        .district-bar {
            background: #1e40af;
            color: #fff;
            font-weight: 700;
            padding: 6px 8px;
            margin-bottom: 4px;
        }

        .school-bar {
            background: #fef08a;
            color: #1e3a8a;
            border: 1px solid #94a3b8;
            font-weight: 700;
            padding: 5px 8px;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            background: #fffde7;
        }

        th, td {
            border: 1px solid #94a3b8;
            padding: 3px 4px;
            vertical-align: top;
        }

        th {
            background: #1e40af;
            color: #fff;
            font-size: 9px;
            text-align: center;
        }

        .left { text-align: left; }
        .center { text-align: center; }
        .right { text-align: right; }
    </style>
</head>
<body>
@php
    $school = $schoolRows->first()?->candidate?->school;
    $districtName = $schoolRows->first()?->candidate?->school?->district?->name ?? 'Unknown District';
    $regionName = $schoolRows->first()?->candidate?->school?->region?->name ?? 'Unknown Region';
    $sortedRows = $schoolRows->sortBy(function ($row) {
        $div = (int) ($row->division ?? 0);
        $divRank = $div === 0 ? 9 : $div;
        $aggt = (float) ($row->grade_points ?? 999);
        $gpa = (float) ($row->gpa ?? 999);
        $name = (string) ($row->candidate?->full_name ?? '');
        return sprintf('%02d-%08.4F-%08.4F-%s', $divRank, $aggt, $gpa, $name);
    })->values();
@endphp

<div class="page">
    <div class="header-wrap">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    COAT<br>OF<br>ARMS
                </td>
                <td class="center-header">
                    <div class="h1">PRIME MINISTER'S OFFICE</div>
                    <div class="h2">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</div>
                    <div class="h3">OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION</div>
                    <div class="h4">EXAM YEAR: {{ $yearLabel }}</div>
                </td>
                <td class="logo-cell">
                    COAT<br>OF<br>ARMS
                </td>
            </tr>
        </table>
    </div>

    <div class="meta">
        <strong>Filters:</strong>
        Region: {{ $selectedRegion?->name ?? 'All' }},
        District: {{ $selectedDistrict?->name ?? 'All' }} |
        <strong>Generated:</strong> {{ $exportedAt->format('d/m/Y H:i') }} |
        <strong>By:</strong> {{ $exportedBy }}
    </div>

    <div class="district-bar">
        DISTRICT: {{ strtoupper($districtName) }} | REGION: {{ strtoupper($regionName) }}
    </div>
    <div class="school-bar">
        SCHOOL: {{ strtoupper($school?->code ?? 'N/A') }} - {{ strtoupper($school?->name ?? 'Unknown School') }}
        | CANDIDATES: {{ $sortedRows->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">CNO</th>
                <th style="width: 4%;">SEX</th>
                <th style="width: 6%;">COMB</th>
                <th style="width: 46%;" class="left">DETAILED SUBJECTS RESULT</th>
                <th style="width: 6%;">TOTAL</th>
                <th style="width: 6%;">AVG</th>
                <th style="width: 5%;">GRD</th>
                <th style="width: 5%;">AGGT</th>
                <th style="width: 4%;">DIV</th>
                <th style="width: 4%;">GPA</th>
                <th style="width: 4%;">POS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sortedRows as $idx => $row)
                @php
                    $details = $row->subjectMarks
                        ->sortBy(fn ($m) => $m->subject?->code)
                        ->map(function ($m) {
                            $code = $m->subject?->code ?? 'SUB';
                            $val = $m->grade ?: ($m->subject_status ?: '-');
                            return $code . '=' . $val;
                        })
                        ->implode(', ');
                    $combinationCode = $row->candidate
                        ? ($row->candidate->getAttributes()['combination'] ?? null)
                        : null;
                @endphp
                <tr>
                    <td class="center">{{ $row->candidate?->candidate_id ?? '—' }}</td>
                    <td class="center">{{ $row->candidate?->gender ?? '—' }}</td>
                    <td class="center">{{ $combinationCode ?: '—' }}</td>
                    <td class="left">{{ $details ?: '—' }}</td>
                    <td class="right">{{ $row->total_marks ?? '—' }}</td>
                    <td class="right">{{ $row->total_percentage ?? '—' }}</td>
                    <td class="center">{{ $row->overall_grade ?? '—' }}</td>
                    <td class="center">{{ $row->grade_points ?? '—' }}</td>
                    <td class="center">{{ $row->division ?? '—' }}</td>
                    <td class="center">{{ $row->gpa ?? '—' }}</td>
                    <td class="center">{{ $idx + 1 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">No candidates found for this school.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</body>
</html>
