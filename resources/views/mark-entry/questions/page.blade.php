@extends('layout')

@section('content')
@include('registration.partials.theme')

@php
    $statusLabel = $loadedEntry?->status ? strtoupper($loadedEntry->status) : 'NEW';
    $routeBase = 'mark-entry.' . strtolower($examCode) . '.questions';
@endphp

<div class="registration-shell mark-entry-shell">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="registration-surface p-6 sm:p-8 space-y-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $examType->code }} Question Entry</p>
                    <h1 class="text-2xl font-semibold text-slate-900">Mark Entry by Question</h1>
                    <p class="mt-2 text-sm text-slate-600">Active exam year: {{ $examYear->year_label }}. Region access is enforced from the logged-in account scope.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs text-slate-600">
                    <span class="registration-page-chip">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $examYear->year_label }}</span>
                    </span>
                    <span class="registration-page-chip">
                        <i class="fas fa-book-open"></i>
                        <span>{{ $subjects->count() }} subjects</span>
                    </span>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (!empty($pageErrors))
                <div class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="space-y-1">
                        @foreach ($pageErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="border border-slate-200 bg-white p-5 sm:p-6">
                <form method="GET" action="{{ route($routeBase . '.load') }}" class="grid gap-4 lg:grid-cols-[1.2fr_1fr_auto]">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Number</label>
                        <input
                            id="candidate_no_input"
                            type="text"
                            name="candidate_no"
                            value="{{ old('candidate_no', $candidateNo) }}"
                            class="w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            style="border-radius: 0 !important;"
                            placeholder="Enter candidate number"
                            autocomplete="off"
                            required
                        >
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Subject</label>
                        <input type="hidden" name="subject_id" id="subject_filter" value="{{ old('subject_id', $selectedSubjectId) }}">
                        <input
                            id="subject_filter_search"
                            list="subject_filter_options"
                            class="w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            style="border-radius: 0 !important;"
                            placeholder="Search subject"
                            autocomplete="off"
                            value="{{ optional($subjects->firstWhere('id', (int) old('subject_id', $selectedSubjectId)))?->code ? optional($subjects->firstWhere('id', (int) old('subject_id', $selectedSubjectId)))->code . ' - ' . optional($subjects->firstWhere('id', (int) old('subject_id', $selectedSubjectId)))->name : '' }}"
                            required
                        >
                        <datalist id="subject_filter_options">
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->code }} - {{ $subject->name }}" data-id="{{ $subject->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-none bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                            Load
                        </button>
                    </div>
                </form>
            </section>

            @if ($loaded && $candidate)
                <div class="grid gap-6 xl:grid-cols-[1.05fr_1.45fr]">
                    <section class="border border-slate-200 bg-white p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Candidate Details</h2>
                                <p class="text-sm text-slate-500">Loaded from the active exam registration context.</p>
                            </div>
                            <span class="rounded-none border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">{{ $statusLabel }}</span>
                        </div>

                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Candidate Name</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Candidate Number</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->candidate_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Sex</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->gender }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">School/Centre</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->school?->code }} - {{ $candidate->school?->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Region</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->school?->region?->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Exam Type</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $examType->code }}</dd>
                            </div>
                        </dl>

                        @if ($structure)
                            <div class="mt-5 rounded-none border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                {{ $structure['label'] }}
                            </div>
                        @endif
                    </section>

                    <section class="border border-slate-200 bg-white p-5 sm:p-6" x-data="questionMarkEntryPage(@js($scores), @js($structure ?? []))">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Question Marks</h2>
                                <p class="text-sm text-slate-500">Enter marks by question. Total updates automatically as you type.</p>
                                @if (!empty($structure['total_label']))
                                    <p class="mt-2 text-xs text-slate-500">{{ $structure['total_label'] }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                                <p class="text-2xl font-semibold text-slate-900" x-text="formattedTotal"></p>
                            </div>
                        </div>

                        @if (!empty($structure['papers']) && count($structure['papers']) > 1)
                            <div class="mt-5 grid gap-3 md:grid-cols-2">
                                @foreach ($structure['papers'] as $paper)
                                    <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $paper['paper_code'] }}</p>
                                        <p class="mt-1 text-sm font-medium text-slate-800">{{ $paper['paper_label'] }}</p>
                                        <div class="mt-2 flex items-center justify-between text-sm text-slate-600">
                                            <span>Paper total</span>
                                            <span x-text="paperTotal('{{ $paper['paper_code'] }}')"></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (!empty($structure['choice_groups']))
                            <div class="mt-5 rounded-none border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                <ul class="space-y-1">
                                    @foreach ($structure['choice_groups'] as $choiceGroup)
                                        <li>{{ $choiceGroup['label'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route($routeBase . '.store') }}" class="mt-5 space-y-5">
                            @csrf
                            <input type="hidden" name="candidate_no" value="{{ $candidate->candidate_id }}">
                            <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach (($structure['questions'] ?? []) as $question)
                                    @php
                                        $questionNo = (int) $question['question_no'];
                                        $field = 'scores.' . $questionNo;
                                        $defaultScore = old("scores.$questionNo", $scores[$questionNo] ?? null);
                                    @endphp
                                    <div class="border border-slate-200 p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <label for="score_{{ $questionNo }}" class="text-sm font-semibold text-slate-800">{{ $question['display_label'] ?? ('Q' . $questionNo) }}</label>
                                                <p class="mt-1 text-xs text-slate-500">Maximum {{ number_format((float) $question['max_mark'], 2) }}</p>
                                                @if (!empty($question['paper_label']))
                                                    <p class="mt-1 text-xs text-slate-500">{{ $question['paper_label'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <input
                                            id="score_{{ $questionNo }}"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $question['max_mark'] }}"
                                            name="scores[{{ $questionNo }}]"
                                            value="{{ $defaultScore }}"
                                            x-model="scores['{{ $questionNo }}']"
                                            @if (!empty($question['choice_group']))
                                                :disabled="{{ $canEdit ? 'isChoiceLocked(\'' . $question['choice_group'] . '\', ' . $questionNo . ')' : 'true' }}"
                                            @endif
                                            @disabled(!$canEdit)
                                            class="mt-3 w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                        >
                                        @error($field)
                                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                                @if ($canEdit)
                                    <button type="submit" name="entry_action" value="draft" class="rounded-none border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Save Draft
                                    </button>
                                    <button type="submit" name="entry_action" value="submit" class="rounded-none bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                                        Submit
                                    </button>
                                    <button type="submit" name="entry_action" value="next" class="rounded-none bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                                        Save &amp; Next Candidate
                                    </button>
                                @else
                                    <p class="text-sm text-amber-700">This submitted entry is read-only for your account.</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function questionMarkEntryPage(initialScores, structure) {
        return {
            scores: initialScores || {},
            structure: structure || {},
            get questions() {
                return this.structure.questions || [];
            },
            get papers() {
                return this.structure.papers || [];
            },
            get total() {
                if ((this.structure.aggregation || 'sum') === 'normalize_to_100' && this.papers.length) {
                    const weightedSum = this.papers.reduce((sum, paper) => sum + this.numericPaperTotal(paper), 0);
                    const weightedMax = this.papers.reduce((sum, paper) => sum + Number(paper.max_mark_total || 0), 0);
                    return weightedMax > 0 ? (weightedSum / weightedMax) * 100 : 0;
                }

                if ((this.structure.aggregation || 'sum') === 'average_paper_totals' && this.papers.length) {
                    const paperTotals = this.papers.map((paper) => this.numericPaperTotal(paper));
                    return paperTotals.reduce((sum, value) => sum + value, 0) / this.papers.length;
                }

                return this.questions.reduce((sum, question) => sum + this.numericQuestionScore(question.question_no), 0);
            },
            get formattedTotal() {
                return this.total.toFixed(2);
            },
            numericQuestionScore(questionNo) {
                const value = parseFloat(this.scores[String(questionNo)] ?? this.scores[questionNo] ?? 0);
                return Number.isFinite(value) ? value : 0;
            },
            numericPaperTotal(paper) {
                return (paper.question_numbers || []).reduce((sum, questionNo) => sum + this.numericQuestionScore(questionNo), 0);
            },
            paperTotal(paperCode) {
                const paper = this.papers.find((item) => item.paper_code === paperCode);
                return paper ? this.numericPaperTotal(paper).toFixed(2) : '0.00';
            },
            isChoiceLocked(groupKey, questionNo) {
                const groups = this.structure.choice_groups || [];
                const group = groups.find((item) => item.group_key === groupKey);
                if (!group) {
                    return false;
                }

                const currentValue = this.scores[String(questionNo)] ?? this.scores[questionNo] ?? '';
                if (currentValue !== null && currentValue !== '') {
                    return false;
                }

                const filledCount = (group.question_numbers || []).filter((groupQuestionNo) => {
                    const value = this.scores[String(groupQuestionNo)] ?? this.scores[groupQuestionNo] ?? '';
                    return value !== null && value !== '';
                }).length;

                return filledCount >= Number(group.limit || 0);
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const shouldFocusCandidateInput = @js((bool) session('focus_candidate_no'));
        const loadForm = document.querySelector('form[action="{{ route($routeBase . '.load') }}"]');

        setupSearchField('subject_filter_search', 'subject_filter', 'subject_filter_options');

        if (loadForm) {
            loadForm.addEventListener('submit', function (event) {
                if (!resolveSearchField('subject_filter_search', 'subject_filter', 'subject_filter_options')) {
                    event.preventDefault();
                    alert('Please select Subject from the searchable list.');
                    return;
                }
            });
        }

        if (!shouldFocusCandidateInput) {
            return;
        }

        const candidateInput = document.getElementById('candidate_no_input');
        if (!candidateInput) {
            return;
        }

        window.requestAnimationFrame(() => {
            candidateInput.focus();
            candidateInput.select();
        });
    });

    function setupSearchField(inputId, hiddenId, datalistId) {
        const input = document.getElementById(inputId);
        if (!input) {
            return;
        }

        input.addEventListener('change', function () {
            resolveSearchField(inputId, hiddenId, datalistId, true);
        });
    }

    function resolveSearchField(inputId, hiddenId, datalistId, allowBlank = false) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const options = Array.from(document.querySelectorAll(`#${datalistId} option`));
        const value = (input?.value || '').trim();

        if (allowBlank && value === '') {
            if (hidden) {
                hidden.value = '';
            }
            return true;
        }

        const matched = options.find((option) => option.value === value);

        if (hidden) {
            hidden.value = matched ? (matched.dataset.id || '') : '';
        }

        return Boolean(matched);
    }
</script>
@endsection
