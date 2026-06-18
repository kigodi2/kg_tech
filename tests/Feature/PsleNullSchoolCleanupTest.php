<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Candidate;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsleNullSchoolCleanupTest extends TestCase
{
    use RefreshDatabase;

    private Region $region;
    private School $safeSchool;
    private School $referencedSchool;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::create([
            'code' => 'IR01',
            'name' => 'IRINGA',
        ]);

        // School 1: Null council, Null district, no references (SAFE)
        $this->safeSchool = School::create([
            'code' => 'SAFE001',
            'name' => 'SAFE UNMAPPED SECONDARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->region->id,
            'district_id' => null,
            'council_id' => null,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);

        // School 2: Null council, Null district, referenced by candidate (BLOCKED)
        $this->referencedSchool = School::create([
            'code' => 'BLOCKED001',
            'name' => 'BLOCKED REFERENCED SECONDARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->region->id,
            'district_id' => null,
            'council_id' => null,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);

        // Create reference
        $this->candidate = Candidate::create([
            'school_id' => $this->referencedSchool->id,
            'candidate_id' => 'CAN001',
            'full_name' => 'John Doe',
            'gender' => 'M',
            'is_active' => true,
        ]);
    }

    /**
     * Test the cleanup command in Dry-Run mode.
     */
    public function test_cleanup_command_dry_run_does_not_modify_database(): void
    {
        $this->artisan('psle:cleanup-null-schools')
            ->expectsOutputToContain('Total Null-Mapped Schools scanned: 2')
            ->expectsOutputToContain('Safe to remove/deactivate       : 1')
            ->expectsOutputToContain('Blocked (Active references)     : 1')
            ->expectsOutputToContain('Dry run completed. To commit these changes, run with the --commit option.')
            ->assertExitCode(0);

        // Verify no DB changes
        $this->safeSchool->refresh();
        $this->referencedSchool->refresh();
        $this->assertTrue($this->safeSchool->is_active);
        $this->assertTrue($this->referencedSchool->is_active);
    }

    /**
     * Test deactivating safe schools using the cleanup command.
     */
    public function test_cleanup_command_deactivates_safe_schools_by_default(): void
    {
        $this->artisan('psle:cleanup-null-schools', ['--commit' => true])
            ->expectsOutputToContain('Successfully deactivated 1 schools')
            ->assertExitCode(0);

        $this->safeSchool->refresh();
        $this->referencedSchool->refresh();

        // Safe school should be deactivated
        $this->assertFalse($this->safeSchool->is_active);

        // Referenced school must remain active
        $this->assertTrue($this->referencedSchool->is_active);
    }

    /**
     * Test hard-deleting safe schools using the cleanup command.
     */
    public function test_cleanup_command_hard_deletes_safe_schools_when_delete_flag_passed(): void
    {
        $this->artisan('psle:cleanup-null-schools', ['--commit' => true, '--delete' => true])
            ->expectsOutputToContain('Successfully hard-deleted 1 schools')
            ->assertExitCode(0);

        // Safe school is deleted from DB
        $this->assertDatabaseMissing('schools', ['id' => $this->safeSchool->id]);

        // Referenced school remains in DB
        $this->assertDatabaseHas('schools', ['id' => $this->referencedSchool->id]);
        $this->referencedSchool->refresh();
        $this->assertTrue($this->referencedSchool->is_active);
    }

    /**
     * Test the audit command is clean of deactivated/removed schools.
     */
    public function test_audit_command_ignores_deactivated_schools(): void
    {
        // Assert safe school initially appears in null council list
        $this->artisan('psle:check-school-admin-mapping')
            ->expectsOutputToContain('Found 2 schools with null council_id.')
            ->expectsOutputToContain('Found 2 schools with null district_id.');

        // Clean up safe school
        $this->artisan('psle:cleanup-null-schools', ['--commit' => true]);

        // Assert deactivated school no longer appears in audit command list
        $this->artisan('psle:check-school-admin-mapping')
            ->expectsOutputToContain('Found 1 schools with null council_id.')
            ->expectsOutputToContain('Found 1 schools with null district_id.');
    }
}
