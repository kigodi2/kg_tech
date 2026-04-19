<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\GradingProfile;
use App\Models\MarkImportBatch;
use App\Models\Region;
use App\Models\ResultProcess;
use App\Models\Role;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

class AcseeResultsPipelineRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_compute_run_uses_latest_live_marks_and_clears_stale_scope_drafts(): void
    {
        [$admin, $examType, $examYear, $region, $district] = $this->baseFixture();
        $subject = $this->createSubject($examType, 'ENG', 'ENGLISH');
        [$schoolA, $schoolB] = $this->createDistrictSchools($region, $district);
        $candidateA = $this->createCandidate($schoolA, 'S1001-0001', 'Candidate A');
        $staleCandidate = $this->createCandidate($schoolB, 'S1002-0002', 'Stale Candidate');

        MarkImportBatch::create([
            'batch_code' => 'READY-1',
            'exam_year' => (int) $examYear->year_label,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_id' => $schoolA->id,
            'subject_id' => $subject->id,
            'exam_type_id' => $examType->id,
            'status' => MarkImportBatch::STATUS_LOCKED,
            'lifecycle_state' => 'locked',
            'total_records' => 1,
            'valid_records' => 1,
            'error_records' => 0,
        ]);

        DB::table('subject_marks')->insert([
            [
                'candidate_id' => $candidateA->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'year' => (int) $examYear->year_label,
                'paper_1' => 40,
                'marks_obtained' => 40,
                'max_marks' => 100,
                'percentage' => 40,
                'grade' => 'E',
                'subject_status' => null,
                'process_id' => 10,
                'snapshot_id' => null,
                'created_at' => now()->subMinutes(2),
                'updated_at' => now()->subMinutes(2),
            ],
            [
                'candidate_id' => $candidateA->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'year' => (int) $examYear->year_label,
                'paper_1' => 80,
                'marks_obtained' => 80,
                'max_marks' => 100,
                'percentage' => 80,
                'grade' => 'A',
                'subject_status' => null,
                'process_id' => 11,
                'snapshot_id' => null,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'candidate_id' => $candidateA->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'year' => (int) $examYear->year_label,
                'paper_1' => 95,
                'marks_obtained' => 95,
                'max_marks' => 100,
                'percentage' => 95,
                'grade' => 'A',
                'subject_status' => null,
                'process_id' => 12,
                'snapshot_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('candidate_results')->insert(
            $this->candidateResultPayload($staleCandidate->id, $examType->id, (int) $examYear->year_label, 30, 'D', 55, 4, '4')
        );

        DB::table('final_grades')->insert(
            $this->finalGradePayload($staleCandidate->id, $examType->id, (int) $examYear->year_label, 30, 'D', 55, 4, '4')
        );

        $response = $this->actingAs($admin)->postJson('/api/results/acsee/compute-validate/run', [
            'exam_year_id' => $examYear->id,
            'run_type' => 'draft',
            'promote_marks' => false,
            'district_id' => $district->id,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('candidate_results', [
            'candidate_id' => $candidateA->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'total_percentage' => 80,
            'snapshot_id' => null,
        ]);

        $this->assertDatabaseMissing('candidate_results', [
            'candidate_id' => $staleCandidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'snapshot_id' => null,
        ]);

        $this->assertDatabaseMissing('final_grades', [
            'candidate_id' => $staleCandidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'snapshot_id' => null,
        ]);
    }

    public function test_compute_marks_candidate_incomplete_when_registered_principal_subject_is_missing(): void
    {
        [$admin, $examType, $examYear, $region, $district] = $this->baseFixture();
        [$school] = $this->createDistrictSchools($region, $district, 1);
        $candidate = $this->createCandidate($school, 'S4001-0001', 'Incomplete Principal Candidate');
        $chem = $this->createSubject($examType, 'CHEM', 'CHEMISTRY');
        $bio = $this->createSubject($examType, 'BIO', 'BIOLOGY');
        $fhn = $this->createSubject($examType, 'FHN', 'FOOD AND HUMAN NUTRITION');

        foreach ([$chem, $bio, $fhn] as $subject) {
            MarkImportBatch::create([
                'batch_code' => 'READY-' . $subject->code,
                'exam_year' => (int) $examYear->year_label,
                'region_id' => $region->id,
                'district_id' => $district->id,
                'school_id' => $school->id,
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'status' => MarkImportBatch::STATUS_LOCKED,
                'lifecycle_state' => 'locked',
                'total_records' => 1,
                'valid_records' => 1,
                'error_records' => 0,
            ]);
        }

        DB::table('candidate_subject_selections')->insert([
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'subject_id' => $chem->id,
                'year' => (int) $examYear->year_label,
                'is_active' => true,
                'is_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'subject_id' => $bio->id,
                'year' => (int) $examYear->year_label,
                'is_active' => true,
                'is_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
                'subject_id' => $fhn->id,
                'year' => (int) $examYear->year_label,
                'is_active' => true,
                'is_principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('subject_marks')->insert([
            [
                'candidate_id' => $candidate->id,
                'subject_id' => $chem->id,
                'exam_type_id' => $examType->id,
                'year' => (int) $examYear->year_label,
                'paper_1' => 72,
                'marks_obtained' => 72,
                'max_marks' => 100,
                'percentage' => 72,
                'grade' => 'B',
                'subject_status' => null,
                'snapshot_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'candidate_id' => $candidate->id,
                'subject_id' => $bio->id,
                'exam_type_id' => $examType->id,
                'year' => (int) $examYear->year_label,
                'paper_1' => 68,
                'marks_obtained' => 68,
                'max_marks' => 100,
                'percentage' => 68,
                'grade' => 'C',
                'subject_status' => null,
                'snapshot_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->postJson('/api/results/acsee/compute-validate/run', [
            'exam_year_id' => $examYear->id,
            'run_type' => 'draft',
            'promote_marks' => false,
            'school_id' => $school->id,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('candidate_results', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'result_status' => 'INC',
            'overall_grade' => 'INC',
            'snapshot_id' => null,
        ]);
    }

    public function test_publish_uses_latest_completed_process_rows(): void
    {
        [$admin, $examType, $examYear, $region, $district] = $this->baseFixture();
        [$school] = $this->createDistrictSchools($region, $district, 1);
        $candidate = $this->createCandidate($school, 'S2001-0001', 'Publish Candidate');
        $subject = $this->createSubject($examType, 'GEO', 'GEOGRAPHY');

        $olderProcess = ResultProcess::create([
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
            'started_at' => now()->subMinutes(4),
            'completed_at' => now()->subMinutes(3),
            'finished_at' => now()->subMinutes(3),
        ]);

        $latestProcess = ResultProcess::create([
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
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
            'finished_at' => now()->subMinute(),
        ]);

        $this->seedDraftRows($candidate->id, $examType->id, (int) $examYear->year_label, (int) $olderProcess->id, (int) $subject->id, 'C', 60);
        $this->seedDraftRows($candidate->id, $examType->id, (int) $examYear->year_label, (int) $latestProcess->id, (int) $subject->id, 'A', 85);

        $response = $this->actingAs($admin)->postJson('/api/results/acsee/publish', [
            'exam_year_id' => $examYear->id,
            'publish_notes' => 'Publish latest completed draft',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $snapshotId = (int) data_get($response->json(), 'snapshot.id');

        $this->assertDatabaseHas('candidate_results', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'process_id' => $latestProcess->id,
            'snapshot_id' => $snapshotId,
            'overall_grade' => 'A',
        ]);

        $this->assertDatabaseMissing('candidate_results', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'year' => (int) $examYear->year_label,
            'process_id' => $olderProcess->id,
            'snapshot_id' => $snapshotId,
        ]);
    }

    public function test_district_export_includes_all_schools_even_when_candidates_come_from_different_draft_processes(): void
    {
        [$admin, $examType, $examYear, $region, $district] = $this->baseFixture();
        [$schoolA, $schoolB] = $this->createDistrictSchools($region, $district);
        $subject = $this->createSubject($examType, 'ECO', 'ECONOMICS');
        $candidateA = $this->createCandidate($schoolA, 'S3001-0001', 'Export Candidate A');
        $candidateB = $this->createCandidate($schoolB, 'P3002-0002', 'Export Candidate B');

        $olderProcess = ResultProcess::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'completed',
            'user_id' => $admin->id,
            'scope_type' => 'district',
            'scope_id' => $district->id,
            'total_candidates' => 1,
            'processed_count' => 1,
            'error_count' => 0,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now()->subMinutes(4),
            'finished_at' => now()->subMinutes(4),
        ]);

        $latestProcess = ResultProcess::create([
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'type' => 'draft',
            'status' => 'completed',
            'user_id' => $admin->id,
            'scope_type' => 'district',
            'scope_id' => $district->id,
            'total_candidates' => 1,
            'processed_count' => 1,
            'error_count' => 0,
            'started_at' => now()->subMinutes(3),
            'completed_at' => now()->subMinutes(2),
            'finished_at' => now()->subMinutes(2),
        ]);

        $this->seedDraftRows($candidateA->id, $examType->id, (int) $examYear->year_label, (int) $olderProcess->id, (int) $subject->id, 'B', 72);
        $this->seedDraftRows($candidateB->id, $examType->id, (int) $examYear->year_label, (int) $latestProcess->id, (int) $subject->id, 'A', 84);

        $response = $this->actingAs($admin)->postJson('/api/results/acsee/exports/download', [
            'exam_year_id' => $examYear->id,
            'format' => 'pdf',
            'report_type' => 'district_school_results',
            'mode' => 'draft',
            'region_id' => $region->id,
            'district_id' => $district->id,
        ]);

        $response->assertStatus(200);

        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        $this->assertCount(2, $entries);
        $this->assertTrue(collect($entries)->contains(fn ($name) => str_contains($name, 'S1101_ALPHA_SECONDARY_SCHOOL_SCHOOL_CANDIDATES.pdf')));
        $this->assertTrue(collect($entries)->contains(fn ($name) => str_contains($name, 'P1102_BETA_PRIVATE_CENTRE_PRIVATE_CANDIDATES.pdf')));
    }

    private function baseFixture(): array
    {
        $admin = $this->createUserWithRole('admin');
        $examType = ExamType::firstOrCreate(['code' => 'ACSEE'], [
            'name' => 'ACSEE',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);
        $examYear = ExamYear::factory()->year2026()->create();
        $region = Region::factory()->create(['name' => 'Iringa', 'code' => 'IR']);
        $district = District::create([
            'name' => 'Iringa DC',
            'code' => 'IDC',
            'region_id' => $region->id,
            'status' => 'active',
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

        return [$admin, $examType, $examYear, $region, $district];
    }

    private function createDistrictSchools(Region $region, District $district, int $count = 2): array
    {
        $schools = [
            School::create([
                'code' => 'S1101',
                'name' => 'Alpha Secondary School',
                'registration_number' => 'S1101',
                'region_id' => $region->id,
                'district_id' => $district->id,
                'school_type' => 'SECONDARY',
                'education_level' => 'SECONDARY',
                'is_active' => true,
            ]),
        ];

        if ($count > 1) {
            $schools[] = School::create([
                'code' => 'P1102',
                'name' => 'Beta Private Centre',
                'registration_number' => 'P1102',
                'region_id' => $region->id,
                'district_id' => $district->id,
                'school_type' => 'SECONDARY',
                'education_level' => 'SECONDARY',
                'is_active' => true,
            ]);
        }

        return $schools;
    }

    private function createCandidate(School $school, string $indexNumber, string $name): Candidate
    {
        return Candidate::create([
            'school_id' => $school->id,
            'candidate_id' => $indexNumber,
            'full_name' => $name,
            'gender' => 'M',
            'combination' => 'PCM',
            'exam_type' => 'ACSEE',
            'status' => 'registered',
            'is_active' => true,
        ]);
    }

    private function createSubject(ExamType $examType, string $code, string $name): Subject
    {
        return Subject::create([
            'code' => $code,
            'name' => $name,
            'exam_type_id' => $examType->id,
            'written_papers' => 1,
            'has_practical' => false,
            'has_project' => false,
        ]);
    }

    private function seedDraftRows(
        int $candidateId,
        int $examTypeId,
        int $year,
        int $processId,
        int $subjectId,
        string $grade,
        float $percentage
    ): void {
        $gradingProfileId = (int) GradingProfile::query()->where('exam_type_id', $examTypeId)->value('id');
        $division = $grade === 'A' ? '1' : '2';
        $aggt = $grade === 'A' ? 1 : 2;

        DB::table('candidate_results')->insert(
            $this->candidateResultPayload($candidateId, $examTypeId, $year, $processId, $grade, $percentage, $aggt, $division)
        );

        DB::table('final_grades')->insert(
            $this->finalGradePayload($candidateId, $examTypeId, $year, $processId, $grade, $percentage, $aggt, $division, $gradingProfileId)
        );

        DB::table('subject_marks')->insert([
            'candidate_id' => $candidateId,
            'subject_id' => $subjectId,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'paper_1' => $percentage,
            'marks_obtained' => $percentage,
            'max_marks' => 100,
            'percentage' => $percentage,
            'grade' => $grade,
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

    private function candidateResultPayload(
        int $candidateId,
        int $examTypeId,
        int $year,
        int $processId,
        string $grade,
        float $percentage,
        int $aggt,
        string $division
    ): array {
        $payload = [
            'candidate_id' => $candidateId,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('candidate_results', 'total_marks')) {
            $payload['total_marks'] = $percentage;
        }
        if (Schema::hasColumn('candidate_results', 'total_percentage')) {
            $payload['total_percentage'] = $percentage;
        }
        if (Schema::hasColumn('candidate_results', 'overall_grade')) {
            $payload['overall_grade'] = $grade;
        }
        if (Schema::hasColumn('candidate_results', 'result_status')) {
            $payload['result_status'] = 'COMPLETE';
        }
        if (Schema::hasColumn('candidate_results', 'grade_points')) {
            $payload['grade_points'] = $aggt;
        }
        if (Schema::hasColumn('candidate_results', 'division')) {
            $payload['division'] = $division;
        }
        if (Schema::hasColumn('candidate_results', 'gpa')) {
            $payload['gpa'] = $aggt;
        }
        if (Schema::hasColumn('candidate_results', 'status')) {
            $payload['status'] = 'PENDING';
        }
        if (Schema::hasColumn('candidate_results', 'process_id')) {
            $payload['process_id'] = $processId;
        }
        if (Schema::hasColumn('candidate_results', 'snapshot_id')) {
            $payload['snapshot_id'] = null;
        }

        return $payload;
    }

    private function finalGradePayload(
        int $candidateId,
        int $examTypeId,
        int $year,
        int $processId,
        string $grade,
        float $percentage,
        int $aggt,
        string $division,
        ?int $gradingProfileId = null
    ): array {
        $payload = [
            'candidate_id' => $candidateId,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('final_grades', 'grading_profile_id')) {
            $payload['grading_profile_id'] = $gradingProfileId ?? (int) GradingProfile::query()->where('exam_type_id', $examTypeId)->value('id');
        }
        if (Schema::hasColumn('final_grades', 'overall_grade')) {
            $payload['overall_grade'] = $grade;
        }
        if (Schema::hasColumn('final_grades', 'grade')) {
            $payload['grade'] = $grade;
        }
        if (Schema::hasColumn('final_grades', 'grade_name')) {
            $payload['grade_name'] = $grade;
        }
        if (Schema::hasColumn('final_grades', 'total_marks')) {
            $payload['total_marks'] = $percentage;
        }
        if (Schema::hasColumn('final_grades', 'final_percentage')) {
            $payload['final_percentage'] = $percentage;
        }
        if (Schema::hasColumn('final_grades', 'grade_points')) {
            $payload['grade_points'] = $aggt;
        }
        if (Schema::hasColumn('final_grades', 'gpa')) {
            $payload['gpa'] = $aggt;
        }
        if (Schema::hasColumn('final_grades', 'division')) {
            $payload['division'] = $division;
        }
        if (Schema::hasColumn('final_grades', 'grading_breakdown')) {
            $payload['grading_breakdown'] = json_encode(['aggt_points' => $aggt, 'run_type' => 'draft']);
        }
        if (Schema::hasColumn('final_grades', 'process_id')) {
            $payload['process_id'] = $processId;
        }
        if (Schema::hasColumn('final_grades', 'snapshot_id')) {
            $payload['snapshot_id'] = null;
        }

        return $payload;
    }
}
