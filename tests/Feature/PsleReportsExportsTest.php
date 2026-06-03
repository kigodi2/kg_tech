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

class PsleReportsExportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reo;
    private User $otherReo;
    private User $officer;
    private ExamYear $examYear;
    private ExamType $psle;
    private ExamType $acsee;
    private Region $region;
    private Region $otherRegion;
    private District $district;
    private School $school;
    private School $otherSchool;
    private Subject $subject;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);

        // Create active year
        $this->examYear = ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true]
        );

        // Create regions and districts
        $this->region = Region::factory()->create(['name' => 'IRINGA']);
        $this->otherRegion = Region::factory()->create(['name' => 'SINGIDA']);

        $this->district = District::create([
            'name' => 'IRINGA MC',
            'code' => 'IRMC',
            'region_id' => $this->region->id
        ]);

        // Create schools
        $this->school = School::factory()->create([
            'name' => 'IFUNDA PRIMARY',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
        ]);

        $this->otherSchool = School::factory()->create([
            'name' => 'SINGIDA PRIMARY',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'region_id' => $this->otherRegion->id,
        ]);

        // Create exam types
        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->acsee = ExamType::factory()->acsee()->create([
            'education_level' => 'SECONDARY',
        ]);

        // Create users
        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->region->id,
            'status' => 'active',
        ]);

        $this->otherReo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->otherRegion->id,
            'status' => 'active',
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->region->id,
            'status' => 'active',
        ]);

        // Create subject
        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        // Create candidate & registration
        $this->candidate = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);

        // Create a batch with raw marks
        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH-TEST-99',
            'batch_name' => 'Test Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
            'district_id' => $this->district->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'approved',
            'total_records' => 1,
            'created_by' => $this->officer->id,
        ]);

        RawMark::factory()->create([
            'mark_import_batch_id' => $batch->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'candidate_id' => $this->candidate->id,
            'candidate_index_number' => $this->candidate->candidate_id,
            'paper_1_marks' => 42,
            'subject_status' => null,
            'has_errors' => false,
        ]);
    }

    public function test_admin_can_view_reports_exports_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=reports-exports');

        $response->assertStatus(200);
        $response->assertSee('Reports & Exports');
        $response->assertSee('Export Raw Data');
    }

    public function test_reo_is_scoped_to_assigned_region(): void
    {
        // REO from Iringa can view reports page
        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=reports-exports');
        $response->assertStatus(200);

        // REO trying to export regional progress for their region
        $responseExport = $this->actingAs($this->reo)->get('/api/mark-entry/psle/reports/progress/excel?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->region->id);
        $responseExport->assertStatus(200);

        // REO trying to export regional progress outside their region should get 403
        $responseExportDenied = $this->actingAs($this->reo)->get('/api/mark-entry/psle/reports/progress/excel?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->otherRegion->id);
        $responseExportDenied->assertStatus(403);
    }

    public function test_subject_panel_leader_cannot_access_reports(): void
    {
        $spl = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active'
        ]);

        $response = $this->actingAs($spl)->get('/mark-entry/psle?view=reports-exports');
        $response->assertRedirect('/subject-panel/verification');
    }

    public function test_reports_stats_are_scoped_to_selected_filters(): void
    {
        // Global admin stats query
        $response = $this->actingAs($this->admin)->getJson('/api/mark-entry/psle/reports/summary?exam_year_id=' . $this->examYear->id);
        
        $response->assertStatus(200);
        $response->assertJsonPath('data.total_candidates', 1);
        $response->assertJsonPath('data.total_marks', 1);

        // Stats with wrong region (should return 0 counts)
        $responseEmpty = $this->actingAs($this->admin)->getJson('/api/mark-entry/psle/reports/summary?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->otherRegion->id);
        $responseEmpty->assertStatus(200);
        $responseEmpty->assertJsonPath('data.total_candidates', 0);
    }

    public function test_scoresheet_pdf_generation_without_filters_aborts(): void
    {
        // Missing school & subject
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/scoresheet-pdf?exam_year_id=' . $this->examYear->id);
        $response->assertStatus(422);
    }

    public function test_scoresheet_pdf_can_be_generated_with_filters(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/scoresheet-pdf?exam_year_id=' . $this->examYear->id . '&school_id=' . $this->school->id . '&subject_id=' . $this->subject->id);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_entered_marks_pdf_can_be_generated(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/entered-marks-pdf?exam_year_id=' . $this->examYear->id . '&school_id=' . $this->school->id . '&subject_id=' . $this->subject->id);
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_raw_data_export_respects_scoping(): void
    {
        // Admin gets the raw data CSV
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/export?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->region->id);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // REO cannot export raw data outside region
        $responseDenied = $this->actingAs($this->reo)->get('/api/mark-entry/psle/reports/export?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->otherRegion->id);
        $responseDenied->assertStatus(403);
    }

    public function test_cascading_reset_fallback(): void
    {
        // If neither region nor district is selected, school_id is reset to null
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=reports-exports&school_id=' . $this->school->id);
        $response->assertStatus(200);
        $this->assertNull(request()->query('region_id'));
    }

    public function test_entered_marks_school_zip_can_be_generated(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/entered-marks-pdf/school-zip?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->region->id . '&school_id=' . $this->school->id . '&exam_year=2026');
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_entered_marks_district_zip_can_be_generated(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/entered-marks-pdf/district-zip?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->region->id . '&district_id=' . $this->district->id . '&exam_year=2026');
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_entered_marks_region_zip_can_be_generated(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/mark-entry/psle/reports/entered-marks-pdf/region-zip?exam_year_id=' . $this->examYear->id . '&region_id=' . $this->region->id . '&exam_year=2026');
        
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }
}

