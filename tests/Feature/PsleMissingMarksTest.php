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
use App\Models\PsleMissingMarkValidation;
use App\Models\Region;
use App\Services\MarkEntry\PsleScoresheetFpdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleMissingMarksTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reo;
    private User $otherReo;
    private User $meo;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private School $otherSchool;
    private Subject $subj1;
    private Subject $subj2;
    private Candidate $candidateAbs;
    private Candidate $candidateInc;
    private Candidate $candidateComplete;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        // Setup region for school to be one of the TASIDO regions, e.g. TABORA
        $regionTabora = Region::create([
            'name' => 'TABORA',
            'code' => 'TAB',
        ]);

        $regionSingida = Region::create([
            'name' => 'SINGIDA',
            'code' => 'SGD',
        ]);

        $districtTabora = \App\Models\District::create([
            'name' => 'TABORA MC',
            'code' => 'TMC',
            'region_id' => $regionTabora->id,
        ]);

        $districtSingida = \App\Models\District::create([
            'name' => 'SINGIDA MC',
            'code' => 'SMC',
            'region_id' => $regionSingida->id,
        ]);

        $this->school = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $regionTabora->id,
            'district_id' => $districtTabora->id,
        ]);

        $this->otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $regionSingida->id,
            'district_id' => $districtSingida->id,
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        $this->otherReo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->otherSchool->region_id,
            'status' => 'active',
        ]);

        $this->meo = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        // Create exactly 2 active subjects for easy testing
        $this->subj1 = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'KISW',
            'name' => 'Kiswahili',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $this->subj2 = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'ENGL',
            'name' => 'English',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        // Candidate ABS: no marks in any subject
        $this->candidateAbs = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidateAbs->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-ABS-1',
            'status' => 'APPROVED',
        ]);

        // Candidate INC: mark in KISW, missing ENGL
        $this->candidateInc = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidateInc->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-INC-2',
            'status' => 'APPROVED',
        ]);

        // Create raw mark for candidateInc in subj1
        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH-INC',
            'batch_name' => 'Test Batch INC',
            'batch_type' => 'manual',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj1->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'created_by' => $this->meo->id,
        ]);

        RawMark::create([
            'mark_import_batch_id' => $batch->id,
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj1->id,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_index_number' => $this->candidateInc->candidate_id,
            'full_name' => $this->candidateInc->full_name,
            'paper_1_marks' => 35,
            'has_errors' => false,
            'row_number' => 1,
            'raw_data' => [],
            'processed_at' => now(),
        ]);

        // Candidate COMPLETE: marks in both subjects
        $this->candidateComplete = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidateComplete->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-COM-3',
            'status' => 'APPROVED',
        ]);

        RawMark::create([
            'mark_import_batch_id' => $batch->id,
            'candidate_id' => $this->candidateComplete->id,
            'subject_id' => $this->subj1->id,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_index_number' => $this->candidateComplete->candidate_id,
            'full_name' => $this->candidateComplete->full_name,
            'paper_1_marks' => 40,
            'has_errors' => false,
            'row_number' => 1,
            'raw_data' => [],
            'processed_at' => now(),
        ]);

        $batch2 = MarkImportBatch::create([
            'batch_code' => 'BATCH-SUBJ2',
            'batch_name' => 'Test Batch SUBJ2',
            'batch_type' => 'manual',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj2->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'created_by' => $this->meo->id,
        ]);

        RawMark::create([
            'mark_import_batch_id' => $batch2->id,
            'candidate_id' => $this->candidateComplete->id,
            'subject_id' => $this->subj2->id,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_index_number' => $this->candidateComplete->candidate_id,
            'full_name' => $this->candidateComplete->full_name,
            'paper_1_marks' => 42,
            'has_errors' => false,
            'row_number' => 1,
            'raw_data' => [],
            'processed_at' => now(),
        ]);
    }

    public function test_candidate_classifications(): void
    {
        $service = app(\App\Services\MarkEntry\PsleMissingMarksService::class);
        $details = $service->getSchoolDetails($this->school, [
            'exam_year_id' => $this->examYear->id,
            'classification' => 'all',
        ], $this->admin);

        $rows = collect($details['rows']);

        // Candidate ABS: no marks in any subject
        $absRow = $rows->firstWhere('candidate.id', $this->candidateAbs->id);
        $this->assertNotNull($absRow);
        $this->assertEquals('ABS', $absRow['classification']);

        // Candidate INC: mark in KISW, missing ENGL
        $incRow = $rows->firstWhere('candidate.id', $this->candidateInc->id);
        $this->assertNotNull($incRow);
        $this->assertEquals('INC', $incRow['classification']);

        // Candidate COMPLETE: has marks in all subjects
        $completeRow = $rows->firstWhere('candidate.id', $this->candidateComplete->id);
        $this->assertNotNull($completeRow);
        $this->assertEquals('COMPLETE', $completeRow['classification']);
    }

    public function test_reo_can_approve_only_within_assigned_region(): void
    {
        $response = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/approve', [
            'selected_items' => [
                [
                    'candidate_id' => $this->candidateAbs->id,
                    'subject_id' => $this->subj1->id,
                    'exam_year_id' => $this->examYear->id,
                    'school_id' => $this->school->id,
                ],
            ],
            'reason' => 'Confirmed from attendance sheet',
        ]);
        $response->assertStatus(200);

        // Verify it is approved
        $this->assertTrue(PsleMissingMarkValidation::where([
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'decision' => 'approved_abs',
        ])->exists());

        // Try to approve candidate in other region (otherSchool)
        $candidateOther = Candidate::factory()->school()->create([
            'school_id' => $this->otherSchool->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $candidateOther->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'REG-OTH-4',
            'status' => 'APPROVED',
        ]);

        $response2 = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/approve', [
            'selected_items' => [
                [
                    'candidate_id' => $candidateOther->id,
                    'subject_id' => $this->subj1->id,
                    'exam_year_id' => $this->examYear->id,
                    'school_id' => $this->otherSchool->id,
                ],
            ],
            'reason' => 'Confirmed from attendance sheet',
        ]);
        $response2->assertStatus(400); // unauthorized by region scoping in service
    }

    public function test_meo_cannot_approve_or_commit(): void
    {
        $this->withoutMiddleware();

        $response = $this->actingAs($this->meo)->postJson('/mark-entry/psle/missing-marks/approve', [
            'selected_items' => [
                [
                    'candidate_id' => $this->candidateAbs->id,
                    'subject_id' => $this->subj1->id,
                    'exam_year_id' => $this->examYear->id,
                    'school_id' => $this->school->id,
                ],
            ],
            'reason' => 'Confirmed from attendance sheet',
        ]);
        $response->assertStatus(403);

        $responseCommit = $this->actingAs($this->meo)->postJson('/mark-entry/psle/missing-marks/commit', [
            'school_id' => $this->school->id,
            'exam_year_id' => $this->examYear->id,
        ]);
        $responseCommit->assertStatus(403);
    }

    public function test_approval_requires_remarks(): void
    {
        $response = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/approve', [
            'selected_items' => [
                [
                    'candidate_id' => $this->candidateAbs->id,
                    'subject_id' => $this->subj1->id,
                    'exam_year_id' => $this->examYear->id,
                    'school_id' => $this->school->id,
                ],
            ],
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reason']);
    }

    public function test_commit_creates_abs_status_safely_and_no_overwrite(): void
    {
        // Approve first
        PsleMissingMarkValidation::create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'classification' => 'ABS',
            'decision' => 'approved_abs',
            'reason' => 'Confirmed from attendance sheet',
            'created_by' => $this->reo->id,
        ]);

        // Manually create approved_abs validation for candidateInc (which has numeric mark)
        PsleMissingMarkValidation::create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj1->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'classification' => 'INC',
            'decision' => 'approved_abs',
            'reason' => 'Fake approval',
            'created_by' => $this->reo->id,
        ]);

        $response = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/commit', [
            'school_id' => $this->school->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'committed' => 1,
            'skipped' => 1,
        ]);

        // Verify candidateAbs has raw mark committed as ABS
        $this->assertTrue(RawMark::where([
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'subject_status' => 'ABS',
        ])->exists());

        // Verify candidateInc raw mark was NOT overwritten (paper_1_marks is still 35)
        $markInc = RawMark::where([
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj1->id,
        ])->first();
        $this->assertEquals(35, $markInc->paper_1_marks);
        $this->assertNull($markInc->subject_status);
    }

    public function test_committed_abs_shows_in_filter(): void
    {
        PsleMissingMarkValidation::create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'classification' => 'ABS',
            'decision' => 'committed',
            'reason' => 'Confirmed from attendance sheet',
            'created_by' => $this->reo->id,
        ]);

        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=missing-marks&classification=committed');
        $response->assertStatus(200);
    }

    public function test_zips_still_work_with_committed_abs(): void
    {
        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH-ABS-COMMITTED',
            'batch_name' => 'Test Batch Commits',
            'batch_type' => 'manual',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj1->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'created_by' => $this->meo->id,
        ]);

        RawMark::create([
            'mark_import_batch_id' => $batch->id,
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_index_number' => $this->candidateAbs->candidate_id,
            'full_name' => $this->candidateAbs->full_name,
            'subject_status' => 'ABS',
            'has_errors' => false,
            'row_number' => 1,
            'raw_data' => [],
            'processed_at' => now(),
        ]);

        $fpdfService = app(PsleScoresheetFpdfService::class);
        
        $zipResult = $fpdfService->generateEnteredSchoolZip('2026', $this->school->id);
        $this->assertFileExists($zipResult['file_path']);
        @unlink($zipResult['file_path']);

        $zipResult2 = $fpdfService->generateEnteredDistrictZip('2026', (int) $this->school->district_id);
        $this->assertFileExists($zipResult2['file_path']);
        @unlink($zipResult2['file_path']);
    }

    public function test_ui_only_shows_tasido_regions(): void
    {
        // Unassigned CSEE region that shouldn't show up in PSLE
        Region::create([
            'name' => 'UNASSIGNED CSEE CENTRES',
            'code' => 'UNS',
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=missing-marks');
        $response->assertStatus(200);
        $response->assertSee('TABORA');
        $response->assertSee('SINGIDA');
        $response->assertDontSee('UNASSIGNED CSEE CENTRES');
    }

    public function test_bulk_approve_works_for_allowed_roles_and_scoping(): void
    {
        // Approve school
        $response = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/bulk-approve', [
            'school_ids' => [$this->school->id],
            'exam_year_id' => $this->examYear->id,
            'reason' => 'Bulk approved preset',
        ]);
        $response->assertStatus(200);

        // Verify approved record exists for candidateAbs in school
        $this->assertTrue(PsleMissingMarkValidation::where([
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'decision' => 'approved_abs',
        ])->exists());

        // Verify candidateInc was skipped because they are an INC candidate (have numeric mark in subj1)
        $this->assertFalse(PsleMissingMarkValidation::where([
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj1->id,
            'decision' => 'approved_abs',
        ])->exists());

        // Try bulk approving otherSchool (Singida) as Tabora REO
        $response2 = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/bulk-approve', [
            'school_ids' => [$this->otherSchool->id],
            'exam_year_id' => $this->examYear->id,
            'reason' => 'Tabora REO bulk approving Singida',
        ]);
        $response2->assertStatus(400); // unauthorized by region scoping

        // Try bulk approving as MEO
        $this->withoutMiddleware();
        $response3 = $this->actingAs($this->meo)->postJson('/mark-entry/psle/missing-marks/bulk-approve', [
            'school_ids' => [$this->school->id],
            'exam_year_id' => $this->examYear->id,
            'reason' => 'MEO bulk approving',
        ]);
        $response3->assertStatus(403); // Forbidden
    }

    public function test_bulk_commit_preview_and_execution_works_safely(): void
    {
        // Approve the missing marks first
        PsleMissingMarkValidation::create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'classification' => 'ABS',
            'decision' => 'approved_abs',
            'reason' => 'Bulk approved preview test',
            'created_by' => $this->reo->id,
        ]);

        // Create an approved_abs for candidateInc in subj1 (which has numeric mark 35)
        PsleMissingMarkValidation::create([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj1->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'classification' => 'ABS',
            'decision' => 'approved_abs',
            'reason' => 'Should be skipped',
            'created_by' => $this->reo->id,
        ]);

        // Preview commit
        $responsePreview = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/bulk-commit-preview', [
            'exam_year_id' => $this->examYear->id,
            'school_ids' => [$this->school->id],
        ]);
        $responsePreview->assertStatus(200);
        $responsePreview->assertJsonFragment([
            'schools_count' => 1,
            'to_commit_count' => 1,
            'skipped_count' => 1,
        ]);

        // Commit with invalid confirmation text
        $responseCommitFail = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/bulk-commit', [
            'exam_year_id' => $this->examYear->id,
            'school_ids' => [$this->school->id],
            'confirmation_text' => 'COMMIT',
        ]);
        $responseCommitFail->assertStatus(422);

        // Commit with valid confirmation text
        $responseCommitSuccess = $this->actingAs($this->reo)->postJson('/mark-entry/psle/missing-marks/bulk-commit', [
            'exam_year_id' => $this->examYear->id,
            'school_ids' => [$this->school->id],
            'confirmation_text' => 'COMMIT ABS',
        ]);
        $responseCommitSuccess->assertStatus(200);
        $responseCommitSuccess->assertJsonFragment([
            'results' => [
                'total_approved' => 2,
                'committed' => 1,
                'skipped' => 1,
                'failed' => 0,
            ]
        ]);

        // Verify committed RawMark exists for candidateAbs
        $this->assertTrue(RawMark::where([
            'candidate_id' => $this->candidateAbs->id,
            'subject_id' => $this->subj1->id,
            'subject_status' => 'ABS',
        ])->exists());

        // Verify skipped candidateInc raw mark was NOT modified
        $markInc = RawMark::where([
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj1->id,
        ])->first();
        $this->assertEquals(35, $markInc->paper_1_marks);
        $this->assertNull($markInc->subject_status);
    }

    public function test_save_inc_missing_mark_success_as_admin(): void
    {
        // Setup initial INC state to test transition
        $this->candidateInc->status = 'INC';
        $this->candidateInc->save();

        $reg = CandidateExamRegistration::where([
            'candidate_id' => $this->candidateInc->id,
            'exam_year_id' => $this->examYear->id,
        ])->first();
        if ($reg) {
            $reg->status = 'INC';
            $reg->save();
        }

        // Create CandidateResult record with result_status = 'INC'
        $candidateResult = \App\Models\CandidateResult::create([
            'candidate_id' => $this->candidateInc->id,
            'exam_type_id' => $this->psle->id,
            'year' => 2026,
            'result_status' => 'INC',
            'overall_grade' => 'F',
        ]);

        $this->assertFalse(RawMark::where([
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj2->id,
        ])->exists());

        $payload = [
            'candidate_id' => $this->candidateInc->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj2->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45.5,
            'remark' => 'Completed missing INC mark for English language',
        ];

        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/missing-marks/inc/save', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Missing INC mark completed successfully.',
        ]);

        // Verify raw mark was created
        $rawMark = RawMark::where([
            'candidate_id' => $this->candidateInc->id,
            'subject_id' => $this->subj2->id,
        ])->first();
        $this->assertNotNull($rawMark);
        $this->assertEquals(45.5, $rawMark->paper_1_marks);
        $this->assertNull($rawMark->subject_status);

        // Verify status transitions: candidate status should be 'Complete' since all subjects now have marks
        $candidate = Candidate::find($this->candidateInc->id);
        $this->assertEquals('Complete', $candidate->status);

        $reg = CandidateExamRegistration::where([
            'candidate_id' => $this->candidateInc->id,
            'exam_year_id' => $this->examYear->id,
        ])->first();
        $this->assertEquals('Complete', $reg->status);
        
        $candidateResult = \App\Models\CandidateResult::where([
            'candidate_id' => $this->candidateInc->id,
            'exam_type_id' => $this->psle->id,
            'year' => 2026,
        ])->first();
        $this->assertEquals('COMPLETE', $candidateResult->result_status);

        // Verify audit log entries
        $this->assertTrue(\App\Models\SystemEventLog::where('action', 'inc_mark_completed')->exists());
        $this->assertTrue(\App\Models\GovernanceAuditLog::where('action', 'inc_mark_completed')->exists());
        $this->assertTrue(\App\Models\MarkEntryChange::where('raw_mark_id', $rawMark->id)->exists());
    }

    public function test_save_inc_missing_mark_unauthorized_scope(): void
    {
        $payload = [
            'candidate_id' => $this->candidateInc->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj2->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45.5,
            'remark' => 'Completed missing INC mark for English language',
        ];

        $response = $this->actingAs($this->otherReo)->postJson('/mark-entry/psle/missing-marks/inc/save', $payload);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized: School belongs to another region.',
        ]);
    }

    public function test_save_inc_missing_mark_validation_errors(): void
    {
        $payload = [
            'candidate_id' => $this->candidateInc->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj2->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 55, // max is 50
            'remark' => 'Over limit',
        ];

        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/missing-marks/inc/save', $payload);

        $response->assertStatus(400);
        $response->assertJsonFragment([
            'success' => false,
        ]);
        $this->assertStringContainsString('Validation Error: Score must be a number between 0 and 50', $response->json('message'));
    }

    public function test_save_inc_missing_mark_fails_if_existing_mark_or_abs(): void
    {
        $payload = [
            'candidate_id' => $this->candidateInc->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subj1->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 40,
            'remark' => 'Try overwrite',
        ];

        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/missing-marks/inc/save', $payload);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Existing numeric mark exists. Overwriting is not permitted via this form.',
        ]);
    }
}
