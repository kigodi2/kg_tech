<?php

namespace Tests\Unit\Services\Results;

use App\Models\Candidate;
use App\Models\District;
use App\Models\ExamType;
use App\Models\MarkImportBatch;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Services\Results\AcseeLiveMarkSetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AcseeLiveMarkSetServiceTest extends TestCase
{
    use RefreshDatabase;

    private AcseeLiveMarkSetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AcseeLiveMarkSetService::class);
    }

    public function test_ready_batch_pair_keys_are_unique_per_school_and_subject(): void
    {
        $examType = ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
        $region = Region::factory()->create(['name' => 'Iringa', 'code' => 'IR']);
        $district = District::create(['name' => 'Iringa DC', 'code' => 'IDC', 'region_id' => $region->id]);
        $school = School::create([
            'code' => 'S0101',
            'name' => 'Alpha Secondary School',
            'registration_number' => 'S0101',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);
        $subjectA = Subject::create(['code' => 'ENG', 'name' => 'ENGLISH', 'exam_type_id' => $examType->id]);
        $subjectB = Subject::create(['code' => 'HIS', 'name' => 'HISTORY', 'exam_type_id' => $examType->id]);

        MarkImportBatch::create([
            'batch_code' => 'B1',
            'exam_year' => 2026,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_id' => $school->id,
            'subject_id' => $subjectA->id,
            'exam_type_id' => $examType->id,
            'status' => MarkImportBatch::STATUS_LOCKED,
            'lifecycle_state' => 'locked',
            'total_records' => 1,
            'valid_records' => 1,
            'error_records' => 0,
        ]);

        MarkImportBatch::create([
            'batch_code' => 'B2',
            'exam_year' => 2026,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_id' => $school->id,
            'subject_id' => $subjectA->id,
            'exam_type_id' => $examType->id,
            'status' => MarkImportBatch::STATUS_APPROVED,
            'lifecycle_state' => 'approved',
            'total_records' => 1,
            'valid_records' => 1,
            'error_records' => 0,
        ]);

        MarkImportBatch::create([
            'batch_code' => 'B3',
            'exam_year' => 2026,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_id' => $school->id,
            'subject_id' => $subjectB->id,
            'exam_type_id' => $examType->id,
            'status' => MarkImportBatch::STATUS_LOCKED,
            'lifecycle_state' => 'locked',
            'total_records' => 1,
            'valid_records' => 1,
            'error_records' => 0,
        ]);

        $keys = $this->service->readyBatchPairKeys(MarkImportBatch::query());

        $this->assertSame([
            $school->id . ':' . $subjectA->id,
            $school->id . ':' . $subjectB->id,
        ], $keys);
    }

    public function test_current_live_subject_marks_use_latest_unsnapshotted_rows_and_ready_pair_filter(): void
    {
        $examType = ExamType::firstOrCreate(['code' => 'ACSEE'], ['name' => 'ACSEE']);
        $region = Region::factory()->create(['name' => 'Iringa', 'code' => 'IR']);
        $district = District::create(['name' => 'Iringa DC', 'code' => 'IDC', 'region_id' => $region->id]);
        $school = School::create([
            'code' => 'S0102',
            'name' => 'Beta Secondary School',
            'registration_number' => 'S0102',
            'region_id' => $region->id,
            'district_id' => $district->id,
            'school_type' => 'SECONDARY',
            'education_level' => 'SECONDARY',
            'is_active' => true,
        ]);
        $candidate = Candidate::factory()->school()->create([
            'school_id' => $school->id,
            'exam_type' => 'ACSEE',
            'status' => 'registered',
            'is_active' => true,
        ]);
        $subjectA = Subject::create(['code' => 'ENG', 'name' => 'ENGLISH', 'exam_type_id' => $examType->id, 'written_papers' => 1]);
        $subjectB = Subject::create(['code' => 'HIS', 'name' => 'HISTORY', 'exam_type_id' => $examType->id, 'written_papers' => 1]);

        SubjectMarks::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subjectA->id,
            'exam_type_id' => $examType->id,
            'year' => 2026,
            'marks_obtained' => 35,
            'paper_1' => 35,
            'max_marks' => 100,
            'percentage' => 35,
            'grade' => 'S',
            'snapshot_id' => null,
        ]);

        $latestLive = SubjectMarks::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subjectA->id,
            'exam_type_id' => $examType->id,
            'year' => 2026,
            'marks_obtained' => 78,
            'paper_1' => 78,
            'max_marks' => 100,
            'percentage' => 78,
            'grade' => 'B',
            'snapshot_id' => null,
        ]);

        SubjectMarks::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subjectA->id,
            'exam_type_id' => $examType->id,
            'year' => 2026,
            'marks_obtained' => 99,
            'paper_1' => 99,
            'max_marks' => 100,
            'percentage' => 99,
            'grade' => 'A',
            'snapshot_id' => 1,
        ]);

        SubjectMarks::create([
            'candidate_id' => $candidate->id,
            'subject_id' => $subjectB->id,
            'exam_type_id' => $examType->id,
            'year' => 2026,
            'marks_obtained' => 64,
            'paper_1' => 64,
            'max_marks' => 100,
            'percentage' => 64,
            'grade' => 'C',
            'snapshot_id' => null,
        ]);

        $rows = $this->service->currentLiveSubjectMarksCollection(
            new Request(['exam_year_id' => 1]),
            $examType->id,
            2026,
            static function ($query, $request, $candidateAlias, $schoolAlias): void {
            },
            true,
            null,
            [$school->id . ':' . $subjectA->id]
        );

        $this->assertCount(1, $rows);
        $this->assertSame($latestLive->id, (int) $rows->first()->id);
        $this->assertSame(78.0, (float) $rows->first()->marks_obtained);
        $this->assertSame('ENG', $rows->first()->subject?->code);
    }
}
