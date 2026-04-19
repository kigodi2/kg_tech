<?php

namespace Tests\Unit\Services;

use App\Models\ExamSubmission;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamFormatValidation\ExamTypeFinalReportBuilder;
use App\Services\ExamFormatValidation\NectaFormatRulebook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExamTypeFinalReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_ready_final_report_when_all_subjects_are_compliant_and_approved(): void
    {
        Config::set('csee.official_subjects', [
            ['code' => '011', 'name' => 'CIVICS', 'source_page' => 14],
            ['code' => '012', 'name' => 'HISTORY', 'source_page' => 18],
        ]);

        $examType = ExamType::factory()->csee()->create();
        $examYear = ExamYear::factory()->create(['year_label' => '2026']);
        $user = User::factory()->create();

        $civics = Subject::create([
            'code' => '011',
            'name' => 'Civics',
            'exam_type_id' => $examType->id,
            'is_active' => true,
        ]);

        $history = Subject::create([
            'code' => '012',
            'name' => 'History',
            'exam_type_id' => $examType->id,
            'is_active' => true,
        ]);

        foreach ([$civics, $history] as $subject) {
            ExamSubmission::create([
                'user_id' => $user->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'subject_id' => $subject->id,
                'exam_paper_path' => 'exam-submissions/' . $subject->code . '.pdf',
                'original_filename' => $subject->code . '.pdf',
                'status' => 'approved',
                'validation_results' => [
                    'is_valid' => true,
                    'template_comparison' => [
                        'compliance_score' => 94,
                    ],
                ],
                'submitted_at' => now(),
                'validated_at' => now(),
            ]);
        }

        $report = app(ExamTypeFinalReportBuilder::class)->build($examType, $examYear, $user);

        $this->assertSame('ready_for_official_submission', $report['overall_determination']['state']);
        $this->assertSame(2, $report['summary']['expected_subjects']);
        $this->assertSame(2, $report['summary']['ready_subjects']);
        $this->assertCount(0, $report['outstanding_subjects']);
    }

    public function test_it_marks_missing_and_non_compliant_subjects_in_the_final_report(): void
    {
        Config::set('ftna.official_subjects_general_2022', [
            ['code' => '011', 'name' => 'CIVICS', 'source_page' => 1],
        ]);
        Config::set('ftna.official_subjects', [
            ['code' => '022', 'name' => 'ENGLISH LANGUAGE', 'source_page' => 1],
            ['code' => '033', 'name' => 'BIOLOGY', 'source_page' => 18],
        ]);

        $examType = ExamType::factory()->create([
            'code' => 'FTNA',
            'name' => 'Form Two National Assessment',
            'is_active' => true,
        ]);
        $examYear = ExamYear::factory()->create(['year_label' => '2026']);
        $user = User::factory()->create();

        $english = Subject::create([
            'code' => '022',
            'name' => 'English Language',
            'exam_type_id' => $examType->id,
            'is_active' => true,
        ]);

        ExamSubmission::create([
            'user_id' => $user->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'subject_id' => $english->id,
            'exam_paper_path' => 'exam-submissions/022.pdf',
            'original_filename' => '022.pdf',
            'status' => 'pending',
            'validation_results' => [
                'is_valid' => false,
                'errors' => ['Subject code is missing from the title page.'],
                'template_comparison' => [
                    'compliance_score' => 41,
                ],
            ],
            'submitted_at' => now(),
        ]);

        $report = app(ExamTypeFinalReportBuilder::class)->build($examType, $examYear, $user);

        $this->assertSame('attention_required', $report['overall_determination']['state']);
        $this->assertSame(3, $report['summary']['expected_subjects']);
        $this->assertSame(1, $report['summary']['submitted_subjects']);
        $this->assertSame(2, $report['summary']['missing_subjects']);
        $this->assertSame(1, $report['summary']['attention_required_subjects']);
        $this->assertStringContainsString('Subject code is missing', $report['subjects']->firstWhere('subject_code', '022')['remarks_summary']);
        $this->assertStringContainsString('not yet submitted', strtolower($report['subjects']->firstWhere('subject_code', '033')['remarks_summary']));
        $this->assertStringContainsString('not yet submitted', strtolower($report['subjects']->firstWhere('subject_code', '011')['remarks_summary']));
    }

    public function test_ftna_rulebook_merges_general_and_vocational_subjects_without_duplicates(): void
    {
        Config::set('ftna.official_subjects_general_2022', [
            ['code' => '022', 'name' => 'ENGLISH LANGUAGE'],
            ['code' => '031', 'name' => 'PHYSICS'],
        ]);
        Config::set('ftna.official_subjects', [
            ['code' => '022', 'name' => 'ENGLISH LANGUAGE'],
            ['code' => '403', 'name' => 'ANIMAL HEALTH AND PRODUCTION'],
        ]);

        $subjects = app(NectaFormatRulebook::class)->getOfficialSubjects('FTNA');

        $this->assertSame(['022', '031', '403'], array_column($subjects, 'code'));
    }
}
