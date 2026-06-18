<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Models\Role;
use App\Models\MarkingCentre;
use App\Models\MarkEntryAssignment;
use App\Models\District;
use App\Models\Region;
use App\Models\MarkImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PsleBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private User $reo;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private Subject $subject;
    private Candidate $candidate1;
    private Candidate $candidate2;
    private Region $region;
    private District $district;

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

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->region = Region::factory()->create(['name' => 'IRINGA']);
        $this->district = District::create([
            'region_id' => $this->region->id,
            'code' => 'IR01',
            'name' => 'IRINGA MC',
        ]);

        $this->school = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $this->district->id,
            'region_id' => $this->region->id,
        ]);

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

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        // Registered candidates
        $this->candidate1 = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
            'candidate_id' => 'PSLE-2026-0001',
            'full_name' => 'Pupil One',
            'gender' => 'M',
            'prem_no' => 'P12345',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidate1->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);

        $this->candidate2 = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
            'candidate_id' => 'PSLE-2026-0002',
            'full_name' => 'Pupil Two',
            'gender' => 'F',
            'prem_no' => 'P67890',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidate2->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0002',
            'status' => 'APPROVED',
        ]);
    }

    public function test_meo_can_view_bulk_import_page(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=bulk-import');
        
        $response->assertStatus(200);
        $response->assertViewIs('mark-entry.psle.index');
        $response->assertViewHas('currentView', 'bulk-import');
    }

    public function test_admin_and_reo_cannot_preview_or_commit_bulk_import(): void
    {
        $file = UploadedFile::fake()->createWithContent('template.csv', "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,45");

        // Admin Preview
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/bulk-import/preview', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);
        $response->assertStatus(403);

        // REO Preview
        $response = $this->actingAs($this->reo)->postJson('/mark-entry/psle/bulk-import/preview', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);
        $response->assertStatus(403);

        // Admin Commit
        $response = $this->actingAs($this->admin)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);
        $response->assertStatus(403);
    }

    public function test_meo_can_download_template_for_school_in_assigned_region(): void
    {
        $response = $this->actingAs($this->officer)->get("/mark-entry/psle/bulk-import/template?school_id={$this->school->id}&subject_id={$this->subject->id}&exam_year_id={$this->examYear->id}");
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('CNO,PReM,Name,Sex,Mark', $content);
        $this->assertStringContainsString('PSLE-2026-0001,P12345,"Pupil One",M,', $content);
        $this->assertStringContainsString('PSLE-2026-0002,P67890,"Pupil Two",F,', $content);
    }

    public function test_template_candidates_are_sorted_by_index_number_ascending(): void
    {
        $response = $this->actingAs($this->officer)->get("/mark-entry/psle/bulk-import/template?school_id={$this->school->id}&subject_id={$this->subject->id}&exam_year_id={$this->examYear->id}");
        $content = $response->streamedContent();
        
        $lines = explode("\n", trim($content));
        // Line 0 is header, Line 1 should be Pupil One, Line 2 should be Pupil Two
        $this->assertStringContainsString('PSLE-2026-0001', $lines[1]);
        $this->assertStringContainsString('PSLE-2026-0002', $lines[2]);
    }

    public function test_template_excludes_outside_school_candidates(): void
    {
        $otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $this->district->id,
            'region_id' => $this->region->id,
        ]);

        $otherCandidate = Candidate::factory()->school()->create([
            'school_id' => $otherSchool->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
            'candidate_id' => 'PSLE-2026-9999',
            'full_name' => 'Other Pupil',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $otherCandidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-9999',
            'status' => 'APPROVED',
        ]);

        $response = $this->actingAs($this->officer)->get("/mark-entry/psle/bulk-import/template?school_id={$this->school->id}&subject_id={$this->subject->id}&exam_year_id={$this->examYear->id}");
        $content = $response->streamedContent();

        $this->assertStringNotContainsString('PSLE-2026-9999', $content);
        $this->assertStringNotContainsString('Other Pupil', $content);
    }

    public function test_preview_validates_correct_csv_rows_without_saving(): void
    {
        $csvContent = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,42\nPSLE-2026-0002,P67890,Pupil Two,F,39";
        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/preview', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('totals.valid_rows', 2);
        $response->assertJsonPath('totals.invalid_rows', 0);

        // No marks should be committed to database yet
        $this->assertDatabaseEmpty('raw_marks');
    }

    public function test_preview_rejects_various_invalid_rows(): void
    {
        $csvContent = "CNO,PReM,Name,Sex,Mark\n"
                    . "UNKNOWN-CNO,P12345,Pupil One,M,42\n"       // Unknown CNO
                    . "PSLE-2026-0001,P12345,Pupil One,M,60\n"    // Mark out of range (> 50)
                    . "PSLE-2026-0002,P67890,Pupil Two,F,abc\n"   // Non-numeric mark
                    . "PSLE-2026-0001,P12345,Pupil One,M,45\n";   // Duplicate CNO

        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/preview', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('totals.invalid_rows', 4);
    }

    public function test_preview_rejects_candidate_from_another_school(): void
    {
        $otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $this->district->id,
            'region_id' => $this->region->id,
        ]);

        $otherCandidate = Candidate::factory()->school()->create([
            'school_id' => $otherSchool->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
            'candidate_id' => 'PSLE-2026-0099',
            'full_name' => 'Foreign Pupil',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $otherCandidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0099',
            'status' => 'APPROVED',
        ]);

        $csvContent = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0099,P12345,Foreign Pupil,M,40";
        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/preview', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('totals.invalid_rows', 1);
    }

    public function test_commit_saves_valid_rows_and_creates_draft_batch(): void
    {
        $csvContent = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,41\nPSLE-2026-0002,P67890,Pupil Two,F,32";
        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('summary.inserted', 2);

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate1->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => 41,
        ]);

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate2->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => 32,
        ]);

        $this->assertDatabaseHas('mark_import_batches', [
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'status' => 'draft',
            'created_by' => $this->officer->id,
        ]);
    }

    public function test_commit_updates_existing_marks_instead_of_duplicating(): void
    {
        // First import
        $csvContent = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,30";
        $file1 = UploadedFile::fake()->createWithContent('template1.csv', $csvContent);

        $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file1,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ])->assertOk();

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate1->id,
            'paper_1_marks' => 30,
        ]);

        // Second import (update)
        $csvContent2 = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,45";
        $file2 = UploadedFile::fake()->createWithContent('template2.csv', $csvContent2);

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file2,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('summary.updated', 1);

        // Verify mark was updated, and no duplicates exist
        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate1->id,
            'paper_1_marks' => 45,
        ]);
        $this->assertEquals(1, RawMark::where('candidate_id', $this->candidate1->id)->where('subject_id', $this->subject->id)->count());
    }

    public function test_commit_rejects_outside_region_school(): void
    {
        $otherRegion = Region::factory()->create(['name' => 'KILIMANJARO']);
        $otherDistrict = District::create([
            'region_id' => $otherRegion->id,
            'code' => 'KL01',
            'name' => 'MOSHI MC',
        ]);
        $otherSchool = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'district_id' => $otherDistrict->id,
            'region_id' => $otherRegion->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('template.csv', "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,45");

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file,
            'school_id' => $otherSchool->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_commit_rejects_locked_submitted_or_approved_batches(): void
    {
        // Save a mark first
        $csvContent = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,40";
        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ])->assertOk();

        // Submit the batch
        $batch = MarkImportBatch::first();
        $batch->status = 'submitted';
        $batch->save();

        // Try importing again
        $file2 = UploadedFile::fake()->createWithContent('template2.csv', "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,42");

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file2,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_imported_marks_appear_in_start_entry_sheet(): void
    {
        $csvContent = "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,48";
        $file = UploadedFile::fake()->createWithContent('template.csv', $csvContent);

        $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
        ])->assertOk();

        // Fetch start-entry entry-sheet view
        $response = $this->actingAs($this->officer)->get("/mark-entry/psle?view=entry-sheet&school_id={$this->school->id}&subject_id={$this->subject->id}&exam_year_id={$this->examYear->id}");
        
        $response->assertStatus(200);
        $candidates = $response->viewData('candidates');
        $this->assertEquals(48, $candidates->firstWhere('candidate_id', 'PSLE-2026-0001')->rawMarks->first()?->paper_1_marks);
    }

    public function test_non_psle_subject_is_rejected(): void
    {
        $nonPsleType = ExamType::factory()->create([
            'code' => 'CSEE',
            'name' => 'Certificate of Secondary Education Examination',
        ]);

        $nonPsleSubject = Subject::create([
            'exam_type_id' => $nonPsleType->id,
            'code' => 'OTHER',
            'name' => 'Other Subject',
            'max_marks' => 100,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent('template.csv', "CNO,PReM,Name,Sex,Mark\nPSLE-2026-0001,P12345,Pupil One,M,45");

        $response = $this->actingAs($this->officer)->postJson('/mark-entry/psle/bulk-import/confirm', [
            'file' => $file,
            'school_id' => $this->school->id,
            'subject_id' => $nonPsleSubject->id,
            'exam_year_id' => $this->examYear->id,
        ]);

        $response->assertStatus(422);
    }
}
