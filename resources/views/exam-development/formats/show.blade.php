@extends('layouts.auth-rms')

@section('title', 'Subject Format')

@section('content')
@include('exam-development.partials.nav', [
    'title' => $format->format_name,
    'subtitle' => $format->examType?->code . ' · ' . $format->subject?->name . ' · format-governed paper structure',
])

<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
        <form action="{{ route('exam-development.formats.update', $format) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="block text-sm font-medium text-slate-700">Format Name</label><input type="text" name="format_name" value="{{ $format->format_name }}" class="mt-1 w-full rounded-xl border-slate-300"></div>
                <div><label class="block text-sm font-medium text-slate-700">Format Code</label><input type="text" name="format_code" value="{{ $format->format_code }}" class="mt-1 w-full rounded-xl border-slate-300"></div>
                <div><label class="block text-sm font-medium text-slate-700">Version Year</label><input type="text" name="version_year" value="{{ $format->version_year }}" class="mt-1 w-full rounded-xl border-slate-300"></div>
                <div><label class="block text-sm font-medium text-slate-700">Total Papers</label><input type="number" name="total_papers" value="{{ $format->total_papers }}" class="mt-1 w-full rounded-xl border-slate-300"></div>
            </div>
            <div><label class="block text-sm font-medium text-slate-700">General Instructions</label><textarea name="general_instructions" rows="3" class="mt-1 w-full rounded-xl border-slate-300">{{ $format->general_instructions }}</textarea></div>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" {{ $format->is_active ? 'checked' : '' }}> Active format</label>
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Save Format</button>
            </div>
        </form>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-bold text-slate-900">Add Paper</h2>
            <form action="{{ route('exam-development.formats.papers.store', $format) }}" method="POST" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                <input name="paper_code" placeholder="Paper code" class="rounded-xl border-slate-300">
                <input name="paper_no" type="number" min="1" placeholder="Paper no" class="rounded-xl border-slate-300">
                <input name="paper_name" placeholder="Paper name" class="rounded-xl border-slate-300 md:col-span-2">
                <input name="paper_type" placeholder="Paper type" class="rounded-xl border-slate-300">
                <input name="duration_minutes" type="number" min="0" placeholder="Duration minutes" class="rounded-xl border-slate-300">
                <input name="total_marks" type="number" step="0.01" min="0" placeholder="Total marks" class="rounded-xl border-slate-300">
                <input name="questions_total" type="number" min="0" placeholder="Questions total" class="rounded-xl border-slate-300">
                <input name="questions_to_answer" type="number" min="0" placeholder="Questions to answer" class="rounded-xl border-slate-300">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="has_sections" value="1"> Has sections</label>
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold md:col-span-2">Add Paper</button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-bold text-slate-900">Add General Note</h2>
            <form action="{{ route('exam-development.formats.notes.store', $format) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 md:grid-cols-2">
                    <input name="note_type" placeholder="Note type" class="rounded-xl border-slate-300">
                    <select name="subject_format_paper_id" class="rounded-xl border-slate-300">
                        <option value="">All papers</option>
                        @foreach($format->papers as $paper)
                            <option value="{{ $paper->id }}">{{ $paper->paper_code }} - {{ $paper->paper_name }}</option>
                        @endforeach
                    </select>
                </div>
                <textarea name="note_text" rows="3" placeholder="Candidate/admin note" class="w-full rounded-xl border-slate-300"></textarea>
                <div class="flex gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="applies_to_candidates" value="1" checked> Candidates</label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="applies_to_admins" value="1"> Admins</label>
                </div>
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Add Note</button>
            </form>
        </div>
    </div>

    @foreach($format->papers as $paper)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $paper->paper_code }} · {{ $paper->paper_name }}</h2>
                    <p class="text-sm text-slate-600">{{ ucfirst($paper->paper_type) }} · {{ $paper->duration_minutes }} minutes · {{ $paper->total_marks }} marks</p>
                </div>
                <div class="text-sm text-slate-600">Questions: {{ $paper->questions_total ?: 'n/a' }} | Answer: {{ $paper->questions_to_answer ?: 'all/fixed by rules' }}</div>
            </div>
            <div class="grid gap-6 xl:grid-cols-2 p-5">
                <div class="space-y-4">
                    <div class="border border-slate-200 rounded-2xl p-4">
                        <h3 class="font-semibold text-slate-900">Add Section</h3>
                        <form action="{{ route('exam-development.formats.sections.store', $paper) }}" method="POST" class="mt-3 grid gap-3 md:grid-cols-2">
                            @csrf
                            <input name="section_code" placeholder="Section code" class="rounded-xl border-slate-300">
                            <input name="section_name" placeholder="Section name" class="rounded-xl border-slate-300">
                            <input name="total_marks" type="number" step="0.01" placeholder="Section marks" class="rounded-xl border-slate-300">
                            <input name="number_of_questions" type="number" min="0" placeholder="Questions count" class="rounded-xl border-slate-300">
                            <input name="questions_to_answer" type="number" min="0" placeholder="Questions to answer" class="rounded-xl border-slate-300">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="is_all_compulsory" value="1"> All compulsory</label>
                            <textarea name="instructions" rows="2" placeholder="Section instructions" class="rounded-xl border-slate-300 md:col-span-2"></textarea>
                            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold md:col-span-2">Add Section</button>
                        </form>
                    </div>

                    @foreach($paper->sections as $section)
                        <div class="border border-slate-200 rounded-2xl p-4 space-y-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $section->section_code ?: 'Section' }} · {{ $section->section_name }}</p>
                                <p class="text-sm text-slate-600">{{ $section->total_marks }} marks · {{ $section->number_of_questions }} questions</p>
                            </div>
                            <form action="{{ route('exam-development.formats.rules.store', $section) }}" method="POST" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                <input name="question_no_from" type="number" min="1" placeholder="Q from" class="rounded-xl border-slate-300">
                                <input name="question_no_to" type="number" min="1" placeholder="Q to" class="rounded-xl border-slate-300">
                                <input name="question_type" placeholder="Type" class="rounded-xl border-slate-300">
                                <input name="items_per_question" type="number" min="1" placeholder="Items/question" class="rounded-xl border-slate-300">
                                <input name="marks_per_item" type="number" step="0.01" placeholder="Marks/item" class="rounded-xl border-slate-300">
                                <input name="marks_per_question" type="number" step="0.01" placeholder="Marks/question" class="rounded-xl border-slate-300">
                                <input name="total_marks" type="number" step="0.01" placeholder="Rule total" class="rounded-xl border-slate-300">
                                <input name="answer_mode" placeholder="Answer mode" class="rounded-xl border-slate-300">
                                <input name="choice_count" type="number" min="0" placeholder="Choice count" class="rounded-xl border-slate-300">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700 md:col-span-2"><input type="checkbox" name="is_compulsory" value="1" checked> Compulsory</label>
                                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold md:col-span-3">Add Rule</button>
                            </form>
                            <div class="space-y-2">
                                @foreach($section->rules as $rule)
                                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-700">
                                        Q{{ $rule->question_no_from ?: '?' }}{{ $rule->question_no_to && $rule->question_no_to !== $rule->question_no_from ? '–Q'.$rule->question_no_to : '' }}
                                        · {{ $rule->question_type }} · {{ $rule->total_marks }} marks · {{ $rule->answer_mode }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-4">
                    <div class="border border-slate-200 rounded-2xl p-4">
                        <h3 class="font-semibold text-slate-900">Add Blueprint</h3>
                        <form action="{{ route('exam-development.formats.blueprints.store', $paper) }}" method="POST" class="mt-3 grid gap-3 md:grid-cols-2">
                            @csrf
                            <input name="blueprint_name" placeholder="Blueprint name" class="rounded-xl border-slate-300 md:col-span-2">
                            <input name="total_items" type="number" min="0" placeholder="Total items" class="rounded-xl border-slate-300">
                            <input name="total_weight" type="number" step="0.01" min="0" placeholder="Total weight" class="rounded-xl border-slate-300">
                            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold md:col-span-2">Create Blueprint</button>
                        </form>
                    </div>

                    @foreach($paper->blueprints as $blueprint)
                        <div class="border border-slate-200 rounded-2xl p-4 space-y-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $blueprint->blueprint_name }}</p>
                                <p class="text-sm text-slate-600">{{ $blueprint->total_items }} items · {{ $blueprint->total_weight }} total weight</p>
                            </div>
                            <form action="{{ route('exam-development.formats.blueprint-topics.store', $blueprint) }}" method="POST" class="grid gap-3 md:grid-cols-2">
                                @csrf
                                <input name="topic_name" placeholder="Topic label" class="rounded-xl border-slate-300 md:col-span-2">
                                <input name="items_count" type="number" min="0" placeholder="Items count" class="rounded-xl border-slate-300">
                                <input name="percentage_weight" type="number" step="0.01" min="0" placeholder="% weight" class="rounded-xl border-slate-300">
                                <input name="remembering_weight" type="number" step="0.01" min="0" placeholder="Remembering" class="rounded-xl border-slate-300">
                                <input name="understanding_weight" type="number" step="0.01" min="0" placeholder="Understanding" class="rounded-xl border-slate-300">
                                <input name="applying_weight" type="number" step="0.01" min="0" placeholder="Applying" class="rounded-xl border-slate-300">
                                <input name="analysing_weight" type="number" step="0.01" min="0" placeholder="Analysing" class="rounded-xl border-slate-300">
                                <input name="evaluating_weight" type="number" step="0.01" min="0" placeholder="Evaluating" class="rounded-xl border-slate-300">
                                <input name="creating_weight" type="number" step="0.01" min="0" placeholder="Creating" class="rounded-xl border-slate-300">
                                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold md:col-span-2">Add Topic Row</button>
                            </form>
                            <div class="space-y-2">
                                @foreach($blueprint->topics as $topic)
                                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-700">
                                        {{ $topic->topic_name }} · {{ $topic->items_count }} items · {{ $topic->percentage_weight }}%
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
