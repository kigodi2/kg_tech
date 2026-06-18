@extends('layout')

@section('content')
@php
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
    <div style="width: min(98vw, 1760px); margin: 0 auto; padding: 0 0.15rem;">
        <div style="margin-bottom: 0.8rem; display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; min-height: 74px;">
                <a href="{{ route('evaluations.psle.regionalwise.region', ['region' => $region->id]) }}" style="color: #003366; text-decoration: none; font-weight: bold; font-size: 1.05rem;">
                    ← Back to {{ strtoupper($region->name) }}
                </a>
            </div>
            <div style="flex: 1 1 760px; display: flex; justify-content: center; align-items: flex-end;">
                <form method="GET" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: flex-end; justify-content: center; color: #000080;">
                    <div>
                        <label for="activity_date" style="display: block; font-size: 0.9rem; margin-bottom: 0.2rem;">Activity Date</label>
                        <input id="activity_date" name="activity_date" type="date" value="{{ $filters['activity_date'] ?? '' }}" style="border: 1px solid #999; padding: 0.3rem 0.45rem; background: #fffde7; min-width: 150px; height: 38px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label for="date_from" style="display: block; font-size: 0.9rem; margin-bottom: 0.2rem;">Date From</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" style="border: 1px solid #999; padding: 0.3rem 0.45rem; background: #fffde7; min-width: 150px; height: 38px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label for="date_to" style="display: block; font-size: 0.9rem; margin-bottom: 0.2rem;">Date To</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" style="border: 1px solid #999; padding: 0.3rem 0.45rem; background: #fffde7; min-width: 150px; height: 38px; box-sizing: border-box;">
                    </div>
                    <div>
                        <label for="status" style="display: block; font-size: 0.9rem; margin-bottom: 0.2rem;">Status</label>
                        <select id="status" name="status" style="border: 1px solid #999; padding: 0.3rem 0.45rem; background: #fffde7; min-width: 150px; height: 38px; box-sizing: border-box;">
                            <option value="">All Statuses</option>
                            @foreach(['Complete', 'Near Complete', 'In Progress', 'Not Started'] as $statusOption)
                                <option value="{{ $statusOption }}" @selected(($filters['status'] ?? '') === $statusOption)>{{ $statusOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; gap: 0.45rem; align-items: center; padding-bottom: 0;">
                        <button type="submit" style="border: 1px solid #003366; background: #003366; color: #fff; padding: 0.34rem 0.8rem; height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fas fa-filter" aria-hidden="true" style="color: #ffffff;"></i><span>Apply Filter</span></button>
                        <a href="{{ route('evaluations.psle.regionalwise.region.evaluation', ['region' => $region->id, 'evaluation' => 'mark-entry-status-report']) }}" style="border: 1px solid #999; background: #fffde7; color: #000080; padding: 0.34rem 0.8rem; text-decoration: none; height: 38px; display: inline-flex; align-items: center; gap: 0.35rem;"><i class="fas fa-rotate-left" aria-hidden="true" style="color: #000080;"></i><span>Reset</span></a>
                    </div>
                </form>
            </div>
            <div style="width: 180px; display: flex; justify-content: flex-end; align-items: flex-end; gap: 0.45rem; min-height: 74px;">
                <a href="{{ route('evaluations.psle.regionalwise.region.evaluation.export', ['region' => $region->id, 'evaluation' => 'mark-entry-status-report', 'format' => 'xlsx'] + request()->query()) }}" style="border: 1px solid #999; background: #fffde7; color: #000080; padding: 0.34rem 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; height: 38px;">
                    <i class="fas fa-file-excel" aria-hidden="true" style="color: #16a34a;"></i><span>Excel</span>
                </a>
                <a href="{{ route('evaluations.psle.regionalwise.region.evaluation.export', ['region' => $region->id, 'evaluation' => 'mark-entry-status-report', 'format' => 'pdf'] + request()->query()) }}" style="border: 1px solid #999; background: #fffde7; color: #000080; padding: 0.34rem 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem; height: 38px;">
                    <i class="fas fa-file-pdf" aria-hidden="true" style="color: #dc2626;"></i><span>PDF</span>
                </a>
            </div>
        </div>

        <div style="background-color: #B0E0E6; padding-top: 1.35rem; padding-bottom: 0.3rem; padding-left: 0.25rem; padding-right: 0.25rem; margin-bottom: 0.18rem;">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
                <div class="flex-1 text-center px-4">
                    <p class="text-lg font-bold text-blue-900">PRIME MINISTER'S OFFICE</p>
                    <p class="text-lg font-bold text-blue-900">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">STANDARD SEVEN ZONAL JOINT MOCK EVALUATION RESULTS - MAY, {{ $examYearValue }}</p>
                    <p class="text-lg font-bold text-blue-900 mt-1">{{ strtoupper($region->name) }} - {{ strtoupper($evaluationLabel) }}</p>
                </div>
                <div class="flex-shrink-0">
                    <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" class="h-20 w-20 object-contain">
                </div>
            </div>
        </div>
        <div style="padding: 0 0.25rem; margin-bottom: 0.3rem;">
            <div style="height: 4px; width: 100%; background: linear-gradient(to right, #00a651 0%, #00a651 30%, #f5d000 30%, #f5d000 54%, #000000 54%, #000000 70%, #0b2f5b 70%, #0b2f5b 100%);"></div>
        </div>

        @php
            $completeSchools = $rows->where('status', 'Complete')->count();
            $inProgressSchools = $rows->where('status', 'In Progress')->count() + $rows->where('status', 'Near Complete')->count();
            $notStartedSchools = $rows->where('status', 'Not Started')->count();
        @endphp
        <div style="padding: 0.15rem 0.25rem 0.25rem 0.25rem; color: #000080;">
            <div style="font-size: 1rem; line-height: 1.35; white-space: normal;">
                <p style="margin: 0 0 0.2rem 0;">REGION: {{ strtoupper($region->name) }}</p>
                <p style="margin: 0 0 0.2rem 0;">TOTAL SCHOOLS: {{ $summary['schools'] ?? number_format($rows->count()) }} | TOTAL REGISTERED CANDIDATES: {{ number_format((int) $rows->sum('registered')) }}</p>
                <p style="margin: 0 0 0.2rem 0;">TOTAL EXPECTED SCRIPTS: {{ $summary['total_scripts'] ?? number_format((int) $rows->sum('total')) }} | TOTAL MARKED SCRIPTS: {{ $summary['marked_scripts'] ?? number_format((int) $rows->sum('marked_scripts')) }} | TOTAL PENDING SCRIPTS: {{ $summary['pending_scripts'] ?? number_format((int) $rows->sum('pending_scripts')) }}</p>
                <p style="margin: 0 0 0.2rem 0;">OVERALL COMPLETION RATE: {{ $summary['completion'] ?? number_format((float) ($rows->sum('total') > 0 ? ($rows->sum('marked_scripts') / $rows->sum('total')) * 100 : 0), 1) }}%</p>
                <p style="margin: 0;">SCHOOL STATUS SUMMARY: COMPLETE {{ number_format($completeSchools) }} | IN PROGRESS {{ number_format($inProgressSchools) }} | NOT STARTED {{ number_format($notStartedSchools) }}</p>
            </div>
        </div>

        <div style="padding: 0.05rem 0.25rem 0.2rem 0.25rem; color: #000080;">
            <div style="font-size: 0.95rem; line-height: 1.3; white-space: normal;">
                <span style="font-weight: bold;">STATUS LEGEND:</span>
                <span style="background: #1FEE0B; color: #0f172a; padding: 0 0.3rem; margin-left: 0.25rem;">COMPLETE</span>
                <span style="background: #DEF043; color: #0f172a; padding: 0 0.3rem; margin-left: 0.25rem;">NEAR COMPLETE</span>
                <span style="background: #FF772F; color: #0f172a; padding: 0 0.3rem; margin-left: 0.25rem;">IN PROGRESS</span>
                <span style="background: #FF272F; color: #0f172a; padding: 0 0.3rem; margin-left: 0.25rem;">NOT STARTED</span>
            </div>
        </div>

        <div style="background-color: #B0E0E6; padding: 0.25rem;">
            <div style="width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch;">
                <table style="width: max-content; min-width: 100%; background-color: LIGHTYELLOW; border-collapse: collapse; border: 1px solid #999;">
                    <thead>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            <th rowspan="3" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 42px; min-width: 42px; max-width: 42px; text-align: center;">S/N</th>
                            <th rowspan="3" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: left; font-size: 0.82rem; line-height: 1.02; width: 320px; min-width: 320px; max-width: 320px;">SCHOOL</th>
                            <th rowspan="3" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 68px; min-width: 68px; max-width: 68px; text-align: center;">REGIST</th>
                            <th colspan="12" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; text-align: center;">SUBJECTS</th>
                            <th rowspan="3" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 76px; min-width: 76px; max-width: 76px; text-align: center;">TOTAL</th>
                            <th colspan="2" rowspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; text-align: center;">MARKED</th>
                            <th colspan="2" rowspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; text-align: center;">PENDING</th>
                            <th rowspan="3" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 120px; min-width: 120px; max-width: 120px; text-align: center;">COMPLETION %</th>
                            <th rowspan="3" style="border: 1px solid #999; padding: 0.12rem 0.14rem; text-align: left; font-size: 0.82rem; line-height: 1.02; width: 120px; min-width: 120px; max-width: 120px;">STATUS</th>
                        </tr>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            <th colspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.78rem; line-height: 1.02; text-align: center;">KISWAHILI</th>
                            <th colspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.78rem; line-height: 1.02; text-align: center;">ENGLISH</th>
                            <th colspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.78rem; line-height: 1.02; text-align: center;">SOCIAL</th>
                            <th colspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.78rem; line-height: 1.02; text-align: center;">MATHEMATICS</th>
                            <th colspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.78rem; line-height: 1.02; text-align: center;">SCIENCE</th>
                            <th colspan="2" style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.78rem; line-height: 1.02; text-align: center;">CIVIC</th>
                        </tr>
                        <tr style="background-color: #003366; color: #FFFFFF;">
                            @foreach(range(1, 6) as $unused)
                                <th style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.76rem; line-height: 1.02; width: 48px; min-width: 48px; max-width: 48px; text-align: center;">SCRIPTS</th>
                                <th style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.76rem; line-height: 1.02; width: 44px; min-width: 44px; max-width: 44px; text-align: center;">%</th>
                            @endforeach
                            <th style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 48px; min-width: 48px; max-width: 48px; text-align: center;">SCRIPTS</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 44px; min-width: 44px; max-width: 44px; text-align: center;">%</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 48px; min-width: 48px; max-width: 48px; text-align: center;">SCRIPTS</th>
                            <th style="border: 1px solid #999; padding: 0.12rem 0.12rem; font-size: 0.82rem; line-height: 1.02; width: 44px; min-width: 44px; max-width: 44px; text-align: center;">%</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: LIGHTYELLOW; color: #000080;">
                        @forelse($rows as $index => $row)
                            @php
                                $statusColor = match ($row['status']) {
                                    'Complete' => '#1FEE0B',
                                    'Near Complete' => '#DEF043',
                                    'In Progress' => '#FF772F',
                                    default => '#FF272F',
                                };
                            @endphp
                            <tr>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 42px; min-width: 42px; max-width: 42px;">{{ $index + 1 }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.14rem; font-size: 0.8rem; line-height: 1.02; width: 320px; min-width: 320px; max-width: 320px;">{{ $row['school'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 68px; min-width: 68px; max-width: 68px;">{{ $row['registered'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['kisw'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['kisw_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['eng'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['eng_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['sst'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['sst_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['math'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['math_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['sci'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['sci_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['cme'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.1rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['cme_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 72px; min-width: 72px; max-width: 72px;">{{ $row['total'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['marked_scripts'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['marked_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 48px; min-width: 48px; max-width: 48px;">{{ $row['pending_scripts'] }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 44px; min-width: 44px; max-width: 44px;">{{ number_format((float) $row['pending_pct'], 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; text-align: center; font-size: 0.78rem; line-height: 1; width: 120px; min-width: 120px; max-width: 120px;">{{ number_format((float) $row['completion'], 1) }}%</td>
                                <td style="border: 1px solid #999; padding: 0.1rem 0.12rem; background: {{ $statusColor }}; color: #0f172a; font-size: 0.8rem; line-height: 1.02; width: 120px; min-width: 120px; max-width: 120px;">{{ $row['status'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="22" style="border: 1px solid #999; padding: 1rem; text-align: center;">No PSLE mark-entry status rows are available for this region.</td>
                            </tr>
                        @endforelse
                        @if($rows->isNotEmpty())
                            @php
                                $totalRegistered = (int) $rows->sum('registered');
                            @endphp
                            <tr style="background-color: #fff7bf; color: #000080;">
                                <td colspan="2" style="border: 1px solid #999; padding: 0.14rem 0.14rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">TOTAL</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('registered')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('kisw')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format($totalRegistered > 0 ? ($rows->sum('kisw') / $totalRegistered) * 100 : 0, 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('eng')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format($totalRegistered > 0 ? ($rows->sum('eng') / $totalRegistered) * 100 : 0, 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('sst')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format($totalRegistered > 0 ? ($rows->sum('sst') / $totalRegistered) * 100 : 0, 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('math')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format($totalRegistered > 0 ? ($rows->sum('math') / $totalRegistered) * 100 : 0, 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('sci')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format($totalRegistered > 0 ? ($rows->sum('sci') / $totalRegistered) * 100 : 0, 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('cme')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.1rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format($totalRegistered > 0 ? ($rows->sum('cme') / $totalRegistered) * 100 : 0, 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('total')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('marked_scripts')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((float) ($rows->sum('total') > 0 ? ($rows->sum('marked_scripts') / $rows->sum('total')) * 100 : 0), 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((int) $rows->sum('pending_scripts')) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((float) ($rows->sum('total') > 0 ? ($rows->sum('pending_scripts') / $rows->sum('total')) * 100 : 0), 1) }}</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: center; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">{{ number_format((float) ($rows->sum('total') > 0 ? ($rows->sum('marked_scripts') / $rows->sum('total')) * 100 : 0), 1) }}%</td>
                                <td style="border: 1px solid #999; padding: 0.14rem 0.12rem; text-align: left; font-size: 0.8rem; line-height: 1.02; font-weight: bold;">SUMMARY</td>
                            </tr>
                        @endif
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
