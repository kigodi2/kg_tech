<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\RawMark;
use App\Models\School;
use App\Models\Subject;
use App\Models\Region;
use App\Services\Results\CandidateResultStatusService;
use App\Services\Results\SchoolResultSummaryService;
use App\Services\Results\RegionalSchoolResultDiagnosticService;
use App\Services\Results\TasidoResultProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TasidoResultsProcessingTest extends TestCase
{
    use RefreshDatabase;

    private ExamYear $examYear;
    private ExamType $psle;
    private Region $region;
    private School $school;
    private array $subjects = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->examYear = ExamYear::create([
            'year_label' => '2026',
            'is_active' => true,
        ]);

        $this->psle = ExamType::factory()->psle()->create([
            'education_level' => 'PRIMARY',
        ]);

        $this->region = Region::factory()->create(['name' => 'DODOMA']);

        $this->school = School::factory()->create([
            'region_id' => $this->region->id,
            'school_type' => 'PRIMARY',
            'education_level' => 'PRIMARY',
            'name' => 'TASIDO TEST PRIMARY SCHOOL',
            'code' => 'PS1001',
        ]);

        // Create exactly 6 subjects for PSLE
        $codes = ['KISW', 'HIST', 'MATH', 'SCIE', 'CIVI', 'ENGL'];
        $names = ['Kiswahili', 'Maarifa ya Jamii', 'Hisabati', 'Sayansi', 'Uraia', 'English'];
        foreach ($codes as $idx => $code) {
            $this->subjects[] = Subject::create([
                'exam_type_id' => $this->psle->id,
                'code' => $code,
                'name' => $names[$idx],
                'max_marks' => 50,
                'is_active' => true,
            ]);
        }
    }

    public function test_candidate_result_status_evaluation(): void
    {
        $statusService = app(CandidateResultStatusService::class);
        $subjectIds = collect($this->subjects)->pluck('id')->toArray();

        // 1. COMPLETE Candidate: 6 numeric marks
        $candA = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'candidate_id' => 'PS1001/0001',
        ]);
        $marksA = [
            $subjectIds[0] => 45, // A
            $subjectIds[1] => 35, // B
            $subjectIds[2] => 25, // C
            $subjectIds[3] => 15, // D
            $subjectIds[4] => 5,  // E
            $subjectIds[5] => 42, // A
        ];
        $evalA = $statusService->evaluateCandidate($candA->id, $marksA, $subjectIds);
        $this->assertEquals('RELEASED', $evalA['db_status']);
        $this->assertEquals('COMPLETE', $evalA['status']);
        $this->assertNotNull($evalA['total_marks']);
        $this->assertNotNull($evalA['total_percentage']);
        $this->assertNotEquals('INC', $evalA['overall_grade']);
        $this->assertNotEquals('ABS', $evalA['overall_grade']);

        // 2. INC Candidate: 1-5 numeric marks
        $candB = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'candidate_id' => 'PS1001/0002',
        ]);
        $marksB = [
            $subjectIds[0] => 40,
            $subjectIds[1] => 30,
        ];
        $evalB = $statusService->evaluateCandidate($candB->id, $marksB, $subjectIds);
        $this->assertEquals('PENDING', $evalB['db_status']);
        $this->assertEquals('INC', $evalB['status']);
        $this->assertNull($evalB['total_marks']);
        $this->assertNull($evalB['total_percentage']);
        $this->assertEquals('INC', $evalB['overall_grade']);
        $this->assertNull($evalB['subjects'][$subjectIds[2]]['marks_obtained']);
        $this->assertEquals('INC', $evalB['subjects'][$subjectIds[2]]['grade']);

        // 3. ABS Candidate: 0 numeric marks
        $candC = Candidate::factory()->create([
            'school_id' => $this->school->id,
            'exam_type' => 'PSLE',
            'candidate_id' => 'PS1001/0003',
        ]);
        $marksC = [];
        $evalC = $statusService->evaluateCandidate($candC->id, $marksC, $subjectIds);
        $this->assertEquals('RELEASED', $evalC['db_status']);
        $this->assertEquals('ABS', $evalC['status']);
        $this->assertNull($evalC['total_marks']);
        $this->assertNull($evalC['total_percentage']);
        $this->assertEquals('ABS', $evalC['overall_grade']);
        $this->assertNull($evalC['subjects'][$subjectIds[0]]['marks_obtained']);
        $this->assertEquals('ABS', $evalC['subjects'][$subjectIds[0]]['grade']);
    }

    public function test_school_result_summary_aggregation(): void
    {
        $summaryService = app(SchoolResultSummaryService::class);

        // Mock a collection of candidate result DTOs/arrays
        $candidates = collect([
            // 2 Complete
            [
                'status' => 'COMPLETE',
                'total_marks' => 240,
                'overall_grade' => 'A',
                'gender' => 'F',
            ],
            [
                'status' => 'COMPLETE',
                'total_marks' => 180,
                'overall_grade' => 'B',
                'gender' => 'M',
            ],
            // 1 INC
            [
                'status' => 'INC',
                'total_marks' => null,
                'overall_grade' => 'INC',
                'gender' => 'F',
            ],
            // 1 ABS
            [
                'status' => 'ABS',
                'total_marks' => null,
                'overall_grade' => 'ABS',
                'gender' => 'M',
            ]
        ]);

        $summary = $summaryService->summarizeCandidates($candidates);

        $this->assertEquals(4, $summary['registered']);
        $this->assertEquals(3, $summary['sat']);
        $this->assertEquals(2, $summary['clean']);
        $this->assertEquals('INCOMPLETE', $summary['status_badge']);
        $this->assertEquals(210.0, $summary['average_marks']);
    }
}
