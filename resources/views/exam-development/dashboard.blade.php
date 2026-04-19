@extends('layouts.auth-rms')

@section('title', 'Exam Development')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Exam Development Dashboard',
    'subtitle' => 'Format-governed paper development, review, approval, and export in one isolated workflow.',
])

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 font-semibold">Formats</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $formatCount }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 font-semibold">Projects</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $projectCount }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 font-semibold">Questions</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $questionCount }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500 font-semibold">Approved Projects</p>
            <p class="text-3xl font-bold text-slate-900 mt-2">{{ $approvedProjectCount }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Recent Projects</h2>
                    <p class="text-sm text-slate-600">Newly created working papers cloned from official subject formats.</p>
                </div>
                <a href="{{ route('exam-development.projects.create') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Create Project</a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentProjects as $project)
                    <a href="{{ route('exam-development.projects.show', $project) }}" class="block p-5 hover:bg-slate-50">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $project->project_name }}</p>
                                <p class="text-sm text-slate-600">{{ $project->examType?->code }} · {{ $project->subject?->name }} · {{ $project->exam_year }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold uppercase">{{ str_replace('_', ' ', $project->status) }}</span>
                        </div>
                    </a>
                @empty
                    <div class="p-5 text-sm text-slate-600">No projects yet. Start by creating or importing a subject format, then spin up a development project from it.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 space-y-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Implementation Scope</h2>
                <p class="text-sm text-slate-600 mt-1">This module is isolated from mark entry, results, and current registration flows.</p>
            </div>
            <ul class="space-y-3 text-sm text-slate-700">
                <li class="p-3 rounded-xl bg-slate-50 border border-slate-200">Format Master: official papers, sections, notes, rules, and blueprint rows.</li>
                <li class="p-3 rounded-xl bg-slate-50 border border-slate-200">Project Builder: clone format into slot-based working papers and validate coverage.</li>
                <li class="p-3 rounded-xl bg-slate-50 border border-slate-200">Question Bank: reusable items with metadata, options, version history, and statuses.</li>
                <li class="p-3 rounded-xl bg-slate-50 border border-slate-200">Review, Approval, Practical Tools, Audit Logging, and PDF export paths.</li>
            </ul>
        </div>
    </div>
</div>
@endsection
