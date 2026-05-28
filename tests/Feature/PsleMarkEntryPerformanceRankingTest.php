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
use App\Models\MarkEntryAssignment;
use App\Models\MarkingCentre;
use App\Models\Region;
use App\Models\District;
use App\Models\MarkImportBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Carbon\Carbon;

class PsleMarkEntryPerformanceRankingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ExamYear $examYear;
    private ExamType $psle;
    private School $school1;
    private School $school2;
    private Subject $subject;
    private MarkingCentre $markingCentre;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Helpers\MarkEntrySettings::setGeofenceEnabled(false);

        // Create core structures
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

        // Region and Districts
        $region = Region::factory()->create(['name' => 'KILIMANJARO']);
        $district = District::create([
            'region_id' => $region->id,
            'name' => 'MOSHI',
            'code' => 'MOSHI01',
        ]);

        $this->school1 = School::factory()->create([
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'name' => 'School A',
        ]);

        $this->school2 = School::factory()->create([
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'name' => 'School B',
        ]);

        $this->subject = Subject::create([
            'exam_type_id' => $this->psle->id,
            'code' => 'MATH',
            'name' => 'Mathematics',
            'max_marks' => 50,
            'is_active' => true,
        ]);

        $this->markingCentre = MarkingCentre::create([
            'region_id' => $region->id,
            'code' => 'MC-KILI',
            'name' => 'Kilimanjaro Marking Centre',
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_rankings(): void
    {
        $response = $this->getJson('/api/mark-entry/psle/performance-rankings');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_rankings(): void
    {
        $officer = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $this->school1->region_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($officer)->getJson("/api/mark-entry/psle/performance-rankings?exam_year_id={$this->examYear->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'generated_at', 'scope', 'rankings']);
    }

    public function test_rankings_sorting_and_tie_breaker_logic(): void
    {
        // 1. Create four officers
        $officerA = User::factory()->create(['portal_role' => 'mark_officer', 'region_id' => $this->school1->region_id, 'status' => 'active', 'name' => 'Officer A']);
        $officerB = User::factory()->create(['portal_role' => 'mark_officer', 'region_id' => $this->school1->region_id, 'status' => 'active', 'name' => 'Officer B']);
        $officerC = User::factory()->create(['portal_role' => 'mark_officer', 'region_id' => $this->school1->region_id, 'status' => 'active', 'name' => 'Officer C']);
        $officerD = User::factory()->create(['portal_role' => 'mark_officer', 'region_id' => $this->school1->region_id, 'status' => 'active', 'name' => 'Officer D']);

        // 2. Create candidates for School 1 (5 candidates) and School 2 (7 candidates)
        $candidatesSchool1 = [];
        for ($i = 1; $i <= 5; $i++) {
            $cand = Candidate::factory()->school()->create(['school_id' => $this->school1->id, 'exam_type' => 'PSLE', 'is_active' => true]);
            CandidateExamRegistration::create([
                'candidate_id' => $cand->id,
                'exam_type_id' => $this->psle->id,
                'exam_year_id' => $this->examYear->id,
                'year' => 2026,
                'registration_number' => 'PSLE-S1-' . $cand->id,
                'status' => 'APPROVED',
            ]);
            $candidatesSchool1[] = $cand;
        }

        $candidatesSchool2 = [];
        for ($i = 1; $i <= 7; $i++) {
            $cand = Candidate::factory()->school()->create(['school_id' => $this->school2->id, 'exam_type' => 'PSLE', 'is_active' => true]);
            CandidateExamRegistration::create([
                'candidate_id' => $cand->id,
                'exam_type_id' => $this->psle->id,
                'exam_year_id' => $this->examYear->id,
                'year' => 2026,
                'registration_number' => 'PSLE-S2-' . $cand->id,
                'status' => 'APPROVED',
            ]);
            $candidatesSchool2[] = $cand;
        }

        // 3. Assignments
        // Officer A: Assigned to School 1 (5 candidates expected)
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school1->region_id, 'district_id' => $this->school1->district_id,
            'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'marking_centre_id' => $this->markingCentre->id, 'assigned_to' => $officerA->id, 'assigned_by' => $this->admin->id, 'status' => 'active'
        ]);

        // Officer B: Assigned to School 2 (expected count: 7 candidates)
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school2->region_id, 'district_id' => $this->school2->district_id,
            'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'marking_centre_id' => $this->markingCentre->id, 'assigned_to' => $officerB->id, 'assigned_by' => $this->admin->id, 'status' => 'active'
        ]);

        // Officer C: Assigned to School 1 (5 candidates expected)
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school1->region_id, 'district_id' => $this->school1->district_id,
            'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'marking_centre_id' => $this->markingCentre->id, 'assigned_to' => $officerC->id, 'assigned_by' => $this->admin->id, 'status' => 'active'
        ]);

        // Officer D: Assigned to School 2 (expected count: 7 candidates)
        MarkEntryAssignment::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school2->region_id, 'district_id' => $this->school2->district_id,
            'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'marking_centre_id' => $this->markingCentre->id, 'assigned_to' => $officerD->id, 'assigned_by' => $this->admin->id, 'status' => 'active'
        ]);

        // Create batches to link raw marks
        $batch1 = MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school1->region_id, 'district_id' => $this->school1->district_id,
            'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'exam_year' => '2026', 'created_by' => $this->admin->id, 'status' => 'draft', 'batch_code' => 'BATCH-A'
        ]);

        $batch2 = MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school2->region_id, 'district_id' => $this->school2->district_id,
            'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'exam_year' => '2026', 'created_by' => $this->admin->id, 'status' => 'draft', 'batch_code' => 'BATCH-B'
        ]);

        // 4. Enter marks & activity timestamps
        // Officer A: enters 3 marks on School 1. Completion = 3 / 5 = 60.0%. Last activity: 20 mins ago
        foreach (array_slice($candidatesSchool1, 0, 3) as $cand) {
            RawMark::factory()->create([
                'exam_year_id' => $this->examYear->id, 'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'candidate_id' => $cand->id, 'paper_1_marks' => 30, 'entered_by' => $officerA->id, 'mark_import_batch_id' => $batch1->id,
                'updated_at' => Carbon::now()->subMinutes(20), 'created_at' => Carbon::now()->subMinutes(20)
            ]);
        }

        // Officer B: enters 3 marks on School 2. Completion = 3 / 7 = 42.9%. Last activity: 30 mins ago
        foreach (array_slice($candidatesSchool2, 0, 3) as $cand) {
            RawMark::factory()->create([
                'exam_year_id' => $this->examYear->id, 'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'candidate_id' => $cand->id, 'paper_1_marks' => 35, 'entered_by' => $officerB->id, 'mark_import_batch_id' => $batch2->id,
                'updated_at' => Carbon::now()->subMinutes(30), 'created_at' => Carbon::now()->subMinutes(30)
            ]);
        }

        // Officer C: enters 2 marks on School 1. Completion = 2 / 5 = 40.0%. Last activity: 5 mins ago
        foreach (array_slice($candidatesSchool1, 3, 2) as $cand) {
            RawMark::factory()->create([
                'exam_year_id' => $this->examYear->id, 'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'candidate_id' => $cand->id, 'paper_1_marks' => 40, 'entered_by' => $officerC->id, 'mark_import_batch_id' => $batch1->id,
                'updated_at' => Carbon::now()->subMinutes(5), 'created_at' => Carbon::now()->subMinutes(5)
            ]);
        }

        // Officer D: enters 3 marks on School 2. Completion = 3 / 7 = 42.9%. Last activity: 10 mins ago (More recent than Officer B)
        foreach (array_slice($candidatesSchool2, 3, 3) as $cand) {
            RawMark::factory()->create([
                'exam_year_id' => $this->examYear->id, 'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'candidate_id' => $cand->id, 'paper_1_marks' => 38, 'entered_by' => $officerD->id, 'mark_import_batch_id' => $batch2->id,
                'updated_at' => Carbon::now()->subMinutes(10), 'created_at' => Carbon::now()->subMinutes(10)
            ]);
        }

        // 5. Query rankings as Admin (to get all records without region locks)
        $response = $this->actingAs($this->admin)->getJson("/api/mark-entry/psle/performance-rankings?exam_year_id={$this->examYear->id}");

        $response->assertOk();
        $rankings = $response->json('rankings');

        // Let's verify the counts, percentages, and order
        // Sorted by: marks_entered DESC, completion_percentage DESC, last_activity_timestamp DESC
        // Expected Order:
        // Rank 1: Officer A (Marks: 3, Completion: 60.0%, Last Activity: 20 mins ago) - Higher completion than D/B
        // Rank 2: Officer D (Marks: 3, Completion: 42.9%, Last Activity: 10 mins ago) - Tie-breaker 3 over B
        // Rank 3: Officer B (Marks: 3, Completion: 42.9%, Last Activity: 30 mins ago)
        // Rank 4: Officer C (Marks: 2, Completion: 40.0%, Last Activity: 5 mins ago)

        $this->assertCount(4, $rankings);

        $this->assertEquals($officerA->id, $rankings[0]['user_id']);
        $this->assertEquals($officerD->id, $rankings[1]['user_id']);
        $this->assertEquals($officerB->id, $rankings[2]['user_id']);
        $this->assertEquals($officerC->id, $rankings[3]['user_id']);

        $this->assertEquals(1, $rankings[0]['rank']);
        $this->assertEquals(2, $rankings[1]['rank']);
        $this->assertEquals(3, $rankings[2]['rank']);
        $this->assertEquals(4, $rankings[3]['rank']);
    }

    public function test_rankings_scope_filtering_district_school_subject(): void
    {
        $officer = User::factory()->create(['portal_role' => 'mark_officer', 'region_id' => $this->school1->region_id, 'status' => 'active']);

        $cand1 = Candidate::factory()->school()->create(['school_id' => $this->school1->id, 'exam_type' => 'PSLE', 'is_active' => true]);
        CandidateExamRegistration::create([
            'candidate_id' => $cand1->id, 'exam_type_id' => $this->psle->id, 'exam_year_id' => $this->examYear->id, 'year' => 2026, 'registration_number' => 'PSLE-F1', 'status' => 'APPROVED',
        ]);

        $cand2 = Candidate::factory()->school()->create(['school_id' => $this->school2->id, 'exam_type' => 'PSLE', 'is_active' => true]);
        CandidateExamRegistration::create([
            'candidate_id' => $cand2->id, 'exam_type_id' => $this->psle->id, 'exam_year_id' => $this->examYear->id, 'year' => 2026, 'registration_number' => 'PSLE-F2', 'status' => 'APPROVED',
        ]);

        $batch1 = MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school1->region_id, 'district_id' => $this->school1->district_id,
            'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'exam_year' => '2026', 'created_by' => $this->admin->id, 'status' => 'draft', 'batch_code' => 'BATCH-C1'
        ]);

        $batch2 = MarkImportBatch::create([
            'exam_year_id' => $this->examYear->id, 'exam_type_id' => $this->psle->id, 'region_id' => $this->school2->region_id, 'district_id' => $this->school2->district_id,
            'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'exam_year' => '2026', 'created_by' => $this->admin->id, 'status' => 'draft', 'batch_code' => 'BATCH-C2'
        ]);

        // Enter marks under School 1
        RawMark::factory()->create([
            'exam_year_id' => $this->examYear->id, 'school_id' => $this->school1->id, 'subject_id' => $this->subject->id, 'candidate_id' => $cand1->id, 'paper_1_marks' => 30, 'entered_by' => $officer->id, 'mark_import_batch_id' => $batch1->id
        ]);

        // Enter marks under School 2
        RawMark::factory()->create([
            'exam_year_id' => $this->examYear->id, 'school_id' => $this->school2->id, 'subject_id' => $this->subject->id, 'candidate_id' => $cand2->id, 'paper_1_marks' => 35, 'entered_by' => $officer->id, 'mark_import_batch_id' => $batch2->id
        ]);

        // Query with School 1 filter
        $response = $this->actingAs($this->admin)->getJson("/api/mark-entry/psle/performance-rankings?exam_year_id={$this->examYear->id}&school_id={$this->school1->id}");
        $response->assertOk();
        $rankings = $response->json('rankings');
        $this->assertEquals(1, $rankings[0]['marks_entered']);

        // Query with School 2 filter
        $response = $this->actingAs($this->admin)->getJson("/api/mark-entry/psle/performance-rankings?exam_year_id={$this->examYear->id}&school_id={$this->school2->id}");
        $response->assertOk();
        $rankings = $response->json('rankings');
        $this->assertEquals(1, $rankings[0]['marks_entered']);
    }

    public function test_region_assigned_meo_is_region_restricted(): void
    {
        // Setup two regions
        $regionA = Region::factory()->create(['name' => 'REGION A']);
        $regionB = Region::factory()->create(['name' => 'REGION B']);

        $districtA = District::create(['region_id' => $regionA->id, 'name' => 'DISTRICT A', 'code' => 'DA01']);
        $districtB = District::create(['region_id' => $regionB->id, 'name' => 'DISTRICT B', 'code' => 'DB01']);

        $schoolA = School::factory()->create(['region_id' => $regionA->id, 'district_id' => $districtA->id, 'school_type' => 'PRIMARY', 'education_level' => 'PRIMARY']);
        $schoolB = School::factory()->create(['region_id' => $regionB->id, 'district_id' => $districtB->id, 'school_type' => 'PRIMARY', 'education_level' => 'PRIMARY']);

        // Create MEO scoped to Region A
        $meo = User::factory()->create([
            'portal_role' => 'mark_officer',
            'region_id' => $regionA->id,
            'status' => 'active',
        ]);

        // MEO tries to view rankings for Region B (directly via request parameter)
        $response = $this->actingAs($meo)->getJson("/api/mark-entry/psle/performance-rankings?exam_year_id={$this->examYear->id}&region_id={$regionB->id}");

        $response->assertOk();
        // Since MEO is region-locked to Region A, the backend should override region_id to Region A
        $this->assertEquals($regionA->id, $response->json('scope.region_id'));
    }
}
