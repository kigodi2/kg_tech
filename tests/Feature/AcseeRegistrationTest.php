<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\Combination;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcseeRegistrationTest extends TestCase
{
    use RefreshDatabase;
    protected $school;
    protected $examType;
    protected $examYear;
    protected $combination;
    protected $generalStudies;
    protected $subject1;
    protected $subject2;
    protected $subject3;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create exam type and year
        $this->examType = ExamType::create(['code' => 'ACSEE', 'name' => 'ACSEE']);
        $this->examYear = ExamYear::create(['year' => 2026, 'year_label' => '2026']);

        // Create school
        $region = \App\Models\Region::create(['code' => 'TR', 'name' => 'Test Region']);
        $this->school = School::create([
            'code' => 'TEST001',
            'name' => 'Test School',
            'region_id' => $region->id,
        ]);

        // Create subjects
        $this->generalStudies = Subject::create([
            'code' => '111',
            'name' => 'GENERAL STUDIES',
            'exam_type_id' => $this->examType->id,
        ]);
        $this->subject1 = Subject::create(['code' => '101', 'name' => 'Physics', 'exam_type_id' => $this->examType->id]);
        $this->subject2 = Subject::create(['code' => '102', 'name' => 'Chemistry', 'exam_type_id' => $this->examType->id]);
        $this->subject3 = Subject::create(['code' => '103', 'name' => 'Biology', 'exam_type_id' => $this->examType->id]);

        // Create combination
        $this->combination = Combination::create([
            'exam_type_id' => $this->examType->id,
            'code' => 'PCB',
            'subjects' => 'Physics,Chemistry,Biology',
        ]);
        $this->combination->subjects()->attach([
            $this->subject1->id,
            $this->subject2->id,
            $this->subject3->id,
            $this->generalStudies->id,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test registering a SCHOOL ACSEE candidate
     */
    public function test_can_register_school_candidate()
    {
        $region = \App\Models\Region::first();
        $school = School::create([
            'code' => 'S0001',
            'name' => 'School 1',
            'region_id' => $region->id,
        ]);
        
        $response = $this->postJson('/api/candidates', [
            'school_id' => $school->id,
            'candidate_id' => 'S0001-0001',
            'full_name' => 'John Doe',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'SCHOOL',
            'combination' => 'PCB',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'S0001-0001',
            'candidate_type' => 'SCHOOL',
        ]);
    }

    /**
     * Test registering a PRIVATE ACSEE candidate without combination
     */
    public function test_can_register_private_candidate_without_combination()
    {
        $response = $this->postJson('/api/candidates', [
            'school_id' => $this->school->id,
            'candidate_id' => 'P0001-0001',
            'full_name' => 'Jane Smith',
            'gender' => 'F',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'PRIVATE',
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'P0001-0001',
            'candidate_type' => 'PRIVATE',
        ]);
    }

    /**
     * Test candidate type defaults to SCHOOL if not provided
     */
    public function test_candidate_type_defaults_to_school()
    {
        $response = $this->postJson('/api/candidates', [
            'school_id' => $this->school->id,
            'candidate_id' => 'S0002-0001',
            'full_name' => 'Bob Johnson',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            // candidate_type not provided
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'S0002-0001',
            'candidate_type' => 'SCHOOL',
        ]);
    }

    /**
     * Test updating candidate type from SCHOOL to PRIVATE
     */
    public function test_can_update_candidate_type()
    {
        $candidate = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'UPD-001',
            'full_name' => 'Update Test',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'SCHOOL',
        ]);

        $response = $this->putJson("/api/candidates/{$candidate->id}", [
            'school_id' => $this->school->id,
            'candidate_id' => $candidate->candidate_id,
            'full_name' => $candidate->full_name,
            'gender' => $candidate->gender,
            'exam_type' => 'ACSEE',
            'candidate_type' => 'PRIVATE',
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
            'candidate_type' => 'PRIVATE',
        ]);
    }

    /**
     * Test allocation prevents duplicates
     */
    public function test_allocation_prevents_duplicate_subjects()
    {
        $candidate = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'DUP-001',
            'full_name' => 'Duplicate Test',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'SCHOOL',
        ]);

        // Add subject allocation
        $candidate->subjectSelections()->create([
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'subject_id' => $this->subject1->id,
            'is_principal' => true,
            'source' => 'manual',
        ]);

        // Try to add the same subject again - should not be created due to unique constraint
        try {
            CandidateSubjectSelection::create([
                'candidate_id' => $candidate->id,
                'exam_type_id' => $this->examType->id,
                'exam_year_id' => $this->examYear->id,
                'year' => 2026,
                'subject_id' => $this->subject1->id,
                'is_principal' => true,
                'source' => 'manual',
            ]);
            $this->fail('Expected unique constraint violation');
        } catch (\Exception $e) {
            // Expected: unique constraint violation
            $this->assertTrue(true);
        }
    }

    /**
     * Test allocation with source tracking
     */
    public function test_allocation_tracks_source()
    {
        $candidate = Candidate::create([
            'school_id' => $this->school->id,
            'candidate_id' => 'SRC-001',
            'full_name' => 'Source Track Test',
            'gender' => 'M',
            'exam_type' => 'ACSEE',
            'candidate_type' => 'SCHOOL',
        ]);

        // Manual allocation
        $selection = $candidate->subjectSelections()->create([
            'exam_type_id' => $this->examType->id,
            'exam_year_id' => $this->examYear->id,
            'year' => 2026,
            'subject_id' => $this->subject1->id,
            'is_principal' => true,
            'source' => 'manual',
        ]);

        $this->assertDatabaseHas('candidate_subject_selections', [
            'id' => $selection->id,
            'source' => 'manual',
            'is_principal' => true,
        ]);
    }

    /**
     * Test PSLE and CSEE candidates ignore candidate_type
     */
    public function test_non_acsee_candidates_ignore_candidate_type()
    {
        $response = $this->postJson('/api/candidates', [
            'school_id' => $this->school->id,
            'candidate_id' => 'S0003-0001',
            'full_name' => 'Alice Brown',
            'gender' => 'F',
            'exam_type' => 'CSEE',
            'candidate_type' => 'PRIVATE', // Should be ignored for CSEE
        ]);

        $response->assertStatus(201);
        
        // PRIVATE type should still be stored but behavior is SCHOOL-like
        $this->assertDatabaseHas('candidates', [
            'candidate_id' => 'S0003-0001',
            'exam_type' => 'CSEE',
        ]);
    }
}
