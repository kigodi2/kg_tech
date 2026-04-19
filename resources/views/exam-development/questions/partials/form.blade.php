<div class="max-w-5xl mx-auto px-4 py-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif
            <div class="grid gap-4 md:grid-cols-2">
                <div><label class="block text-sm font-medium text-slate-700">Exam Type</label><select name="exam_type_id" class="mt-1 w-full rounded-xl border-slate-300">@foreach($examTypes as $examType)<option value="{{ $examType->id }}" @selected(optional($question)->exam_type_id === $examType->id)>{{ $examType->code }} - {{ $examType->name }}</option>@endforeach</select></div>
                <div><label class="block text-sm font-medium text-slate-700">Subject</label><select name="subject_id" class="mt-1 w-full rounded-xl border-slate-300">@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(optional($question)->subject_id === $subject->id)>{{ $subject->code }} - {{ $subject->name }}</option>@endforeach</select></div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <input type="text" name="paper_type" value="{{ old('paper_type', optional($question)->paper_type) }}" placeholder="Paper type" class="rounded-xl border-slate-300">
                <input type="text" name="topic_name" value="{{ old('topic_name', optional($question)->topic_name) }}" placeholder="Topic" class="rounded-xl border-slate-300">
                <input type="text" name="subtopic_name" value="{{ old('subtopic_name', optional($question)->subtopic_name) }}" placeholder="Subtopic" class="rounded-xl border-slate-300">
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <input type="text" name="competency_code" value="{{ old('competency_code', optional($question)->competency_code) }}" placeholder="Competency code" class="rounded-xl border-slate-300">
                <input type="text" name="difficulty_level" value="{{ old('difficulty_level', optional($question)->difficulty_level) }}" placeholder="Difficulty" class="rounded-xl border-slate-300">
                <input type="text" name="question_type" value="{{ old('question_type', optional($question)->question_type) }}" placeholder="Question type" class="rounded-xl border-slate-300">
                <input type="number" step="0.01" min="0" name="marks" value="{{ old('marks', optional($question)->marks) }}" placeholder="Marks" class="rounded-xl border-slate-300">
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" name="title" value="{{ old('title', optional($question)->title) }}" placeholder="Question title" class="rounded-xl border-slate-300">
                <input type="text" name="status" value="{{ old('status', optional($question)->status ?: 'draft') }}" placeholder="Status" class="rounded-xl border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Question Text</label>
                <textarea name="question_text" rows="8" class="mt-1 w-full rounded-xl border-slate-300">{{ old('question_text', optional($question)->question_text) }}</textarea>
            </div>
            <div class="grid gap-4 md:grid-cols-4">
                <input type="number" min="0" name="estimated_minutes" value="{{ old('estimated_minutes', optional(optional($question)->metadataRow)->estimated_minutes) }}" placeholder="Estimated minutes" class="rounded-xl border-slate-300">
                <input type="text" name="blueprint_topic_label" value="{{ old('blueprint_topic_label', optional(optional($question)->metadataRow)->blueprint_topic_label) }}" placeholder="Blueprint label" class="rounded-xl border-slate-300">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="requires_calculator" value="1" {{ old('requires_calculator', optional(optional($question)->metadataRow)->requires_calculator) ? 'checked' : '' }}> Calculator</label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="requires_diagram" value="1" {{ old('requires_diagram', optional(optional($question)->metadataRow)->requires_diagram) ? 'checked' : '' }}> Diagram</label>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="requires_apparatus" value="1" {{ old('requires_apparatus', optional(optional($question)->metadataRow)->requires_apparatus) ? 'checked' : '' }}> Apparatus required</label>
            @if($question)
                <input type="text" name="change_summary" value="{{ old('change_summary') }}" placeholder="Change summary for new version" class="w-full rounded-xl border-slate-300">
            @endif
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
                <h2 class="font-semibold text-slate-900">Options / Matching Items</h2>
                @for($i = 0; $i < 6; $i++)
                    <div class="grid gap-3 md:grid-cols-[0.2fr_1fr_0.2fr]">
                        <input type="text" name="options[{{ $i }}][option_label]" value="{{ old("options.$i.option_label", optional(optional($question)->options[$i] ?? null)->option_label) }}" placeholder="A" class="rounded-xl border-slate-300">
                        <input type="text" name="options[{{ $i }}][option_text]" value="{{ old("options.$i.option_text", optional(optional($question)->options[$i] ?? null)->option_text) }}" placeholder="Option or item text" class="rounded-xl border-slate-300">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="options[{{ $i }}][is_correct]" value="1" {{ old("options.$i.is_correct", optional(optional($question)->options[$i] ?? null)->is_correct) ? 'checked' : '' }}> Correct</label>
                    </div>
                @endfor
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-4">
                <h2 class="font-semibold text-slate-900">Attachments</h2>
                <input type="file" name="attachments[]" multiple class="mt-3 block w-full text-sm text-slate-600">
                @if($question && $question->attachments->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($question->attachments as $attachment)
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-700">{{ $attachment->caption ?: $attachment->file_path }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
            @if($question)
                <div class="bg-white border border-slate-200 rounded-2xl p-4">
                    <h2 class="font-semibold text-slate-900">Version History</h2>
                    <div class="mt-3 space-y-2">
                        @foreach($question->versions as $version)
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 text-sm text-slate-700">v{{ $version->version_no }} · {{ $version->change_summary ?: 'No summary' }} · {{ $version->created_at?->format('Y-m-d H:i') }}</div>
                        @endforeach
                    </div>
                </div>
            @endif
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">{{ $question ? 'Update Question' : 'Create Question' }}</button>
        </form>
    </div>
</div>
