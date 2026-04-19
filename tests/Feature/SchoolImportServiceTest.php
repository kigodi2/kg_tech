<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\School;
use App\Services\Schools\SchoolImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SchoolImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUploadFromString(string $csv): UploadedFile
    {
        $dir = sys_get_temp_dir();
        $path = tempnam($dir, 'csv');
        file_put_contents($path, $csv);

        return new UploadedFile($path, 'schools.csv', 'text/csv', null, true);
    }

    public function test_validate_allows_existing_school_code_to_update_name_from_csv(): void
    {
        $region = Region::create([
            'code' => 'IR01',
            'name' => 'Iringa',
        ]);

        School::create([
            'code' => 'S0203',
            'name' => 'OLD SCHOOL NAME',
            'region_id' => $region->id,
            'ownership' => 'GOVERNMENT',
            'is_active' => true,
        ]);

        $csv = "Code,Name,Region ID,District ID,Ownership\n";
        $csv .= "S0203,NEW SCHOOL NAME,IR01,,GOVERNMENT\n";

        $service = new SchoolImportService();
        $result = $service->validateCSV($this->makeUploadFromString($csv));

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['valid_count']);
        $this->assertSame(1, $result['update_count']);
        $this->assertSame(0, $result['invalid_count']);
    }

    public function test_commit_updates_existing_school_name_when_code_matches_csv(): void
    {
        $region = Region::create([
            'code' => 'IR01',
            'name' => 'Iringa',
        ]);

        $school = School::create([
            'code' => 'S0203',
            'name' => 'SYNCED SCHOOL NAME',
            'region_id' => $region->id,
            'ownership' => 'GOVERNMENT',
            'is_active' => true,
        ]);

        $csv = "Code,Name,Region ID,District ID,Ownership\n";
        $csv .= "S0203,CSV SCHOOL NAME,IR01,,GOVERNMENT\n";

        $service = new SchoolImportService();
        $result = $service->commitImport($this->makeUploadFromString($csv));

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['imported_count']);
        $this->assertSame(1, $result['updated_count']);
        $this->assertSame(0, $result['failed_count']);

        $school->refresh();

        $this->assertSame('CSV SCHOOL NAME', $school->name);
        $this->assertSame('S0203', $school->code);
    }
}
