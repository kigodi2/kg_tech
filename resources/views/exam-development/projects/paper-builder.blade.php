@extends('layouts.auth-rms')

@section('title', 'Paper Builder')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Paper Builder: ' . $paper->paper_name,
    'subtitle' => 'Slot-based assembly only. Questions must match the official format rule structure.',
])

<div class="max-w-7xl mx-auto px-4 py-6 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
    <div class="space-y-4">
        @foreach($paper->sections as $section)
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <h2 class="text-lg font-bold text-slate-900">{{ $section->section_code ?: 'Section' }} · {{ $section->section_name }}</h2>
                <p class="text-sm text-slate-600 mt-1">{{ $section->instructions }}</p>
                <div class="space-y-4 mt-4">
                    @foreach($section->slots as $slot)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-4 flex-wrap">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $slot->slot_label }} · {{ $slot->question_type }}</p>
                                    <p class="text-sm text-slate-600">{{ $slot->marks_per_question }} marks {{ $slot->choice_group ? '· Choice group '.$slot->choice_group : '' }}</p>
                                </div>
                                <span class="text-sm text-slate-700">{{ $slot->assignedQuestion?->title ?: 'Unassigned' }}</span>
                            </div>
                            <form action="{{ route('exam-development.projects.slots.assign', $slot) }}" method="POST" class="mt-3 grid gap-3 md:grid-cols-[1.1fr_0.35fr_1fr_auto]">
                                @csrf
                                <select name="question_id" class="rounded-xl border-slate-300">
                                    @foreach($approvedQuestions as $question)
                                        <option value="{{ $question->id }}" @selected($slot->assigned_question_id === $question->id)>{{ $question->title ?: $question->topic_name }} · {{ $question->question_type }} · {{ $question->marks }}m</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" min="0" name="custom_marks" value="{{ $slot->marks_per_question }}" class="rounded-xl border-slate-300">
                                <input type="text" name="custom_instructions" placeholder="Custom instructions" class="rounded-xl border-slate-300">
                                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Assign</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-bold text-slate-900">Blueprint Coverage</h2>
            <div class="space-y-2 mt-4">
                @forelse($coverage['targets'] as $target)
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm">
                        <div class="font-semibold text-slate-900">{{ $target['topic'] }}</div>
                        <div class="text-slate-600">Target {{ $target['target_items'] }} · Actual {{ $target['actual_items'] }} · {{ $target['percentage_weight'] }}%</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-600">No blueprint rows have been configured for this paper.</p>
                @endforelse
            </div>
        </div>

        @if($paper->paper_type === 'practical')
            <a href="{{ route('exam-development.practical.show', $paper) }}" class="block bg-white border border-slate-200 rounded-2xl shadow-sm p-5 hover:bg-slate-50">
                <h2 class="text-lg font-bold text-slate-900">Practical Setup</h2>
                <p class="text-sm text-slate-600 mt-1">Manage apparatus lists and confidential instructions for this paper.</p>
            </a>
        @endif
    </div>
</div>
@endsection
