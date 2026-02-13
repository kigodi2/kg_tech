<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ACSEE Results - {{ $year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .page-break {
            page-break-after: always;
            margin-bottom: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-size: 11px;
            margin-bottom: 3px;
        }

        .header-meta {
            font-size: 9px;
            color: #666;
        }

        /* School Section */
        .school-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .school-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 10px;
            background-color: #f0f0f0;
            padding: 5px 8px;
            border-left: 3px solid #003d82;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }

        thead {
            background-color: #003d82;
            color: white;
            font-weight: bold;
        }

        th {
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #000;
            font-size: 8px;
        }

        td {
            padding: 5px 4px;
            border: 1px solid #ddd;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f0f0f0;
        }

        /* Column Alignment */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Grade Badge */
        .grade {
            font-weight: bold;
            text-align: center;
            padding: 2px 4px;
            border-radius: 2px;
        }

        .grade-a { background-color: #d4edda; color: #155724; }
        .grade-b { background-color: #d1ecf1; color: #0c5460; }
        .grade-c { background-color: #fff3cd; color: #856404; }
        .grade-d { background-color: #f8d7da; color: #721c24; }
        .grade-e { background-color: #f5c6cb; color: #721c24; }

        /* Division Badge */
        .division {
            font-weight: bold;
            text-align: center;
            padding: 2px 4px;
            border-radius: 2px;
        }

        .div-i { background-color: #d4edda; color: #155724; }
        .div-ii { background-color: #d1ecf1; color: #0c5460; }
        .div-iii { background-color: #fff3cd; color: #856404; }
        .div-iv { background-color: #f8d7da; color: #721c24; }

        /* Index Number */
        .index-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #999;
            font-size: 8px;
            color: #666;
            text-align: center;
        }

        .footer-item {
            display: inline-block;
            margin: 0 10px;
        }

        /* Print Styling */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    @foreach ($grouped as $schoolId => $schoolResults)
        <div class="school-section">
            <!-- Header -->
            <div class="header">
                <div class="header-title">ACSEE RESULTS</div>
                <div class="header-subtitle">Advanced Certificate of Secondary Education</div>
                <div class="header-meta">
                    Year: {{ $year }} | 
                    School: {{ $schoolResults->first()?->candidate?->school?->name ?? 'Unknown' }} | 
                    Total Candidates: {{ count($schoolResults) }}
                </div>
            </div>

            <div class="school-name">
                📍 {{ $schoolResults->first()?->candidate?->school?->name ?? 'Unknown School' }}
            </div>

            <!-- Results Table -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Index #</th>
                        <th style="width: 20%;">Candidate Name</th>
                        <th style="width: 3%; text-align: center;">Sex</th>
                        
                        @php
                            $subjectCount = $schoolResults->first()?->subjectMarks?->count() ?? 0;
                            $subjectWidth = (50 - 3 - 20 - 8) / max(1, $subjectCount);
                        @endphp
                        @foreach ($schoolResults->first()?->subjectMarks ?? [] as $mark)
                            <th style="width: {{ $subjectWidth }}%; text-align: center;">
                                {{ $mark->subject?->code }}
                            </th>
                        @endforeach

                        <th style="width: 6%; text-align: center;">Pts</th>
                        <th style="width: 6%; text-align: center; background-color: #f0f0f0;">Div</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($schoolResults as $result)
                        <tr>
                            <td class="index-number">{{ $result->candidate->candidate_id }}</td>
                            <td>{{ $result->candidate->full_name }}</td>
                            <td class="text-center">
                                {{ $result->candidate->gender === 'M' ? 'M' : 'F' }}
                            </td>

                            @foreach ($result->subjectMarks->sortBy('subject.code') as $mark)
                                <td class="text-center">
                                    <span class="grade grade-{{ strtolower($mark->grade) }}">
                                        {{ $mark->grade ?? '-' }}
                                    </span>
                                </td>
                            @endforeach

                            <td class="text-center">{{ $result->grade_points ?? '-' }}</td>
                            <td class="text-center div-{{ strtolower(str_replace(' ', '-', $result->division)) }}">
                                <strong>{{ $result->division ?? '-' }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-item">📅 Generated: {{ $exportedAt->format('d/m/Y H:i') }}</div>
                <div class="footer-item">👤 By: {{ $exportedBy }}</div>
                <div class="footer-item">📊 Total: {{ count($schoolResults) }} candidate(s)</div>
            </div>
        </div>

        <!-- Page Break Between Schools -->
        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>
</html>
