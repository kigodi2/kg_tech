<?php

namespace Tests\Feature\MarkEntry;

use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionMarkEntryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_regional_user_can_load_candidate_and_save_draft_in_assigned_region(): void
    {
        [$user, $candidate, $subject, $examYear] = $this->createRegionalQuestionEntryContext();

        $this->actingAs($user)
            ->get(route('mark-entry.csee.questions.show', [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
            ]))
            ->assertOk()
            ->assertSee($candidate->full_name)
            ->assertSee($candidate->candidate_id);

        $this->actingAs($user)
            ->post(route('mark-entry.csee.questions.store'), [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
                'entry_action' => 'draft',
                'scores' => [
                    1 => 55.5,
                ],
            ])
            ->assertRedirect(route('mark-entry.csee.questions.show', [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
            ]));

        $this->assertDatabaseHas('question_mark_entries', [
            'exam_year_id' => $examYear->id,
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'candidate_no' => $candidate->candidate_id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('question_mark_entry_items', [
            'question_no' => 1,
            'score' => 55.5,
        ]);
    }

    public function test_regional_user_cannot_load_candidate_from_another_region(): void
    {
        [$user, $candidate, $subject] = $this->createRegionalQuestionEntryContext();
        $otherRegion = Region::create(['code' => 'R2', 'name' => 'Other Region', 'is_active' => true]);
        $otherDistrict = District::create(['code' => 'D2', 'name' => 'Other District', 'region_id' => $otherRegion->id, 'status' => 'active']);
        $otherSchool = School::create([
            'code' => 'SCH2',
            'name' => 'Other School',
            'registration_number' => 'S0002',
            'district_id' => $otherDistrict->id,
            'region_id' => $otherRegion->id,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);

        $otherCandidateId = DB::table('candidates')->insertGetId([
            'school_id' => $otherSchool->id,
            'candidate_id' => 'S0002-0002',
            'full_name' => 'Asha Ngowi',
            'gender' => 'F',
            'exam_type' => 'CSEE',
            'status' => 'registered',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherExamType = ExamType::where('code', 'CSEE')->firstOrFail();
        $activeYear = ExamYear::activeOrFail();

        DB::table('candidate_exam_registrations')->insert([
            'candidate_id' => $otherCandidateId,
            'exam_type_id' => $otherExamType->id,
            'exam_year_id' => $activeYear->id,
            'year' => (int) $activeYear->year_label,
            'registration_number' => 'REG-OTHER',
            'status' => 'APPROVED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('candidate_subject_selections')->insert([
            'candidate_id' => $otherCandidateId,
            'exam_type_id' => $otherExamType->id,
            'exam_year_id' => $activeYear->id,
            'subject_id' => $subject->id,
            'year' => (int) $activeYear->year_label,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('mark-entry.csee.questions.show', [
                'candidate_no' => 'S0002-0002',
                'subject_id' => $subject->id,
            ]))
            ->assertForbidden();
    }

    public function test_submitted_entry_is_read_only_for_non_admin_user(): void
    {
        [$user, $candidate, $subject, $examYear] = $this->createRegionalQuestionEntryContext();

        $this->actingAs($user)
            ->post(route('mark-entry.csee.questions.store'), [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
                'entry_action' => 'submit',
                'scores' => [
                    1 => 64,
                ],
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('mark-entry.csee.questions.store'), [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
                'entry_action' => 'draft',
                'scores' => [
                    1 => 44,
                ],
            ])
            ->assertSessionHasErrors('candidate_no');

        $this->assertDatabaseHas('question_mark_entries', [
            'exam_year_id' => $examYear->id,
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'status' => 'submitted',
            'total' => 64.00,
        ]);
    }

    public function test_csee_multi_paper_subject_total_is_normalized_to_100_from_paper_totals(): void
    {
        [$user, $candidate, $subject, $examYear] = $this->createRegionalQuestionEntryContext([
            'code' => '031',
            'name' => 'Physics',
            'written_papers' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('mark-entry.csee.questions.show', [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
            ]))
            ->assertOk()
            ->assertSee('P1 Q1')
            ->assertSee('P2 Q1')
            ->assertSee('Final total is normalized to 100');

        $this->actingAs($user)
            ->post(route('mark-entry.csee.questions.store'), [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
                'entry_action' => 'draft',
                'scores' => [
                    1 => 10,
                    2 => 6,
                    3 => 9,
                    4 => 9,
                    5 => 9,
                    6 => 9,
                    7 => 9,
                    8 => 9,
                    9 => 15,
                    10 => 15,
                    12 => 25,
                    13 => 25,
                ],
            ])
            ->assertRedirect(route('mark-entry.csee.questions.show', [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
            ]));

        $this->assertDatabaseHas('question_mark_entries', [
            'exam_year_id' => $examYear->id,
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'status' => 'draft',
            'total' => 100.00,
        ]);
    }

    public function test_csee_standard_theory_choice_group_rejects_more_than_two_answers_in_q9_to_q11(): void
    {
        [$user, $candidate, $subject] = $this->createRegionalQuestionEntryContext([
            'code' => '011',
            'name' => 'Civics',
        ]);

        $this->actingAs($user)
            ->post(route('mark-entry.csee.questions.store'), [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
                'entry_action' => 'draft',
                'scores' => [
                    9 => 10,
                    10 => 12,
                    11 => 14,
                ],
            ])
            ->assertSessionHasErrors('scores');
    }

    public function test_psle_english_uses_official_pdf_rubric_and_totals_to_50(): void
    {
        [$user, $candidate, $subject, $examYear] = $this->createRegionalQuestionEntryContext([
            'exam_code' => 'PSLE',
            'subject_code' => 'PSLE-02',
            'subject_name' => 'ENGLISH LANGUAGE',
            'candidate_no' => 'P0001-0001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $this->actingAs($user)
            ->get(route('mark-entry.psle.questions.show', [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
            ]))
            ->assertOk()
            ->assertSee('Loaded from PSLE_FORMAT_ENGLISH_2024.pdf rubric')
            ->assertSee('Q7')
            ->assertSee('out of 50 marks');

        $this->actingAs($user)
            ->post(route('mark-entry.psle.questions.store'), [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
                'entry_action' => 'draft',
                'scores' => [
                    1 => 5,
                    2 => 5,
                    3 => 5,
                    4 => 5,
                    5 => 10,
                    6 => 10,
                    7 => 10,
                ],
            ])
            ->assertRedirect(route('mark-entry.psle.questions.show', [
                'candidate_no' => $candidate->candidate_id,
                'subject_id' => $subject->id,
            ]));

        $this->assertDatabaseHas('question_mark_entries', [
            'exam_year_id' => $examYear->id,
            'candidate_id' => $candidate->id,
            'subject_id' => $subject->id,
            'status' => 'draft',
            'total' => 50.00,
        ]);
    }

    private function createRegionalQuestionEntryContext(array $overrides = []): array
    {
        $role = Role::firstOrCreate(
            ['code' => 'regional_officer'],
            ['name' => 'Regional Officer']
        );

        $region = Region::create([
            'code' => 'R1',
            'name' => 'Arusha',
            'is_active' => true,
        ]);

        $district = District::create([
            'code' => 'D1',
            'name' => 'Arusha District',
            'region_id' => $region->id,
            'status' => 'active',
        ]);

        $school = School::create([
            'code' => 'SCH1',
            'name' => 'Regional Secondary School',
            'registration_number' => 'S0001',
            'district_id' => $district->id,
            'region_id' => $region->id,
            'school_type' => $overrides['school_type'] ?? 'SECONDARY',
            'education_level' => $overrides['education_level'] ?? 'SECONDARY',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
            'first_name' => 'Regional',
            'last_name' => 'Officer',
        ]);

        UserScope::create([
            'user_id' => $user->id,
            'scope_type' => UserScope::SCOPE_REGION,
            'scope_id' => $region->id,
        ]);

        $examCode = $overrides['exam_code'] ?? 'CSEE';
        $examType = ExamType::create([
            'code' => $examCode,
            'name' => $examCode === 'PSLE'
                ? 'Primary School Leaving Examination'
                : 'Certificate of Secondary Education Examination',
            'education_level' => $examCode === 'PSLE' ? 'PRIMARY' : 'SECONDARY',
            'is_active' => true,
        ]);

        $examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
            'is_locked' => false,
        ]);

        $subjectOverrides = $overrides['subject_attributes'] ?? [];
        foreach (['code', 'name', 'max_marks', 'written_papers', 'has_practical', 'has_project', 'is_active'] as $legacyKey) {
            if (array_key_exists($legacyKey, $overrides)) {
                $subjectOverrides[$legacyKey] = $overrides[$legacyKey];
            }
        }

        $subject = Subject::create(array_merge([
            'exam_type_id' => $examType->id,
            'code' => $overrides['subject_code'] ?? 'CIV',
            'name' => $overrides['subject_name'] ?? 'Civics',
            'max_marks' => 100,
            'is_active' => true,
        ], $subjectOverrides));

        $candidateId = DB::table('candidates')->insertGetId([
            'school_id' => $school->id,
            'candidate_id' => $overrides['candidate_no'] ?? 'S0001-0001',
            'full_name' => 'John Mollel',
            'gender' => 'M',
            'exam_type' => $examCode,
            'status' => 'registered',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('candidate_exam_registrations')->insert([
            'candidate_id' => $candidateId,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-CSEE-001',
            'status' => 'APPROVED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('candidate_subject_selections')->insert([
            'candidate_id' => $candidateId,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'subject_id' => $subject->id,
            'year' => 2026,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $candidate = DB::table('candidates')->where('id', $candidateId)->first();

        return [
            $user,
            \App\Models\Candidate::findOrFail($candidate->id),
            $subject,
            $examYear,
        ];
    }
}
