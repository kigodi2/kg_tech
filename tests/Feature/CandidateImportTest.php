<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use App\Models\Combination;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Candidate;
use App\Services\Candidates\CandidateImportService;

class CandidateImportTest extends TestCase
{
  use RefreshDatabase;

  protected function setUp(): void
  {
    parent::setUp();

    // Create ACSEE exam type and an active exam year
    ExamType::create(['code' => 'ACSEE', 'name' => 'ACSEE']);
    ExamYear::create(['year_label' => '2026', 'is_active' => true]);

    // Create a region and a school
    $region = \App\Models\Region::create(['code' => 'R1', 'name' => 'TestRegion']);
    School::create(['code' => 'S001', 'name' => 'Test School', 'region_id' => $region->id]);

    // Create combinations
    $acsee = ExamType::where('code', 'ACSEE')->first();
    Combination::create(['exam_type_id' => $acsee->id, 'code' => 'HGL', 'subjects' => '', 'is_active' => true]);
    Combination::create(['exam_type_id' => $acsee->id, 'code' => 'OLD', 'subjects' => '', 'is_active' => true]);
  }

  private function makeUploadFromString(string $csv): UploadedFile
  {
    $dir = sys_get_temp_dir();
    $path = tempnam($dir, 'csv');
    file_put_contents($path, $csv);
    return new UploadedFile($path, 'candidates.csv', null, null, true);
  }

  public function test_import_creates_candidate_with_exact_combination()
  {
    $csv = "candidate_id,full_name,gender,school_code,combination\n";
    $csv .= "X001,John Doe,M,S001,HGL\n";

    $file = $this->makeUploadFromString($csv);

    $service = new CandidateImportService();
    $result = $service->commitImport($file, '2026', 'ACSEE', 'skip');

    $this->assertTrue($result['success']);
    $this->assertEquals(1, $result['imported_count']);

    $candidate = Candidate::where('candidate_id', 'X001')->first();
    $this->assertNotNull($candidate);
    $this->assertEquals('HGL', $candidate->combination);
    $this->assertNotNull($candidate->combination_id);
  }

  public function test_import_fails_on_unknown_combination()
  {
    $csv = "candidate_id,full_name,gender,school_code,combination\n";
    $csv .= "X002,Jane Doe,F,S001,ZZZ\n";

    $file = $this->makeUploadFromString($csv);

    $service = new CandidateImportService();
    $result = $service->commitImport($file, '2026', 'ACSEE', 'skip');

    $this->assertFalse($result['success']);
    $this->assertEquals(0, $result['imported_count']);
    $this->assertNotEmpty($result['errors']);
  }

  public function test_replace_mode_updates_combination_to_csv_value()
  {
    // existing candidate with OLD
    $school = School::where('code', 'S001')->first();
    $oldCombo = Combination::where('code', 'OLD')->first();
    Candidate::create([
      'school_id' => $school->id,
      'candidate_id' => 'X003',
      'full_name' => 'Existing',
      'gender' => 'M',
      'exam_type' => 'ACSEE',
      'combination' => 'OLD',
      'combination_id' => $oldCombo->id,
      'candidate_type' => 'SCHOOL',
      'status' => 'registered',
      'is_active' => true,
    ]);

    $csv = "candidate_id,full_name,gender,school_code,combination\n";
    $csv .= "X003,Existing Updated,M,S001,HGL\n";

    $file = $this->makeUploadFromString($csv);

    $service = new CandidateImportService();
    $result = $service->commitImport($file, '2026', 'ACSEE', 'replace');

    $this->assertTrue($result['success']);
    $this->assertEquals(0, $result['imported_count']);
    $this->assertEquals(1, $result['updated_count']);

    $candidate = Candidate::where('candidate_id', 'X003')->first();
    $this->assertEquals('HGL', $candidate->combination);
  }

  public function test_skip_mode_keeps_existing_combination()
  {
    $school = School::where('code', 'S001')->first();
    $oldCombo = Combination::where('code', 'OLD')->first();
    Candidate::create([
      'school_id' => $school->id,
      'candidate_id' => 'X004',
      'full_name' => 'Existing2',
      'gender' => 'F',
      'exam_type' => 'ACSEE',
      'combination' => 'OLD',
      'combination_id' => $oldCombo->id,
      'candidate_type' => 'SCHOOL',
      'status' => 'registered',
      'is_active' => true,
    ]);

    $csv = "candidate_id,full_name,gender,school_code,combination\n";
    $csv .= "X004,Existing2 Update,F,S001,HGL\n";

    $file = $this->makeUploadFromString($csv);

    $service = new CandidateImportService();
    $result = $service->commitImport($file, '2026', 'ACSEE', 'skip');

    $this->assertTrue($result['success']);
    $this->assertEquals(0, $result['imported_count']);
    $this->assertEquals(1, $result['skipped_count']);

    $candidate = Candidate::where('candidate_id', 'X004')->first();
    $this->assertEquals('OLD', $candidate->combination);
  }
}
