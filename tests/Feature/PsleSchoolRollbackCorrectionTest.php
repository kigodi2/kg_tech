<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Region;
use App\Models\District;
use App\Models\SchoolResultCorrectionBatch;
use App\Models\SchoolResultCorrectionAudit;
use App\Services\Results\SchoolRollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsleSchoolRollbackCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $schoolA;
    private School $schoolB;
    private Region $region;
    private District $district;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Helpers\MarkEntrySettings::setGeofenceEnabled(false);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->teacher = User::factory()->create([
            'is_admin' => false,
            'portal_role' => 'teacher',
            'status' => 'active',
        ]);

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->region = Region::factory()->create(['name' => 'DAR ES SALAAM']);
        $this->district = District::create([
            'region_id' => $this->region->id,
            'name' => 'ILALA',
            'code' => 'ILALA01',
        ]);

        $this->schoolA = School::factory()->create([
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'name' => 'WILOLESI PRIMARY SCHOOL',
            'code' => 'PS0001',
        ]);

        $this->schoolB = School::factory()->create([
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'name' => 'OTHER PRIMARY SCHOOL',
            'code' => 'PS0002',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        // Create registrations and initial marks
        $candA = Candidate::factory()->school()->create([
            'school_id' => $this->schoolA->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candA->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-A01',
            'status' => 'APPROVED',
        ]);

        $batchA = \App\Models\MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->schoolA->region_id,
            'district_id' => $this->schoolA->district_id,
            'school_id' => $this->schoolA->id,
            'subject_id' => $this->subject->id,
            'exam_year' => '2026',
            'created_by' => $this->admin->id,
            'status' => 'submitted',
            'batch_code' => 'BATCH-A'
        ]);

        RawMark::factory()->create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->schoolA->id,
            'subject_id' => $this->subject->id,
            'candidate_id' => $candA->id,
            'paper_1_marks' => 42,
            'entered_by' => $this->admin->id,
            'mark_import_batch_id' => $batchA->id,
            'is_locked' => true,
        ]);

        $candB = Candidate::factory()->school()->create([
            'school_id' => $this->schoolB->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candB->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-B01',
            'status' => 'APPROVED',
        ]);

        $batchB = \App\Models\MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->schoolB->region_id,
            'district_id' => $this->schoolB->district_id,
            'school_id' => $this->schoolB->id,
            'subject_id' => $this->subject->id,
            'exam_year' => '2026',
            'created_by' => $this->admin->id,
            'status' => 'submitted',
            'batch_code' => 'BATCH-B'
        ]);

        RawMark::factory()->create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->schoolB->id,
            'subject_id' => $this->subject->id,
            'candidate_id' => $candB->id,
            'paper_1_marks' => 38,
            'entered_by' => $this->admin->id,
            'mark_import_batch_id' => $batchB->id,
            'is_locked' => true,
        ]);
    }

    public function test_school_rollback_service_flow(): void
    {
        // 1. Setup initial state: marks are locked, results compiled & published
        $snapshotId = DB::table('result_snapshots')->insertGetId([
            'exam_year_id' => $this->examYear->id,
            'exam_type' => 'PSLE',
            'version' => 1,
            'is_active' => true,
            'is_rolled_back' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('psle_result_publications')->insert([
            'exam_year_id' => $this->examYear->id,
            'snapshot_id' => $snapshotId,
            'status' => 'published',
            'version_no' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = new SchoolRollbackService();

        // 2. Initiate rollback for School A
        $batch = $service->initiateRollback(
            $this->schoolA,
            $this->examYear->id,
            'Correcting PSLE Maths scores',
            $this->admin->id
        );

        $this->assertInstanceOf(SchoolResultCorrectionBatch::class, $batch);
        $this->assertEquals('open', $batch->status);
        $this->assertEquals($this->schoolA->id, $batch->school_id);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'psle_school_rollback_initiated',
            'user_id' => $this->admin->id,
        ]);

        // Verify School A raw marks are unlocked (is_locked = false)
        $this->assertDatabaseHas('raw_marks', [
            'school_id' => $this->schoolA->id,
            'is_locked' => 0, // SQLite boolean false
        ]);

        // Verify School B raw marks remain locked (is_locked = true)
        $this->assertDatabaseHas('raw_marks', [
            'school_id' => $this->schoolB->id,
            'is_locked' => 1, // SQLite boolean true
        ]);

        // 3. Complete Correction
        $batch = $service->completeCorrection($batch, $this->admin->id);
        $this->assertEquals('corrected', $batch->status);

        // Verify marks are locked again (is_locked = true)
        $this->assertDatabaseHas('raw_marks', [
            'school_id' => $this->schoolA->id,
            'is_locked' => 1,
        ]);

        // 4. Recalculate
        $batch = $service->recalculateResults($batch, $this->admin->id);
        $this->assertEquals('recalculated', $batch->status);

        // 5. Republish
        $batch = $service->republishResults($batch, $this->admin->id);
        $this->assertEquals('republished', $batch->status);
    }

    public function test_public_and_evaluation_portals_are_blocked_during_active_correction(): void
    {
        // Setup published results
        $snapshotId = DB::table('result_snapshots')->insertGetId([
            'exam_year_id' => $this->examYear->id,
            'exam_type' => 'PSLE',
            'version' => 1,
            'is_active' => true,
            'is_rolled_back' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('psle_result_publications')->insert([
            'exam_year_id' => $this->examYear->id,
            'snapshot_id' => $snapshotId,
            'status' => 'published',
            'version_no' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify portals are accessible initially
        $this->get('/results/2026/psle')->assertOk();
        $this->get('/evaluations/psle?year=2026')->assertOk();

        // Initiate correction
        $service = new SchoolRollbackService();
        $batch = $service->initiateRollback(
            $this->schoolA,
            $this->examYear->id,
            'Correction started',
            $this->admin->id
        );

        // Verify portals return 403 (with correction block message)
        $responsePublic = $this->get('/results/2026/psle');
        $responsePublic->assertStatus(403);
        $responsePublic->assertSee('Results are temporarily under correction. Please check again later.');

        $responseEval = $this->get('/evaluations/psle?year=2026');
        $responseEval->assertStatus(403);
        $responseEval->assertSee('Results are temporarily under correction. Please check again later.');

        // Admins bypass the block
        $this->actingAs($this->admin)->get('/results/2026/psle')->assertOk();
        $this->actingAs($this->admin)->get('/evaluations/psle?year=2026')->assertOk();

        // Cancel the batch
        $service->cancelRollback($batch, 'Mistake', $this->admin->id);

        // Verify portals are open again
        $this->get('/results/2026/psle')->assertOk();
        $this->get('/evaluations/psle?year=2026')->assertOk();
    }
}
