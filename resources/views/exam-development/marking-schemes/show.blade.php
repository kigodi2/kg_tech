@extends('layouts.auth-rms')

@section('title', 'Marking Scheme')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Marking Scheme',
    'subtitle' => ($question->title ?: $question->topic_name) . ' · ' . $question->marks . ' marks',
])

<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
        <p class="text-sm text-slate-700">{{ $question->question_text }}</p>
    </div>

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-lg font-bold text-slate-900">Add Marking Scheme</h2>
        <form action="{{ route('exam-development.marking-schemes.store', $question) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div class="grid gap-4 md:grid-cols-3">
                <input type="text" name="scheme_type" placeholder="Scheme type" class="rounded-xl border-slate-300">
                <input type="number" step="0.01" min="0" name="total_marks" value="{{ $question->marks }}" placeholder="Total marks" class="rounded-xl border-slate-300">
                <input type="text" name="status" value="draft" placeholder="Status" class="rounded-xl border-slate-300">
            </div>
            <textarea name="answer_text" rows="4" placeholder="Model answer / key" class="w-full rounded-xl border-slate-300"></textarea>
            <div class="space-y-3">
                @for($i = 0; $i < 6; $i++)
                    <div class="grid gap-3 md:grid-cols-[0.25fr_1fr_0.25fr]">
                        <input type="text" name="items[{{ $i }}][item_label]" placeholder="A" class="rounded-xl border-slate-300">
                        <input type="text" name="items[{{ $i }}][description]" placeholder="Point / rubric description" class="rounded-xl border-slate-300">
                        <input type="number" step="0.01" min="0" name="items[{{ $i }}][marks]" placeholder="Marks" class="rounded-xl border-slate-300">
                    </div>
                @endfor
            </div>
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Save Scheme</button>
        </form>
    </div>

    @foreach($question->markingSchemes as $scheme)
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $scheme->scheme_type }}</h2>
                    <p class="text-sm text-slate-600">{{ $scheme->status }} · {{ $scheme->total_marks }} marks</p>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                @foreach($scheme->items as $item)
                    <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-700">{{ $item->item_label }} {{ $item->description }} ({{ $item->marks }})</div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
