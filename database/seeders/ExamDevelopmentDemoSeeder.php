<?php

namespace Database\Seeders;

use App\Models\ExamType;
use App\Models\ExamDevelopment\SubjectFormat;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ExamDevelopmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $csee = ExamType::query()->firstOrCreate(
            ['code' => 'CSEE'],
            ['name' => 'Certificate of Secondary Education Examination', 'description' => 'O-Level examination', 'education_level' => 'SECONDARY', 'level' => 'SECONDARY', 'is_active' => true]
        );

        $civics = $this->seedSubject($csee, 'CIV', 'Civics', 100, false);
        $math = $this->seedSubject($csee, 'BAM', 'Basic Mathematics', 100, false);
        $physics = $this->seedSubject($csee, 'PHY', 'Physics', 150, true, 2);

        $this->seedCivicsFormat($csee, $civics);
        $this->seedBasicMathFormat($csee, $math);
        $this->seedPhysicsFormat($csee, $physics);
    }

    protected function seedSubject(ExamType $examType, string $code, string $name, int $maxMarks, bool $hasPractical, int $writtenPapers = 1): Subject
    {
        return Subject::query()->updateOrCreate(
            ['exam_type_id' => $examType->id, 'code' => $code],
            [
                'name' => $name,
                'short_name' => $name,
                'category' => 'SCIENCE',
                'written_papers' => $writtenPapers,
                'has_practical' => $hasPractical,
                'has_project' => false,
                'subject_group_label' => 'Exam Development Demo',
                'paper_pattern_label' => 'Official format governed',
                'max_marks' => $maxMarks,
                'is_active' => true,
            ]
        );
    }

    protected function seedCivicsFormat(ExamType $examType, Subject $subject): void
    {
        $format = SubjectFormat::query()->updateOrCreate(
            ['exam_type_id' => $examType->id, 'subject_id' => $subject->id, 'format_name' => 'CSEE Civics Official Format'],
            ['format_code' => 'CSEE-CIV-OF', 'version_year' => '2026', 'total_papers' => 1, 'is_active' => true]
        );

        $paper = $format->papers()->firstOrCreate([
            'paper_code' => 'P1',
        ], [
            'paper_no' => 1,
            'paper_name' => 'Civics',
            'paper_type' => 'mixed',
            'duration_minutes' => 150,
            'total_marks' => 100,
            'questions_total' => 11,
            'questions_to_answer' => 10,
            'has_sections' => true,
            'display_order' => 1,
        ]);

        $paper->sections()->delete();
        $sectionA = $paper->sections()->create(['section_code' => 'A', 'section_name' => 'Objective and Matching', 'total_marks' => 16, 'number_of_questions' => 2, 'questions_to_answer' => 2, 'is_all_compulsory' => true, 'display_order' => 1]);
        $sectionA->rules()->createMany([
            ['question_no_from' => 1, 'question_no_to' => 1, 'question_type' => 'mcq', 'items_per_question' => 10, 'marks_per_item' => 1, 'marks_per_question' => 10, 'total_marks' => 10, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1],
            ['question_no_from' => 2, 'question_no_to' => 2, 'question_type' => 'matching', 'items_per_question' => 6, 'marks_per_item' => 1, 'marks_per_question' => 6, 'total_marks' => 6, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 2],
        ]);

        $sectionB = $paper->sections()->create(['section_code' => 'B', 'section_name' => 'Short Answer', 'total_marks' => 54, 'number_of_questions' => 6, 'questions_to_answer' => 6, 'is_all_compulsory' => true, 'display_order' => 2]);
        $sectionB->rules()->create(['question_no_from' => 3, 'question_no_to' => 8, 'question_type' => 'short_answer', 'marks_per_question' => 9, 'total_marks' => 54, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1]);

        $sectionC = $paper->sections()->create(['section_code' => 'C', 'section_name' => 'Essay', 'total_marks' => 30, 'number_of_questions' => 3, 'questions_to_answer' => 2, 'is_all_compulsory' => false, 'display_order' => 3]);
        $sectionC->rules()->create(['question_no_from' => 9, 'question_no_to' => 11, 'question_type' => 'essay', 'marks_per_question' => 15, 'total_marks' => 45, 'answer_mode' => 'fixed_count', 'is_compulsory' => false, 'choice_count' => 2, 'display_order' => 1]);
    }

    protected function seedBasicMathFormat(ExamType $examType, Subject $subject): void
    {
        $format = SubjectFormat::query()->updateOrCreate(
            ['exam_type_id' => $examType->id, 'subject_id' => $subject->id, 'format_name' => 'CSEE Basic Mathematics Official Format'],
            ['format_code' => 'CSEE-BAM-OF', 'version_year' => '2026', 'total_papers' => 1, 'is_active' => true]
        );

        $paper = $format->papers()->firstOrCreate(['paper_code' => 'P1'], [
            'paper_no' => 1,
            'paper_name' => 'Basic Mathematics',
            'paper_type' => 'mixed',
            'duration_minutes' => 180,
            'total_marks' => 100,
            'questions_total' => 14,
            'questions_to_answer' => 14,
            'has_sections' => true,
            'display_order' => 1,
        ]);

        $paper->sections()->delete();
        $sectionA = $paper->sections()->create(['section_code' => 'A', 'section_name' => 'Short Answer', 'total_marks' => 60, 'number_of_questions' => 10, 'questions_to_answer' => 10, 'is_all_compulsory' => true, 'display_order' => 1]);
        $sectionA->rules()->create(['question_no_from' => 1, 'question_no_to' => 10, 'question_type' => 'short_answer', 'marks_per_question' => 6, 'total_marks' => 60, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1]);

        $sectionB = $paper->sections()->create(['section_code' => 'B', 'section_name' => 'Structured', 'total_marks' => 40, 'number_of_questions' => 4, 'questions_to_answer' => 4, 'is_all_compulsory' => true, 'display_order' => 2]);
        $sectionB->rules()->create(['question_no_from' => 11, 'question_no_to' => 14, 'question_type' => 'structured', 'marks_per_question' => 10, 'total_marks' => 40, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1]);
    }

    protected function seedPhysicsFormat(ExamType $examType, Subject $subject): void
    {
        $format = SubjectFormat::query()->updateOrCreate(
            ['exam_type_id' => $examType->id, 'subject_id' => $subject->id, 'format_name' => 'CSEE Physics Official Format'],
            ['format_code' => 'CSEE-PHY-OF', 'version_year' => '2026', 'total_papers' => 2, 'is_active' => true]
        );

        $theory = $format->papers()->updateOrCreate(['paper_code' => 'P1'], [
            'paper_no' => 1,
            'paper_name' => 'Physics Theory',
            'paper_type' => 'theory',
            'duration_minutes' => 150,
            'total_marks' => 100,
            'questions_total' => 10,
            'questions_to_answer' => 8,
            'has_sections' => true,
            'display_order' => 1,
        ]);
        $theory->sections()->delete();
        $theorySectionA = $theory->sections()->create(['section_code' => 'A', 'section_name' => 'Short and Structured', 'total_marks' => 70, 'number_of_questions' => 8, 'questions_to_answer' => 8, 'is_all_compulsory' => true, 'display_order' => 1]);
        $theorySectionA->rules()->create(['question_no_from' => 1, 'question_no_to' => 8, 'question_type' => 'structured', 'marks_per_question' => 8.75, 'total_marks' => 70, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1]);
        $theorySectionB = $theory->sections()->create(['section_code' => 'B', 'section_name' => 'Essay', 'total_marks' => 30, 'number_of_questions' => 2, 'questions_to_answer' => 2, 'is_all_compulsory' => true, 'display_order' => 2]);
        $theorySectionB->rules()->create(['question_no_from' => 9, 'question_no_to' => 10, 'question_type' => 'essay', 'marks_per_question' => 15, 'total_marks' => 30, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1]);

        $practical = $format->papers()->updateOrCreate(['paper_code' => 'P2'], [
            'paper_no' => 2,
            'paper_name' => 'Physics Practical',
            'paper_type' => 'practical',
            'duration_minutes' => 150,
            'total_marks' => 50,
            'questions_total' => 2,
            'questions_to_answer' => 2,
            'has_sections' => false,
            'display_order' => 2,
        ]);
        $practical->sections()->delete();
        $practicalSection = $practical->sections()->create(['section_code' => 'P', 'section_name' => 'Practical', 'total_marks' => 50, 'number_of_questions' => 2, 'questions_to_answer' => 2, 'is_all_compulsory' => true, 'display_order' => 1]);
        $practicalSection->rules()->create(['question_no_from' => 1, 'question_no_to' => 2, 'question_type' => 'practical', 'marks_per_question' => 25, 'total_marks' => 50, 'answer_mode' => 'all', 'is_compulsory' => true, 'display_order' => 1]);

        $blueprint = $theory->blueprints()->updateOrCreate(['blueprint_name' => 'Physics Theory Coverage'], ['total_items' => 10, 'total_weight' => 100, 'is_active' => true]);
        $blueprint->topics()->delete();
        $blueprint->topics()->createMany([
            ['topic_name' => 'Mechanics', 'items_count' => 3, 'percentage_weight' => 30, 'display_order' => 1],
            ['topic_name' => 'Electricity and Magnetism', 'items_count' => 3, 'percentage_weight' => 30, 'display_order' => 2],
            ['topic_name' => 'Heat and Waves', 'items_count' => 2, 'percentage_weight' => 20, 'display_order' => 3],
            ['topic_name' => 'Modern Physics', 'items_count' => 2, 'percentage_weight' => 20, 'display_order' => 4],
        ]);
    }
}
