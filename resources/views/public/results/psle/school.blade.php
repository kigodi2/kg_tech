<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standard Seven Mock Results {{ $examYear }} - {{ $school->name }}</title>
    
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root { --irms-font: 'Maiandra GD', "Ubuntu Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        html, body, body * { font-family: var(--irms-font) !important; }
        input, button, select, textarea { font-family: var(--irms-font) !important; }
        * { box-sizing: border-box; }
        
        /* Portal Site Header retro theme styles */
        .site-header { background: #004080; color: #fff; padding: 12px 18px; border-bottom: 4px solid #ffcc00; }
        .site-header-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .site-header-center { text-align: center; flex: 1; }
        .site-title { font-size: 24px; font-weight: 800; letter-spacing: .5px; color: #ffb300; line-height: 1.1; text-shadow: 0 0 6px rgba(255, 80, 0, 0.7), 0 0 12px rgba(255, 140, 0, 0.5); }
        .site-subtitle { font-size: 24px; color: #ffb300; line-height: 1.1; text-shadow: 0 0 6px rgba(255, 80, 0, 0.7), 0 0 12px rgba(255, 140, 0, 0.5); }
        .header-right-text { font-size: 24px; margin-top: 2px; color: #fff; line-height: 1.1; }
        .header-places { font-size: 24px; margin-top: 2px; color: #fff; opacity: .95; line-height: 1.15; }
        .header-logo { max-height: 96px; width: auto; }
        .announcement { margin-top: 8px; font-size: 13px; font-weight: bold; overflow: hidden; position: relative; }
        .announcement-track { display: inline-flex; align-items: center; white-space: nowrap; min-width: max-content; padding-left: 100%; animation: portalTicker 22s linear infinite; }
        .announcement-track:hover { animation-play-state: paused; }
        .announcement-copy { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; padding-right: 56px; }
        .announcement .fire-icon { color: #ff6b00; font-weight: 900; display: inline-block; animation: flameBlink 0.9s ease-in-out infinite; transform-origin: center bottom; }
        .announcement .fire-text { color: #ffb300; font-weight: 800; text-shadow: 0 0 6px rgba(255, 102, 0, 0.6); }

        @keyframes portalTicker {
          from { transform: translateX(0); }
          to { transform: translateX(-100%); }
        }
        @keyframes flameBlink {
          0%, 100% { opacity: 1; transform: scale(1) rotate(-2deg); text-shadow: 0 0 4px rgba(255, 90, 0, 0.55); }
          50% { opacity: 0.7; transform: scale(1.15) rotate(2deg); text-shadow: 0 0 10px rgba(255, 160, 0, 0.95); }
        }

        .pdf-tooltip-container:hover .pdf-tooltip-text {
            visibility: visible !important;
            opacity: 1 !important;
        }

        @media (max-width: 768px) {
            .header-logo { display: block; max-height: 58px; }
            .site-title { font-size: 14px; }
            .site-subtitle { font-size: 14px; }
            .header-right-text { font-size: 14px; }
            .header-places { font-size: 14px; }
        }

        @media print {
            .site-header, .announcement, button, .back-link-container, .print-btn-container {
                display: none !important;
            }
            body {
                background-color: #fff !important;
            }
        }
    </style>
</head>
<body style="background-color: #B0E0E6; margin: 0; padding: 0;">

    <!-- Site Header Banner matching Portal Index -->
    <header class="site-header">
        <div class="site-header-top">
            <img src="{{ asset('images/emblem.png') }}" class="header-logo" alt="Left Logo">

            <div class="site-header-center">
                <div class="header-right-text">PRIME MINISTER'S OFFICE</div>
                <div class="site-subtitle">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</div>
                <div class="header-places">ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA</div>
                <div class="site-title">STANDARD SEVEN MOCK RESULTS - {{ $examYear }} - {{ strtoupper($school->name) }}</div>
                <div class="header-right-text"><div></div></div>
            </div>

            <img src="{{ asset('images/emblem.png') }}" class="header-logo" alt="Right Logo">
        </div>

        <div class="announcement">
            <div class="announcement-track">
                <span class="announcement-copy">
                    <span class="fire-icon">&#128293;</span>
                    <span class="fire-text">Results have been officially published.</span>
                    <span class="fire-icon">&#128293;</span>
                </span>
            </div>
        </div>
    </header>

    @php
        $subjectDisplayNames = [
            'KISWAHILI' => 'KISWAHILI',
            'MAARIFA' => 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
            'HISABATI' => 'MATHEMATICS',
            'SCIENCE' => 'SCIENCE AND TECHNOLOGY',
            'URAIA' => 'CIVIC AND MORAL EDUCATION',
            'ENGLISH' => 'ENGLISH LANGUAGE',
        ];
        $metricColumnWidth = '56px';
        $subjectNameColumnWidth = '320px';
        $avgColumnWidth = '66px';
        $competenceColumnWidth = '170px';
        $candidateNoColumnWidth = '165px';
        $premNoColumnWidth = '149px';
        $sexColumnWidth = '44px';
        $candidateMetricColumnWidth = '58px';
        $centreGradeColumnWidth = '90px';
    @endphp

    <div style="background-color: #B0E0E6; min-height: calc(100vh - 150px); padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'Maiandra GD', sans-serif; font-weight: 700; white-space: nowrap;">
        <div class="container mx-auto px-4">

            <div style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;" class="back-link-container">
                <a href="{{ route('public.results.psle.schools', ['examYear' => $examYear, 'region' => $region->id, 'district' => $district->id]) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 1.05rem;">
                    ← Back to Schools
                </a>
                
                @if($resultsAvailable)
                    @if(auth()->check() && (auth()->user()->is_admin || (auth()->user()->role ?? '') === 'admin'))
                        <a href="{{ route('results.psle.reports.school-export', ['school' => $school->id, 'exam_year_id' => \App\Models\ExamYear::where('year_label', $examYear)->value('id'), 'mode' => 'draft']) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 8px; background-color: #003366; color: #FFFFFF; padding: 6px 14px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 0.9rem; box-shadow: 0 2px 5px rgba(0,0,0,0.15); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#002244'" onmouseout="this.style.backgroundColor='#003366'">
                            <i class="fa-solid fa-file-pdf"></i> Download Official School PDF
                        </a>
                    @else
                        <div style="position: relative; display: inline-block;" class="pdf-tooltip-container">
                            <span style="display: inline-flex; align-items: center; gap: 8px; background-color: rgba(0,51,102,0.4); color: rgba(255,255,255,0.85); padding: 6px 14px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; cursor: help;">
                                <i class="fa-solid fa-lock"></i> PDF Statement Sheet
                            </span>
                            <div class="pdf-tooltip-text" style="visibility: hidden; width: 260px; background-color: #333; color: #fff; text-align: center; border-radius: 6px; padding: 8px 12px; position: absolute; z-index: 100; right: 0; top: 125%; opacity: 0; transition: opacity 0.3s; font-size: 0.75rem; font-family: sans-serif; font-weight: normal; line-height: 1.4; box-shadow: 0 4px 10px rgba(0,0,0,0.25); white-space: normal;">
                                Official Statement Sheets are compiled in the **Admin Results Portal**. Please log in as an administrator to download the print-ready PDF.
                            </div>
                        </div>
                    @endif
                @endif
            </div>

            @if(!$resultsAvailable)
                <div style="background-color: #B0E0E6; padding: 1rem;">
                    <div style="background-color: LIGHTYELLOW; border: 1px solid #999; padding: 1rem; color: #000080; line-height: 1.8;">
                        <div style="font-weight: bold; font-size: 1rem;">RESULTS NOT AVAILABLE YET</div>
                        <div>{{ strtoupper($school->code) }} - {{ strtoupper($school->name) }}</div>
                        <div>No stored Standard Seven Mock {{ $examYear }} result rows were found for this school yet.</div>
                        <div>The school is listed because it exists in the synced schools directory.</div>
                    </div>
                </div>
            @else
                <div style="background-color: #B0E0E6; padding: 0.35rem 1rem 0.5rem 1rem; margin-bottom: 0;">
                    <div style="color: #000080; font-size: 1rem; line-height: 1.8;">
                        <div><strong>EXAMINATION CENTRE:</strong> {{ strtoupper($school->name) }} - {{ strtoupper($school->code) }}</div>
                        <div>
                            CANDIDATES SAT : {{ $candidateCount }} OUT OF {{ $registeredCandidateCount }} REGISTERED CANDIDATES
                            (F: {{ $satGirlsCount }}/{{ $registeredGirlsCount }}, M: {{ $satBoysCount }}/{{ $registeredBoysCount }})
                        </div>
                        <div>
                            SCHOOL AVERAGE : {{ abs($schoolAverage - round($schoolAverage)) < 0.00005 ? number_format($schoolAverage, 0) : number_format($schoolAverage, 4) }}
                            <span style="background-color: {{ $schoolAverageMeta['color'] }}; color: #000080; font-weight: bold; padding: 0 0.2rem;">{{ $schoolAverageMeta['label'] }}</span>
                        </div>
                        <div>PASS RATE (A-C): {{ number_format($passRateAC, 2) }}% | PASS RATE (A-D): {{ number_format($passRateAD, 2) }}%</div>
                        @if($districtPosition && $districtSchoolsWithResults > 0)
                            <div>DISTRICT POSITION: {{ $districtPosition }} OUT OF {{ $districtSchoolsWithResults }} SCHOOLS WITH RESULTS</div>
                        @endif
                        @if($regionalPosition && $regionalSchoolsWithResults > 0)
                            <div>REGIONAL POSITION: {{ $regionalPosition }} OUT OF {{ $regionalSchoolsWithResults }} SCHOOLS WITH RESULTS</div>
                        @endif
                        @if($topCandidate)
                            <div>TOP CANDIDATE: {{ strtoupper($topCandidate['candidate_id']) }} (TOTAL: {{ number_format($topCandidate['total_score'], 0) }}, GRD: {{ strtoupper($topCandidate['average_grade']) }})</div>
                        @endif
                    </div>
                </div>

                <div style="background-color: #B0E0E6; padding: 0.35rem 1rem; margin-bottom: 0;">
                    <table class="w-full" style="table-layout: fixed; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                        <thead>
                            <tr style="background-color: #003366;">
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="12">EXAMINATION CENTRE GRADE PERFORMANCE</th>
                            </tr>
                            <tr style="background-color: #003366;">
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">SEX</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">REGIST</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">SAT</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">WITHHELD</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">CLEAN</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">A</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">B</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">C</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">D</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">E</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">INC</th>
                                <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF;">ABS</th>
                            </tr>
                        </thead>
                        <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                            <tr style="background-color: LIGHTYELLOW;">
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">F</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $registeredGirlsCount }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $satGirlsCount }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">0</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ max($satGirlsCount - ($sexSummary['F']['INC'] ?? 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['A'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['B'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['C'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['D'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['E'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['INC'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['F']['ABS'] }}</td>
                            </tr>
                            <tr style="background-color: LIGHTYELLOW;">
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">M</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $registeredBoysCount }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $satBoysCount }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">0</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ max($satBoysCount - ($sexSummary['M']['INC'] ?? 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['A'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['B'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['C'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['D'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['E'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['INC'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center;">{{ $sexSummary['M']['ABS'] }}</td>
                            </tr>
                            <tr style="background-color: LIGHTYELLOW;">
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">T</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $registeredCandidateCount }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $candidateCount }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">0</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ max($candidateCount - ($totals['INC'] ?? 0), 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['A'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['B'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['C'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['D'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['E'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['INC'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.25rem; text-align: center; font-weight: bold;">{{ $totals['ABS'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div style="background-color: #B0E0E6; padding: 0.35rem 1rem; margin-bottom: 0;">
                    <div class="overflow-x-auto">
                        <table class="w-full" style="background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                            <thead>
                                <tr style="background-color: #003366;">
                                    <th style="border: 1px solid #999; padding: 0.25rem 0.4rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF; width: {{ $candidateNoColumnWidth }}; min-width: {{ $candidateNoColumnWidth }}; max-width: {{ $candidateNoColumnWidth }};">CAND. NO</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem 0.4rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF; width: {{ $premNoColumnWidth }}; min-width: {{ $premNoColumnWidth }}; max-width: {{ $premNoColumnWidth }};">PREM NO</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: {{ $sexColumnWidth }}; min-width: {{ $sexColumnWidth }}; max-width: {{ $sexColumnWidth }};">SEX</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;">DETAILED SUBJECTS RESULT</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }};">TOTAL</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }};">GRD</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }};">AGGT</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: {{ $avgColumnWidth }}; min-width: {{ $avgColumnWidth }}; max-width: {{ $avgColumnWidth }};">GPA</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: center; color: #FFFFFF; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }};">POS</th>
                                </tr>
                            </thead>
                            <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                                @foreach($candidates as $candidate)
                                    <tr>
                                        @php
                                            $subjectLine = collect($candidate['subject_rows'] ?? [])
                                                ->map(function (array $subject) {
                                                    $score = number_format((float) ($subject['score_50'] ?? 0), 0);
                                                    $label = strtoupper((string) ($subject['subject'] ?? ''));
                                                    $label = match ($label) {
                                                        'MAARIFA' => 'SOCIAL',
                                                        'HISABATI' => 'MATHEMATICS',
                                                        'URAIA' => 'CIVIC',
                                                        default => $label,
                                                    };
                                                    return $label . " - {$score} '{$subject['grade']}'";
                                                })
                                                ->implode(', ');
                                        @endphp
                                        <td style="border: 1px solid #999; padding: 0.25rem 0.4rem; width: {{ $candidateNoColumnWidth }}; min-width: {{ $candidateNoColumnWidth }}; max-width: {{ $candidateNoColumnWidth }}; text-align: left; font-weight: bold;">{{ $candidate['candidate_id'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem 0.4rem; width: {{ $premNoColumnWidth }}; min-width: {{ $premNoColumnWidth }}; max-width: {{ $premNoColumnWidth }}; text-align: left; font-weight: bold;">{{ $candidate['prem_no'] ?: '-' }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $sexColumnWidth }}; min-width: {{ $sexColumnWidth }}; max-width: {{ $sexColumnWidth }}; text-align: center; font-weight: bold;">{{ $candidate['gender'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem;">{{ $subjectLine }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }}; text-align: center; font-weight: bold;">{{ number_format($candidate['total_score'], 0) }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }}; text-align: center; font-weight: bold;">{{ strtoupper($candidate['average_grade']) }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }}; text-align: center; font-weight: bold;">{{ $candidate['aggregate_points'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $avgColumnWidth }}; min-width: {{ $avgColumnWidth }}; max-width: {{ $avgColumnWidth }}; text-align: center; font-weight: bold;">{{ number_format($candidate['gpa'], 4) }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $candidateMetricColumnWidth }}; min-width: {{ $candidateMetricColumnWidth }}; max-width: {{ $candidateMetricColumnWidth }}; text-align: center; font-weight: bold;">{{ $candidate['position'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($candidates->lastPage() > 1)
                        <div style="margin: 20px 0; display: flex; justify-content: center; align-items: center; gap: 10px; font-family: 'Maiandra GD', sans-serif;">
                            <div style="font-size: 13px; color: #003366; margin-right: 15px;">
                                Page <strong>{{ $candidates->currentPage() }}</strong> of <strong>{{ $candidates->lastPage() }}</strong>
                            </div>
                            
                            <div style="display: flex; gap: 5px;">
                                @if(!$candidates->onFirstPage())
                                    <a href="{{ $candidates->previousPageUrl() }}" style="padding: 5px 10px; border: 1px solid #003366; background: LIGHTYELLOW; color: #003366; text-decoration: none; border-radius: 3px; font-size: 12px; font-weight: bold;">« Previous</a>
                                @endif

                                @php
                                    $start = max(1, $candidates->currentPage() - 2);
                                    $end = min($candidates->lastPage(), $candidates->currentPage() + 2);
                                @endphp

                                @for($i = $start; $i <= $end; $i++)
                                    <a href="{{ $candidates->url($i) }}" style="padding: 5px 10px; border: 1px solid #003366; background: {{ $i == $candidates->currentPage() ? '#003366' : 'LIGHTYELLOW' }}; color: {{ $i == $candidates->currentPage() ? '#fff' : '#003366' }}; text-decoration: none; border-radius: 3px; font-size: 12px; font-weight: bold;">{{ $i }}</a>
                                @endfor

                                @if($candidates->hasMorePages())
                                    <a href="{{ $candidates->nextPageUrl() }}" style="padding: 5px 10px; border: 1px solid #003366; background: LIGHTYELLOW; color: #003366; text-decoration: none; border-radius: 3px; font-size: 12px; font-weight: bold;">Next »</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div style="background-color: #B0E0E6; padding: 0.35rem 1rem; white-space: normal;">
                    <div class="overflow-x-auto">
                        <table class="w-full" style="background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                            <thead>
                                <tr style="background-color: #003366;">
                                    <th style="border: 1px solid #999; padding: 0.25rem; font-size: 0.90rem; font-weight: bold; text-align: left; color: #FFFFFF;" colspan="15">EXAMINATION CENTRE SUBJECTS PERFORMANCE</th>
                                </tr>
                                <tr style="background-color: #003366;">
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">CODE</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $subjectNameColumnWidth }}; min-width: {{ $subjectNameColumnWidth }}; max-width: {{ $subjectNameColumnWidth }}; text-align: left; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">SUBJECT NAME</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">REGIST</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">SAT</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">ABS</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">A</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">B</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">C</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">A - C</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">D</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">A - D</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">E</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">AVG</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; font-weight: bold; white-space: nowrap;">GRD</th>
                                    <th style="border: 1px solid #999; padding: 0.25rem; width: {{ $competenceColumnWidth }}; min-width: {{ $competenceColumnWidth }}; max-width: {{ $competenceColumnWidth }}; text-align: left; color: #FFFFFF; white-space: nowrap; font-size: 0.90rem; font-weight: bold;">COMPETENCE LEVEL</th>
                                </tr>
                            </thead>
                            <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                                @foreach($subjectSummary as $index => $subject)
                                    <tr>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ preg_replace('/^PSLE-/', '', (string) ($subject['code'] ?: ($index + 1))) }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $subjectNameColumnWidth }}; min-width: {{ $subjectNameColumnWidth }}; max-width: {{ $subjectNameColumnWidth }}; white-space: nowrap;">{{ $subjectDisplayNames[strtoupper($subject['subject'])] ?? strtoupper($subject['subject']) }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['registered'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['sat'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['abs'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['A'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['B'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['C'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['passed'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['D'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['a_to_d'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ $subject['E'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; white-space: nowrap;">{{ number_format($subject['average_score'], 0) }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $metricColumnWidth }}; min-width: {{ $metricColumnWidth }}; max-width: {{ $metricColumnWidth }}; text-align: center; font-weight: bold; white-space: nowrap;">{{ $subject['grade'] }}</td>
                                        <td style="border: 1px solid #999; padding: 0.25rem; width: {{ $competenceColumnWidth }}; min-width: {{ $competenceColumnWidth }}; max-width: {{ $competenceColumnWidth }}; background-color: {{ $subject['competence_meta']['color'] }}; color: #000080; font-weight: bold; white-space: nowrap;">{{ $subject['competence_meta']['label'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Print Button -->
                <div class="print-btn-container" style="margin-top: 1.5rem; text-align: center;">
                    <button onclick="window.print()" style="background-color: #003366; color: white; padding: 0.90rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-print"></i> Print Results
                    </button>
                </div>
            @endif
        </div>
    </div>

    <script>
        // Disable Developer Tools for non-admins
        (function() {
            @if(!(auth()->check() && auth()->user()->isAdmin()))
                document.addEventListener('contextmenu', event => event.preventDefault());
                document.onkeydown = function(e) {
                    if (e.keyCode == 123) return false;
                    if (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0) || e.keyCode == 'C'.charCodeAt(0))) return false;
                    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
                };
            @endif
        })();
    </script>
</body>
</html>
