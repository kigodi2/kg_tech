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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PsleMarkSaveTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $officer;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school;
    private Subject $subject;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Helpers\MarkEntrySettings::setGeofenceEnabled(false);

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

        $this->school = School::factory()->create([
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
        ]);

        $this->officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $this->candidate = Candidate::factory()->school()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'is_active' => true,
        ]);

        CandidateExamRegistration::create([
            'candidate_id' => $this->candidate->id,
            'exam_type_id' => $this->psle->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'registration_number' => 'PSLE-2026-0001',
            'status' => 'APPROVED',
        ]);

        $markingCentre = \App\Models\MarkingCentre::create([
            'region_id' => $this->school->region_id,
            'code' => 'MC1',
            'name' => 'Test Marking Centre',
            'status' => 'active',
        ]);

        \App\Models\MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id,
            'exam_type_id' => $this->psle->id,
            'region_id' => $this->school->region_id,
            'district_id' => $this->school->district_id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'marking_centre_id' => $markingCentre->id,
            'assigned_to' => $this->officer->id,
            'assigned_by' => $this->admin->id,
            'assignment_type' => 'entry',
            'status' => 'active',
            'active_lock' => 1,
            'starts_at' => now(),
        ]);
    }

    public function test_valid_psle_mark_saves_successfully(): void
    {
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 41]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Mark saved.')
            ->assertJsonPath('status', 'entered')
            ->assertJsonPath('candidate_id', $this->candidate->id)
            ->assertJsonStructure(['saved_at', 'mark_id', 'completion' => ['total_candidates', 'saved_count', 'pending_count', 'is_complete']]);

        $this->assertDatabaseHas('raw_marks', [
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'candidate_id' => $this->candidate->id,
            'paper_1_marks' => 41,
        ]);
    }

    public function test_invalid_mark_returns_422_json(): void
    {
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 75]));

        $response->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('type', 'validation_error');
    }

    public function test_unauthorized_school_returns_403_json(): void
    {
        $otherOfficer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->school->region_id + 999,
            'status' => 'active',
        ]);

        $response = $this->actingAs($otherOfficer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 33]));

        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('type', 'authorization_error');
    }

    public function test_repeated_save_updates_existing_row_without_duplicates(): void
    {
        $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 28]))->assertOk();
        $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 36]))->assertOk();

        $this->assertSame(1, RawMark::where([
            'exam_year_id' => $this->examYear->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'candidate_id' => $this->candidate->id,
        ])->count());

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate->id,
            'paper_1_marks' => 36,
        ]);
    }

    public function test_simulated_concurrent_saves_do_not_create_duplicates(): void
    {
        $first = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 21]));
        $second = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 22]));

        $first->assertOk();
        $second->assertOk();

        $this->assertSame(1, RawMark::where('candidate_id', $this->candidate->id)
            ->where('subject_id', $this->subject->id)
            ->where('exam_year_id', $this->examYear->id)
            ->count());
    }

    public function test_endpoint_returns_json_on_exception(): void
    {
        DB::statement('DROP TABLE raw_marks');

        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 31]));

        $this->assertTrue($response->headers->contains('content-type', 'application/json'));
        $this->assertFalse($response->json('ok'));
        $this->assertContains($response->status(), [500, 503]);
    }

    public function test_candidate_registration_view_redirects_safely_to_index(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=candidate-registration');

        $response->assertRedirect('/mark-entry/psle');
    }

    public function test_other_views_do_not_redirect_for_officers(): void
    {
        $response = $this->actingAs($this->officer)->get('/mark-entry/psle?view=start-entry');

        $response->assertStatus(200);
    }

    public function test_admin_and_reo_are_blocked_from_saving_marks(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 41]));
        $response->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('type', 'authorization_error');

        $reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        $responseReo = $this->actingAs($reo)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 41]));
        $responseReo->assertStatus(403)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('type', 'authorization_error');
    }

    public function test_admin_and_reo_are_redirected_from_restricted_views(): void
    {
        $response = $this->actingAs($this->admin)->get('/mark-entry/psle?view=start-entry');
        $response->assertRedirect('/mark-entry/psle');

        $reo = User::factory()->create([
            'portal_role' => 'reo',
            'region_id' => $this->school->region_id,
            'status' => 'active',
        ]);

        $responseReo = $this->actingAs($reo)->get('/mark-entry/psle?view=start-entry');
        $responseReo->assertRedirect('/mark-entry/psle');
    }

    public function test_concurrent_batch_creation_is_handled_gracefully(): void
    {
        // Clean up any existing batches
        \App\Models\MarkImportBatch::query()->delete();

        // Listen to the creating event of MarkImportBatch to simulate a concurrent insertion
        $hasRun = false;
        \App\Models\MarkImportBatch::creating(function ($batch) use (&$hasRun) {
            if (!$hasRun) {
                $hasRun = true;
                // Insert a conflicting batch with the exact same batch_code manually
                DB::table('mark_import_batches')->insert([
                    'batch_code' => $batch->batch_code,
                    'exam_year' => $batch->exam_year,
                    'exam_year_id' => $batch->exam_year_id,
                    'exam_type_id' => $batch->exam_type_id,
                    'region_id' => $batch->region_id,
                    'district_id' => $batch->district_id,
                    'school_id' => $batch->school_id,
                    'subject_id' => $batch->subject_id,
                    'status' => 'draft',
                    'batch_type' => 'manual',
                    'created_by' => $batch->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 45]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'entered');

        $this->assertEquals(1, \App\Models\MarkImportBatch::where('school_id', $this->school->id)
            ->where('subject_id', $this->subject->id)
            ->where('exam_year_id', $this->examYear->id)
            ->where('status', 'draft')
            ->count());
    }

    public function test_saving_abs_status_saves_correctly(): void
    {
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 'ABS']));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('subject_status', 'ABS')
            ->assertJsonPath('mark', 'ABS');

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => null,
            'subject_status' => 'ABS',
        ]);
    }

    public function test_clearing_score_saves_as_abs_status(): void
    {
        // First save a numeric mark
        $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 40]))->assertOk();

        // Now save empty string
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => '']));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('subject_status', 'ABS')
            ->assertJsonPath('mark', 'ABS');

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => null,
            'subject_status' => 'ABS',
        ]);
    }

    public function test_clearing_inc_mark_updates_to_abs_status(): void
    {
        // First save as INC
        $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 'INC']))->assertOk();

        // Now save null/empty
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => null]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('subject_status', 'ABS')
            ->assertJsonPath('mark', 'ABS');

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => null,
            'subject_status' => 'ABS',
        ]);
    }

    public function test_saving_inc_status_saves_correctly(): void
    {
        $response = $this->actingAs($this->officer)->postJson('/api/mark-entry/psle/marks/save', $this->payload(['score' => 'INC']));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('subject_status', 'INC')
            ->assertJsonPath('mark', 'INC');

        $this->assertDatabaseHas('raw_marks', [
            'candidate_id' => $this->candidate->id,
            'subject_id' => $this->subject->id,
            'paper_1_marks' => null,
            'subject_status' => 'INC',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'candidate_id' => $this->candidate->id,
            'school_id' => $this->school->id,
            'subject_id' => $this->subject->id,
            'exam_year_id' => $this->examYear->id,
            'score' => 40,
        ], $overrides);
    }
}
