<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\ExamYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleResultsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed default roles if not present
        Role::firstOrCreate(['code' => 'admin'], ['name' => 'Administrator']);
        Role::firstOrCreate(['code' => 'school_registrar'], ['name' => 'School Registrar']);
        Role::firstOrCreate(['code' => 'mock_headteacher'], ['name' => 'Headteacher']);

        // Create active exam year so router has default parameter context
        ExamYear::firstOrCreate(
            ['year_label' => '2026'],
            ['is_active' => true, 'is_locked' => false]
        );
    }

    /** @test */
    public function guests_are_redirected_to_login()
    {
        $response = $this->get('/results/psle');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function non_admins_are_redirected_away()
    {
        $role = Role::where('code', 'mock_headteacher')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get('/results/psle');
        $response->assertStatus(302);
        
        $location = $response->headers->get('Location');
        $this->assertTrue(
            str_contains($location, '/mark-entry/psle') || 
            str_contains($location, '/mock-portal'),
            "Redirect target was: {$location}"
        );
    }

    /** @test */
    public function admins_can_access_results_portal()
    {
        $role = Role::where('code', 'admin')->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'is_admin' => true,
        ]);

        // Seed basic TASIDO region/districts if controller requires them
        \App\Models\Region::firstOrCreate(['code' => 'TEST'], ['name' => 'Test Region']);

        $response = $this->actingAs($user)->get('/results/psle');
        $response->assertStatus(302); // Redirects to year-based manage route
        $response->assertRedirectContains('/results/manage');
    }
}
