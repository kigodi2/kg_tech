<?php

namespace Tests\Unit\Policies;

use App\Models\District;
use App\Models\Role;
use App\Models\User;
use App\Models\UserScope;
use App\Policies\MarkImportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkImportPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected MarkImportPolicy $policy;
    protected User $admin;
    protected User $officer;
    protected User $suspended;
    protected District $district;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a region first (required for districts)
        \App\Models\Region::firstOrCreate(['code' => 'TEST'], ['name' => 'Test Region', 'description' => 'Test Region']);

        $this->policy = new MarkImportPolicy();

        // Create admin
        $adminRole = Role::where('code', 'admin')->first();
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        // Create district officer
        $officerRole = Role::where('code', 'district_data_entry_officer')->first();
        $this->officer = User::create([
            'name' => 'Officer User',
            'email' => 'officer@test.com',
            'password' => bcrypt('password'),
            'role_id' => $officerRole->id,
            'status' => 'active',
        ]);

        // Create scope for officer
        $this->district = District::first() ?? District::create([
            'code' => 'TEST01',
            'name' => 'Test District',
            'region_id' => 1,
        ]);

        UserScope::create([
            'user_id' => $this->officer->id,
            'scope_type' => 'district',
            'scope_id' => $this->district->id,
        ]);

        // Create suspended user
        $this->suspended = User::create([
            'name' => 'Suspended User',
            'email' => 'suspended@test.com',
            'password' => bcrypt('password'),
            'role_id' => $officerRole->id,
            'status' => 'suspended',
        ]);
    }

    /**
     * Test admin can create import
     */
    public function test_admin_can_create_import(): void
    {
        $result = $this->policy->create($this->admin);
        $this->assertTrue($result);
    }

    /**
     * Test officer can create import
     */
    public function test_officer_can_create_import(): void
    {
        $result = $this->policy->create($this->officer);
        $this->assertTrue($result);
    }

    /**
     * Test suspended user cannot create import
     */
    public function test_suspended_user_cannot_create_import(): void
    {
        $result = $this->policy->create($this->suspended);
        $this->assertFalse($result);
    }

    /**
     * Test officer can only upload to own district
     */
    public function test_officer_can_only_upload_to_own_district(): void
    {
        $result = $this->policy->uploadForDistrict($this->officer, null, $this->district->id);
        $this->assertTrue($result);
    }

    /**
     * Test officer cannot upload to other district
     */
    public function test_officer_cannot_upload_to_other_district(): void
    {
        $otherDistrict = District::create([
            'code' => 'TEST02',
            'name' => 'Other District',
            'region_id' => 1,
        ]);

        $result = $this->policy->uploadForDistrict($this->officer, null, $otherDistrict->id);
        $this->assertFalse($result);
    }

    /**
     * Test admin can upload to any district
     */
    public function test_admin_can_upload_to_any_district(): void
    {
        $otherDistrict = District::create([
            'code' => 'TEST03',
            'name' => 'Another District',
            'region_id' => 1,
        ]);

        $result = $this->policy->uploadForDistrict($this->admin, null, $otherDistrict->id);
        $this->assertTrue($result);
    }

    /**
     * Test suspended user cannot upload
     */
    public function test_suspended_user_cannot_upload(): void
    {
        $result = $this->policy->uploadForDistrict($this->suspended, null, $this->district->id);
        $this->assertFalse($result);
    }
}
