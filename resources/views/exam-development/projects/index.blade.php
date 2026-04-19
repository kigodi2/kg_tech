@extends('layouts.auth-rms')

@section('title', 'Exam Projects')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Exam Development Projects',
    'subtitle' => 'Working projects cloned from approved subject formats.',
])

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Projects</h2>
            <a href="{{ route('exam-development.projects.create') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Create Project</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Project</th>
                        <th class="px-4 py-3 text-left">Exam</th>
                        <th class="px-4 py-3 text-left">Format</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($projects as $project)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $project->project_name }}</div>
                                <div class="text-slate-600">{{ $project->project_code ?: 'No code' }} · {{ $project->exam_year }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $project->examType?->code }} · {{ $project->subject?->name }}</td>
                            <td class="px-4 py-3">{{ $project->format?->format_name }}</td>
                            <td class="px-4 py-3"><span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold uppercase">{{ str_replace('_', ' ', $project->status) }}</span></td>
                            <td class="px-4 py-3"><a href="{{ route('exam-development.projects.show', $project) }}" class="text-slate-900 font-semibold">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-200">{{ $projects->links() }}</div>
    </div>
</div>
@endsection
