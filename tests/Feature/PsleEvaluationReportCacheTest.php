<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Region;
use App\Models\District;
use App\Models\DistrictCouncil;
use App\Models\Role;
use App\Models\PslePrecalculatedEvaluation;
use App\Services\Results\PslePrecalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PsleEvaluationReportCacheTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $normalUser;
    private ExamYear $examYear;
    private ExamType $psle;
    private Region $region;
    private District $district;
    private DistrictCouncil $council;
    private School $school;
    private Subject $math;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'headteacher'], ['name' => 'Head Teacher']);

        $this->examYear = ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true]
        );

        // Must be one of the hardcoded zonal regions: TABORA, SINGIDA, IRINGA, DODOMA
        $this->region = Region::factory()->create(['name' => 'TABORA']);

        $this->district = District::create([
            'name' => 'TABORA MC',
            'code' => 'TBMC',
            'region_id' => $this->region->id
        ]);

        $this->council = DistrictCouncil::create([
            'region_id' => $this->region->id,
            'code' => 'TBC1',
            'name' => 'TABORA MC COUNCIL',
            'is_active' => true,
        ]);

        $this->school = School::factory()->create([
            'name' => 'TABORA PRIMARY',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'council_id' => $this->council->id,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->normalUser = User::factory()->create([
            'is_admin' => false,
            'portal_role' => 'headteacher',
            'status' => 'active',
        ]);

        $this->math = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        // Candidate
        $cand = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $cand->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-9001',
            'status' => 'APPROVED',
        ]);

        // Marks
        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH-TB-01',
            'batch_name' => 'Tabora Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'total_records' => 1,
            'created_by' => $this->admin->id,
        ]);
        RawMark::factory()->create([
            'mark_import_batch_id' => $batch->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->math->id,
            'exam_year_id' => $this->examYear->id,
            'candidate_id' => $cand->id,
            'candidate_index_number' => $cand->candidate_id,
            'paper_1_marks' => 48,
            'subject_status' => null,
            'has_errors' => false,
        ]);

        // Set up active snapshot and publication
        $snapshotId = DB::table('result_snapshots')->insertGetId([
            'exam_year_id' => $this->examYear->id,
            'exam_type' => 'PSLE',
            'version' => 'v1',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('psle_result_publications')->insert([
            'exam_year_id' => $this->examYear->id,
            'snapshot_id' => $snapshotId,
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_precalculation_service_saves_payload(): void
    {
        $service = app(PslePrecalculationService::class);
        $service->precalculate(2026, 'zonal', null, 'schoolwise', true);

        $status = $service->getEvaluationStatus(2026, 'zonal', null, 'schoolwise');
        $this->assertEquals(PslePrecalculatedEvaluation::STATUS_READY, $status);

        $payload = $service->getReadyPayloadOrNull(2026, 'zonal', null, 'schoolwise');
        $this->assertNotNull($payload);
        $this->assertArrayHasKey('rows', $payload);
    }

    public function test_normal_user_navigation_denied_when_cache_not_ready(): void
    {
        \App\Http\Controllers\PsleEvaluationsController::$bypassTestSafeguard = true;

        // Cache is not ready yet (missing / pending)
        $response = $this->actingAs($this->normalUser)
            ->get('/evaluations/psle/zonalwise/evaluation/schoolwise');

        \App\Http\Controllers\PsleEvaluationsController::$bypassTestSafeguard = false;

        // Normal user is served the "not ready" polling page
        $response->assertStatus(200);
        $response->assertSee('Preparing Evaluation Report');
        $response->assertSee('PENDING');
    }

    public function test_normal_user_navigation_allowed_when_cache_ready(): void
    {
        // Precalculate cache first
        $service = app(PslePrecalculationService::class);
        $service->precalculate(2026, 'zonal', null, 'schoolwise', true);

        $response = $this->actingAs($this->normalUser)
            ->get('/evaluations/psle/zonalwise/evaluation/schoolwise');

        // Served actual report
        $response->assertStatus(200);
        $response->assertSee('ZONAL SCHOOLWISE EVALUATION');
        $response->assertSee('TABORA PRIMARY');
    }

    public function test_admin_can_rebuild_failed_or_missing_cache(): void
    {
        // Seed a failed status
        $snapshotId = app(PslePrecalculationService::class)->getActiveSnapshotId(2026);
        PslePrecalculatedEvaluation::create([
            'exam_year' => 2026,
            'exam_type' => 'PSLE',
            'scope_type' => 'zonal',
            'scope_id' => null,
            'evaluation_key' => 'schoolwise',
            'snapshot_id' => $snapshotId,
            'status' => PslePrecalculatedEvaluation::STATUS_FAILED,
            'payload' => null,
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/evaluations/psle/rebuild', [
                'scope_type' => 'zonal',
                'scope_id' => '',
                'evaluation_key' => 'schoolwise',
                'exam_year' => '2026',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Cache rebuild started successfully.']);
        
        // Assert status was reset to pending or ready (in testing environment it might run sync or dispatch job)
        $status = app(PslePrecalculationService::class)->getEvaluationStatus(2026, 'zonal', null, 'schoolwise');
        $this->assertTrue(in_array($status, [PslePrecalculatedEvaluation::STATUS_PENDING, PslePrecalculatedEvaluation::STATUS_BUILDING, PslePrecalculatedEvaluation::STATUS_READY]));
    }

    public function test_artisan_precalculate_command(): void
    {
        $exitCode = Artisan::call('psle:precalculate-evaluations', [
            'year' => 2026,
            '--force' => true,
            '--scope' => 'zonal',
            '--evaluation' => 'schoolwise',
        ]);

        $this->assertEquals(0, $exitCode);

        $status = app(PslePrecalculationService::class)->getEvaluationStatus(2026, 'zonal', null, 'schoolwise');
        $this->assertEquals(PslePrecalculatedEvaluation::STATUS_READY, $status);
    }
}
