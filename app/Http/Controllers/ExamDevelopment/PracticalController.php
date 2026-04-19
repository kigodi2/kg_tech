<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Services\ExamDevelopment\PracticalPaperService;
use Illuminate\Http\Request;

class PracticalController extends Controller
{
    public function __construct(
        protected PracticalPaperService $practicalPaperService
    ) {
    }

    public function show(ExamProjectPaper $paper)
    {
        $paper->load(['project.subject', 'apparatusLists.items', 'confidentialInstructions']);

        return view('exam-development.practical.show', ['paper' => $paper]);
    }

    public function update(Request $request, ExamProjectPaper $paper)
    {
        $validated = $request->validate([
            'apparatus_lists.*.title' => ['nullable', 'string', 'max:255'],
            'apparatus_lists.*.issued_before_days' => ['nullable', 'integer', 'min:0'],
            'apparatus_lists.*.notes' => ['nullable', 'string'],
            'apparatus_lists.*.items.*.item_name' => ['nullable', 'string', 'max:255'],
            'apparatus_lists.*.items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'apparatus_lists.*.items.*.unit' => ['nullable', 'string', 'max:50'],
            'apparatus_lists.*.items.*.remarks' => ['nullable', 'string', 'max:255'],
            'confidential_instructions.*.release_hours_before' => ['nullable', 'integer', 'min:0'],
            'confidential_instructions.*.instruction_text' => ['nullable', 'string'],
        ]);

        $apparatusLists = collect($validated['apparatus_lists'] ?? [])
            ->filter(fn ($row) => filled($row['title'] ?? null))
            ->values()
            ->all();

        $confidentialInstructions = collect($validated['confidential_instructions'] ?? [])
            ->filter(fn ($row) => filled($row['instruction_text'] ?? null))
            ->values()
            ->all();

        $this->practicalPaperService->saveSetup($paper, [
            'apparatus_lists' => $apparatusLists,
            'confidential_instructions' => $confidentialInstructions,
        ]);

        return back()->with('success', 'Practical setup saved.');
    }
}
