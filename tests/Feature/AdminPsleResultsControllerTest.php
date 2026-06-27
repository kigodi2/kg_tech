<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\ExamYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPsleResultsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed default roles if not present
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        
        // Create active exam year so router has default parameter context
        ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true, 'is_locked' => false]
        );

        // Seed regions, districts, schools
        $region = Region::firstOrCreate(['code' => 'TEST_REG'], ['name' => 'Test Region']);
        $district = District::firstOrCreate(
            ['code' => 'TEST_DIST'], 
            ['name' => 'Test District', 'region_id' => $region->id]
        );
        School::firstOrCreate(
            ['code' => 'TEST_SCH'], 
            [
                'name' => 'Test School', 
                'district_id' => $district->id, 
                'region_id' => $region->id,
                'education_level' => 'PRIMARY'
            ]
        );

        // Mock config for active regions
        config(['irms.tasido_region_ids' => [$region->id]]);
    }

    /** @test */
    public function admin_can_load_dashboard_manage_route()
    {
        $role = Role::where('code', 'admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->get('/results/manage/2026/psle');
        $response->assertStatus(200);
        $response->assertViewIs('results.psle.index');
        $response->assertViewHas('metrics');
        $response->assertViewHas('viewData');
    }
}
