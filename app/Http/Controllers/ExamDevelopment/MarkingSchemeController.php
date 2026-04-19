<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\Question;
use App\Services\ExamDevelopment\MarkingSchemeValidationService;
use Illuminate\Http\Request;

class MarkingSchemeController extends Controller
{
    public function __construct(
        protected MarkingSchemeValidationService $validationService
    ) {
    }

    public function show(Question $question)
    {
        $question->load(['markingSchemes.items']);

        return view('exam-development.marking-schemes.show', ['question' => $question]);
    }

    public function store(Request $request, Question $question)
    {
        $validated = $request->validate([
            'scheme_type' => ['required', 'string', 'max:60'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'answer_text' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'items.*.item_label' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.marks' => ['nullable', 'numeric', 'min:0'],
        ]);

        $scheme = $question->markingSchemes()->create([
            'scheme_type' => $validated['scheme_type'],
            'total_marks' => $validated['total_marks'],
            'answer_text' => $validated['answer_text'] ?? null,
            'status' => $validated['status'],
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['items'] ?? [] as $index => $item) {
            if (!filled($item['description'] ?? null)) {
                continue;
            }

            $scheme->items()->create([
                'item_label' => $item['item_label'] ?? null,
                'description' => $item['description'],
                'marks' => $item['marks'] ?? 0,
                'display_order' => $index + 1,
            ]);
        }

        $this->validationService->ensureValid($scheme->fresh('items', 'question'));

        return back()->with('success', 'Marking scheme saved.');
    }
}
