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
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleZonalwiseCouncilwiseEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExamYear $examYear;
    private ExamType $psle;
    private Region $tabora;
    private Region $singida;
    private District $taboraDistrict;
    private District $singidaDistrict;
    private School $taboraSchool;
    private School $singidaSchool;
    private Subject $math;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);

        $this->examYear = ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true]
        );

        $this->tabora = Region::factory()->create(['name' => 'TABORA']);
        $this->singida = Region::factory()->create(['name' => 'SINGIDA']);

        $this->taboraDistrict = District::create([
            'name' => 'TABORA MC',
            'code' => 'TBMC',
            'region_id' => $this->tabora->id
        ]);

        $this->singidaDistrict = District::create([
            'name' => 'SINGIDA MC',
            'code' => 'SGMC',
            'region_id' => $this->singida->id
        ]);

        $this->taboraSchool = School::factory()->create([
            'name' => 'TABORA PRIMARY',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->tabora->id,
            'district_id' => $this->taboraDistrict->id,
        ]);

        $this->singidaSchool = School::factory()->create([
            'name' => 'SINGIDA PRIMARY',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->singida->id,
            'district_id' => $this->singidaDistrict->id,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->math = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        // Candidate 1 (Tabora)
        $cand1 = Candidate::factory()->school()->create([
            'school_id' => $this->taboraSchool->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $cand1->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-1001',
            'status' => 'APPROVED',
        ]);

        // Candidate 2 (Singida)
        $cand2 = Candidate::factory()->school()->create([
            'school_id' => $this->singidaSchool->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);
        CandidateExamRegistration::create([
            'candidate_id' => $cand2->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-1002',
            'status' => 'APPROVED',
        ]);

        // Marks Tabora
        $batch1 = MarkImportBatch::create([
            'batch_code' => 'BATCH-TB-01',
            'batch_name' => 'Tabora Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->tabora->id,
            'district_id' => $this->taboraDistrict->id,
            'school_id' => $this->taboraSchool->id,
            'subject_id' => $this->math->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'total_records' => 1,
            'created_by' => $this->admin->id,
        ]);
        RawMark::factory()->create([
            'mark_import_batch_id' => $batch1->id,
            'school_id' => $this->taboraSchool->id,
            'subject_id' => $this->math->id,
            'exam_year_id' => $this->examYear->id,
            'candidate_id' => $cand1->id,
            'candidate_index_number' => $cand1->candidate_id,
            'paper_1_marks' => 45,
            'subject_status' => null,
            'has_errors' => false,
        ]);

        // Marks Singida
        $batch2 = MarkImportBatch::create([
            'batch_code' => 'BATCH-SG-01',
            'batch_name' => 'Singida Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->singida->id,
            'district_id' => $this->singidaDistrict->id,
            'school_id' => $this->singidaSchool->id,
            'subject_id' => $this->math->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'total_records' => 1,
            'created_by' => $this->admin->id,
        ]);
        RawMark::factory()->create([
            'mark_import_batch_id' => $batch2->id,
            'school_id' => $this->singidaSchool->id,
            'subject_id' => $this->math->id,
            'exam_year_id' => $this->examYear->id,
            'candidate_id' => $cand2->id,
            'candidate_index_number' => $cand2->candidate_id,
            'paper_1_marks' => 38,
            'subject_status' => null,
            'has_errors' => false,
        ]);
    }

    public function test_admin_can_view_zonal_councilwise_evaluation(): void
    {
        $response = $this->actingAs($this->admin)->get('/evaluations/psle/zonalwise/evaluation/councilwise');

        $response->assertStatus(200);
        $response->assertSee('ZONAL COUNCILWISE EVALUATION');
        $response->assertSee('TABORA MC');
        $response->assertSee('SINGIDA MC');
        $response->assertSee('TABORA');
        $response->assertSee('SINGIDA');
    }

    public function test_admin_can_export_zonal_councilwise_evaluation_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get('/evaluations/psle/zonalwise/evaluation/councilwise/export/pdf');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
