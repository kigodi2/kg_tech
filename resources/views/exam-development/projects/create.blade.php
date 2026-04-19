@extends('layouts.auth-rms')

@section('title', 'Create Project')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Create Exam Project',
    'subtitle' => 'Clone an active official format into a working paper project with auto-generated slots.',
])

<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <form action="{{ route('exam-development.projects.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Official Subject Format</label>
                <select name="subject_format_id" id="subject_format_id" class="mt-1 w-full rounded-xl border-slate-300">
                    @foreach($formats as $format)
                        <option value="{{ $format->id }}" data-exam-type="{{ $format->exam_type_id }}" data-subject="{{ $format->subject_id }}">{{ $format->format_name }} · {{ $format->examType?->code }} · {{ $format->subject?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Project Name</label>
                    <input type="text" name="project_name" required class="mt-1 w-full rounded-xl border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Project Code</label>
                    <input type="text" name="project_code" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Exam Year</label>
                    <input type="text" name="exam_year" value="{{ now()->year }}" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Exam Type Id</label>
                    <input type="number" name="exam_type_id" id="exam_type_id" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Subject Id</label>
                    <input type="number" name="subject_id" id="subject_id" class="mt-1 w-full rounded-xl border-slate-300">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Description</label>
                <textarea name="description" rows="4" class="mt-1 w-full rounded-xl border-slate-300"></textarea>
            </div>
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Create Project and Generate Slots</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const formatSelect = document.getElementById('subject_format_id');
    const examTypeInput = document.getElementById('exam_type_id');
    const subjectInput = document.getElementById('subject_id');
    const sync = () => {
        const option = formatSelect.options[formatSelect.selectedIndex];
        examTypeInput.value = option.dataset.examType || '';
        subjectInput.value = option.dataset.subject || '';
    };
    sync();
    formatSelect.addEventListener('change', sync);
});
</script>
@endsection
