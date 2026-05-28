<?php

namespace Tests\Feature;

use App\Models\MarkingCentre;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\MarkEntryAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleMarkingCentresTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private User $reo;
    private Region $region;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_admin_can_view_marking_centres_page(): void
    {
        $centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'MC-IR-01',
            'name' => 'IFUNDA GIRLS SECONDARY SCHOOL',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=marking-centres');

        $response->assertStatus(200);
        $response->assertSee('IFUNDA GIRLS SECONDARY SCHOOL');
        $response->assertSee('MC-IR-01');
    }

    public function test_meo_cannot_access_marking_centres_management(): void
    {
        $response = $this->actingAs($this->officer)
            ->withSession(['mark_entry_location_verified_at' => \Carbon\Carbon::now()->toIso8601String()])
            ->get('/mark-entry/psle?view=marking-centres');
        
        $response->assertRedirect('/mark-entry/psle');
        $response->assertSessionHas('warning');
    }

    public function test_reo_has_read_only_access_to_marking_centres(): void
    {
        MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'REO-READ',
            'name' => 'REO Read Only Centre',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->reo)->get('/mark-entry/psle?view=marking-centres');
        
        $response->assertStatus(200);
        $response->assertDontSee('Add New Marking Centre');
        $response->assertSee('Read-only');
    }

    public function test_empty_centre_submission_returns_validation_errors(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/create', []);

        $response->assertSessionHasErrors(['name', 'code', 'region_id']);
    }

    public function test_admin_can_create_valid_marking_centre(): void
    {
        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/create', [
            'name' => 'BIHAWANA SECONDARY SCHOOL',
            'code' => 'mc-dom-01',
            'region_id' => $this->region->id,
            'location' => 'Dodoma CC',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('marking_centres', [
            'name' => 'BIHAWANA SECONDARY SCHOOL',
            'code' => 'MC-DOM-01',
            'region_id' => $this->region->id,
            'location' => 'Dodoma CC',
            'status' => 'active',
        ]);
    }

    public function test_duplicate_centre_code_is_blocked(): void
    {
        MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'DUPLICATE',
            'name' => 'Original Centre',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/create', [
            'name' => 'New Centre',
            'code' => 'duplicate',
            'region_id' => $this->region->id,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_admin_can_update_marking_centre(): void
    {
        $centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'ORIGINAL-CODE',
            'name' => 'Original Centre',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/' . $centre->id . '/update', [
            'name' => 'Updated Centre Name',
            'code' => 'updated-code',
            'region_id' => $this->region->id,
            'location' => 'Updated Location',
            'status' => 'inactive',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('marking_centres', [
            'id' => $centre->id,
            'name' => 'Updated Centre Name',
            'code' => 'UPDATED-CODE',
            'location' => 'Updated Location',
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_toggle_marking_centre_status(): void
    {
        $centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'TOGGLE',
            'name' => 'Toggle Centre',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/' . $centre->id . '/toggle-status');

        $response->assertRedirect();
        $this->assertSame('inactive', $centre->fresh()->status);
    }

    public function test_centre_cannot_be_removed_if_linked_to_users(): void
    {
        $centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'LINKED-U',
            'name' => 'Linked User Centre',
            'status' => 'active',
        ]);

        User::factory()->create([
            'marking_centre_id' => $centre->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/' . $centre->id . '/delete');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('marking_centres', ['id' => $centre->id]);
    }

    public function test_centre_can_be_removed_if_unreferenced(): void
    {
        $centre = MarkingCentre::create([
            'region_id' => $this->region->id,
            'code' => 'UNREFERENCED',
            'name' => 'Unreferenced Centre',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post('/mark-entry/psle/marking-centres/' . $centre->id . '/delete');

        $response->assertRedirect();
        $this->assertSoftDeleted('marking_centres', ['id' => $centre->id]);
    }
}
