<?php

namespace Tests\Feature;

use App\Models\DistrictCouncil;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkingCentre;
use App\Models\Region;
use App\Models\Role;
use App\Models\Subject;
use App\Models\SubjectPanelAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PsleUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private User $reo;
    private Region $region;
    private DistrictCouncil $council;
    private MarkingCentre $centre;
    private ExamType $psle;
    private ExamYear $examYear;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'mark_officer'], ['name' => 'Mark Entry Officer']);
        Role::firstOrCreate(['code' => 'reo'], ['name' => 'Regional Education Officer']);
        Role::firstOrCreate(['code' => 'centre_verifier'], ['name' => 'Marking Centre Verifier']);
        Role::firstOrCreate(['code' => 'subject_panel_leader'], ['name' => 'Subject Panel Leader']);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->region = Region::factory()->create(['name' => 'IRINGA']);
        $this->council = DistrictCouncil::create([
            'region_id' => $this->region->id,
            'code' => 'IRMC',
            'name' => 'IRINGA MC',
            'is_active' => true,
        ]);
        $this->centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'IFUNDA',
            'name' => "IFUNDA GIRLS' SECONDARY SCHOOL",
            'status' => 'active',
        ]);

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

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);
        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);
        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        config([
            'mark_entry.geofence_enabled' => false,
            'mark_entry.enable_single_device_restriction' => false,
        ]);
    }

    public function test_admin_can_view_user_management_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=user-management');
        
        $response->assertStatus(200);
        $response->assertViewHas('portalUsers');
        $response->assertViewHas('userCounts');
        $userCounts = $response->viewData('userCounts');
        $this->assertArrayHasKey('panelLeaders', $userCounts);
    }

    public function test_meo_cannot_access_user_management_page(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=user-management');
        
        $response->assertRedirect('/mark-entry/psle');
        $response->assertSessionHas('warning');
    }

    public function test_add_user_validation_errors_appear_for_empty_input(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', []);
        
        $response->assertSessionHasErrors(['name', 'email', 'role_id', 'status']);
    }

    public function test_admin_can_create_mark_entry_officer(): void
    {
        $role = Role::where('code', 'mark_officer')->firstOrFail();

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', [
            'name' => 'Mark Entry Officer Test',
            'email' => 'meo.test@irms.test',
            'role_id' => $role->id,
            'region_id' => $this->region->id,
            'district_council_id' => $this->council->id,
            'marking_centre_id' => $this->centre->id,
            'status' => 'active',
            'password_mode' => 'manual',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'meo.test@irms.test',
            'portal_role' => 'mark_officer',
            'region_id' => $this->region->id,
        ]);
    }

    public function test_admin_can_create_psle_subject_panel_user(): void
    {
        $role = Role::where('code', 'subject_panel_leader')->firstOrFail();

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', [
            'name' => 'Panel Leader Test',
            'email' => 'panel.test@irms.test',
            'role_id' => $role->id,
            'region_id' => $this->region->id,
            'status' => 'active',
            'password_mode' => 'manual',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'panel.test@irms.test',
            'portal_role' => 'subject_panel_leader',
            'region_id' => $this->region->id,
            'marking_centre_id' => null,
        ]);
    }

    public function test_reo_creation_requires_region_id(): void
    {
        $role = Role::where('code', 'reo')->firstOrFail();

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', [
            'name' => 'REO Test No Region',
            'email' => 'reo.noregone@irms.test',
            'role_id' => $role->id,
            'region_id' => '', // Empty
            'status' => 'active',
            'password_mode' => 'manual',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['region_id']);
    }

    public function test_psle_subject_panel_role_appears_in_role_options(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=user-management');
        
        $response->assertStatus(200);
        $roles = $response->viewData('roles');
        $this->assertTrue($roles->contains('code', 'subject_panel_leader'));
    }

    public function test_created_subject_panel_user_appears_in_user_list(): void
    {
        $user = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=user-management');
        
        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('PSLE Subject Panel');
    }

    public function test_subject_panel_user_appears_in_subject_panel_assignment_dropdown(): void
    {
        $panelUser = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertStatus(200);
        $panelLeaders = $response->viewData('panelLeaders');
        $this->assertTrue($panelLeaders->contains('id', $panelUser->id));
    }

    public function test_inactive_subject_panel_user_is_excluded_from_new_panel_leader_dropdown(): void
    {
        $inactiveUser = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=subject-panel-assignments');
        
        $response->assertStatus(200);
        $panelLeaders = $response->viewData('panelLeaders');
        $this->assertFalse($panelLeaders->contains('id', $inactiveUser->id));
    }

    public function test_csv_template_includes_subject_panel_role(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle/users/template');
        
        $response->assertStatus(200);
        $content = $response->streamedContent();
        $this->assertStringContainsString('Subject Panel Leader', $content);
    }

    public function test_csv_import_accepts_subject_panel_leader_role(): void
    {
        $csv = $this->csv([
            ['Vivian Agrey', 'vivian@example.test', '255700000003', 'Subject Panel Leader', 'IRINGA', '', '', 'Password@123', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($csv),
        ]);

        $response->assertRedirect('/mark-entry/psle?view=user-management');
        $response->assertSessionHas('user_import_summary');

        $this->assertDatabaseHas('users', [
            'email' => 'vivian@example.test',
            'portal_role' => 'subject_panel_leader',
            'status' => 'active',
        ]);
    }

    public function test_csv_import_accepts_psle_subject_panel_alias(): void
    {
        $csv = $this->csv([
            ['Alias Leader', 'alias.leader@example.test', '', 'PSLE Subject Panel', '', '', '', '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($csv),
        ]);

        $response->assertRedirect('/mark-entry/psle?view=user-management');
        
        $this->assertDatabaseHas('users', [
            'email' => 'alias.leader@example.test',
            'portal_role' => 'subject_panel_leader',
        ]);
    }

    public function test_csv_import_rejects_invalid_role(): void
    {
        $csv = $this->csv([
            ['Invalid Leader', 'invalid.leader@example.test', '', 'Random Invalid Role', 'IRINGA', '', '', '', 'active'],
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/import', [
            'users_csv' => $this->uploadedCsv($csv),
        ]);

        $response->assertSessionHas('user_import_summary');
        $summary = session('user_import_summary');
        $this->assertSame(0, $summary['created']);
        $this->assertSame(1, $summary['failed']);
    }

    public function test_subject_panel_user_cannot_save_marks(): void
    {
        $panelUser = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($panelUser)->postJson('/api/mark-entry/psle/marks/save', [
            'school_id' => 1,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 45,
        ]);

        $response->assertStatus(403);
    }

    public function test_subject_panel_user_cannot_manage_assignments(): void
    {
        $panelUser = User::factory()->create([
            'portal_role' => 'subject_panel_leader',
            'status' => 'active',
        ]);

        $response = $this->actingAs($panelUser)->post('/mark-entry/psle/subject-panel-assignments', [
            'user_id' => $panelUser->id,
            'subject_id' => $this->subject->id,
        ]);

        $response->assertRedirect('/subject-panel/verification');
    }

    public function test_admin_cannot_create_user_with_mismatched_region_and_council(): void
    {
        $role = Role::where('code', 'mark_officer')->firstOrFail();
        
        $otherRegion = Region::factory()->create(['name' => 'DODOMA']);
        $otherCouncil = DistrictCouncil::create([
            'region_id' => $otherRegion->id,
            'code' => 'BAHI',
            'name' => 'BAHI',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', [
            'name' => 'Mismatched Council Test',
            'email' => 'mismatch.council@irms.test',
            'role_id' => $role->id,
            'region_id' => $this->region->id,
            'district_council_id' => $otherCouncil->id,
            'status' => 'active',
            'password_mode' => 'manual',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['district_council_id']);
        $this->assertDatabaseMissing('users', [
            'email' => 'mismatch.council@irms.test',
        ]);
    }

    public function test_admin_cannot_create_user_with_mismatched_region_and_marking_centre(): void
    {
        $role = Role::where('code', 'mark_officer')->firstOrFail();
        
        $otherRegion = Region::factory()->create(['name' => 'DODOMA']);
        $otherCentre = MarkingCentre::create([
            'region_id' => $otherRegion->id,
            'code' => 'DODOMAMARKING',
            'name' => 'DODOMA CENTRE',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/users/create', [
            'name' => 'Mismatched Centre Test',
            'email' => 'mismatch.centre@irms.test',
            'role_id' => $role->id,
            'region_id' => $this->region->id,
            'marking_centre_id' => $otherCentre->id,
            'status' => 'active',
            'password_mode' => 'manual',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['marking_centre_id']);
        $this->assertDatabaseMissing('users', [
            'email' => 'mismatch.centre@irms.test',
        ]);
    }

    private function csv(array $rows): string
    {
        $lines = ['name,email,phone,role,region,council,marking_centre,password,status'];
        foreach ($rows as $row) {
            $handle = fopen('php://temp', 'r+');
            fputcsv($handle, $row);
            rewind($handle);
            $lines[] = rtrim(stream_get_contents($handle));
            fclose($handle);
        }

        return implode("\n", $lines) . "\n";
    }

    private function uploadedCsv(string $contents): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'psle_users_test_');
        file_put_contents($path, $contents);

        return new UploadedFile($path, 'users.csv', 'text/csv', null, true);
    }
}
