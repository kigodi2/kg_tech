@extends('layout')

@section('content')
<div class="min-h-screen bg-slate-50" style="font-family: 'Maiandra GD', sans-serif;">
    <div class="mx-auto max-w-5xl px-6 py-16 lg:px-8">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-10 shadow-sm shadow-slate-200/70">
            <div class="flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.24em] text-blue-700">
                <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                Regional Evaluation Detail
            </div>
            <h1 class="mt-4 text-3xl font-black text-slate-900">{{ $evaluationLabel ?? 'PSLE Evaluation' }}</h1>
            <p class="mt-3 text-base text-slate-600">Region: <span class="font-bold text-slate-800">{{ strtoupper($region->name) }}</span></p>
            <p class="mt-6 text-sm text-slate-600">This PSLE evaluation category has not been mapped to a dedicated report page yet.</p>
            <div class="mt-8">
                <a href="{{ route('evaluations.psle.regionalwise.region', ['region' => $region->id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <span>&larr;</span>
                    <span>Back to {{ strtoupper($region->name) }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
