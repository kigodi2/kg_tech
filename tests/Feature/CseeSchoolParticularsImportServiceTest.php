<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Region;
use App\Models\School;
use App\Services\Schools\CseeSchoolParticularsImportService;
use App\Services\Schools\NectaCsee2025CentreSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CseeSchoolParticularsImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUploadFromString(string $csv): UploadedFile
    {
        $dir = sys_get_temp_dir();
        $path = tempnam($dir, 'csv');
        file_put_contents($path, $csv);

        return new UploadedFile($path, 'csee-particulars.csv', 'text/csv', null, true);
    }

    public function test_import_from_csv_uses_existing_local_school_when_code_matches(): void
    {
        $region = Region::create([
            'code' => 'IR01',
            'name' => 'Iringa',
        ]);

        $district = District::create([
            'code' => 'IR0101',
            'name' => 'Iringa DC',
            'region_id' => $region->id,
            'status' => 'active',
        ]);

        $school = School::create([
            'code' => 'S1378',
            'name' => 'LOCAL SCHOOL NAME',
            'region_id' => $region->id,
            'ownership' => 'GOVERNMENT',
            'is_active' => true,
        ]);

        $csv = "Code,Name,Region ID,District ID,Ownership\n";
        $csv .= "S1378,CSV NAME,IR01,IR0101,NON-GOVERNMENT\n";

        $service = new CseeSchoolParticularsImportService();
        $result = $service->importFromCsv($this->makeUploadFromString($csv));

        $this->assertSame(1, $result['rows_processed']);
        $this->assertSame(1, $result['rows_updated']);
        $this->assertSame(0, $result['rows_created']);
        $this->assertSame(1, $result['rows_replaced']);
        $this->assertSame(0, $result['rows_failed']);
        $this->assertEmpty($result['errors']);

        $school->refresh();

        $this->assertSame(NectaCsee2025CentreSyncService::SOURCE_SYSTEM, $school->source_system);
        $this->assertSame($district->id, $school->district_id);
        $this->assertSame('NON-GOVERNMENT', $school->ownership);
        $this->assertSame('S1378', $school->code);
    }

    public function test_import_from_csv_creates_missing_csee_school_when_code_is_new(): void
    {
        $region = Region::create([
            'code' => 'IR01',
            'name' => 'Iringa',
        ]);

        $district = District::create([
            'code' => 'IR0101',
            'name' => 'Iringa DC',
            'region_id' => $region->id,
            'status' => 'active',
        ]);

        $csv = "Code,Name,Region ID,District ID,Ownership\n";
        $csv .= "S4197,NEW CSEE CENTRE,IR01,IR0101,GOVERNMENT\n";

        $service = new CseeSchoolParticularsImportService();
        $result = $service->importFromCsv($this->makeUploadFromString($csv));

        $this->assertSame(1, $result['rows_processed']);
        $this->assertSame(1, $result['rows_updated']);
        $this->assertSame(1, $result['rows_created']);
        $this->assertSame(0, $result['rows_replaced']);
        $this->assertSame(0, $result['rows_failed']);
        $this->assertEmpty($result['errors']);

        $school = School::where('code', 'S4197')->first();

        $this->assertNotNull($school);
        $this->assertSame(NectaCsee2025CentreSyncService::SOURCE_SYSTEM, $school->source_system);
        $this->assertSame('NEW CSEE CENTRE', $school->name);
        $this->assertSame('S4197', $school->registration_number);
        $this->assertSame($region->id, $school->region_id);
        $this->assertSame($district->id, $school->district_id);
        $this->assertSame('GOVERNMENT', $school->ownership);
        $this->assertSame(School::TYPE_SECONDARY, $school->school_type);
        $this->assertSame('SECONDARY', $school->education_level);
    }

    public function test_import_from_csv_treats_existing_particulars_as_replace_not_skip(): void
    {
        $region = Region::create([
            'code' => 'IR01',
            'name' => 'Iringa',
        ]);

        $district = District::create([
            'code' => 'IR0101',
            'name' => 'Iringa DC',
            'region_id' => $region->id,
            'status' => 'active',
        ]);

        School::create([
            'code' => 'S5000',
            'name' => 'MATCHED CSEE SCHOOL',
            'registration_number' => 'S5000',
            'source_system' => NectaCsee2025CentreSyncService::SOURCE_SYSTEM,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'ownership' => 'NON-GOVERNMENT',
            'school_type' => School::TYPE_SECONDARY,
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);

        $csv = "Code,Name,Region ID,District ID,Ownership\n";
        $csv .= "S5000,MATCHED CSEE SCHOOL,IR01,IR0101,NON-GOVERNMENT\n";

        $service = new CseeSchoolParticularsImportService();
        $result = $service->importFromCsv($this->makeUploadFromString($csv));

        $this->assertSame(1, $result['rows_processed']);
        $this->assertSame(1, $result['rows_updated']);
        $this->assertSame(0, $result['rows_created']);
        $this->assertSame(1, $result['rows_replaced']);
        $this->assertSame(0, $result['rows_skipped']);
        $this->assertSame(0, $result['rows_failed']);
        $this->assertEmpty($result['errors']);
        $this->assertEmpty($result['skips']);
    }
}
