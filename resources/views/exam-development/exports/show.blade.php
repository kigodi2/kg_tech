@extends('layouts.auth-rms')

@section('title', 'Exam Exports')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Export Center',
    'subtitle' => $project->project_name . ' · controlled candidate, moderator, scheme, and archive exports',
])

<div class="max-w-6xl mx-auto px-4 py-6 grid gap-6 md:grid-cols-2">
    @foreach($project->papers as $paper)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-bold text-slate-900">{{ $paper->paper_code }} · {{ $paper->paper_name }}</h2>
            <p class="text-sm text-slate-600 mt-1">{{ ucfirst($paper->paper_type) }} · {{ $paper->total_marks }} marks</p>
            <div class="mt-5 grid gap-3">
                <a href="{{ route('exam-development.exports.download', [$paper, 'candidate']) }}" class="px-4 py-3 rounded-xl bg-slate-900 text-white text-sm font-semibold text-center">Candidate Paper</a>
                <a href="{{ route('exam-development.exports.download', [$paper, 'marking-scheme']) }}" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold text-center">Confidential Marking Scheme</a>
                <a href="{{ route('exam-development.exports.download', [$paper, 'moderator']) }}" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold text-center">Moderator Version</a>
                <a href="{{ route('exam-development.exports.download', [$paper, 'archive']) }}" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-700 text-sm font-semibold text-center">Archive Copy</a>
            </div>
        </div>
    @endforeach
</div>
@endsection
