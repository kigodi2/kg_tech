@extends('layout')

@section('content')
@php
    $metricWidth = '38px';
    $summaryMetricWidth = '52px';
    $gradeSummary = $summary['grade_summary'] ?? [];
    $overall = $summary['overall'] ?? [];
    $gpaInfo = $overall['gpa_info'] ?? null;
    $isSubjectSummary = str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'SUMMARY');
    $barcodePayload = sprintf(
        'PSLE-%s-%s-WEB',
        substr((string) (preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $region->name)) ?: 'REG'), 0, 3),
        now()->format('Ymd-His')
    );
    $barcodePatterns = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
        '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
        '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
    ];
    $barcodeBars = [];
    $barcodeWidth = 0;
    $narrowBar = 2;
    $wideBar = 5;
    foreach (str_split('*' . strtoupper($barcodePayload) . '*') as $char) {
        $pattern = $barcodePatterns[$char] ?? $barcodePatterns['-'];
        foreach (str_split($pattern) as $index => $bar) {
            $lineWidth = $bar === 'w' ? $wideBar : $narrowBar;
            $barcodeBars[] = ['draw' => $index % 2 === 0, 'width' => $lineWidth];
            $barcodeWidth += $lineWidth;
        }
        $barcodeBars[] = ['draw' => false, 'width' => $narrowBar];
        $barcodeWidth += $narrowBar;
    }
@endphp

