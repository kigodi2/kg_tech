<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Models\ExamDevelopment\Question;
use App\Models\ExamDevelopment\ReviewComment;
use App\Services\ExamDevelopment\ApprovalWorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ReviewApprovalController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $workflowService
    ) {
    }

    public function show(ExamProject $project)
    {
        $project->load(['papers.sections.slots.assignedQuestion.markingSchemes', 'papers.reviewComments', 'papers.confidentialInstructions', 'papers.apparatusLists.items']);

        return view('exam-development.review.show', ['project' => $project]);
    }

    public function storeComment(Request $request, ExamProject $project)
    {
        $validated = $request->validate([
            'question_id' => ['nullable', 'exists:questions,id'],
            'exam_project_paper_id' => ['nullable', 'exists:exam_project_papers,id'],
            'comment_type' => ['nullable', 'string', 'max:60'],
            'comment_text' => ['required', 'string'],
        ]);

        ReviewComment::query()->create([
            ...$validated,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Review comment recorded.');
    }

    public function transitionProject(Request $request, ExamProject $project)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'comment' => ['nullable', 'string'],
        ]);

        $this->workflowService->transition($project, $validated['status'], $validated['comment'] ?? null);

        return back()->with('success', 'Project status updated.');
    }

    public function transitionPaper(Request $request, ExamProjectPaper $paper)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'comment' => ['nullable', 'string'],
        ]);

        $this->workflowService->transition($paper, $validated['status'], $validated['comment'] ?? null);

        return back()->with('success', 'Paper status updated.');
    }

    public function transitionQuestion(Request $request, Question $question)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'comment' => ['nullable', 'string'],
        ]);

        $this->workflowService->transition($question, $validated['status'], $validated['comment'] ?? null);

        return back()->with('success', 'Question status updated.');
    }
}
