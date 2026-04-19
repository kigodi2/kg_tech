<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\SubjectBlueprint;
use App\Models\ExamDevelopment\SubjectFormat;
use App\Models\ExamDevelopment\SubjectFormatPaper;
use App\Models\ExamDevelopment\SubjectFormatSection;
use App\Services\ExamDevelopment\AuditLogService;
use App\Services\ExamDevelopment\FormatImportService;
use App\Models\ExamType;
use App\Models\Subject;
use Illuminate\Http\Request;

class FormatController extends Controller
{
    public function __construct(
        protected FormatImportService $formatImportService,
        protected AuditLogService $auditLogService
    ) {
    }

    public function index()
    {
        return view('exam-development.formats.index', [
            'formats' => SubjectFormat::query()->with(['examType', 'subject'])->latest()->paginate(12),
            'examTypes' => ExamType::query()->active()->orderBy('name')->get(),
            'subjects' => Subject::query()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'format_name' => ['required', 'string', 'max:255'],
            'format_code' => ['nullable', 'string', 'max:100'],
            'version_year' => ['nullable', 'string', 'max:20'],
            'candidate_scope' => ['nullable', 'string', 'max:150'],
            'total_papers' => ['required', 'integer', 'min:1', 'max:6'],
            'general_instructions' => ['nullable', 'string'],
            'general_objectives_text' => ['nullable', 'string'],
            'general_competencies_text' => ['nullable', 'string'],
            'administrative_notes' => ['nullable', 'string'],
            'source_reference' => ['nullable', 'string', 'max:255'],
        ]);

        $format = $this->formatImportService->createOrUpdate([
            ...$validated,
            'is_active' => true,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'papers' => [],
            'notes' => [],
        ]);

        $this->auditLogService->record('format-created', SubjectFormat::class, $format->id, null, $format->toArray());

        return redirect()->route('exam-development.formats.show', $format)->with('success', 'Subject format created.');
    }

    public function show(SubjectFormat $format)
    {
        $format->load(['examType', 'subject', 'papers.sections.rules', 'papers.blueprints.topics', 'notes']);

        return view('exam-development.formats.show', ['format' => $format]);
    }

    public function update(Request $request, SubjectFormat $format)
    {
        $validated = $request->validate([
            'format_name' => ['required', 'string', 'max:255'],
            'format_code' => ['nullable', 'string', 'max:100'],
            'version_year' => ['nullable', 'string', 'max:20'],
            'candidate_scope' => ['nullable', 'string', 'max:150'],
            'total_papers' => ['required', 'integer', 'min:1', 'max:6'],
            'general_instructions' => ['nullable', 'string'],
            'general_objectives_text' => ['nullable', 'string'],
            'general_competencies_text' => ['nullable', 'string'],
            'administrative_notes' => ['nullable', 'string'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $format->update([
            ...$validated,
            'updated_by' => auth()->id(),
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $this->auditLogService->record('format-updated', SubjectFormat::class, $format->id, null, $format->fresh()->toArray());

        return back()->with('success', 'Format details updated.');
    }

    public function storePaper(Request $request, SubjectFormat $format)
    {
        $validated = $request->validate([
            'paper_code' => ['required', 'string', 'max:50'],
            'paper_no' => ['required', 'integer', 'min:1'],
            'paper_name' => ['required', 'string', 'max:255'],
            'paper_type' => ['required', 'string', 'max:50'],
            'duration_minutes' => ['required', 'integer', 'min:0'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'questions_total' => ['nullable', 'integer', 'min:0'],
            'questions_to_answer' => ['nullable', 'integer', 'min:0'],
            'has_sections' => ['nullable', 'boolean'],
            'candidate_notes' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $format->papers()->create([
            ...$validated,
            'has_sections' => (bool) ($validated['has_sections'] ?? false),
            'display_order' => $format->papers()->max('display_order') + 1,
        ]);

        return back()->with('success', 'Paper added to the format.');
    }

    public function storeSection(Request $request, SubjectFormatPaper $paper)
    {
        $validated = $request->validate([
            'section_code' => ['nullable', 'string', 'max:50'],
            'section_name' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'number_of_questions' => ['required', 'integer', 'min:0'],
            'questions_to_answer' => ['nullable', 'integer', 'min:0'],
            'is_all_compulsory' => ['nullable', 'boolean'],
        ]);

        $paper->sections()->create([
            ...$validated,
            'is_all_compulsory' => (bool) ($validated['is_all_compulsory'] ?? false),
            'display_order' => $paper->sections()->max('display_order') + 1,
        ]);

        return back()->with('success', 'Section added.');
    }

    public function storeRule(Request $request, SubjectFormatSection $section)
    {
        $validated = $request->validate([
            'question_no_from' => ['nullable', 'integer', 'min:1'],
            'question_no_to' => ['nullable', 'integer', 'min:1'],
            'question_type' => ['required', 'string', 'max:50'],
            'items_per_question' => ['nullable', 'integer', 'min:1'],
            'marks_per_item' => ['nullable', 'numeric', 'min:0'],
            'marks_per_question' => ['nullable', 'numeric', 'min:0'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'answer_mode' => ['required', 'string', 'max:50'],
            'is_compulsory' => ['nullable', 'boolean'],
            'choice_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $section->rules()->create([
            ...$validated,
            'is_compulsory' => (bool) ($validated['is_compulsory'] ?? false),
            'display_order' => $section->rules()->max('display_order') + 1,
        ]);

        return back()->with('success', 'Rule added.');
    }

    public function storeNote(Request $request, SubjectFormat $format)
    {
        $validated = $request->validate([
            'subject_format_paper_id' => ['nullable', 'exists:subject_format_papers,id'],
            'note_type' => ['required', 'string', 'max:60'],
            'note_text' => ['required', 'string'],
            'applies_to_candidates' => ['nullable', 'boolean'],
            'applies_to_admins' => ['nullable', 'boolean'],
        ]);

        $format->notes()->create([
            ...$validated,
            'applies_to_candidates' => (bool) ($validated['applies_to_candidates'] ?? true),
            'applies_to_admins' => (bool) ($validated['applies_to_admins'] ?? false),
            'display_order' => $format->notes()->max('display_order') + 1,
        ]);

        return back()->with('success', 'Note added.');
    }

    public function storeBlueprint(Request $request, SubjectFormatPaper $paper)
    {
        $validated = $request->validate([
            'blueprint_name' => ['required', 'string', 'max:255'],
            'total_items' => ['required', 'integer', 'min:0'],
            'total_weight' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paper->blueprints()->create([
            ...$validated,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('success', 'Blueprint created.');
    }

    public function storeBlueprintTopic(Request $request, SubjectBlueprint $blueprint)
    {
        $validated = $request->validate([
            'topic_name' => ['required', 'string', 'max:255'],
            'items_count' => ['required', 'integer', 'min:0'],
            'percentage_weight' => ['required', 'numeric', 'min:0'],
            'remembering_weight' => ['nullable', 'numeric', 'min:0'],
            'understanding_weight' => ['nullable', 'numeric', 'min:0'],
            'applying_weight' => ['nullable', 'numeric', 'min:0'],
            'analysing_weight' => ['nullable', 'numeric', 'min:0'],
            'evaluating_weight' => ['nullable', 'numeric', 'min:0'],
            'creating_weight' => ['nullable', 'numeric', 'min:0'],
        ]);

        $blueprint->topics()->create([
            ...$validated,
            'display_order' => $blueprint->topics()->max('display_order') + 1,
        ]);

        return back()->with('success', 'Blueprint topic added.');
    }
}
