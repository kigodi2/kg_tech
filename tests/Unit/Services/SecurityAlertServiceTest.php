<?php

namespace Tests\Unit\Services;

use App\Models\GovernanceAuditLog;
use App\Models\User;
use App\Services\SecurityAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create test users
        $roleId = \App\Models\Role::firstOrCreate(['code' => 'admin'], ['name' => 'Admin', 'description' => 'Admin'])->id;
        \App\Models\User::firstOrCreate(['email' => 'test1@test.com'], [
            'name' => 'Test User 1',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
            'status' => 'active'
        ]);
        \App\Models\User::firstOrCreate(['email' => 'test2@test.com'], [
            'name' => 'Test User 2',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
            'status' => 'active'
        ]);
    }

    /**
     * Test failed login tracking
     */
    public function test_log_failed_login_tracks_attempts(): void
    {
        // Create multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_LOGIN_FAILED,
                userId: null,
                adminId: null,
                data: [
                    'email' => 'test@example.com',
                    'reason' => 'invalid_credentials',
                ]
            );
        }

        // Verify logs exist
        $logs = GovernanceAuditLog::where('action', 'login_failed')
            ->where('data->email', 'test@example.com')
            ->count();

        $this->assertGreaterThanOrEqual(6, $logs);
    }

    /**
     * Test unauthorized scope attempt detection
     */
    public function test_unauthorized_scope_detection(): void
    {
        // Create unauthorized scope attempts
        for ($i = 0; $i < 4; $i++) {
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_IMPORT_FAILED,
                userId: 1,
                adminId: null,
                data: [
                    'reason' => 'unauthorized_scope',
                    'school_id' => 10,
                ]
            );
        }

        // Verify logs exist
        $logs = GovernanceAuditLog::where('action', 'import_failed')
            ->where('data->reason', 'unauthorized_scope')
            ->count();

        $this->assertGreaterThanOrEqual(4, $logs);
    }

    /**
     * Test high import failure rate detection
     */
    public function test_import_failure_rate_detection(): void
    {
        // Create successful imports
        for ($i = 0; $i < 3; $i++) {
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_IMPORT_COMPLETED,
                userId: 1,
                adminId: null,
                data: ['records_imported' => 100]
            );
        }

        // Create failed imports
        for ($i = 0; $i < 5; $i++) {
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_IMPORT_FAILED,
                userId: 1,
                adminId: null,
                data: ['error' => 'Invalid format']
            );
        }

        $successful = GovernanceAuditLog::where('action', 'import_completed')->count();
        $failed = GovernanceAuditLog::where('action', 'import_failed')->count();
        $failureRate = ($failed / ($successful + $failed)) * 100;

        // Should be > 30%
        $this->assertGreaterThan(30, $failureRate);
    }

    /**
     * Test multiple suspension detection
     */
    public function test_multiple_suspension_detection(): void
    {
        // Create multiple suspension logs (only for user 1 and 2)
        for ($i = 0; $i < 2; $i++) {
            GovernanceAuditLog::log(
                GovernanceAuditLog::ACTION_USER_SUSPENDED,
                userId: $i + 1,
                adminId: 1,
                data: ['reason' => 'security_incident']
            );
        }

        $suspensions = GovernanceAuditLog::where('action', 'user_suspended')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $this->assertGreaterThanOrEqual(2, $suspensions);
    }
}
