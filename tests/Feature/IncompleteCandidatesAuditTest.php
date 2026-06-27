<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Subject;
use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncompleteCandidatesAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nonAdmin;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private Region $region;
    private array $subjects;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seed Roles
        $adminRole = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        $otherRole = Role::firstOrCreate(['code' => 'mock_headteacher'], ['name' => 'Headteacher']);

        // 2. Create Users
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->nonAdmin = User::factory()->create([
            'role_id' => $otherRole->id,
            'is_admin' => false,
            'portal_role' => 'mock_headteacher',
            'status' => 'active',
        ]);

        // 3. Create Exam Year
        $this->examYear = ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true, 'is_locked' => false]
        );

        // 4. Create Regions, Districts, Schools
        $this->region = Region::firstOrCreate(['code' => 'TABORA'], ['name' => 'Tabora']);
        $district = District::firstOrCreate(
            ['code' => 'TABORA_DIST'],
            ['name' => 'Tabora District', 'region_id' => $this->region->id]
        );
        $this->school = School::firstOrCreate(
            ['code' => 'TABORA_SCH'],
            [
                'name' => 'Tabora Primary School',
                'district_id' => $district->id,
                'region_id' => $this->region->id,
                'education_level' => 'PRIMARY'
            ]
        );

        // Mock config for active regions
        config(['irms.tasido_region_ids' => [$this->region->id]]);

        // 5. Create PSLE Exam Type
        $this->psle = ExamType::firstOrCreate(
            ['code' => 'PSLE'],
            ['name' => 'PSLE', 'education_level' => 'PRIMARY']
        );

        // 6. Create 6 PSLE Subjects
        $subjectCodes = ['KIS', 'ENG', 'MAT', 'SCI', 'CIV', 'SOC'];
        $subjectNames = ['Kiswahili', 'English', 'Mathematics', 'Science', 'Civic', 'Social'];
        $this->subjects = [];
        for ($i = 0; $i < 6; $i++) {
            $this->subjects[] = Subject::firstOrCreate(
                ['code' => $subjectCodes[$i]],
                [
                    'exam_type_id' => $this->psle->id,
                    'name' => $subjectNames[$i],
                    'max_marks' => 50,
                    'is_active' => true,
                ]
            );
        }
    }

    public function test_guest_is_redirected_from_incomplete_candidates_audit(): void
    {
        $response = $this->get('/results/manage/2026/psle?view=incomplete-candidates');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_incomplete_candidates_audit(): void
    {
        $response = $this->actingAs($this->nonAdmin)
            ->get('/results/manage/2026/psle?view=incomplete-candidates');

        $response->assertStatus(302);
    }

    public function test_admin_can_view_only_candidates_with_less_than_required_subject_marks(): void
    {
        // 1. Create a complete candidate (has all 6 subject marks entered)
        $completeCandidate = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'exam_year_id' => $this->examYear->id,
            'exam_type' => 'PSLE',
            'full_name' => 'Complete Candidate One',
            'candidate_id' => 'PSLE-2026-0001',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $completeCandidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);

        foreach ($this->subjects as $subject) {
            DB::table('subject_marks')->insert([
                'candidate_id' => $completeCandidate->id,
                'exam_type_id' => $this->psle->id,
                'subject_id' => $subject->id,
                'year' => 2026,
                'marks_obtained' => 45,
                'max_marks' => 50,
                'percentage' => 90,
                'grade' => 'A',
                'subject_status' => 'present',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Create an incomplete candidate (has only 3 subject marks entered)
        $incompleteCandidate = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'exam_year_id' => $this->examYear->id,
            'exam_type' => 'PSLE',
            'full_name' => 'Incomplete Candidate Two',
            'candidate_id' => 'PSLE-2026-0002',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $incompleteCandidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0002',
            'status' => 'APPROVED',
        ]);

        for ($i = 0; $i < 3; $i++) {
            DB::table('subject_marks')->insert([
                'candidate_id' => $incompleteCandidate->id,
                'exam_type_id' => $this->psle->id,
                'subject_id' => $this->subjects[$i]->id,
                'year' => 2026,
                'marks_obtained' => 40,
                'max_marks' => 50,
                'percentage' => 80,
                'grade' => 'B',
                'subject_status' => 'present',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Make request
        $response = $this->actingAs($this->admin)
            ->get('/results/manage/2026/psle?view=incomplete-candidates');

        $response->assertOk();
        $response->assertSee('Incomplete Candidates Audit');
        
        // Assert we see the incomplete candidate
        $response->assertSee('Incomplete Candidate Two');
        $response->assertSee('3 of 6');
        $response->assertSee('Open Entry Sheet');

        // Assert we do NOT see the complete candidate
        $response->assertDontSee('Complete Candidate One');
    }
}
