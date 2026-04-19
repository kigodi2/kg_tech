<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>District-wise School Results - {{ $yearLabel }}</title>
    <style>
        @page {
            size: A3 portrait;
            margin: 10mm;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
            color: #0f172a;
            background: #b0e0e6;
        }

        .page {
            page-break-after: always;
            padding: 8px;
            background: #b0e0e6;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            text-align: center;
            font-weight: 700;
            color: #1e3a8a;
            line-height: 1.25;
            margin-bottom: 8px;
        }

        .header .title {
            font-size: 14px;
        }

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

        .footer {
            margin-top: 6px;
            font-size: 8px;
            color: #334155;
            text-align: right;
        }
    </style>
</head>
<body>
@php
    $districtGroups = $results->groupBy(function ($row) {
        return $row->candidate?->school?->district_id ?? 'unknown';
    });
@endphp

@foreach ($districtGroups as $districtId => $districtRows)
    @php
        $districtName = $districtRows->first()?->candidate?->school?->district?->name ?? 'Unknown District';
        $regionName = $districtRows->first()?->candidate?->school?->region?->name ?? 'Unknown Region';
        $schoolGroups = $districtRows->groupBy(function ($row) {
            return $row->candidate?->school_id ?? 'unknown';
        });
    @endphp

    @foreach ($schoolGroups as $schoolId => $schoolRows)
        @php
            $school = $schoolRows->first()?->candidate?->school;
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
            <div class="header">
                <div class="title">OVERALL RESULTS FOR FORM SIX ZONAL JOINT MOCK EXAMINATION</div>
                <div>Exam Year: {{ $yearLabel }}</div>
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

            <div class="footer">
                District-wise bulk school results export (A3 Portrait)
            </div>
        </div>
    @endforeach
@endforeach
</body>
</html>
