<?php

namespace Tests\Feature;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\Subject;
use App\Models\SubjectPanelAssignment;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleSubjectPanelAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private User $reo;
    private User $panelLeader;
    private ExamYear $examYear;
    private ExamType $psle;
    private ExamType $csee;
    private Region $region;
    private Subject $psleSubject;
    private Subject $cseeSubject;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mark_entry.geofence_enabled' => false,
            'mark_entry.enable_single_device_restriction' => false,
        ]);

        $this->seed(\Database\Seeders\SettingsSeeder::class);

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->region = Region::factory()->create(['name' => 'IRINGA']);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->region->id,
            'status' => 'active',
        ]);

        $this->reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->region->id,
            'status' => 'active',
        ]);

        $this->panelLeader = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active',
        ]);

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->csee = ExamType::factory()->csee()->create([
            'education_level' => 'SECONDARY',
        ]);

        $this->psleSubject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Kiswahili',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $this->cseeSubject = Subject::create([
            'exam_type_id' => $this->csee->id,
            'code' => 'CIVICS',
            'name' => 'Civics',
            'max_marks' => 100,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_subject_panel_assignments_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertStatus(200);
        $response->assertViewHas('assignments');
        $response->assertViewHas('panelLeaders');
    }

    public function test_old_standalone_route_redirects_to_psle_view_query_route(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle/subject-panel-assignments');
        
        $response->assertRedirect('/mark-entry/psle?view=subject-panel-assignments');
    }

    public function test_meo_cannot_access_subject_panel_assignments_page(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertRedirect('/mark-entry/psle');
        $response->assertSessionHas('warning');
    }

    public function test_reo_has_read_only_access_scoped_to_region(): void
    {
        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertStatus(200);
        $response->assertViewHas('assignments');
        $response->assertViewHas('panelLeaders');
    }

    public function test_subject_dropdown_contains_only_psle_subjects_in_controller(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertStatus(200);
        $subjects = $response->viewData('psleSubjects');
        $this->assertTrue($subjects->contains('id', $this->psleSubject->id));
        $this->assertFalse($subjects->contains('id', $this->cseeSubject->id));
    }

    public function test_panel_leader_dropdown_contains_only_active_subject_panel_leader_users(): void
    {
        $inactiveLeader = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'inactive',
        ]);

        $nonLeader = User::factory()->create([
            'portal_role' => 'mark_officer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertStatus(200);
        $panelLeaders = $response->viewData('panelLeaders');
        $this->assertTrue($panelLeaders->contains('id', $this->panelLeader->id));
        $this->assertFalse($panelLeaders->contains('id', $inactiveLeader->id));
        $this->assertFalse($panelLeaders->contains('id', $nonLeader->id));
    }

    public function test_empty_create_assignment_returns_validation_errors(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/subject-panel-assignments', []);
        
        $response->assertSessionHasErrors(['user_id', 'subject_id']);
    }

    public function test_admin_can_create_valid_subject_panel_assignment(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/subject-panel-assignments', [
            'user_id' => $this->panelLeader->id,
            'subject_id' => $this->psleSubject->id,
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
        ]);

        $response->assertRedirect('/mark-entry/psle?view=subject-panel-assignments');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subject_panel_assignments', [
            'user_id' => $this->panelLeader->id,
            'subject_id' => $this->psleSubject->id,
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
            'is_active' => true,
        ]);
    }

    public function test_duplicate_active_assignment_is_blocked(): void
    {
        // First assignment
        SubjectPanelAssignment::create([
            'user_id' => $this->panelLeader->id,
            'exam_type_id' => $this->psle->id,
            'subject_id' => $this->psleSubject->id,
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
            'is_active' => true,
        ]);

        // Attempt duplicate
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/subject-panel-assignments', [
            'user_id' => $this->panelLeader->id,
            'subject_id' => $this->psleSubject->id,
            'exam_year_id' => $this->examYear->id,
            'region_id' => $this->region->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['user_id']);
    }

    public function test_non_psle_subject_cannot_be_assigned(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/subject-panel-assignments', [
            'user_id' => $this->panelLeader->id,
            'subject_id' => $this->cseeSubject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['subject_id']);
    }

    public function test_admin_can_deactivate_assignment(): void
    {
        $assignment = SubjectPanelAssignment::create([
            'user_id' => $this->panelLeader->id,
            'exam_type_id' => $this->psle->id,
            'subject_id' => $this->psleSubject->id,
            'exam_year_id' => $this->examYear->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch("/mark-entry/psle/subject-panel-assignments/{$assignment->id}/toggle");

        $response->assertRedirect('/mark-entry/psle?view=subject-panel-assignments');
        $response->assertSessionHas('success');
        $this->assertFalse($assignment->fresh()->is_active);
    }

    public function test_admin_can_remove_assignment(): void
    {
        $assignment = SubjectPanelAssignment::create([
            'user_id' => $this->panelLeader->id,
            'exam_type_id' => $this->psle->id,
            'subject_id' => $this->psleSubject->id,
            'exam_year_id' => $this->examYear->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/mark-entry/psle/subject-panel-assignments/{$assignment->id}");

        $response->assertRedirect('/mark-entry/psle?view=subject-panel-assignments');
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('subject_panel_assignments', ['id' => $assignment->id]);
    }
}
