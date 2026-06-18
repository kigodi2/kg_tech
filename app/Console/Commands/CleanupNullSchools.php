<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupNullSchools extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psle:cleanup-null-schools {--commit : Save changes to database} {--delete : Hard delete unreferenced records instead of deactivating them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely clean up or deactivate schools with null council_id and null district_id';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $commit = $this->option('commit');
        $deleteMode = $this->option('delete');
        $mode = $commit ? 'REPAIR MODE' : 'AUDIT MODE';
        $action = $deleteMode ? 'HARD DELETE' : 'DEACTIVATE';

        $this->info("=== PSLE Null-Mapped Schools Safe Cleanup [{$mode}] ===");
        $this->info("Active DB: " . DB::connection()->getDatabaseName());
        $this->info("Cleanup Action: " . $action);
        $this->newLine();

        $schools = DB::table('schools')
            ->whereNull('council_id')
            ->whereNull('district_id')
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $this->warn(sprintf("Found %d schools with NULL council_id and NULL district_id.", $schools->count()));
        
        if ($schools->isEmpty()) {
            $this->info("No schools with NULL council_id and NULL district_id found. Database is clean!");
            return self::SUCCESS;
        }

        // Dynamically discover all tables containing a school_id column (except schools table itself)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table'"))->pluck('name');
            $schoolIdTables = collect();
            foreach ($tables as $table) {
                if ($table === 'schools') {
                    continue;
                }
                try {
                    $columns = DB::select("PRAGMA table_info(`{$table}`)");
                    foreach ($columns as $column) {
                        if ($column->name === 'school_id') {
                            $schoolIdTables->push($table);
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    // Ignore errors
                }
            }
        } else {
            $schoolIdTables = collect(DB::select("
                SELECT DISTINCT TABLE_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND COLUMN_NAME = 'school_id'
            "))
                ->pluck('TABLE_NAME')
                ->filter(fn ($table) => $table !== 'schools' && Schema::hasTable($table))
                ->values();
        }

        $this->line(sprintf("Discovered %d tables referencing 'school_id':", $schoolIdTables->count()));
        foreach ($schoolIdTables as $table) {
            $this->line("  - {$table}");
        }
        $this->newLine();

        $this->info("Checking references for unmapped schools...");

        $safe = [];
        $blocked = [];

        foreach ($schools as $school) {
            $references = [];

            foreach ($schoolIdTables as $table) {
                try {
                    $count = DB::table($table)->where('school_id', $school->id)->count();
                    if ($count > 0) {
                        $references[$table] = $count;
                    }
                } catch (\Throwable $e) {
                    $references[$table] = 'ERROR: ' . $e->getMessage();
                }
            }

            if (count($references) > 0) {
                $blocked[] = [$school, $references];
                $refStr = '';
                foreach ($references as $table => $count) {
                    $refStr .= "{$table}={$count} ";
                }
                $this->error(sprintf("  [BLOCKED] School: %s (%s) | References: %s", $school->name, $school->code, trim($refStr)));
            } else {
                $safe[] = $school;
                $this->line(sprintf("  [SAFE]    School: %s (%s)", $school->name, $school->code));
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->line("Total Null-Mapped Schools scanned: " . $schools->count());
        $this->line("Safe to remove/deactivate       : " . count($safe));
        $this->line("Blocked (Active references)     : " . count($blocked));
        $this->newLine();

        if (!$commit) {
            $this->info("Dry run completed. To commit these changes, run with the --commit option.");
            return self::SUCCESS;
        }

        $safeIds = collect($safe)->pluck('id')->values();

        if ($safeIds->isEmpty()) {
            $this->warn("No safe schools to remove or deactivate.");
            return self::SUCCESS;
        }

        $this->info(sprintf("Executing cleanup for %d safe schools...", $safeIds->count()));

        DB::beginTransaction();
        try {
            if ($deleteMode) {
                DB::table('schools')->whereIn('id', $safeIds)->delete();
                $this->info(sprintf("Successfully hard-deleted %d schools.", $safeIds->count()));
            } else {
                $updated = DB::table('schools')
                    ->whereIn('id', $safeIds)
                    ->update([
                        'is_active' => 0,
                        'updated_at' => Schema::hasColumn('schools', 'updated_at') ? now() : DB::raw('updated_at'),
                    ]);
                $this->info(sprintf("Successfully deactivated %d schools (set is_active = 0).", $updated));
            }
            DB::commit();
            $this->info("Database transaction committed successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Transaction failed and was rolled back: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
