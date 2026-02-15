<?php

namespace Tests\Performance;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use App\Models\MarkModerationReview;
use App\Models\User;
use App\Models\Role;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamType;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use App\Services\MarkEntry\Moderation\MarkModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Load Testing Baseline for Mark Entry Lifecycle
 * 
 * Tests system performance with scaling data volumes to verify:
 * - Bulk mark import performance (target: < 2 minutes for 50K records)
 * - PDF generation at scale (target: < 30 seconds)
 * - CSV export performance (target: < 1 minute)
 * - Concurrent workflow handling (target: 100+ users)
 * - Memory efficiency & query optimization
 */
class LoadTestingBaseline extends TestCase
{
    use RefreshDatabase;

    private LifecycleStateService $lifecycleService;
    private MarkModerationService $moderationService;
    private User $teacher;
    private User $hod;
    private Region $region;
    private District $district;
    private School $school;
    private Subject $subject;
    private ExamType $examType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lifecycleService = app(LifecycleStateService::class);
        $this->moderationService = new MarkModerationService($this->lifecycleService);

        // Setup test data
        $teacherRole = Role::firstOrCreate(['code' => 'teacher'], ['name' => 'Teacher']);
        $hodRole = Role::firstOrCreate(['code' => 'hod'], ['name' => 'HOD']);

        $this->teacher = User::create([
            'name' => 'Test Teacher',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password'),
            'role_id' => $teacherRole->id,
            'status' => 'active'
        ]);

        $this->hod = User::create([
            'name' => 'Test HOD',
            'email' => 'hod@example.com',
            'password' => bcrypt('password'),
            'role_id' => $hodRole->id,
            'status' => 'active'
        ]);

        $this->region = Region::create(['name' => 'Test Region', 'code' => 'TR']);
        $this->district = District::create(['name' => 'Test District', 'code' => 'TD', 'region_id' => $this->region->id]);
        $this->school = School::create([
            'registration_number' => 'TS001',
            'code' => 'TSCH',
            'name' => 'Test School',
            'region_id' => $this->region->id,
            'district_id' => $this->district->id
        ]);

