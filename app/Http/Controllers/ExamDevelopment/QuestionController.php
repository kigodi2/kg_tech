<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\Question;
use App\Models\ExamType;
use App\Models\Subject;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    public function index()
    {
        return view('exam-development.questions.index', [
            'questions' => Question::query()->with(['subject', 'examType'])->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return view('exam-development.questions.create', [
            'examTypes' => ExamType::query()->active()->orderBy('name')->get(),
            'subjects' => Subject::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'paper_type' => ['nullable', 'string', 'max:50'],
            'topic_name' => ['required', 'string', 'max:255'],
            'subtopic_name' => ['nullable', 'string', 'max:255'],
            'competency_code' => ['nullable', 'string', 'max:100'],
            'difficulty_level' => ['nullable', 'string', 'max:50'],
            'question_type' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'question_text' => ['required', 'string'],
            'marks' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'requires_calculator' => ['nullable', 'boolean'],
            'requires_diagram' => ['nullable', 'boolean'],
            'requires_apparatus' => ['nullable', 'boolean'],
            'blueprint_topic_label' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['nullable', 'file', 'max:5120'],
            'options.*.option_label' => ['nullable', 'string', 'max:20'],
            'options.*.option_text' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ]);

        $question = Question::query()->create([
            ...collect($validated)->except(['estimated_minutes', 'requires_calculator', 'requires_diagram', 'requires_apparatus', 'blueprint_topic_label', 'options'])->all(),
            'author_id' => auth()->id(),
        ]);

        $question->versions()->create([
            'version_no' => 1,
            'question_text' => $question->question_text,
            'change_summary' => 'Initial version',
            'changed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $question->metadataRow()->create([
            'estimated_minutes' => $validated['estimated_minutes'] ?? null,
            'requires_calculator' => (bool) ($validated['requires_calculator'] ?? false),
            'requires_diagram' => (bool) ($validated['requires_diagram'] ?? false),
            'requires_apparatus' => (bool) ($validated['requires_apparatus'] ?? false),
            'blueprint_topic_label' => $validated['blueprint_topic_label'] ?? null,
        ]);

        foreach ($validated['options'] ?? [] as $index => $optionData) {
            if (!filled($optionData['option_text'] ?? null)) {
                continue;
            }

            $question->options()->create([
                'option_label' => $optionData['option_label'] ?? null,
                'option_text' => $optionData['option_text'],
                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                'display_order' => $index + 1,
            ]);
        }

        foreach ($request->file('attachments', []) as $index => $attachment) {
            if (!$attachment) {
                continue;
            }

            $path = $attachment->store('exam-development/questions', 'public');
            $question->attachments()->create([
                'file_path' => $path,
                'file_type' => $attachment->getMimeType(),
                'caption' => $attachment->getClientOriginalName(),
                'display_order' => $index + 1,
            ]);
        }

        return redirect()->route('exam-development.questions.edit', $question)->with('success', 'Question created.');
    }

    public function edit(Question $question)
    {
        $question->load(['options', 'attachments', 'metadataRow', 'versions', 'subject', 'examType']);

        return view('exam-development.questions.edit', [
            'question' => $question,
            'examTypes' => ExamType::query()->active()->orderBy('name')->get(),
            'subjects' => Subject::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'paper_type' => ['nullable', 'string', 'max:50'],
            'topic_name' => ['required', 'string', 'max:255'],
            'subtopic_name' => ['nullable', 'string', 'max:255'],
            'competency_code' => ['nullable', 'string', 'max:100'],
            'difficulty_level' => ['nullable', 'string', 'max:50'],
            'question_type' => ['required', 'string', 'max:50'],
            'title' => ['nullable', 'string', 'max:255'],
            'question_text' => ['required', 'string'],
            'marks' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'estimated_minutes' => ['nullable', 'integer', 'min:0'],
            'requires_calculator' => ['nullable', 'boolean'],
            'requires_diagram' => ['nullable', 'boolean'],
            'requires_apparatus' => ['nullable', 'boolean'],
            'blueprint_topic_label' => ['nullable', 'string', 'max:255'],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'attachments.*' => ['nullable', 'file', 'max:5120'],
            'options.*.option_label' => ['nullable', 'string', 'max:20'],
            'options.*.option_text' => ['nullable', 'string'],
            'options.*.is_correct' => ['nullable', 'boolean'],
        ]);

        $question->update(collect($validated)->except(['estimated_minutes', 'requires_calculator', 'requires_diagram', 'requires_apparatus', 'blueprint_topic_label', 'change_summary', 'options'])->all());

        $question->metadataRow()->updateOrCreate(
            ['question_id' => $question->id],
            [
                'estimated_minutes' => $validated['estimated_minutes'] ?? null,
                'requires_calculator' => (bool) ($validated['requires_calculator'] ?? false),
                'requires_diagram' => (bool) ($validated['requires_diagram'] ?? false),
                'requires_apparatus' => (bool) ($validated['requires_apparatus'] ?? false),
                'blueprint_topic_label' => $validated['blueprint_topic_label'] ?? null,
            ]
        );

        $question->options()->delete();
        foreach ($validated['options'] ?? [] as $index => $optionData) {
            if (!filled($optionData['option_text'] ?? null)) {
                continue;
            }

            $question->options()->create([
                'option_label' => $optionData['option_label'] ?? null,
                'option_text' => $optionData['option_text'],
                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                'display_order' => $index + 1,
            ]);
        }

        foreach ($request->file('attachments', []) as $index => $attachment) {
            if (!$attachment) {
                continue;
            }

            $path = $attachment->store('exam-development/questions', 'public');
            $question->attachments()->create([
                'file_path' => $path,
                'file_type' => $attachment->getMimeType(),
                'caption' => $attachment->getClientOriginalName(),
                'display_order' => $question->attachments()->max('display_order') + $index + 1,
            ]);
        }

        $question->versions()->create([
            'version_no' => $question->current_version_no + 1,
            'question_text' => $question->question_text,
            'change_summary' => $validated['change_summary'] ?? 'Question updated',
            'changed_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $question->increment('current_version_no');

        return back()->with('success', 'Question updated.');
    }
}
