<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MockPortalHeadteacherCandidateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-25'));
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow(null);
        parent::tearDown();
    }

    public function test_headteacher_can_store_candidate_for_own_school(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $examType = ExamType::factory()->psle()->create();
        $examYear = ExamYear::factory()->year2026()->create();

        $response = $this->actingAs($user)->post(route('mock-portal.school.candidate.store'), [
            'candidate_id' => 'PS0301001-0001',
            'full_name' => 'Amina Juma Hassan',
            'gender' => 'F',
            'prem_no' => '20261234567',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $candidate = Candidate::where('candidate_id', 'PS0301001-0001')->first();
        $this->assertNotNull($candidate);
        $this->assertSame($school->id, $candidate->school_id);
        $this->assertSame('Amina Juma Hassan', $candidate->full_name);
        $this->assertSame('F', $candidate->gender);
        $this->assertSame('20261234567', $candidate->prem_no);

        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $candidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
            'status' => 'APPROVED',
        ]);
    }

    public function test_headteacher_cannot_update_candidate_from_another_school(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $otherSchool = School::factory()->create([
            'code' => 'PS0301002',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $candidate = Candidate::factory()->create([
            'school_id' => $otherSchool->id,
            'candidate_id' => 'PS0301002-0001',
            'prem_no' => '20261234567',
            'full_name' => 'Foreign Candidate',
            'gender' => 'F',
            'exam_type' => 'PSLE',
            'candidate_type' => 'SCHOOL',
            'status' => 'registered',
        ]);

        $response = $this->actingAs($user)->put(route('mock-portal.school.candidate.update', $candidate), [
            'candidate_id' => 'PS0301001-0002',
            'full_name' => 'Changed Name',
            'gender' => 'M',
            'prem_no' => '20261234568',
        ]);

        $response->assertForbidden();
        $this->assertSame('Foreign Candidate', $candidate->fresh()->full_name);
    }

    public function test_headteacher_cannot_delete_candidate_from_another_school(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $otherSchool = School::factory()->create([
            'code' => 'PS0301002',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $candidate = Candidate::factory()->create([
            'school_id' => $otherSchool->id,
            'candidate_id' => 'PS0301002-0001',
            'prem_no' => '20261234567',
            'exam_type' => 'PSLE',
            'candidate_type' => 'SCHOOL',
            'status' => 'registered',
        ]);

        $response = $this->actingAs($user)->delete(route('mock-portal.school.candidate.destroy', $candidate));

        $response->assertForbidden();
        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
        ]);
    }

    public function test_headteacher_can_upload_csv_for_own_school(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $examType = ExamType::factory()->psle()->create();
        $examYear = ExamYear::factory()->year2026()->create();

        $csv = <<<CSV
Index Number,PReM No.,Full Name,Sex
PS0301001-0001,20261234567,Amina Juma Hassan,F
PS0301001-0002,20261234568,Emmanuel Mwenda,M
CSV;

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $response = $this->actingAs($user)->post(route('mock-portal.school.candidate.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'PS0301001-0001',
            'school_id' => $school->id,
            'full_name' => 'Amina Juma Hassan',
        ]);

        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'PS0301001-0002',
            'school_id' => $school->id,
            'full_name' => 'Emmanuel Mwenda',
        ]);

        $firstCandidate = Candidate::where('candidate_id', 'PS0301001-0001')->firstOrFail();
        $secondCandidate = Candidate::where('candidate_id', 'PS0301001-0002')->firstOrFail();

        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $firstCandidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
        ]);

        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $secondCandidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
        ]);
    }

    public function test_headteacher_csv_upload_returns_warning_for_partial_failures(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        ExamType::factory()->psle()->create();
        ExamYear::factory()->year2026()->create();

        $csv = <<<CSV
Index Number,PReM No.,Full Name,Sex
PS0301001-0001,20261234567,Amina Juma Hassan,F
PS0301001-0002,INVALID,Broken Candidate,M
CSV;

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $response = $this->actingAs($user)->post(route('mock-portal.school.candidate.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('candidates', [
            'candidate_id' => 'PS0301001-0001',
        ]);
        $this->assertDatabaseMissing('candidates', [
            'candidate_id' => 'PS0301001-0002',
        ]);
    }

    public function test_headteacher_csv_upload_keeps_identical_existing_candidate_unchanged(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $examType = ExamType::factory()->psle()->create();
        $examYear = ExamYear::factory()->year2026()->create();

        $existingCandidate = Candidate::factory()->create([
            'school_id' => $school->id,
            'candidate_id' => 'PS0301001-0001',
            'prem_no' => '20261234567',
            'full_name' => 'Amina Juma Hassan',
            'gender' => 'F',
            'exam_type' => 'PSLE',
            'candidate_type' => 'SCHOOL',
            'status' => 'registered',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $existingCandidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0301001-0001',
            'status' => 'APPROVED',
        ]);

        $csv = <<<CSV
Index Number,PReM No.,Full Name,Sex
PS0301001-0001,20261234567,Amina Juma Hassan,F
PS0301001-0002,20261234568,Emmanuel Mwenda,M
CSV;

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $response = $this->actingAs($user)->post(route('mock-portal.school.candidate.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('candidates', [
            'id' => $existingCandidate->id,
            'candidate_id' => 'PS0301001-0001',
            'full_name' => 'Amina Juma Hassan',
            'prem_no' => '20261234567',
        ]);

        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'PS0301001-0002',
            'school_id' => $school->id,
            'full_name' => 'Emmanuel Mwenda',
        ]);
    }

    public function test_headteacher_csv_upload_rejects_conflicting_existing_candidate_instead_of_overwriting(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        ExamType::factory()->psle()->create();
        ExamYear::factory()->year2026()->create();

        Candidate::factory()->create([
            'school_id' => $school->id,
            'candidate_id' => 'PS0301001-0001',
            'prem_no' => '20261234567',
            'full_name' => 'Original Candidate',
            'gender' => 'F',
            'exam_type' => 'PSLE',
            'candidate_type' => 'SCHOOL',
            'status' => 'registered',
        ]);

        $csv = <<<CSV
Index Number,PReM No.,Full Name,Sex
PS0301001-0001,20261234567,Changed Candidate Name,F
PS0301001-0002,20261234568,Emmanuel Mwenda,M
CSV;

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $response = $this->actingAs($user)->post(route('mock-portal.school.candidate.upload'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'PS0301001-0001',
            'full_name' => 'Original Candidate',
        ]);

        $this->assertDatabaseMissing('candidates', [
            'candidate_id' => 'PS0301001-0002',
        ]);
    }

    public function test_headteacher_csv_upload_can_replace_existing_candidate_when_enabled(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        $examType = ExamType::factory()->psle()->create();
        $examYear = ExamYear::factory()->year2026()->create();

        $existingCandidate = Candidate::factory()->create([
            'school_id' => $school->id,
            'candidate_id' => 'PS0301001-0001',
            'prem_no' => '20261234567',
            'full_name' => 'Original Candidate',
            'gender' => 'F',
            'exam_type' => 'PSLE',
            'candidate_type' => 'SCHOOL',
            'status' => 'rejected',
            'rejection_reason' => 'Old data issue',
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $existingCandidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0301001-0001',
            'status' => 'APPROVED',
        ]);

        $csv = <<<CSV
Index Number,PReM No.,Full Name,Sex
PS0301001-0001,20261239999,Updated Candidate Name,M
PS0301001-0002,20261234568,Emmanuel Mwenda,M
CSV;

        $file = UploadedFile::fake()->createWithContent('candidates.csv', $csv);

        $response = $this->actingAs($user)->post(route('mock-portal.school.candidate.upload'), [
            'csv_file' => $file,
            'replace_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('candidates', [
            'id' => $existingCandidate->id,
            'candidate_id' => 'PS0301001-0001',
            'prem_no' => '20261239999',
            'full_name' => 'Updated Candidate Name',
            'gender' => 'M',
            'status' => 'registered',
            'rejection_reason' => null,
        ]);

        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'PS0301001-0002',
            'school_id' => $school->id,
            'full_name' => 'Emmanuel Mwenda',
        ]);

        $this->assertDatabaseHas('candidate_exam_registrations', [
            'candidate_id' => $existingCandidate->id,
            'exam_type_id' => $examType->id,
            'exam_year_id' => $examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-PS0301001-0001',
        ]);
    }

    public function test_ownership_and_cal_report_are_rejected_when_registration_window_is_closed(): void
    {
        $school = School::factory()->create([
            'code' => 'PS0301001',
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $user = User::factory()->create([
            'portal_role' => 'mock_headteacher',
            'school_id' => $school->id,
            'status' => 'active',
            'password_reset_required' => false,
        ]);

        // Travel past the deadline (2026-04-20 + 31 days = 2026-05-21)
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-05-25'));

        try {
            // Test updateOwnership rejection
            $response = $this->actingAs($user)->post(route('mock-portal.school.update-ownership'), [
                'ownership' => 'GOVERNMENT',
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'success' => false,
                'message' => 'Registration period has expired. This action is no longer available.',
            ]);

            // Test calPdfReport rejection
            $response2 = $this->actingAs($user)->get(route('mock-portal.school.candidate.cal-report'));

            $response2->assertStatus(403);
            $response2->assertJson([
                'success' => false,
                'message' => 'Registration period has expired. This action is no longer available.',
            ]);
        } finally {
            \Carbon\Carbon::setTestNow(null);
        }
    }
}
