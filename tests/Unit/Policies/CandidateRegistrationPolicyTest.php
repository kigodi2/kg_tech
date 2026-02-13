<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\UserScope;
use App\Policies\CandidateRegistrationPolicy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateRegistrationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CandidateRegistrationPolicy $policy;
    protected User $admin;
    protected User $registrar;
    protected User $suspended;
    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required data: region and district
        $region = \App\Models\Region::firstOrCreate(['code' => 'TEST'], ['name' => 'Test Region', 'description' => 'Test Region']);
        \App\Models\District::firstOrCreate(['code' => 'DIST01'], ['name' => 'Test District', 'region_id' => $region->id]);

        $this->policy = new CandidateRegistrationPolicy();

        // Create admin
        $adminRole = Role::where('code', 'admin')->first();
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        // Create school registrar
        $registrarRole = Role::where('code', 'school_registrar')->first();
        $this->registrar = User::create([
            'name' => 'Registrar User',
            'email' => 'registrar@test.com',
            'password' => bcrypt('password'),
            'role_id' => $registrarRole->id,
            'status' => 'active',
        ]);

        // Create scope for registrar
        $district = \App\Models\District::first();
        $this->school = School::first() ?? School::create([
            'code' => 'SCHOOL01',
            'name' => 'Test School',
            'district_id' => $district->id,
            'region_id' => $district->region_id,
        ]);

        UserScope::create([
            'user_id' => $this->registrar->id,
            'scope_type' => 'school',
            'scope_id' => $this->school->id,
        ]);

        // Create suspended registrar
        $this->suspended = User::create([
            'name' => 'Suspended User',
            'email' => 'suspended@test.com',
            'password' => bcrypt('password'),
            'role_id' => $registrarRole->id,
            'status' => 'suspended',
        ]);
    }

    /**
     * Test admin can register
     */
    public function test_admin_can_register(): void
    {
        $result = $this->policy->register($this->admin);
        $this->assertTrue($result);
    }

    /**
     * Test registrar can register
     */
    public function test_registrar_can_register(): void
    {
        $result = $this->policy->register($this->registrar);
        $this->assertTrue($result);
    }

    /**
     * Test suspended user cannot register
     */
    public function test_suspended_user_cannot_register(): void
    {
        $result = $this->policy->register($this->suspended);
        $this->assertFalse($result);
    }

    /**
     * Test registrar can only register at own school
     */
    public function test_registrar_can_only_register_at_own_school(): void
    {
        $result = $this->policy->registerForSchool($this->registrar, null, $this->school->id);
        $this->assertTrue($result);
    }

    /**
     * Test registrar cannot register at other school
     */
    public function test_registrar_cannot_register_at_other_school(): void
    {
        $district = \App\Models\District::first();
        $otherSchool = School::create([
            'code' => 'SCHOOL02',
            'name' => 'Other School',
            'district_id' => $district->id,
            'region_id' => $district->region_id,
        ]);

        $result = $this->policy->registerForSchool($this->registrar, null, $otherSchool->id);
        $this->assertFalse($result);
    }

    /**
     * Test admin can register at any school
     */
    public function test_admin_can_register_at_any_school(): void
    {
        $district = \App\Models\District::first();
        $otherSchool = School::create([
            'code' => 'SCHOOL03',
            'name' => 'Another School',
            'district_id' => $district->id,
            'region_id' => $district->region_id,
        ]);

        $result = $this->policy->registerForSchool($this->admin, null, $otherSchool->id);
        $this->assertTrue($result);
    }

    /**
     * Test suspended user cannot register
     */
    public function test_suspended_user_cannot_register_at_school(): void
    {
        $result = $this->policy->registerForSchool($this->suspended, null, $this->school->id);
        $this->assertFalse($result);
    }
}
