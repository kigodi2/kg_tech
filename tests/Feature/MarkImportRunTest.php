<?php

namespace Tests\Feature;

use App\Models\MarkImportRun;
use App\Models\MarkImportRunError;
use App\Models\MarkImportRunRow;
use App\Services\MarkImport\MarkImportRunService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Tests for MarkImportRun pipeline.
 * Run: php artisan test --filter=MarkImportRunTest
 */
class MarkImportRunTest extends BaseTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        // Must set PRAGMA outside of any transaction for SQLite
        DB::statement('PRAGMA foreign_keys = OFF');
    }

    public function test_import_run_created_and_status_set()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school',
            'file_name' => 'test.csv', 'status' => MarkImportRun::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $this->assertNotNull($run->id);
        $this->assertEquals(MarkImportRun::STATUS_PROCESSING, $run->status);

        $run->complete(10, 8, 2, 1, '8/10 rows valid');
        $run->refresh();

        $this->assertEquals(MarkImportRun::STATUS_COMPLETED, $run->status);
        $this->assertEquals(10, $run->total_rows);
        $this->assertEquals(8, $run->success_rows);
        $this->assertEquals(2, $run->error_rows);
        $this->assertNotNull($run->completed_at);
    }

    public function test_import_run_fail_sets_status()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school', 'file_name' => 'bad.csv',
            'status' => MarkImportRun::STATUS_PROCESSING, 'started_at' => now(),
        ]);

        $run->fail('Invalid file format');
        $run->refresh();

        $this->assertEquals(MarkImportRun::STATUS_FAILED, $run->status);
        $this->assertEquals('Invalid file format', $run->summary);
    }

    public function test_errors_recorded_with_structure()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school', 'file_name' => 'test.csv',
            'status' => MarkImportRun::STATUS_PROCESSING, 'started_at' => now(),
        ]);

        MarkImportRunError::create([
            'run_id' => $run->id, 'row_number' => 5, 'index_number' => 'S0101/0001',
            'subject_id' => 1, 'paper' => 'paper_p1', 'column_name' => 'paper_p1',
            'error_code' => 'OUT_OF_RANGE', 'severity' => 'error',
            'message' => 'Paper 1 marks must be 0-100 (got: 150)', 'raw_value' => '150',
        ]);

        $errors = $run->errors;
        $this->assertCount(1, $errors);
        $this->assertEquals('OUT_OF_RANGE', $errors->first()->error_code);
        $this->assertEquals('error', $errors->first()->severity);
    }

    public function test_preview_rows_stored()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school', 'file_name' => 'test.csv',
            'status' => MarkImportRun::STATUS_COMPLETED, 'started_at' => now(),
        ]);

        MarkImportRunRow::create([
            'run_id' => $run->id, 'row_number' => 1, 'index_number' => 'S0101/0001',
            'school_id' => 1, 'subject_id' => 1, 'paper_1' => 75.5, 'paper_2' => 80.0,
            'is_valid' => true, 'status' => 'pending', 'created_at' => now(),
        ]);

        MarkImportRunRow::create([
            'run_id' => $run->id, 'row_number' => 2, 'index_number' => 'S0101/0002',
            'school_id' => 1, 'subject_id' => 1, 'paper_1' => -5.0,
            'is_valid' => false, 'status' => 'rejected', 'created_at' => now(),
        ]);

        $rows = $run->rows;
        $this->assertCount(2, $rows);
        $this->assertTrue((bool) $rows[0]->is_valid);
        $this->assertFalse((bool) $rows[1]->is_valid);
    }

    public function test_error_csv_generation()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school', 'file_name' => 'test.csv',
            'status' => MarkImportRun::STATUS_COMPLETED, 'started_at' => now(), 'completed_at' => now(),
        ]);

        MarkImportRunError::create([
            'run_id' => $run->id, 'row_number' => 3, 'index_number' => 'S0101/0003',
            'error_code' => 'NOT_REGISTERED', 'severity' => 'error', 'message' => 'Candidate not registered',
        ]);

        $service = app(MarkImportRunService::class);
        $csv = $service->generateErrorCsv($run);

        $this->assertStringContainsString('Row,Index Number', $csv);
        $this->assertStringContainsString('S0101/0003', $csv);
        $this->assertStringContainsString('NOT_REGISTERED', $csv);
    }

    public function test_blocking_errors_prevent_can_commit()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school', 'file_name' => 'test.csv',
            'status' => MarkImportRun::STATUS_FAILED, 'started_at' => now(), 'completed_at' => now(),
            'total_rows' => 5, 'success_rows' => 0, 'error_rows' => 5,
        ]);

        $service = app(MarkImportRunService::class);
        $result = $service->buildResult($run, 5, 0, 5, 0);

        $this->assertFalse($result['can_commit']);
        $this->assertEquals('failed', $result['status']);
    }

    public function test_partial_errors_still_allow_commit()
    {
        $run = MarkImportRun::create([
            'user_id' => 1, 'exam_year_id' => 1, 'school_id' => 1, 'subject_id' => 1,
            'scope' => 'school', 'file_name' => 'test.csv',
            'status' => MarkImportRun::STATUS_COMPLETED, 'started_at' => now(), 'completed_at' => now(),
            'total_rows' => 10, 'success_rows' => 7, 'error_rows' => 3,
        ]);

        $service = app(MarkImportRunService::class);
        $result = $service->buildResult($run, 10, 7, 3, 0);

        $this->assertTrue($result['can_commit']);
        $this->assertEquals('partial', $result['status']);
    }
}
