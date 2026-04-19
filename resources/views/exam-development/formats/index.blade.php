@extends('layouts.auth-rms')

@section('title', 'Format Master')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Format Master',
    'subtitle' => 'Create official subject formats, then enrich them with papers, sections, rules, notes, and blueprints.',
])

<div class="max-w-7xl mx-auto px-4 py-6 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-bold text-slate-900">Create Format</h2>
        </div>
        <form action="{{ route('exam-development.formats.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Exam Type</label>
                    <select name="exam_type_id" class="mt-1 w-full rounded-xl border-slate-300">
                        @foreach($examTypes as $examType)
                            <option value="{{ $examType->id }}">{{ $examType->code }} - {{ $examType->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Subject</label>
                    <select name="subject_id" class="mt-1 w-full rounded-xl border-slate-300">
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Format Name</label>
                    <input type="text" name="format_name" required class="mt-1 w-full rounded-xl border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Format Code</label>
                    <input type="text" name="format_code" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Version Year</label>
                    <input type="text" name="version_year" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Candidate Scope</label>
                    <input type="text" name="candidate_scope" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Total Papers</label>
                    <input type="number" min="1" name="total_papers" value="1" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">General Instructions</label>
                <textarea name="general_instructions" rows="4" class="mt-1 w-full rounded-xl border-slate-300"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Source Reference</label>
                <input type="text" name="source_reference" class="mt-1 w-full rounded-xl border-slate-300">
            </div>
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Create Format</button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Existing Formats</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($formats as $format)
                <a href="{{ route('exam-development.formats.show', $format) }}" class="block p-5 hover:bg-slate-50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $format->format_name }}</p>
                            <p class="text-sm text-slate-600">{{ $format->examType?->code }} · {{ $format->subject?->name }} · {{ $format->version_year ?: 'No version year' }}</p>
                        </div>
                        <span class="text-xs uppercase font-semibold px-3 py-1 rounded-full {{ $format->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">{{ $format->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                </a>
            @empty
                <div class="p-5 text-sm text-slate-600">No formats have been defined yet.</div>
            @endforelse
        </div>
        <div class="p-5 border-t border-slate-200">{{ $formats->links() }}</div>
    </div>
</div>
@endsection
