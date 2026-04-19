<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\FinalGrade;
use App\Models\GradingProfile;
use App\Models\Region;
use App\Models\ResultProcess;
use App\Models\ResultSnapshot;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcseeSubmissionLockingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_publish_fails_without_completed_process(): void
    {
        $admin = $this->createUserWithRole('admin');
        $examYear = ExamYear::factory()->year2026()->create();
        ExamType::factory()->acsee()->create();

        $this->actingAs($admin)
            ->postJson('/api/results/acsee/publish', [
                'exam_year_id' => $examYear->id,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_publish_creates_snapshot_and_keeps_draft_rows(): void
    {
        [$admin, $examType, $examYear, $candidate] = $this->createComputeReadyFixture('admin');

        $process = ResultProcess::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'completed',
            'user_id' => $admin->id,
            'scope_type' => 'national',
            'scope_id' => null,
            'total_candidates' => 1,
            'processed_count' => 1,
            'error_count' => 0,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'finished_at' => now(),
        ]);

        $this->seedDraftRows($candidate->id, $examType->id, (int) $examYear->year_label, $process->id);

        $response = $this->actingAs($admin)
            ->postJson('/api/results/acsee/publish', [
                'exam_year_id' => $examYear->id,
                'publish_notes' => 'Test publish',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $snapshotId = (int) data_get($response->json(), 'snapshot.id');
        $this->assertGreaterThan(0, $snapshotId);

        $this->assertDatabaseHas('result_snapshots', [
            'id' => $snapshotId,
            'exam_year_id' => $examYear->id,
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('candidate_results', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'process_id' => $process->id,
            'snapshot_id' => null,
        ]);

        $this->assertDatabaseHas('candidate_results', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'process_id' => $process->id,
            'snapshot_id' => $snapshotId,
            'status' => 'RELEASED',
        ]);
    }

    public function test_lock_blocks_publish_until_unlocked(): void
    {
        [$admin, $examType, $examYear, $candidate] = $this->createComputeReadyFixture('admin');

        $process = ResultProcess::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'completed',
            'user_id' => $admin->id,
            'scope_type' => 'national',
            'scope_id' => null,
            'total_candidates' => 1,
            'processed_count' => 1,
            'error_count' => 0,
        ]);
        $this->seedDraftRows($candidate->id, $examType->id, (int) $examYear->year_label, $process->id);

        $this->actingAs($admin)
            ->postJson('/api/results/acsee/publish', ['exam_year_id' => $examYear->id])
            ->assertStatus(200);

        $this->actingAs($admin)
            ->postJson('/api/results/acsee/lock', [
                'exam_year_id' => $examYear->id,
                'reason' => 'Ready for national release',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->actingAs($admin)
            ->postJson('/api/results/acsee/publish', ['exam_year_id' => $examYear->id])
            ->assertStatus(423)
            ->assertJson(['success' => false]);
    }

    public function test_admin_unlock_requires_admin_and_reason(): void
    {
        [$admin, $examType, $examYear, $candidate] = $this->createComputeReadyFixture('admin');
        $schoolUser = $this->createUserWithRole('school_user');

        $process = ResultProcess::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'completed',
            'user_id' => $admin->id,
            'scope_type' => 'national',
            'scope_id' => null,
            'total_candidates' => 1,
            'processed_count' => 1,
            'error_count' => 0,
        ]);
        $this->seedDraftRows($candidate->id, $examType->id, (int) $examYear->year_label, $process->id);

        $this->actingAs($admin)->postJson('/api/results/acsee/publish', ['exam_year_id' => $examYear->id])->assertStatus(200);
        $this->actingAs($admin)->postJson('/api/results/acsee/lock', [
            'exam_year_id' => $examYear->id,
            'reason' => 'Locking for control',
        ])->assertStatus(200);

        $this->actingAs($schoolUser)
            ->postJson('/api/results/acsee/admin-unlock', [
                'exam_year_id' => $examYear->id,
                'reason' => 'try unlock',
            ])
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson('/api/results/acsee/admin-unlock', [
                'exam_year_id' => $examYear->id,
            ])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson('/api/results/acsee/admin-unlock', [
                'exam_year_id' => $examYear->id,
                'reason' => 'Correction required',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_school_user_publish_is_constrained_to_school_scope(): void
    {
        [$schoolUser, $examType, $examYear, $candidate, $school] = $this->createComputeReadyFixture('school_user');

        UserScope::updateOrCreate(
            ['user_id' => $schoolUser->id],
            ['scope_type' => 'school', 'scope_id' => $school->id]
        );

        $process = ResultProcess::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'completed',
            'user_id' => $schoolUser->id,
            'scope_type' => 'school',
            'scope_id' => $school->id,
            'total_candidates' => 1,
            'processed_count' => 1,
            'error_count' => 0,
        ]);

        $this->seedDraftRows($candidate->id, $examType->id, (int) $examYear->year_label, $process->id);

        $response = $this->actingAs($schoolUser)
            ->postJson('/api/results/acsee/publish', [
                'exam_year_id' => $examYear->id,
                'scope_type' => 'national',
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $snapshotId = (int) data_get($response->json(), 'snapshot.id');
        $snapshot = ResultSnapshot::query()->findOrFail($snapshotId);

        $this->assertSame('school', (string) $snapshot->scope_type);
        $this->assertSame($school->id, (int) $snapshot->scope_id);
    }

    private function createComputeReadyFixture(string $roleCode = 'admin'): array
    {
        $user = $this->createUserWithRole($roleCode);
        $examType = ExamType::firstOrCreate(['code' => 'ACSEE'], [
            'name' => 'ACSEE',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);
        $examYear = ExamYear::factory()->year2026()->create();
        $region = Region::factory()->create();
        $school = School::factory()->create([
            'region_id' => $region->id,
            'education_level' => 'SECONDARY',
            'school_type' => 'SECONDARY',
        ]);

        if ($roleCode === 'school_user') {
            DB::table('users')->where('id', $user->id)->update(['school_id' => $school->id]);
            $user->refresh();
        }

        $subject = Subject::create([
            'code' => 'SUB' . random_int(100, 999),
            'name' => 'Subject',
            'exam_type_id' => $examType->id,
        ]);

        $candidate = Candidate::create([
            'school_id' => $school->id,
            'candidate_id' => 'S' . random_int(1000, 9999) . '-' . random_int(1000, 9999),
            'full_name' => 'Test Candidate',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'status' => 'registered',
            'is_active' => true,
        ]);

        GradingProfile::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'code' => 'GP-' . $examYear->year_label,
            'name' => 'Profile',
            'description' => null,
            'version' => 1,
            'grade_boundaries' => [],
            'gpa_mapping' => [],
            'competence_levels' => [],
            'is_active' => true,
            'is_locked' => false,
        ]);

        return [$user, $examType, $examYear, $candidate, $school, $subject];
    }

    private function seedDraftRows(int $candidateId, int $examTypeId, int $year, int $processId): void
    {
        $gradingProfileId = (int) GradingProfile::query()->where('exam_type_id', $examTypeId)->value('id');
        $subjectId = (int) Subject::query()->where('exam_type_id', $examTypeId)->value('id');

        DB::table('candidate_results')->insert([
            'candidate_id' => $candidateId,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'total_marks' => 260,
            'total_percentage' => 65,
            'overall_grade' => 'B',
            'status' => 'PENDING',
            'created_at' => now(),
            'updated_at' => now(),
            'process_id' => $processId,
            'snapshot_id' => null,
        ]);

        DB::table('final_grades')->insert([
            'candidate_id' => $candidateId,
            'exam_type_id' => $examTypeId,
            'grading_profile_id' => $gradingProfileId,
            'year' => $year,
            'grade' => 'B',
            'grade_name' => 'B',
            'final_percentage' => 65,
            'created_at' => now(),
            'updated_at' => now(),
            'process_id' => $processId,
            'snapshot_id' => null,
        ]);

        DB::table('subject_marks')->insert([
            'candidate_id' => $candidateId,
            'subject_id' => $subjectId,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'paper_1' => 65,
            'marks_obtained' => 65,
            'max_marks' => 100,
            'percentage' => 65,
            'grade' => 'B',
            'created_at' => now(),
            'updated_at' => now(),
            'process_id' => $processId,
            'snapshot_id' => null,
        ]);
    }

    private function createUserWithRole(string $roleCode): User
    {
        $role = Role::firstOrCreate(['code' => $roleCode], [
            'name' => ucfirst(str_replace('_', ' ', $roleCode)),
            'description' => ucfirst($roleCode),
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }
}
