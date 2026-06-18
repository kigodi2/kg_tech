@extends('layout')

@section('content')
@php
    $metricWidth = '38px';
    $summaryMetricWidth = '52px';
    $candidateCount = $rows->count();
    $femaleCount = $rows->filter(fn ($row) => strtoupper((string) ($row['gender'] ?? '')) === 'F')->count();
    $maleCount = $rows->filter(fn ($row) => strtoupper((string) ($row['gender'] ?? '')) === 'M')->count();
    $averageTotal = $rows->pluck('total_marks')->filter(fn ($value) => !is_null($value))->avg();
    $averageGpa = $rows->pluck('gpa')->filter(fn ($value) => !is_null($value))->avg();
    $bestRow = $rows->sortBy('position')->first();
    $leastRow = $rows->sortByDesc('position')->first();
    $totalAverageBadge = function ($average) {
        if (is_null($average)) {
            return null;
        }

        $grade = match (true) {
            (float) $average >= 246 => 'A',
            (float) $average >= 186 => 'B',
            (float) $average >= 126 => 'C',
            (float) $average >= 66 => 'D',
            default => 'E',
        };

        $competence = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Satisfactory',
            'E' => 'Unsatisfactory',
        ][$grade] ?? 'Unsatisfactory';

        return [
            'grade' => $grade,
            'competence' => $competence,
            'color' => match ($grade) {
                'A' => '#00A82A',
                'B' => '#1FEE0B',
                'C' => '#DEF043',
                'D' => '#FF772F',
                default => '#FF272F',
            },
        ];
    };
    $gpaAverageBadge = function ($averageGpa) {
        if (is_null($averageGpa)) {
            return null;
        }

        $grade = match (true) {
            (float) $averageGpa <= 1.5 => 'A',
            (float) $averageGpa <= 2.5 => 'B',
            (float) $averageGpa <= 3.5 => 'C',
            (float) $averageGpa <= 4.5 => 'D',
            default => 'E',
        };

        $competence = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Satisfactory',
            'E' => 'Unsatisfactory',
        ][$grade] ?? 'Unsatisfactory';

        return [
            'grade' => $grade,
            'competence' => $competence,
            'color' => match ($grade) {
                'A' => '#00A82A',
                'B' => '#1FEE0B',
                'C' => '#DEF043',
                'D' => '#FF772F',
                default => '#FF272F',
            },
        ];
    };
    $averageTotalBadge = $totalAverageBadge($averageTotal);
    $averageGpaBadge = $gpaAverageBadge($averageGpa);
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
    <div style="width: min(90vw, 1600px); margin: 0 auto; padding: 0 0.25rem;">
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
                        PSLE REGIONAL STUDENT RANKING - {{ $examYearValue }} - {{ strtoupper($region->name) }}
                    </p>
                </div>

                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
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
        </div>

        <div style="padding: 0 0.35rem; margin-bottom: 0.35rem;">
            <div style="height: 4px; width: 100%; background: #f5d000;"></div>
        </div>

        <div style="padding: 0.2rem 0.35rem 0.35rem 0.35rem; color: #000080;">
            <div style="font-size: 1rem; line-height: 1.35; white-space: normal;">
                <p style="margin: 0 0 0.2rem 0;">REGION: {{ strtoupper($region->name) }}</p>
                <p style="margin: 0 0 0.2rem 0;">TOTAL CANDIDATES LISTED: {{ number_format($candidateCount) }} (F: {{ number_format($femaleCount) }}, M: {{ number_format($maleCount) }})</p>
                <p style="margin: 0 0 0.2rem 0;">
                    AVERAGE TOTAL: {{ is_null($averageTotal) ? '-' : number_format((float) $averageTotal, 2) }}
                    @if($averageTotalBadge)
                        <span style="background: {{ $averageTotalBadge['color'] }}; color: #000080; padding: 0 0.25rem;">Grade {{ $averageTotalBadge['grade'] }} ({{ $averageTotalBadge['competence'] }})</span>
                    @endif
                    |
                    AVERAGE GPA: {{ is_null($averageGpa) ? '-' : number_format((float) $averageGpa, 4) }}
                    @if($averageGpaBadge)
                        <span style="background: {{ $averageGpaBadge['color'] }}; color: #000080; padding: 0 0.25rem;">Grade {{ $averageGpaBadge['grade'] }} ({{ $averageGpaBadge['competence'] }})</span>
                    @endif
                </p>
                <p style="margin: 0 0 0.2rem 0;">BEST CANDIDATE: {{ $bestRow['index_number'] ?? '-' }} @if($bestRow) (TOTAL: {{ is_null($bestRow['total_marks'] ?? null) ? '-' : number_format((float) $bestRow['total_marks'], 0) }}, GRD: {{ $bestRow['overall_grade'] ?? '-' }}, POS: {{ $bestRow['position'] ?? '-' }}) @endif</p>
                <p style="margin: 0;">LEAST CANDIDATE: {{ $leastRow['index_number'] ?? '-' }} @if($leastRow) (TOTAL: {{ is_null($leastRow['total_marks'] ?? null) ? '-' : number_format((float) $leastRow['total_marks'], 0) }}, GRD: {{ $leastRow['overall_grade'] ?? '-' }}, POS: {{ $leastRow['position'] ?? '-' }}) @endif</p>
            </div>
        </div>

        <div style="background-color: #B0E0E6; padding: 0.35rem;">
            <div style="width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <table style="width: max-content; min-width: 100%; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: left; font-size: 0.84rem; line-height: 1.05; width: 92px; min-width: 92px; max-width: 92px;">COUNCIL</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; line-height: 1.05;">CNO</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: left; font-size: 0.84rem; line-height: 1.05;">SCHOOL</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; line-height: 1.05; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">SEX</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: left; font-size: 0.84rem; line-height: 1.05;">DETAILED SUBJECTS RESULT</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; line-height: 1.05; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }};">TOTAL</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; line-height: 1.05; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }};">GRD</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; line-height: 1.05; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }};">AGGT</th>
                            <th style="border: 1px solid #999; padding: 0.14rem 0.18rem; text-align: center; font-size: 0.84rem; line-height: 1.05; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }};">POS</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                        @forelse($rows as $row)
                            <tr>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; width: 92px; min-width: 92px; max-width: 92px; font-size: 0.82rem; line-height: 1.05;">{{ $row['council'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; font-size: 0.8rem; line-height: 1;">{{ $row['index_number'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; font-size: 0.82rem; line-height: 1.05;">{{ $row['school'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['gender'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; font-size: 0.82rem; line-height: 1.05;">{{ $row['subject_results_text'] ?: '-' }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ is_null($row['total_marks']) ? '-' : number_format((float) $row['total_marks'], 0) }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['overall_grade'] ?? '-' }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $summaryMetricWidth }}; min-width: {{ $summaryMetricWidth }}; max-width: {{ $summaryMetricWidth }}; font-size: 0.8rem; line-height: 1;">{{ is_null($row['aggt']) ? '-' : $row['aggt'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: center; width: {{ $metricWidth }}; min-width: {{ $metricWidth }}; max-width: {{ $metricWidth }}; font-size: 0.8rem; line-height: 1;">{{ $row['position'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="border: 1px solid #999; padding: 1rem; text-align: center;">No ranked students are available for this PSLE evaluation.</td>
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
