<?php

namespace Tests\Feature;

use App\Models\ExamType;
use App\Models\District;
use App\Models\DistrictCouncil;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CseeExamTypePageTest extends TestCase
{
    public function test_authenticated_user_can_open_csee_exam_type_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/exam-types/csee')
            ->assertOk()
            ->assertSee('CSEE Configuration')
            ->assertSee('2 papers · Theory 3 hours + Practical 2.5 hours')
            ->assertSee('Sections A-C; answer all in A and B, then 2 questions from section C.');
    }

    public function test_csee_official_catalog_sync_creates_subjects(): void
    {
        $user = User::factory()->create();
        $examType = ExamType::query()->create([
            'code' => 'CSEE',
            'name' => 'CERTIFICATE OF SECONDARY EDUCATION EXAMINATION',
            'description' => 'Test CSEE exam type',
            'education_level' => 'SECONDARY',
            'level' => 'Secondary',
            'is_active' => true,
        ]);

        $this->assertSame(0, Subject::query()->where('exam_type_id', $examType->id)->count());

        $this->actingAs($user)
            ->postJson('/api/exam-types/csee/subjects/sync-official')
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Official CSEE subject catalog synchronized successfully.',
            ]);

        $this->assertGreaterThan(
            0,
            Subject::query()->where('exam_type_id', $examType->id)->count()
        );

        $this->assertDatabaseHas('subjects', [
            'exam_type_id' => $examType->id,
            'code' => '011',
            'name' => 'CIVICS',
        ]);
    }

    public function test_csee_school_sync_creates_secondary_centre_records(): void
    {
        $user = User::factory()->create();
        $region = Region::query()->create([
            'code' => 'DSM',
            'name' => 'Dar es Salaam',
            'is_active' => true,
        ]);
        $district = District::query()->create([
            'code' => 'ILA',
            'name' => 'Ilala',
            'region_id' => $region->id,
            'status' => 'active',
        ]);
        $council = DistrictCouncil::query()->create([
            'code' => 'ILMC',
            'name' => 'Ilala Municipal Council',
            'region_id' => $region->id,
            'is_active' => true,
        ]);

        School::query()->create([
            'code' => 'REF-AZANIA',
            'registration_number' => 'S0101',
            'name' => 'AZANIA SECONDARY SCHOOL',
            'source_system' => 'LOCAL_MASTER',
            'ownership' => 'NON-GOVERNMENT',
            'district_id' => $district->id,
            'council_id' => $council->id,
            'region_id' => $region->id,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);

        Http::fake([
            'https://onlinesys.necta.go.tz/results/2025/csee/index.htm' => Http::response(<<<'HTML'
                <html>
                    <body>
                        <a href="results/p0101.htm">P0101 AZANIA CENTRE </a>
                        <a href="results/s0101.htm">S0101 AZANIA SECONDARY SCHOOL </a>
                        <a href="results/p0104.htm">P0104 BWIRU BOYS CENTRE </a>
                        <a href="results/p0104.htm">P0104 BWIRU BOYS CENTRE </a>
                    </body>
                </html>
            HTML, 200),
        ]);

        $this->actingAs($user)
            ->postJson('/api/exam-types/csee/schools/sync-necta-2025')
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'NECTA CSEE 2025 centres sync completed.',
            ]);

        $this->assertDatabaseHas('schools', [
            'code' => 'P0101',
            'name' => 'AZANIA SECONDARY SCHOOL',
            'source_system' => 'NECTA_CSEE_2025',
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
        ]);

        $this->assertDatabaseHas('schools', [
            'code' => 'S0101',
            'name' => 'AZANIA SECONDARY SCHOOL',
            'source_system' => 'NECTA_CSEE_2025',
            'ownership' => 'NON-GOVERNMENT',
            'council_id' => $council->id,
            'region_id' => $region->id,
        ]);

        $this->assertSame(
            3,
            School::query()->where('source_system', 'NECTA_CSEE_2025')->count()
        );

        $this->actingAs($user)
            ->getJson('/api/exam-types/csee/schools?search=AZANIA&page_size=10')
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'S0101',
                'ownership_label' => 'NON-GOVERNMENT',
                'district_name' => 'Ilala',
                'region_name' => 'Dar es Salaam',
            ]);
    }

    public function test_csee_school_particulars_can_be_imported_from_csv(): void
    {
        $user = User::factory()->create();
        $region = Region::query()->create([
            'code' => 'AR',
            'name' => 'Arusha',
            'is_active' => true,
        ]);
        $district = District::query()->create([
            'code' => 'ARUSHA-DC',
            'name' => 'Arusha DC',
            'region_id' => $region->id,
            'status' => 'active',
        ]);
        $cseeRegion = Region::query()->create([
            'code' => 'CSEE-UNK',
            'name' => 'UNASSIGNED CSEE CENTRES',
            'is_active' => true,
        ]);

        School::query()->create([
            'code' => 'S0999',
            'registration_number' => null,
            'name' => 'TEST SEMINARY',
            'source_system' => 'NECTA_CSEE_2025',
            'ownership' => 'GOVERNMENT',
            'region_id' => $cseeRegion->id,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('csee-particulars.csv', implode("\n", [
            'code,name,ownership,region,district',
            'S0999,TEST SEMINARY,NON-GOVERNMENT,Arusha,Arusha DC',
        ]));

        $this->actingAs($user)
            ->post('/api/exam-types/csee/schools/import-particulars', [
                'file' => $file,
            ])
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'CSEE school particulars CSV imported successfully.',
            ]);

        $this->assertDatabaseHas('schools', [
            'code' => 'S0999',
            'ownership' => 'NON-GOVERNMENT',
            'region_id' => $region->id,
            'district_id' => $district->id,
        ]);
    }

    public function test_csee_school_particulars_csv_template_can_be_downloaded(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/api/exam-types/csee/schools/import-particulars/template')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('code,name,ownership,region,district')
            ->assertSee('AZANIA SECONDARY SCHOOL');
    }
}
