<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Services\Candidates\CseeCandidateSubjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CseeCandidateSubjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private CseeCandidateSubjectService $service;
    private ExamType $csee;
    private ExamYear $examYear;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CseeCandidateSubjectService::class);
        $this->csee = ExamType::create(['code' => 'CSEE', 'name' => 'CSEE']);
        $this->examYear = ExamYear::create(['year_label' => '2026', 'is_active' => true]);
        $region = Region::create(['code' => 'R1', 'name' => 'Region']);
        $school = School::create(['code' => 'S5191', 'name' => 'Centre', 'region_id' => $region->id]);

        foreach ([
            ['011', 'CIVICS'],
            ['012', 'HISTORY'],
            ['013', 'GEOGRAPHY'],
            ['021', 'KISWAHILI'],
            ['022', 'ENGLISH LANGUAGE'],
            ['033', 'BIOLOGY'],
            ['041', 'BASIC MATHEMATICS'],
            ['031', 'PHYSICS'],
            ['032', 'CHEMISTRY'],
            ['042', 'ADDITIONAL MATHEMATICS'],
            ['061', 'COMMERCE'],
        ] as [$code, $name]) {
            Subject::create([
                'exam_type_id' => $this->csee->id,
                'code' => $code,
                'name' => $name,
            ]);
        }

        $this->candidate = Candidate::create([
            'school_id' => $school->id,
            'candidate_id' => 'S51910001',
            'full_name' => 'Candidate One',
            'gender' => 'F',
            'exam_type' => 'CSEE',
            'candidate_type' => 'SCHOOL',
            'status' => 'registered',
            'is_active' => true,
        ]);
    }

    public function test_it_assigns_all_csee_core_subjects(): void
    {
        $result = $this->service->ensureCoreSubjects($this->candidate, $this->examYear);

        $this->assertSame(7, count($result['core_subject_ids']));
        $this->assertEquals(7, $this->candidate->subjectSelections()->where('exam_type_id', $this->csee->id)->where('exam_year_id', $this->examYear->id)->count());
    }

    public function test_it_keeps_core_subjects_and_limits_total_to_ten(): void
    {
        $physics = Subject::where('code', '031')->value('id');
        $chemistry = Subject::where('code', '032')->value('id');
        $additionalMath = Subject::where('code', '042')->value('id');

        $result = $this->service->syncSubjects($this->candidate, [$physics, $chemistry, $additionalMath], $this->examYear);

        $this->assertSame(10, $result['total_subjects']);
        $this->assertEquals(10, $this->candidate->subjectSelections()->where('exam_type_id', $this->csee->id)->where('exam_year_id', $this->examYear->id)->count());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be assigned more than 10 subjects');

        $this->service->syncSubjects(
            $this->candidate,
            [$physics, $chemistry, $additionalMath, Subject::where('code', '061')->value('id')],
            $this->examYear
        );
    }
}
