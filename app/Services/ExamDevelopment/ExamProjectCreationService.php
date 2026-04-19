<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\ExamProjectPaper;
use App\Models\ExamDevelopment\ExamProjectSection;
use App\Models\ExamDevelopment\SubjectFormat;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ExamProjectCreationService
{
    public function __construct(
        protected PaperSlotGenerationService $slotGenerationService
    ) {
    }

    public function create(array $payload): ExamProject
    {
        return DB::transaction(function () use ($payload) {
            /** @var SubjectFormat $format */
            $format = SubjectFormat::query()
                ->with(['papers.sections.rules'])
                ->whereKey($payload['subject_format_id'])
                ->where('is_active', true)
                ->firstOrFail();

            $project = ExamProject::query()->create([
                ...Arr::only($payload, [
                    'exam_type_id',
                    'subject_id',
                    'subject_format_id',
                    'exam_year',
                    'project_code',
                    'project_name',
                    'description',
                    'created_by',
                ]),
                'status' => ExamProject::STATUS_DRAFT,
            ]);

            foreach ($format->papers as $paper) {
                $projectPaper = $project->papers()->create([
                    'subject_format_paper_id' => $paper->id,
                    'paper_code' => $paper->paper_code,
                    'paper_name' => $paper->paper_name,
                    'paper_type' => $paper->paper_type,
                    'duration_minutes' => $paper->duration_minutes,
                    'total_marks' => $paper->total_marks,
                    'status' => ExamProjectPaper::STATUS_DRAFT,
                    'display_order' => $paper->display_order,
                ]);

                foreach ($paper->sections as $section) {
                    $projectSection = $projectPaper->sections()->create([
                        'subject_format_section_id' => $section->id,
                        'section_code' => $section->section_code,
                        'section_name' => $section->section_name,
                        'instructions' => $section->instructions,
                        'total_marks' => $section->total_marks,
                        'number_of_questions' => $section->number_of_questions,
                        'questions_to_answer' => $section->questions_to_answer,
                        'is_all_compulsory' => $section->is_all_compulsory,
                        'display_order' => $section->display_order,
                    ]);

                    $this->slotGenerationService->generateForSection($projectSection);
                }
            }

            return $project->fresh(['papers.sections.slots', 'format.subject', 'format.examType']);
        });
    }
}
