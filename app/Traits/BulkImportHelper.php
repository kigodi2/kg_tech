<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BulkImportHelper
 * 
 * Provides utilities for high-performance bulk imports:
 * - Benchmarking (time, memory, queries)
 * - Batch insertion
 * - Database optimization
 * - Progress tracking
 */
trait BulkImportHelper
{
    protected float $benchmarkStartTime;
    protected int $benchmarkStartMemory;
    protected int $benchmarkStartRowCount;
    protected int $benchmarkStartQueries;

    /**
     * Start benchmarking an import operation
     */
    public function startBenchmark(string $table = 'subject_marks'): void
    {
        $this->benchmarkStartTime = microtime(true);
        $this->benchmarkStartMemory = memory_get_usage();
        $this->benchmarkStartRowCount = DB::table($table)->count();
        
        // Disable query logging for performance
        DB::disableQueryLog();
    }

    /**
     * End benchmarking and return metrics
     */
    public function endBenchmark(string $table = 'subject_marks'): array
    {
        $executionTime = microtime(true) - $this->benchmarkStartTime;
        $memoryUsage = round((memory_get_usage() - $this->benchmarkStartMemory) / 1024 / 1024, 2);
        $rowsDiff = DB::table($table)->count() - $this->benchmarkStartRowCount;

        $formattedTime = match (true) {
            $executionTime >= 60 => sprintf('%dm %ds', floor($executionTime / 60), $executionTime % 60),
            $executionTime >= 1 => round($executionTime, 2) . 's',
            default => round($executionTime * 1000) . 'ms',
        };

        return [
            'time' => $formattedTime,
            'time_raw_seconds' => $executionTime,
            'memory_mb' => $memoryUsage,
            'rows_inserted' => $rowsDiff,
        ];
    }

    /**
     * Optimize database for bulk operations
     */
    public function optimizeForBulkImport(): void
    {
        // Disable query logging (huge performance boost)
        DB::disableQueryLog();

        // For MySQL specific optimization
        if (DB::getDriverName() === 'mysql') {
            try {
                // Disable foreign key checks temporarily
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                
                // Disable autocommit for faster inserts
                DB::statement('SET autocommit=0');
            } catch (\Exception $e) {
                Log::warning('Could not optimize MySQL settings: ' . $e->getMessage());
            }
        }
    }

    /**
     * Restore database to normal state
     */
    public function restoreFromBulkImport(): void
    {
        for ($i = 0; $i < 10; $i++) {
            if (DB::getDriverName() === 'mysql') {
                try {
                    // Re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=1');
                    
                    // Commit any pending transactions
                    DB::statement('COMMIT');
                    
                    // Re-enable autocommit
                    DB::statement('SET autocommit=1');
                    break;
                } catch (\Exception $e) {
                    if ($i === 9) {
                        Log::warning('Could not restore MySQL settings: ' . $e->getMessage());
                    }
                    sleep(1);
                }
            }
        }
    }

    /**
     * Clear garbage collection every N rows to prevent memory leaks
     */
    public function garbageCollectEvery(int $rowNumber, int $interval = 1000): void
    {
        if ($rowNumber % $interval === 0) {
            gc_collect_cycles();
        }
    }

    /**
     * Log benchmark results in a formatted way
     */
    public function logBenchmark(string $operation, array $metrics): void
    {
        Log::info("Bulk Import: {$operation}", [
            'time' => $metrics['time'],
            'time_seconds' => round($metrics['time_raw_seconds'], 2),
            'memory_mb' => $metrics['memory_mb'],
            'rows_inserted' => $metrics['rows_inserted'],
            'rows_per_second' => $metrics['rows_inserted'] > 0 
                ? round($metrics['rows_inserted'] / $metrics['time_raw_seconds']) 
                : 0,
        ]);
    }
}
