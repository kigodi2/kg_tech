<?php

namespace Tests\Unit\Services\Results;

use Tests\TestCase;
use App\Services\Results\NectaGradingService;
use App\Models\Candidate;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\ExamType;
use App\Models\School;

class NectaGradingServiceTest extends TestCase
{
    private NectaGradingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NectaGradingService();
    }

    // Grade Calculation Tests

    public function test_calculate_grade_excellent()
    {
        $grade = $this->service->calculateGrade(90);
        $this->assertEquals('A', $grade);

        $grade = $this->service->calculateGrade(80);
        $this->assertEquals('A', $grade);
    }

    public function test_calculate_grade_very_good()
    {
        $grade = $this->service->calculateGrade(75);
        $this->assertEquals('B', $grade);

        $grade = $this->service->calculateGrade(70);
        $this->assertEquals('B', $grade);
    }

    public function test_calculate_grade_good()
    {
        $grade = $this->service->calculateGrade(65);
        $this->assertEquals('C', $grade);

        $grade = $this->service->calculateGrade(60);
        $this->assertEquals('C', $grade);
    }

    public function test_calculate_grade_average()
    {
        $grade = $this->service->calculateGrade(55);
        $this->assertEquals('D', $grade);

        $grade = $this->service->calculateGrade(50);
        $this->assertEquals('D', $grade);
    }

    public function test_calculate_grade_satisfied()
    {
        $grade = $this->service->calculateGrade(45);
        $this->assertEquals('E', $grade);

        $grade = $this->service->calculateGrade(40);
        $this->assertEquals('E', $grade);
    }

    public function test_calculate_grade_unsatisfied()
    {
        $grade = $this->service->calculateGrade(38);
        $this->assertEquals('S', $grade);

        $grade = $this->service->calculateGrade(35);
        $this->assertEquals('S', $grade);
    }

    public function test_calculate_grade_fail()
    {
        $grade = $this->service->calculateGrade(30);
        $this->assertEquals('F', $grade);

        $grade = $this->service->calculateGrade(0);
        $this->assertEquals('F', $grade);
    }

    // Competence Level Tests

    public function test_get_competence_level_for_grades()
    {
        $this->assertEquals('Excellent', $this->service->getCompetenceLevel('A'));
        $this->assertEquals('Very Good', $this->service->getCompetenceLevel('B'));
        $this->assertEquals('Good', $this->service->getCompetenceLevel('C'));
        $this->assertEquals('Average', $this->service->getCompetenceLevel('D'));
        $this->assertEquals('Satisfactory', $this->service->getCompetenceLevel('E'));
        $this->assertEquals('Unsatisfactory', $this->service->getCompetenceLevel('S'));
        $this->assertEquals('Fail', $this->service->getCompetenceLevel('F'));
    }

    public function test_get_competence_level_unknown_grade()
    {
        $competence = $this->service->getCompetenceLevel('X');
        $this->assertEquals('Unknown', $competence);
    }

    // Grade Points Tests

    public function test_get_grade_points()
    {
        $this->assertEquals(1, $this->service->getGradePoints('A'));
        $this->assertEquals(2, $this->service->getGradePoints('B'));
        $this->assertEquals(3, $this->service->getGradePoints('C'));
        $this->assertEquals(4, $this->service->getGradePoints('D'));
        $this->assertEquals(5, $this->service->getGradePoints('E'));
        $this->assertEquals(6, $this->service->getGradePoints('S'));
        $this->assertEquals(7, $this->service->getGradePoints('F'));
    }

    public function test_get_grade_points_invalid_grade()
    {
        $points = $this->service->getGradePoints('X');
        $this->assertEquals(7, $points); // Default to F points
    }

    // Subject Exclusion Tests

    public function test_is_excluded_subject_general_studies()
    {
        $this->assertTrue($this->service->isExcludedSubject('GENERAL STUDIES'));
        $this->assertTrue($this->service->isExcludedSubject('general studies'));
        $this->assertTrue($this->service->isExcludedSubject('General Studies'));
    }

    public function test_is_excluded_subject_basic_applied_mathematics()
    {
        $this->assertTrue($this->service->isExcludedSubject('BASIC APPLIED MATHEMATICS'));
        $this->assertTrue($this->service->isExcludedSubject('basic applied mathematics'));
    }

    public function test_is_not_excluded_subject()
    {
        $this->assertFalse($this->service->isExcludedSubject('ENGLISH'));
        $this->assertFalse($this->service->isExcludedSubject('MATHEMATICS'));
        $this->assertFalse($this->service->isExcludedSubject('PHYSICS'));
    }

    public function test_get_excluded_subjects()
    {
        $excluded = $this->service->getExcludedSubjects();
        $this->assertCount(2, $excluded);
        $this->assertContains('GENERAL STUDIES', $excluded);
        $this->assertContains('BASIC APPLIED MATHEMATICS', $excluded);
    }

    // Grade Boundaries Tests

    public function test_get_grade_boundaries()
    {
        $boundaries = $this->service->getGradeBoundaries();
        $this->assertIsArray($boundaries);
        $this->assertCount(7, $boundaries);

        // Verify A grade boundary
        $aGrade = collect($boundaries)->firstWhere('grade', 'A');
        $this->assertEquals(79.5, $aGrade['min']);
        $this->assertEquals(100, $aGrade['max']);
    }

    public function test_get_grade_points_mapping()
    {
        $mapping = $this->service->getGradePointsMapping();
        $this->assertIsArray($mapping);
        $this->assertCount(7, $mapping);
        $this->assertEquals(1, $mapping['A']);
        $this->assertEquals(7, $mapping['F']);
    }

    // Integration Tests with Database

    public function test_calculate_total_marks()
    {
        // Create test data
        $school = School::factory()->create();
        $candidate = Candidate::factory()->create(['school_id' => $school->id]);
        $examType = ExamType::factory()->create();

        // Create subjects and marks
        $subjects = [
            ['name' => 'ENGLISH', 'marks' => 75],
            ['name' => 'MATHEMATICS', 'marks' => 85],
            ['name' => 'GENERAL STUDIES', 'marks' => 55],
        ];

        foreach ($subjects as $subject) {
            $subj = Subject::factory()->create(['name' => $subject['name']]);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => $subject['marks'],
            ]);
        }

        $totalMarks = $this->service->calculateTotalMarks($candidate, $examType->id, 2024);
        
        // Total should be 75 + 85 + 55 = 215
        $this->assertEquals(215, $totalMarks);
    }

    public function test_calculate_total_marks_no_marks()
    {
        $candidate = Candidate::factory()->create();
        $examType = ExamType::factory()->create();

        $totalMarks = $this->service->calculateTotalMarks($candidate, $examType->id, 2024);
        
        $this->assertNull($totalMarks);
    }

    public function test_calculate_total_points()
    {
        $school = School::factory()->create();
        $candidate = Candidate::factory()->create(['school_id' => $school->id]);
        $examType = ExamType::factory()->create();

        // Create subjects and marks
        $subjects = [
            ['name' => 'ENGLISH', 'marks' => 75],      // B = 2 points
            ['name' => 'MATHEMATICS', 'marks' => 85],  // A = 1 point
            ['name' => 'GENERAL STUDIES', 'marks' => 55], // EXCLUDED
        ];

        foreach ($subjects as $subject) {
            $subj = Subject::factory()->create(['name' => $subject['name']]);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => $subject['marks'],
            ]);
        }

        $totalPoints = $this->service->calculateTotalPoints($candidate, $examType->id, 2024);
        
        // Total should be 2 + 1 = 3 (General Studies excluded)
        $this->assertEquals(3, $totalPoints);
    }

    public function test_calculate_gpa()
    {
        $school = School::factory()->create();
        $candidate = Candidate::factory()->create(['school_id' => $school->id]);
        $examType = ExamType::factory()->create();

        // Create subjects and marks
        $subjects = [
            ['name' => 'ENGLISH', 'marks' => 75],      // B = 2 points
            ['name' => 'MATHEMATICS', 'marks' => 85],  // A = 1 point
            ['name' => 'PHYSICS', 'marks' => 70],      // B = 2 points
            ['name' => 'CHEMISTRY', 'marks' => 80],    // A = 1 point
            ['name' => 'GENERAL STUDIES', 'marks' => 55], // EXCLUDED
        ];

        foreach ($subjects as $subject) {
            $subj = Subject::factory()->create(['name' => $subject['name']]);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => $subject['marks'],
            ]);
        }

        $gpa = $this->service->calculateGPA($candidate, $examType->id, 2024);
        
        // GPA = (2 + 1 + 2 + 1) / 4 = 6 / 4 = 1.5
        $this->assertEquals(1.5, $gpa);
    }

    public function test_calculate_gpa_with_basic_applied_math_excluded()
    {
        $school = School::factory()->create();
        $candidate = Candidate::factory()->create(['school_id' => $school->id]);
        $examType = ExamType::factory()->create();

        // Create subjects including BASIC APPLIED MATHEMATICS
        $subjects = [
            ['name' => 'ENGLISH', 'marks' => 80],      // A = 1 point
            ['name' => 'MATHEMATICS', 'marks' => 80],  // A = 1 point
            ['name' => 'BASIC APPLIED MATHEMATICS', 'marks' => 80], // EXCLUDED
        ];

        foreach ($subjects as $subject) {
            $subj = Subject::factory()->create(['name' => $subject['name']]);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => $subject['marks'],
            ]);
        }

        $gpa = $this->service->calculateGPA($candidate, $examType->id, 2024);
        
        // GPA = (1 + 1) / 2 = 2 / 2 = 1.0 (BASIC APPLIED MATHEMATICS excluded)
        $this->assertEquals(1.0, $gpa);
    }

    public function test_calculate_division_excellent()
    {
        $division = $this->service->calculateDivision(8.5);
        
        $this->assertIsArray($division);
        $this->assertEquals(1, $division['division']);
        $this->assertEquals('Excellent', $division['competence']);
    }

    public function test_calculate_division_very_good()
    {
        $division = $this->service->calculateDivision(12.0);
        
        $this->assertEquals(2, $division['division']);
        $this->assertEquals('Very Good', $division['competence']);
    }

    public function test_calculate_division_good()
    {
        $division = $this->service->calculateDivision(15.0);
        
        $this->assertEquals(3, $division['division']);
        $this->assertEquals('Good', $division['competence']);
    }

    public function test_calculate_division_average()
    {
        $division = $this->service->calculateDivision(18.0);
        
        $this->assertEquals(4, $division['division']);
        $this->assertEquals('Average', $division['competence']);
    }

    public function test_calculate_division_fail()
    {
        $division = $this->service->calculateDivision(21.0);
        
        $this->assertEquals(0, $division['division']);
        $this->assertEquals('Fail', $division['competence']);
    }

    public function test_calculate_overall_grade()
    {
        $school = School::factory()->create();
        $candidate = Candidate::factory()->create(['school_id' => $school->id]);
        $examType = ExamType::factory()->create();

        // Create subjects with varying marks
        $subjects = [
            ['name' => 'ENGLISH', 'marks' => 85],      // A
            ['name' => 'MATHEMATICS', 'marks' => 75],  // B
        ];

        foreach ($subjects as $subject) {
            $subj = Subject::factory()->create(['name' => $subject['name']]);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => $subject['marks'],
            ]);
        }

        $overallGrade = $this->service->calculateOverallGrade($candidate, $examType->id, 2024);
        
        // Best grade should be A
        $this->assertEquals('A', $overallGrade);
    }

    public function test_generate_grading_report()
    {
        $school = School::factory()->create();
        $candidate = Candidate::factory()->create(['school_id' => $school->id]);
        $examType = ExamType::factory()->create();

        // Create subjects
        $subjects = [
            ['name' => 'ENGLISH', 'marks' => 75],      // B
            ['name' => 'MATHEMATICS', 'marks' => 85],  // A
            ['name' => 'GENERAL STUDIES', 'marks' => 55], // D, excluded
        ];

        foreach ($subjects as $subject) {
            $subj = Subject::factory()->create(['name' => $subject['name']]);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => $subject['marks'],
            ]);
        }

        $report = $this->service->generateGradingReport($candidate, $examType->id, 2024);

        // Verify report structure
        $this->assertIsArray($report);
        $this->assertEquals($candidate->id, $report['candidate_id']);
        $this->assertEquals($candidate->full_name, $report['candidate_name']);
        $this->assertEquals(215, $report['total_marks']);
        $this->assertEquals(3, $report['total_points']); // 2 + 1
        $this->assertEquals(1.5, $report['gpa']); // 3 / 2
        $this->assertCount(3, $report['subject_grades']);
        $this->assertCount(2, $report['included_subject_grades']);
        $this->assertCount(1, $report['excluded_subject_grades']);
    }

    public function test_process_batch_grading()
    {
        $school = School::factory()->create();
        $examType = ExamType::factory()->create();

        // Create multiple candidates
        $candidates = Candidate::factory()->count(3)->create(['school_id' => $school->id]);

        foreach ($candidates as $candidate) {
            $subj = Subject::factory()->create(['name' => 'ENGLISH']);
            SubjectMarks::factory()->create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subj->id,
                'exam_type_id' => $examType->id,
                'year' => 2024,
                'marks_obtained' => 75,
            ]);
        }

        $results = $this->service->processBatchGrading($examType->id, 2024, $school->id);

        $this->assertIsArray($results);
        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('candidate_id', $result);
            $this->assertArrayHasKey('gpa', $result);
            $this->assertArrayHasKey('division', $result);
        }
    }
}
