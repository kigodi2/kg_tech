@extends('layout')

@section('content')
@php
    $divisionSummary = $summary['division_summary'] ?? [];
    $overall = $summary['overall'] ?? [];
    $gpaInfo = $overall['gpa_info'] ?? null;
    $isSubjectSummary = str_contains(strtoupper((string) ($evaluationLabel ?? '')), 'SUMMARY');
    $detailEyebrow = $isSubjectSummary ? 'Detailed Subject Summary Evaluation' : 'Detailed Subjectwise Evaluation';
    $mainTitle = $isSubjectSummary ? 'Division and subject summary performance' : 'Division and subject performance summary';
    $mainCopy = $isSubjectSummary
        ? 'The page follows the joined report structure: division summary first, then overall performance, then subjects summary.'
        : 'The page follows the joined report structure: division summary first, then overall performance, then subjects performance.';
    $subjectsSectionTitle = $isSubjectSummary ? 'Examination Centre Subjects Summary' : 'Examination Centre Subjects Performance';
@endphp

<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur sticky top-0 z-30 shadow-sm">
        <div class="mx-auto max-w-[96rem] px-6 py-5 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Regional Subjectwise Report
                    </div>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900 lg:text-4xl">{{ $evaluationLabel }}</h1>
                    <p class="mt-2 text-sm text-slate-600 lg:text-base">{{ strtoupper($region->name) }} · February {{ $examYearValue }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('evaluations.acsee.regionalwise.region', ['region' => $region->id]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        <span>&larr;</span>
                        <span>Back to {{ strtoupper($region->name) }}</span>
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-800">
                        <span>Export PDF</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-[96rem] px-6 py-8 lg:px-8">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 lg:p-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-blue-700">{{ $detailEyebrow }}</p>
                    <h3 class="mt-2 text-2xl font-black text-slate-900">{{ $mainTitle }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $mainCopy }}</p>
                </div>
                <span class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-700">{{ $summary['subjects'] ?? 0 }} subjects</span>
            </div>

            <div class="mt-6 overflow-hidden rounded-[1.5rem] border border-slate-400">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm text-slate-900">
                        <thead>
                            <tr class="bg-[#003366] text-white">
                                <th colspan="8" class="border border-slate-900 px-4 py-3 text-left text-base font-black uppercase">Division Performance Summary</th>
                            </tr>
                            <tr class="bg-[#fffde7] text-[#000080]">
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">Sex</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">I</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">II</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">III</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">IV</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">0</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">INC</th>
                                <th class="border border-slate-900 px-4 py-3 text-center text-sm font-black uppercase">ABS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[#fffde7] text-[#000080]">
                            @foreach(['F', 'M', 'T'] as $sex)
                                <tr>
                                    <td class="border border-slate-900 px-4 py-3 text-center font-bold">{{ $sex }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.I', 0) }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.II', 0) }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.III', 0) }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.IV', 0) }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.0', 0) }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.INC', 0) }}</td>
                                    <td class="border border-slate-900 px-4 py-3 text-center">{{ data_get($divisionSummary, $sex.'.ABS', 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-[1.5rem] border border-slate-400">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm text-slate-900">
                        <thead>
                            <tr class="bg-[#003366] text-white">
                                <th colspan="2" class="border border-slate-900 px-4 py-3 text-left text-base font-black uppercase">Examination Centre Overall Performance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[#fffde7]">
                            <tr>
                                <td class="border border-slate-900 px-4 py-3 font-medium uppercase">Examination Centre Region</td>
                                <td class="border border-slate-900 px-4 py-3">{{ $overall['region'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-900 px-4 py-3 font-medium uppercase">Total Passed Candidates</td>
                                <td class="border border-slate-900 px-4 py-3">{{ $overall['passed'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-900 px-4 py-3 font-medium uppercase">Examination Centre GPA</td>
                                <td class="border border-slate-900 px-4 py-3">{{ !empty($overall['gpa']) ? number_format((float) $overall['gpa'], 4) : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="border border-slate-900 px-4 py-3 font-medium uppercase">GPA Competence</td>
                                <td class="border border-slate-900 px-4 py-3 font-medium" style="background: {{ $gpaInfo['color'] ?? '#fffde7' }} !important; color: #000080;">
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

            <div class="mt-4 overflow-hidden rounded-[1.5rem] border border-slate-400">
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm text-slate-900">
                        <thead>
                            <tr class="bg-[#003366] text-white">
                                <th colspan="12" class="border border-slate-900 px-4 py-3 text-left text-base font-black uppercase">{{ $subjectsSectionTitle }}</th>
                            </tr>
                            <tr class="bg-[#003366] text-white">
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">Code</th>
                                <th class="border border-slate-900 px-3 py-3 text-left text-sm font-black uppercase">Subject Name</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">A</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">B</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">C</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">D</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">E</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">S</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">F</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">Total</th>
                                <th class="border border-slate-900 px-3 py-3 text-center text-sm font-black uppercase">GPA</th>
                                <th class="border border-slate-900 px-3 py-3 text-left text-sm font-black uppercase">Competency Level</th>
                            </tr>
                        </thead>
                        <tbody class="bg-[#fffde7]">
                            @foreach($rows as $row)
                                @php
                                    $competence = $row['competence'] ?? null;
                                    $competenceColor = data_get($competence, 'color', '#fffde7');
                                @endphp
                                <tr>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['code'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-left">{{ $row['name'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_a'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_b'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_c'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_d'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_e'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_s'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['grade_f'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ $row['total'] }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-center">{{ is_null($row['gpa']) ? '-' : number_format((float) $row['gpa'], 4) }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-left font-medium" style="background: {{ $competenceColor }} !important; color: #000080;">
                                        @if($competence)
                                            Grade {{ $competence['grade'] ?? '-' }} ({{ $competence['competence'] ?? '-' }})
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
