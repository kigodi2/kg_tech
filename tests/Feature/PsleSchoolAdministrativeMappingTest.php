<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\District;
use App\Models\DistrictCouncil;
use App\Models\School;
use App\Models\User;
use App\Models\ExamYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsleSchoolAdministrativeMappingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Region $iringa;
    private Region $singida;
    private District $mufindiDistrict;
    private District $singidaDistrict;
    private DistrictCouncil $mufindiCouncil;
    private DistrictCouncil $singidaCouncil;
    private ExamYear $examYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'portal_role' => 'admin',
            'status' => 'active',
        ]);

        $this->iringa = Region::create([
            'code' => 'IR01',
            'name' => 'IRINGA',
        ]);

        $this->singida = Region::create([
            'code' => 'SI02',
            'name' => 'SINGIDA',
        ]);

        $this->mufindiDistrict = District::create([
            'region_id' => $this->iringa->id,
            'code' => 'PSLE25-0404',
            'name' => 'MUFINDI',
            'status' => 'active',
        ]);

        $this->singidaDistrict = District::create([
            'region_id' => $this->singida->id,
            'code' => 'PSLE25-1801',
            'name' => 'SINGIDA',
            'status' => 'active',
        ]);

        $this->mufindiCouncil = DistrictCouncil::create([
            'region_id' => $this->iringa->id,
            'code' => 'PSLE25-0404',
            'name' => 'MUFINDI',
            'is_active' => true,
        ]);

        $this->singidaCouncil = DistrictCouncil::create([
            'region_id' => $this->singida->id,
            'code' => 'PSLE25-1801',
            'name' => 'SINGIDA',
            'is_active' => true,
        ]);

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);
    }

    /**
     * Test PSLE manual school registration endpoint with matched region/council succeeds.
     */
    public function test_creating_psle_school_with_matched_region_and_council_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/exam-types/psle/schools', [
            'code' => 'PS0404001',
            'name' => 'BROOKE BOND PRIMARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->mufindiCouncil->id, // Frontend submits DistrictCouncil id as district_id
            'exam_year' => '2026',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('schools', [
            'code' => 'PS0404001',
            'name' => 'BROOKE BOND PRIMARY SCHOOL',
            'region_id' => $this->iringa->id,
            'council_id' => $this->mufindiCouncil->id,
            'district_id' => $this->mufindiDistrict->id, // Backend resolves from name
        ]);
    }

    /**
     * Test PSLE manual school registration with mismatched region/council fails.
     */
    public function test_creating_psle_school_with_mismatched_region_and_council_fails(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/exam-types/psle/schools', [
            'code' => 'PS0404001',
            'name' => 'BROOKE BOND PRIMARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->singidaCouncil->id, // Mismatched! Singida council belongs to Singida region.
            'exam_year' => '2026',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['district_id']);
        $this->assertDatabaseMissing('schools', [
            'code' => 'PS0404001',
        ]);
    }

    /**
     * Test global API add school with matched region/district succeeds.
     */
    public function test_creating_global_school_with_matched_region_and_district_succeeds(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/schools', [
            'code' => 'S0101',
            'name' => 'TEST SECONDARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->mufindiDistrict->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('schools', [
            'code' => 'S0101',
            'region_id' => $this->iringa->id,
            'district_id' => $this->mufindiDistrict->id,
            'council_id' => $this->mufindiCouncil->id, // Automatically resolved!
        ]);
    }

    /**
     * Test global API add school with mismatched region/district fails.
     */
    public function test_creating_global_school_with_mismatched_region_and_district_fails(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/schools', [
            'code' => 'S0101',
            'name' => 'TEST SECONDARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->singidaDistrict->id, // Mismatched!
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['district_id']);
    }

    /**
     * Test global API edit school with mismatched region/district fails.
     */
    public function test_editing_global_school_with_mismatched_region_and_district_fails(): void
    {
        $school = School::create([
            'code' => 'S0101',
            'name' => 'TEST SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->mufindiDistrict->id,
            'council_id' => $this->mufindiCouncil->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/schools/{$school->id}", [
            'code' => 'S0101',
            'name' => 'TEST SCHOOL UPDATED',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->singidaDistrict->id, // Mismatched!
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['district_id']);
    }

    /**
     * Test council options endpoint only returns councils in the requested region.
     */
    public function test_councils_endpoint_only_returns_region_scoped_councils(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/exam-types/psle/councils?region_id={$this->iringa->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('MUFINDI', $data[0]['name']);
        
        // Assert Singida council is not in the Iringa region response
        $cids = collect($data)->pluck('id');
        $this->assertNotContains($this->singidaCouncil->id, $cids->all());
    }

    /**
     * Test the Eloquent saving hook blocks saves with mismatched mappings.
     */
    public function test_model_saving_hook_blocks_mismatched_save(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The selected council does not belong to the selected region.');

        School::create([
            'code' => 'S9999',
            'name' => 'BYPASSED SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->mufindiDistrict->id,
            'council_id' => $this->singidaCouncil->id, // Mismatched!
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);
    }

    /**
     * Test that the safe data repair command works.
     */
    public function test_safe_data_repair_via_artisan_command(): void
    {
        // Bypass the saving hook using raw database inserts to simulate legacy corrupted data
        \DB::table('schools')->insert([
            'code' => 'PS0404001',
            'name' => 'BROOKE BOND PRIMARY SCHOOL',
            'ownership' => 'GOVERNMENT',
            'region_id' => $this->iringa->id,
            'district_id' => $this->singidaDistrict->id, // Corrupted!
            'council_id' => $this->iringa->id, // Corrupted!
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('psle:check-school-admin-mapping', ['--fix-safe' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('schools', [
            'code' => 'PS0404001',
            'region_id' => $this->iringa->id,
            'district_id' => $this->mufindiDistrict->id, // Repaired!
            'council_id' => $this->mufindiCouncil->id, // Repaired!
        ]);
    }
}