<div style="background-color: #B0E0E6; min-height: 100vh; padding-top: 1.5rem; padding-bottom: 1.5rem; font-family: 'Maiandra GD', sans-serif; font-weight: 700; white-space: nowrap;">
    <div style="width: min(96vw, 1820px); margin: 0 auto; padding: 0 0.35rem;">
        <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <a href="{{ route('evaluations.psle.regionalwise.region', ['region' => $region->id]) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 1.05rem;">
                ← Back to {{ strtoupper($region->name) }}
            </a>
            <a href="{{ route('evaluations.psle.regionalwise.region.evaluation.export', ['region' => $region->id, 'evaluation' => $evaluationKey, 'format' => 'pdf']) }}"
               style="border: 1px solid #065f46; background: linear-gradient(180deg, #10b981 0%, #059669 55%, #047857 100%); color: #ffffff; padding: 0.34rem 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.42rem; height: 38px; border-radius: 0.75rem; box-shadow: 0 3px 0 #064e3b, 0 10px 20px rgba(4, 120, 87, 0.24), inset 0 1px 0 rgba(255,255,255,0.28);">
                <i class="fas fa-file-pdf" style="color: #ffffff;"></i>
                <span>Export PDF</span>
                <span>&rarr;</span>
            </a>
        </div>

        <div style="background-color: #0b2f5b; padding-top: 1.2rem; padding-bottom: 1.2rem; padding-left: 1.5rem; padding-right: 1.5rem; margin-bottom: 0.2rem; position: relative;">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>

                <div class="flex-1 text-center px-4">
                    <p style="margin: 0; font-size: 1.2rem; font-weight: bold; color: #ffffff; line-height: 1.25;">PRIME MINISTER'S OFFICE</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 1.2rem; font-weight: bold; color: #f5d000; line-height: 1.25;">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 1.15rem; font-weight: bold; color: #ffffff; line-height: 1.25;">ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 1.15rem; font-weight: bold; color: #f5d000; line-height: 1.25;">
                        PSLE REGIONAL SUBJECT PERFORMANCE - {{ $examYearValue }} - {{ strtoupper($region->name) }}
                    </p>
                </div>

                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
            </div>
            
            <div style="position: absolute; bottom: 8px; left: 1.5rem; color: #f5d000; font-size: 0.85rem; font-weight: bold; display: flex; align-items: center; gap: 4px;">
                <span>{{ data_get($gradeSummary, 'T.REGIST', 0) }} candidates. 🔥</span>
            </div>
        </div>

        <div style="padding: 0 0.35rem; margin-bottom: 0.35rem;">
            <div style="height: 4px; width: 100%; background: #f5d000;"></div>
        </div>

        <div style="background-color: #B0E0E6; padding: 0.35rem 0.35rem 0 0.35rem;">
            <div style="width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <table style="width: max-content; min-width: 100%; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background-color: #003366;">
                            <th colspan="11" style="border: 1px solid #999; padding: 0.18rem 0.25rem; text-align: left; color: #FFFFFF; font-size: 0.92rem;">EXAMINATION CENTRE GRADE PERFORMANCE</th>
                        </tr>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem;">SEX</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem;">REGIST</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem;">SAT</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">A</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">B</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">C</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">D</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">E</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem;">INC</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem;">ABS</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem;">CLEAN</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                        @foreach(['F', 'M', 'T'] as $sex)
                            <tr>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $sex }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.REGIST', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.SAT', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.A', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.B', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.C', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.D', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.E', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.INC', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.ABS', 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ data_get($gradeSummary, $sex.'.CLEAN', 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background-color: #B0E0E6; padding: 0.35rem 0.35rem 0 0.35rem;">
            <div style="width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <table style="width: max-content; min-width: 100%; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background-color: #003366;">
                            <th colspan="2" style="border: 1px solid #999; padding: 0.18rem 0.25rem; text-align: left; color: #FFFFFF; font-size: 0.92rem;">EXAMINATION CENTRE OVERALL PERFORMANCE</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                        <tr><td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">EXAMINATION CENTRE REGION</td><td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">{{ $overall['region'] ?? '-' }}</td></tr>
                        <tr><td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">TOTAL PASSED CANDIDATES (A - C)</td><td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">{{ $overall['passed'] ?? 0 }}</td></tr>
                        <tr><td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">EXAMINATION CENTRE GPA</td><td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">{{ !empty($overall['gpa']) ? number_format((float) $overall['gpa'], 4) : '-' }}</td></tr>
                        <tr>
                            <td style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.82rem;">GPA COMPETENCE</td>
                            <td style="border: 1px solid #999; padding: 0.14rem 0.18rem; background: {{ $gpaInfo['color'] ?? '#fffde7' }}; color: #000080; font-size: 0.82rem;">
                                @if($gpaInfo)
                                    Grade {{ $gpaInfo['grade'] ?? '-' }} ({{ $gpaInfo['competence'] ?? '-' }})
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background-color: #B0E0E6; padding: 0.35rem;">
            <div style="width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <table style="width: max-content; min-width: 100%; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background-color: #003366;">
                            <th colspan="15" style="border: 1px solid #999; padding: 0.18rem 0.25rem; text-align: left; color: #FFFFFF; font-size: 0.92rem;">{{ $isSubjectSummary ? 'EXAMINATION CENTRE SUBJECTS SUMMARY' : 'EXAMINATION CENTRE SUBJECTS PERFORMANCE' }}</th>
                        </tr>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem;">CODE</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: left; font-size: 0.84rem;">SUBJECT NAME</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem;">REGIST</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem;">SAT</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem;">ABS</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">A</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">B</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">C</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem;">A - C</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">D</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem;">A - D</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; font-size: 0.84rem; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">E</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }};">AVG</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }};">GRD</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: left; font-size: 0.84rem;">COMPETENCY LEVEL</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                        @forelse($rows as $row)
                            <tr>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $row['code'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; font-size: 0.82rem; line-height: 1.05;">{{ $row['name'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $row['registered'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $row['sat'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $row['abs'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ $row['grade_a'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ $row['grade_b'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ $row['grade_c'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $row['a_to_c'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ $row['grade_d'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem;">{{ $row['a_to_d'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem;">{{ $row['grade_e'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem;">{{ number_format((float) $row['avg_marks'], 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem;">{{ $row['grade'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; background: {{ data_get($row, 'competence.color', '#fffde7') }}; color: #000080; font-size: 0.82rem; line-height: 1.05;">{{ data_get($row, 'competence.label', '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" style="border: 1px solid #999; padding: 1rem; text-align: center;">No subjectwise PSLE data is available for this region.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.15rem; padding-top: 0.75rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="{{ $barcodeWidth }}" height="34" viewBox="0 0 {{ $barcodeWidth }} 34" role="img" aria-label="Evaluation barcode">
                    @php($barX = 0)
                    @foreach($barcodeBars as $bar)
                        @if($bar['draw'])
                            <rect x="{{ $barX }}" y="0" width="{{ $bar['width'] }}" height="26" fill="#0f172a"></rect>
                        @endif
                        @php($barX += $bar['width'])
                    @endforeach
                </svg>
                <div style="font-size: 0.78rem; color: #0f172a; letter-spacing: 0.04em;">{{ $barcodePayload }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
