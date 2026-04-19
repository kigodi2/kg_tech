<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRegionsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_regions_page_is_available_to_authenticated_users(): void
    {
        $user = User::create([
            'name' => 'Regions Tester',
            'email' => 'regions-tester@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $response = $this->actingAs($user)->get('/registration/regions');

        $response->assertOk();
        $response->assertSee('Regions Management');
    }
}
