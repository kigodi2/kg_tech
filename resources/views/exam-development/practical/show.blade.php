@extends('layouts.auth-rms')

@section('title', 'Practical Paper')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Practical Paper Manager',
    'subtitle' => $paper->paper_code . ' · ' . $paper->paper_name . ' · practical setup and confidentiality controls',
])

<div class="max-w-6xl mx-auto px-4 py-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <form action="{{ route('exam-development.practical.update', $paper) }}" method="POST" class="space-y-6">
            @csrf
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Apparatus Lists</h2>
                @for($listIndex = 0; $listIndex < 3; $listIndex++)
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                        <div class="grid gap-3 md:grid-cols-2">
                            <input type="text" name="apparatus_lists[{{ $listIndex }}][title]" value="{{ old("apparatus_lists.$listIndex.title", optional($paper->apparatusLists[$listIndex] ?? null)->title) }}" placeholder="List title" class="rounded-xl border-slate-300">
                            <input type="number" min="0" name="apparatus_lists[{{ $listIndex }}][issued_before_days]" value="{{ old("apparatus_lists.$listIndex.issued_before_days", optional($paper->apparatusLists[$listIndex] ?? null)->issued_before_days) }}" placeholder="Issued before days" class="rounded-xl border-slate-300">
                        </div>
                        <textarea name="apparatus_lists[{{ $listIndex }}][notes]" rows="2" placeholder="List notes" class="w-full rounded-xl border-slate-300">{{ old("apparatus_lists.$listIndex.notes", optional($paper->apparatusLists[$listIndex] ?? null)->notes) }}</textarea>
                        @for($itemIndex = 0; $itemIndex < 4; $itemIndex++)
                            <div class="grid gap-3 md:grid-cols-4">
                                <input type="text" name="apparatus_lists[{{ $listIndex }}][items][{{ $itemIndex }}][item_name]" value="{{ old("apparatus_lists.$listIndex.items.$itemIndex.item_name", optional(optional($paper->apparatusLists[$listIndex] ?? null)->items[$itemIndex] ?? null)->item_name) }}" placeholder="Item name" class="rounded-xl border-slate-300">
                                <input type="number" step="0.01" min="0" name="apparatus_lists[{{ $listIndex }}][items][{{ $itemIndex }}][quantity]" value="{{ old("apparatus_lists.$listIndex.items.$itemIndex.quantity", optional(optional($paper->apparatusLists[$listIndex] ?? null)->items[$itemIndex] ?? null)->quantity) }}" placeholder="Qty" class="rounded-xl border-slate-300">
                                <input type="text" name="apparatus_lists[{{ $listIndex }}][items][{{ $itemIndex }}][unit]" value="{{ old("apparatus_lists.$listIndex.items.$itemIndex.unit", optional(optional($paper->apparatusLists[$listIndex] ?? null)->items[$itemIndex] ?? null)->unit) }}" placeholder="Unit" class="rounded-xl border-slate-300">
                                <input type="text" name="apparatus_lists[{{ $listIndex }}][items][{{ $itemIndex }}][remarks]" value="{{ old("apparatus_lists.$listIndex.items.$itemIndex.remarks", optional(optional($paper->apparatusLists[$listIndex] ?? null)->items[$itemIndex] ?? null)->remarks) }}" placeholder="Remarks" class="rounded-xl border-slate-300">
                            </div>
                        @endfor
                    </div>
                @endfor
            </div>

            <div class="space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Confidential Instructions</h2>
                @for($instructionIndex = 0; $instructionIndex < 3; $instructionIndex++)
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-3">
                        <input type="number" min="0" name="confidential_instructions[{{ $instructionIndex }}][release_hours_before]" value="{{ old("confidential_instructions.$instructionIndex.release_hours_before", optional($paper->confidentialInstructions[$instructionIndex] ?? null)->release_hours_before) }}" placeholder="Release hours before exam" class="rounded-xl border-slate-300">
                        <textarea name="confidential_instructions[{{ $instructionIndex }}][instruction_text]" rows="3" placeholder="Confidential instruction" class="w-full rounded-xl border-slate-300">{{ old("confidential_instructions.$instructionIndex.instruction_text", optional($paper->confidentialInstructions[$instructionIndex] ?? null)->instruction_text) }}</textarea>
                    </div>
                @endfor
            </div>
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Save Practical Setup</button>
        </form>
    </div>
</div>
@endsection
