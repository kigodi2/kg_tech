<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Models\ExamDevelopment\ExamProjectSlot;
use App\Models\ExamDevelopment\Question;
use App\Models\ExamDevelopment\SubjectFormat;
use App\Services\ExamDevelopment\BlueprintCoverageService;
use App\Services\ExamDevelopment\ExamProjectCreationService;
use App\Services\ExamDevelopment\PaperValidationService;
use App\Services\ExamDevelopment\QuestionAssignmentService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(
        protected ExamProjectCreationService $projectCreationService,
        protected QuestionAssignmentService $questionAssignmentService,
        protected PaperValidationService $paperValidationService,
        protected BlueprintCoverageService $blueprintCoverageService
    ) {
    }

    public function index()
    {
        return view('exam-development.projects.index', [
            'projects' => ExamProject::query()->with(['subject', 'examType', 'format'])->latest()->paginate(12),
        ]);
    }

    public function create()
    {
        return view('exam-development.projects.create', [
            'formats' => SubjectFormat::query()->with(['subject', 'examType'])->where('is_active', true)->orderBy('format_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_format_id' => ['required', 'exists:subject_formats,id'],
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'exam_year' => ['required', 'string', 'max:20'],
            'project_code' => ['nullable', 'string', 'max:100'],
            'project_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project = $this->projectCreationService->create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('exam-development.projects.show', $project)->with('success', 'Project created and slots generated from the official format.');
    }

    public function show(ExamProject $project)
    {
        $project->load(['examType', 'subject', 'format.papers.sections.rules', 'papers.sections.slots.assignedQuestion.markingSchemes']);

        return view('exam-development.projects.show', [
            'project' => $project,
            'validation' => $this->paperValidationService->validateProject($project),
        ]);
    }

    public function validateProject(ExamProject $project)
    {
        return back()->with('validation_report', $this->paperValidationService->validateProject($project));
    }

    public function paperBuilder(ExamProjectPaper $paper)
    {
        $paper->load(['project.subject', 'sections.slots.assignedQuestion', 'formatPaper.blueprints.topics']);

        return view('exam-development.projects.paper-builder', [
            'paper' => $paper,
            'approvedQuestions' => Question::query()
                ->where('subject_id', $paper->project->subject_id)
                ->where('status', Question::STATUS_APPROVED)
                ->orderBy('topic_name')
                ->get(),
            'coverage' => $this->blueprintCoverageService->analysePaper($paper),
        ]);
    }

    public function assignQuestion(Request $request, ExamProjectSlot $slot)
    {
        $validated = $request->validate([
            'question_id' => ['required', 'exists:questions,id'],
            'custom_marks' => ['nullable', 'numeric', 'min:0'],
            'custom_instructions' => ['nullable', 'string'],
        ]);

        $question = Question::query()->findOrFail($validated['question_id']);
        $this->questionAssignmentService->assign($slot, $question, [
            'custom_marks' => $validated['custom_marks'] ?? null,
            'custom_instructions' => $validated['custom_instructions'] ?? null,
            'inserted_by' => auth()->id(),
        ]);

        return back()->with('success', 'Question assigned to slot.');
    }
}
