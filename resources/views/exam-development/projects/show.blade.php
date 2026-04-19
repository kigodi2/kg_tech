@extends('layouts.auth-rms')

@section('title', 'Exam Project')

@section('content')
@include('exam-development.partials.nav', [
    'title' => $project->project_name,
    'subtitle' => $project->examType?->code . ' · ' . $project->subject?->name . ' · ' . $project->exam_year,
])

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm text-slate-600">Current status</p>
            <p class="text-2xl font-bold text-slate-900">{{ str_replace('_', ' ', $project->status) }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('exam-development.projects.validate', $project) }}" method="POST">@csrf<button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Validate Project</button></form>
            <a href="{{ route('exam-development.review.show', $project) }}" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Review & Approval</a>
            <a href="{{ route('exam-development.exports.show', $project) }}" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold">Exports</a>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-bold text-slate-900">Validation Snapshot</h2>
        <div class="mt-4 grid gap-3">
            @forelse($validation['errors'] as $error)
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $error }}</div>
            @empty
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">No blocking validation errors found in the current snapshot.</div>
            @endforelse
            @foreach($validation['warnings'] as $warning)
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">{{ $warning }}</div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach($project->papers as $paper)
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $paper->paper_code }} · {{ $paper->paper_name }}</h2>
                        <p class="text-sm text-slate-600">{{ ucfirst($paper->paper_type) }} · {{ $paper->total_marks }} marks · {{ str_replace('_', ' ', $paper->status) }}</p>
                    </div>
                    <a href="{{ route('exam-development.projects.papers.builder', $paper) }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Paper Builder</a>
                </div>
                <div class="p-5 space-y-4">
                    @foreach($paper->sections as $section)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="font-semibold text-slate-900">{{ $section->section_code ?: 'Section' }} · {{ $section->section_name }}</p>
                            <p class="text-sm text-slate-600 mb-3">{{ $section->total_marks }} marks · {{ $section->number_of_questions }} questions</p>
                            <div class="space-y-2">
                                @foreach($section->slots as $slot)
                                    <div class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm">
                                        <span class="font-semibold text-slate-900">{{ $slot->slot_label }}</span>
                                        <span class="text-slate-600">· {{ $slot->question_type }} · {{ $slot->marks_per_question }} marks</span>
                                        <div class="text-slate-700 mt-1">{{ $slot->assignedQuestion?->title ?: 'No question assigned yet' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
