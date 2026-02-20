<?php

namespace Tests\Feature;

use App\Models\AcseeRolePermission;
use App\Models\MarkImportBatch;
use App\Models\Role;
use App\Models\SystemEventLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcseeAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $nonAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        $schoolRole = Role::firstOrCreate(['code' => 'school_registrar'], ['name' => 'School Registrar']);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $this->nonAdmin = User::factory()->create([
            'role_id' => $schoolRole->id,
            'status' => 'active',
        ]);
    }

    // ==================== PERMISSION CHECKS ====================

    public function test_non_admin_cannot_access_settings()
    {
        $this->actingAs($this->nonAdmin)
            ->getJson('/api/acsee/admin/settings')
            ->assertStatus(403)
            ->assertJson(['ok' => false]);
    }

    public function test_admin_can_access_settings()
    {
        $this->actingAs($this->admin)
            ->getJson('/api/acsee/admin/settings')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    public function test_non_admin_cannot_access_roles()
    {
        $this->actingAs($this->nonAdmin)
            ->getJson('/api/acsee/admin/roles')
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_access_batches()
    {
        $this->actingAs($this->nonAdmin)
            ->getJson('/api/acsee/admin/batches')
            ->assertStatus(403);
    }

    public function test_non_admin_cannot_access_logs()
    {
        $this->actingAs($this->nonAdmin)
            ->getJson('/api/acsee/admin/logs')
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_admin()
    {
        $this->getJson('/api/acsee/admin/settings')
            ->assertStatus(401);
    }

    // ==================== SETTINGS ====================

    public function test_settings_update_writes_history_and_log()
    {
        $this->actingAs($this->admin)
            ->postJson('/api/acsee/admin/settings/acsee.mark_range_max', ['value' => '200'])
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('system_settings', [
            'key' => 'acsee.mark_range_max',
            'value' => '200',
        ]);

        $this->assertDatabaseHas('acsee_settings_history', [
            'new_value' => '200',
            'changed_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('system_event_logs', [
            'category' => 'admin',
            'action' => 'setting_updated',
            'status' => 'success',
        ]);
    }

    public function test_settings_rejects_unknown_key()
    {
        $this->actingAs($this->admin)
            ->postJson('/api/acsee/admin/settings/acsee.nonexistent_key', ['value' => 'x'])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_settings_validates_boolean_type()
    {
        $this->actingAs($this->admin)
            ->postJson('/api/acsee/admin/settings/acsee.reports_locked_only', ['value' => 'invalid'])
            ->assertStatus(422);
    }

    // ==================== PERMISSIONS ====================

    public function test_admin_can_view_roles_with_permissions()
    {
        $this->actingAs($this->admin)
            ->getJson('/api/acsee/admin/roles')
            ->assertStatus(200)
            ->assertJsonStructure(['ok', 'roles', 'defined_permissions']);
    }

    public function test_admin_can_update_role_permissions()
    {
        $role = Role::where('code', 'school_registrar')->first();

        $this->actingAs($this->admin)
            ->postJson("/api/acsee/admin/roles/{$role->id}/permissions", [
                'permissions' => ['acsee.upload_marks', 'acsee.submit_marks'],
            ])
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('acsee_role_permissions', [
            'role_id' => $role->id,
            'permission' => 'acsee.upload_marks',
            'granted' => true,
        ]);

        $this->assertDatabaseHas('system_event_logs', [
            'action' => 'permissions_updated',
        ]);
    }

    public function test_cannot_remove_admin_config_permission_from_admin_role()
    {
        $adminRole = Role::where('code', 'admin')->first();

        $this->actingAs($this->admin)
            ->postJson("/api/acsee/admin/roles/{$adminRole->id}/permissions", [
                'permissions' => ['acsee.upload_marks'], // Missing admin.configuration
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    // ==================== BATCH MANAGEMENT ====================

    public function test_batch_unlock_requires_reason_and_logs_event()
    {
        // Create prerequisite records for FK constraints
        $region = \App\Models\Region::create(['code' => 'R01', 'name' => 'Test Region']);
        $district = \App\Models\District::create(['code' => 'D01', 'name' => 'Test District', 'region_id' => $region->id]);
        $school = \App\Models\School::create(['code' => 'S01', 'name' => 'Test School', 'district_id' => $district->id, 'region_id' => $region->id]);
        $examType = \App\Models\ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
        $subject = \App\Models\Subject::firstOrCreate(['code' => 'PHY'], ['name' => 'Physics', 'exam_type_id' => $examType->id]);
        $combination = \App\Models\Combination::firstOrCreate(['code' => 'PCM'], ['subjects' => 'PHY,CHE,MAT', 'exam_type_id' => $examType->id]);

        $batch = MarkImportBatch::create([
            'batch_code' => 'TEST-UNLOCK-001',
            'exam_year' => 2026,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'combination_id' => $combination->id,
            'exam_type_id' => $examType->id,
            'status' => MarkImportBatch::STATUS_LOCKED,
            'locked_by' => $this->admin->id,
            'locked_at' => now(),
        ]);

        // Reason too short
        $this->actingAs($this->admin)
            ->postJson("/api/acsee/admin/batches/{$batch->id}/unlock", ['reason' => 'short'])
            ->assertStatus(422);

        // Valid unlock
        $this->actingAs($this->admin)
            ->postJson("/api/acsee/admin/batches/{$batch->id}/unlock", [
                'reason' => 'Data correction required after verification',
            ])
            ->assertJson(['ok' => true]);

        $batch->refresh();
        $this->assertEquals(MarkImportBatch::STATUS_APPROVED, $batch->status);
        $this->assertNull($batch->locked_by);

        $this->assertDatabaseHas('system_event_logs', [
            'action' => 'batch_unlocked',
            'status' => 'success',
        ]);
    }

    public function test_cannot_unlock_non_locked_batch()
    {
        $region = \App\Models\Region::create(['code' => 'R02', 'name' => 'Region 2']);
        $district = \App\Models\District::create(['code' => 'D02', 'name' => 'District 2', 'region_id' => $region->id]);
        $school = \App\Models\School::create(['code' => 'S02', 'name' => 'School 2', 'district_id' => $district->id, 'region_id' => $region->id]);
        $examType = \App\Models\ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
        $subject = \App\Models\Subject::firstOrCreate(['code' => 'CHE'], ['name' => 'Chemistry', 'exam_type_id' => $examType->id]);
        $combination = \App\Models\Combination::firstOrCreate(['code' => 'PCM'], ['subjects' => 'PHY,CHE,MAT', 'exam_type_id' => $examType->id]);

        $batch = MarkImportBatch::create([
            'batch_code' => 'TEST-DRAFT-001',
            'exam_year' => 2026,
            'school_id' => $school->id,
            'subject_id' => $subject->id,
            'combination_id' => $combination->id,
            'exam_type_id' => $examType->id,
            'status' => MarkImportBatch::STATUS_DRAFT,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/api/acsee/admin/batches/{$batch->id}/unlock", [
                'reason' => 'Attempting to unlock a draft batch',
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    // ==================== LOGS ====================

    public function test_logs_endpoint_returns_paginated_results()
    {
        SystemEventLog::record('admin', 'test_action', 'success', 'Test log message');

        $this->actingAs($this->admin)
            ->getJson('/api/acsee/admin/logs')
            ->assertStatus(200)
            ->assertJsonStructure(['ok', 'logs' => ['data', 'current_page', 'last_page', 'total']]);
    }

    public function test_logs_filter_by_category()
    {
        SystemEventLog::record('import', 'csv_imported', 'success', 'Import done');
        SystemEventLog::record('admin', 'settings_changed', 'success', 'Admin action');

        $resp = $this->actingAs($this->admin)
            ->getJson('/api/acsee/admin/logs?category=import')
            ->assertStatus(200);

        $data = $resp->json('logs.data');
        foreach ($data as $log) {
            $this->assertEquals('import', $log['category']);
        }
    }

    public function test_logs_filter_by_status()
    {
        SystemEventLog::record('system', 'error', 'failed', 'Something broke');
        SystemEventLog::record('admin', 'ok', 'success', 'All good');

        $resp = $this->actingAs($this->admin)
            ->getJson('/api/acsee/admin/logs?status=failed')
            ->assertStatus(200);

        $data = $resp->json('logs.data');
        foreach ($data as $log) {
            $this->assertEquals('failed', $log['status']);
        }
    }
}
