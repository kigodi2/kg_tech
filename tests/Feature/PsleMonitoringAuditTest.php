<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkEntryAssignment;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Region;
use App\Models\District;
use App\Models\Role;
use App\Models\SystemEventLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleMonitoringAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $reo;
    private User $otherReo;
    private User $officer;
    private User $unassignedOfficer;
    private ExamYear $examYear;
    private ExamType $psle;
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

        \App\Helpers\MarkEntrySettings::setGeofenceEnabled(false);
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

        $this->unassignedOfficer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => null,
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
    }

    public function test_admin_can_view_monitoring_audit_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit');

        $response->assertStatus(200);
        $response->assertSee('Monitoring & Audit');
        $response->assertSee('Active Officers');
        $response->assertSee('Marks Today');
    }

    public function test_meo_cannot_access_monitoring_audit(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=monitoring-audit');
        
        $response->assertRedirect('/mark-entry/psle');
        $response->assertSessionHas('warning');
    }

    public function test_subject_panel_leader_cannot_access_monitoring_audit(): void
    {
        $spl = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active'
        ]);

        $response = $this->actingAs($spl)->get('/mark-entry/psle?view=monitoring-audit');
        $response->assertRedirect('/subject-panel/verification');
    }

    public function test_reo_is_scoped_to_assigned_region(): void
    {
        // REO from Iringa can view monitoring audit
        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=monitoring-audit');
        $response->assertStatus(200);

        // Region ID filter is automatically overridden to their assigned region (Iringa) in activeFilters
        $response->assertViewHas('activeFilters', function($filters) {
            return (int)$filters['region_id'] === (int)$this->region->id;
        });
    }

    public function test_active_officers_count_respects_scopes(): void
    {
        // Count active officers (only $this->officer is active in IRINGA)
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit&region_id=' . $this->region->id);
        
        $response->assertStatus(200);
        $response->assertViewHas('monitoringSummary', function($summary) {
            return $summary['active_officers'] === 1;
        });

        // Other region has 0 active officers
        $responseOther = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit&region_id=' . $this->otherRegion->id);
        $responseOther->assertStatus(200);
        $responseOther->assertViewHas('monitoringSummary', function($summary) {
            return $summary['active_officers'] === 0;
        });
    }

    public function test_regional_meo_workload_calculation(): void
    {
        // Regional MEO (IRINGA) has $this->candidate under their workload
        // 1 candidate * 1 active PSLE subject = 1 expected mark workload
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit&region_id=' . $this->region->id);
        
        $response->assertStatus(200);
        $productivity = $response->viewData('productivityStats');
        $officerRow = $productivity->firstWhere('officer', $this->officer->name);

        $this->assertNotNull($officerRow);
        $this->assertEquals(1, $officerRow->assigned_candidates);
        $this->assertTrue($officerRow->has_assignment);
    }

    public function test_officer_with_entries_but_no_assignment_shows_missing(): void
    {
        // Unassigned MEO saves a mark historically
        $batch = MarkImportBatch::create([
            'batch_code' => 'BATCH-HISTORICAL',
            'batch_name' => 'Historical Batch',
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
            'created_by' => $this->unassignedOfficer->id,
        ]);

        RawMark::factory()->create([
            'mark_import_batch_id' => $batch->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'candidate_id' => $this->candidate->id,
            'candidate_index_number' => $this->candidate->candidate_id,
            'paper_1_marks' => 45,
            'entered_by' => $this->unassignedOfficer->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit');
        
        $response->assertStatus(200);
        $productivity = $response->viewData('productivityStats');
        $officerRow = $productivity->firstWhere('officer', $this->unassignedOfficer->name);

        $this->assertNotNull($officerRow);
        $this->assertEquals(0, $officerRow->assigned_candidates);
        $this->assertEquals(1, $officerRow->entered_marks);
        $this->assertNull($officerRow->pending_marks); // null pending marks indicates assignment missing
        $this->assertFalse($officerRow->has_assignment);
    }

    public function test_validation_runs_count_matches_events(): void
    {
        // No validation runs logged initially
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit');
        $response->assertStatus(200);
        $response->assertViewHas('monitoringSummary', function($summary) {
            return $summary['validation_runs'] === 0;
        });

        // Log a validation run event with an actor who is region-associated (the REO)
        SystemEventLog::record(
            SystemEventLog::CAT_SYSTEM,
            'validation_run',
            SystemEventLog::STATUS_SUCCESS,
            'Completed NECTA verification.',
            ['school_id' => $this->school->id, 'region_id' => $this->region->id],
            null,
            $this->reo->id
        );

        $responseWithRun = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit&region_id=' . $this->region->id);
        $responseWithRun->assertStatus(200);
        $responseWithRun->assertViewHas('monitoringSummary', function($summary) {
            return $summary['validation_runs'] === 1;
        });
    }

    public function test_submitted_batches_count(): void
    {
        // Create draft batch (should be excluded)
        MarkImportBatch::create([
            'batch_code' => 'DRAFT-01',
            'batch_name' => 'Draft Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'draft',
            'total_records' => 1,
            'created_by' => $this->officer->id,
        ]);

        // Create submitted batch (should be included)
        MarkImportBatch::create([
            'batch_code' => 'SUBMIT-01',
            'batch_name' => 'Submitted Batch',
            'batch_type' => 'single_csv',
            'exam_year' => '2026',
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_type_id' => $this->psle->id,
            'status' => 'submitted',
            'total_records' => 1,
            'created_by' => $this->officer->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=monitoring-audit');
        
        $response->assertStatus(200);
        $response->assertViewHas('monitoringSummary', function($summary) {
            return $summary['submitted_batches'] === 1;
        });
    }
}
