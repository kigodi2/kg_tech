<?php

namespace App\Services\ExamDevelopment;

use App\Models\ExamDevelopment\SubjectBlueprint;
use App\Models\ExamDevelopment\SubjectFormat;
use App\Models\ExamDevelopment\SubjectFormatNote;
use App\Models\ExamDevelopment\SubjectFormatPaper;
use App\Models\ExamDevelopment\SubjectFormatQuestionRule;
use App\Models\ExamDevelopment\SubjectFormatSection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class FormatImportService
{
    public function createOrUpdate(array $payload, ?SubjectFormat $format = null): SubjectFormat
    {
        return DB::transaction(function () use ($payload, $format) {
            $formatData = Arr::only($payload, [
                'exam_type_id',
                'subject_id',
                'format_code',
                'format_name',
                'version_year',
                'candidate_scope',
                'total_papers',
                'general_objectives_text',
                'general_competencies_text',
                'general_instructions',
                'administrative_notes',
                'source_reference',
                'is_active',
                'created_by',
                'updated_by',
            ]);

            $format = $format
                ? tap($format)->update($formatData)
                : SubjectFormat::query()->create($formatData);

            if ($format->exists) {
                $format->papers()->delete();
                $format->notes()->delete();
            }

            foreach ($payload['papers'] ?? [] as $paperIndex => $paperData) {
                $paper = $format->papers()->create([
                    ...Arr::only($paperData, [
                        'paper_code',
                        'paper_no',
                        'paper_name',
                        'paper_type',
                        'duration_minutes',
                        'total_marks',
                        'questions_total',
                        'questions_to_answer',
                        'has_sections',
                        'candidate_notes',
                        'admin_notes',
                    ]),
                    'display_order' => $paperData['display_order'] ?? ($paperIndex + 1),
                ]);

                foreach ($paperData['sections'] ?? [] as $sectionIndex => $sectionData) {
                    $section = $paper->sections()->create([
                        ...Arr::only($sectionData, [
                            'section_code',
                            'section_name',
                            'instructions',
                            'total_marks',
                            'number_of_questions',
                            'questions_to_answer',
                            'is_all_compulsory',
                        ]),
                        'display_order' => $sectionData['display_order'] ?? ($sectionIndex + 1),
                    ]);

                    foreach ($sectionData['rules'] ?? [] as $ruleIndex => $ruleData) {
                        $section->rules()->create([
                            ...Arr::only($ruleData, [
                                'question_no_from',
                                'question_no_to',
                                'question_type',
                                'items_per_question',
                                'marks_per_item',
                                'marks_per_question',
                                'total_marks',
                                'answer_mode',
                                'is_compulsory',
                                'choice_count',
                            ]),
                            'display_order' => $ruleData['display_order'] ?? ($ruleIndex + 1),
                        ]);
                    }
                }

                foreach ($paperData['blueprints'] ?? [] as $blueprintData) {
                    $blueprint = $paper->blueprints()->create(Arr::only($blueprintData, [
                        'blueprint_name',
                        'total_items',
                        'total_weight',
                        'is_active',
                    ]));

                    foreach ($blueprintData['topics'] ?? [] as $topicIndex => $topicData) {
                        $blueprint->topics()->create([
                            ...Arr::only($topicData, [
                                'topic_name',
                                'items_count',
                                'percentage_weight',
                                'remembering_weight',
                                'understanding_weight',
                                'applying_weight',
                                'analysing_weight',
                                'evaluating_weight',
                                'creating_weight',
                            ]),
                            'display_order' => $topicData['display_order'] ?? ($topicIndex + 1),
                        ]);
                    }
                }

                foreach ($paperData['notes'] ?? [] as $noteIndex => $noteData) {
                    $paper->notes()->create([
                        ...Arr::only($noteData, [
                            'subject_format_id',
                            'note_type',
                            'note_text',
                            'applies_to_candidates',
                            'applies_to_admins',
                        ]),
                        'subject_format_id' => $format->id,
                        'display_order' => $noteData['display_order'] ?? ($noteIndex + 1),
                    ]);
                }
            }

            foreach ($payload['notes'] ?? [] as $noteIndex => $noteData) {
                $format->notes()->create([
                    ...Arr::only($noteData, [
                        'subject_format_paper_id',
                        'note_type',
                        'note_text',
                        'applies_to_candidates',
                        'applies_to_admins',
                    ]),
                    'display_order' => $noteData['display_order'] ?? ($noteIndex + 1),
                ]);
            }

            return $format->fresh(['subject', 'examType', 'papers.sections.rules', 'papers.blueprints.topics', 'notes']);
        });
    }
}