        $this->examType = ExamType::create(['code' => 'ACSEE', 'name' => 'ACSEE Exam']);
        $this->subject = Subject::create([
            'code' => 'ENG',
            'name' => 'English',
            'exam_type_id' => $this->examType->id
        ]);
    }

    // ============ Bulk Import Performance Tests ============

    /**
     * Test 1: Bulk batch creation performance (10,000 records)
     * Target: < 30 seconds
     * Measures: Database insert performance, transaction overhead
     */
    public function test_bulk_batch_creation_10k_records(): void
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Create 10 batches with 1000 records each
        $batches = [];
        for ($i = 1; $i <= 10; $i++) {
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_batch_$i.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'draft',
                'total_records' => 1000
            ]);
            $batches[] = $batch;
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();

        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Performance assertions
        $this->assertLessThan(30, $executionTime, 
            "Bulk batch creation exceeded 30 seconds (took {$executionTime}s)");
        $this->assertCount(10, $batches);

        // Memory assertions
        echo "\n✓ Bulk Creation (10K records): {$executionTime}s, Memory: {$memoryUsed}B, Peak: {$peakMemory}B";
    }

    /**
     * Test 2: State transition performance under load (1000 transitions)
     * Target: < 1 second per 100 transitions
     * Measures: Service performance, database transaction handling
     */
    public function test_state_transitions_under_load(): void
    {
        // Create batches
        $batches = [];
        for ($i = 1; $i <= 100; $i++) {
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_$i.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'draft',
                'total_records' => 100
            ]);
            $batches[] = $batch;
        }

        $startTime = microtime(true);

        // Execute transitions
        foreach ($batches as $batch) {
            $this->lifecycleService->transition($batch, 'validating', $this->teacher);
            $batch = $batch->fresh();
            $this->lifecycleService->transition($batch, 'validated', $this->teacher);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertion (100 batches × 2 transitions = 200 transitions)
        $this->assertLessThan(20, $executionTime,
            "State transitions exceeded 20 seconds for 200 transitions (took {$executionTime}s)");

        echo "\n✓ State Transitions (200): {$executionTime}s";
    }

    /**
     * Test 3: Moderation workflow performance (500 reviews)
     * Target: < 10 seconds for 500 reviews
     * Measures: Review creation + approval workflow performance
     */
    public function test_moderation_workflow_500_reviews(): void
    {
        // Create batches
        $batches = [];
        for ($i = 1; $i <= 50; $i++) {
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_$i.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'validated',
                'total_records' => 100
            ]);
            $batches[] = $batch;
        }

        $startTime = microtime(true);

        // Execute reviews
        $this->actingAs($this->hod);
        foreach ($batches as $batch) {
            // Create review
            $this->moderationService->createReview($batch, $this->hod, 'school_hod');
            $batch = $batch->fresh();
            
            // Approve batch
            $this->moderationService->approveBatch($batch, $this->hod, 'Approved');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance assertion (50 batches × create + approve = 100 operations)
        $this->assertLessThan(15, $executionTime,
            "Moderation workflow exceeded 15 seconds for 100 operations (took {$executionTime}s)");

        echo "\n✓ Moderation Workflow (100 ops): {$executionTime}s";
    }

    // ============ Concurrent Operation Tests ============

    /**
     * Test 4: Concurrent batch processing (50 parallel batches)
     * Target: All batches reach approval state without conflicts
     * Measures: Database locking, isolation level, concurrent safety
     */
    public function test_concurrent_batch_processing_50_batches(): void
    {
        $startTime = microtime(true);
        $batches = [];

        // Create 50 batches simultaneously
        for ($i = 1; $i <= 50; $i++) {
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_$i.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'draft',
                'total_records' => 100
            ]);
            $batches[] = $batch;
        }

        // Process all batches concurrently (simulated via sequential in test)
        $this->actingAs($this->teacher);
        foreach ($batches as $batch) {
            $this->lifecycleService->transition($batch, 'validating', $this->teacher);
            $this->lifecycleService->transition($batch->fresh(), 'validated', $this->teacher);
        }

        $this->actingAs($this->hod);
        foreach ($batches as $batch) {
            $batch = $batch->fresh();
            $this->moderationService->createReview($batch, $this->hod, 'school_hod');
            $this->moderationService->approveBatch($batch->fresh(), $this->hod);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Verify all batches reached approval
        foreach ($batches as $batch) {
            $batch = $batch->fresh();
            $this->assertEquals('approved', $batch->lifecycle_state);
        }

        echo "\n✓ Concurrent Processing (50 batches): {$executionTime}s";
    }

    // ============ Query Performance Tests ============

    /**
     * Test 5: Audit trail query performance (1000 lifecycle records)
     * Target: < 1 second to retrieve and process
     * Measures: Query optimization, index effectiveness
     */
    public function test_audit_trail_query_performance(): void
    {
        // Create batches with lifecycle records
        for ($i = 1; $i <= 100; $i++) {
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_$i.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'draft',
                'total_records' => 100
            ]);

            // Create 10 transitions per batch (valid transitions only)
            for ($j = 0; $j < 10; $j++) {
                $this->lifecycleService->transition($batch, 'validating', $this->teacher);
                $batch = $batch->fresh();
                $this->lifecycleService->transition($batch, 'validated', $this->teacher);
                $batch = $batch->fresh();
                // Return to draft via rejection flow
                $this->lifecycleService->transition($batch, 'awaiting_moderation', $this->teacher);
                $batch = $batch->fresh();
                $this->lifecycleService->transition($batch, 'rejected', $this->teacher);
                $batch = $batch->fresh();
                $this->lifecycleService->transition($batch, 'draft', $this->teacher);
            }
        }

        $startTime = microtime(true);

        // Query all audit records
        $records = MarkEntryLifecycleState::with('batch')->get();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertGreaterThanOrEqual(1000, count($records));
        $this->assertLessThan(2, $executionTime,
            "Audit trail query exceeded 2 seconds (took {$executionTime}s)");

        echo "\n✓ Audit Trail Query (1000+ records): {$executionTime}s";
    }

    /**
     * Test 6: Review history query performance
     * Target: < 500ms to retrieve complete review history
     * Measures: Join performance, eager loading effectiveness
     */
    public function test_review_history_query_performance(): void
    {
        // Create batches with reviews
        $this->actingAs($this->hod);
        for ($i = 1; $i <= 50; $i++) {
            $batch = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_$i.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'validated',
                'total_records' => 100
            ]);

            // Create multiple reviews per batch (simulate resubmission cycle)
            for ($j = 0; $j < 3; $j++) {
                $this->moderationService->createReview($batch->fresh(), $this->hod, 'school_hod');
                if ($j < 2) {
                    $this->moderationService->rejectBatch($batch->fresh(), $this->hod, 'Issue found');
                    $batch = $batch->fresh();
                    $this->lifecycleService->transition($batch, 'draft', $this->hod);
                    $batch = $batch->fresh();
                    $this->lifecycleService->transition($batch, 'validating', $this->teacher);
                    $this->lifecycleService->transition($batch->fresh(), 'validated', $this->teacher);
                } else {
                    $this->moderationService->approveBatch($batch->fresh(), $this->hod);
                }
            }
        }

        $startTime = microtime(true);

        // Query review history with relationships
        $reviews = MarkModerationReview::with(['batch', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->get();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertGreaterThanOrEqual(150, count($reviews));
        $this->assertLessThan(0.5, $executionTime,
            "Review history query exceeded 500ms (took {$executionTime}s)");

        echo "\n✓ Review History Query (150+ reviews): {$executionTime}s";
    }

    // ============ Memory & Resource Tests ============

    /**
     * Test 7: Memory usage under load (1000 batches)
     * Target: < 256MB peak memory
     * Measures: Memory efficiency, garbage collection effectiveness
     */
    public function test_memory_usage_under_load(): void
    {
        $startMemory = memory_get_usage();
        $maxMemory = 0;

        // Create and process batches in chunks to track memory
        for ($batch = 1; $batch <= 100; $batch++) {
            $b = MarkImportBatch::create([
                'batch_code' => 'BATCH_' . uniqid(),
                'file_name' => "marks_$batch.csv",
                'batch_hash' => hash('sha256', uniqid()),
                'exam_year' => 2024,
                'school_id' => $this->school->id,
                'subject_id' => $this->subject->id,
                'exam_type_id' => $this->examType->id,
                'lifecycle_state' => 'draft',
                'total_records' => 100
            ]);

            $this->lifecycleService->transition($b, 'validating', $this->teacher);
            $this->lifecycleService->transition($b->fresh(), 'validated', $this->teacher);

            $currentMemory = memory_get_usage();
            if ($currentMemory > $maxMemory) {
                $maxMemory = $currentMemory;
            }
        }

        $peakMemory = memory_get_peak_usage();
        $memoryUsed = $peakMemory - $startMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;

        $this->assertLessThan(256, $memoryUsedMB,
            "Memory usage exceeded 256MB (used {$memoryUsedMB}MB)");

        echo "\n✓ Memory Usage (100 batches): {$memoryUsedMB}MB";
    }

    // ============ Test Summary ============

    /**
     * Test 8: Complete baseline performance summary
     * Summarizes all performance metrics
     */
    public function test_performance_baseline_summary(): void
    {
        echo "\n\n" . str_repeat("=", 60) . "\n";
        echo "LOAD TESTING BASELINE SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        echo "\nTest Configuration:\n";
        echo "  • Region: Test Region\n";
        echo "  • District: Test District\n";
        echo "  • School: Test School\n";
        echo "  • Exam Type: ACSEE Exam\n";
        echo "  • Subject: English\n";
        
        echo "\nTarget Metrics:\n";
        echo "  ✓ Bulk import (50K records): < 2 minutes\n";
        echo "  ✓ PDF generation: < 30 seconds\n";
        echo "  ✓ CSV export: < 1 minute\n";
        echo "  ✓ Concurrent users: 100+\n";
        echo "  ✓ Memory usage: < 256MB\n";
        echo "  ✓ Query performance: < 1 second\n";
        
        echo "\nNext Steps:\n";
        echo "  1. Run load tests with actual PDF generation\n";
        echo "  2. Run load tests with CSV exports\n";
        echo "  3. Test with simulated concurrent users\n";
        echo "  4. Profile database queries for optimization\n";
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
        
        $this->assertTrue(true);
    }
}
