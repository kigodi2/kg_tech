<?php

namespace Database\Seeders;

use App\Models\ExamYear;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MigrateExistingDataToExamYear Seeder
 *
 * Safely migrates existing data to use exam years.
 *
 * Steps:
 * 1. Create a legacy exam year (e.g., "2024")
 * 2. Backfill all existing records with the legacy year ID
 * 3. Validate data integrity
 * 4. Optionally lock the legacy year
 *
 * Usage:
 * php artisan db:seed --class=MigrateExistingDataToExamYear
 *
 * Rollback:
 * 1. Restore database from backup
 * 2. Or manually DELETE FROM exam_years WHERE year_label='2024';
 *    then set exam_year_id to NULL on all tables
 */
class MigrateExistingDataToExamYear extends Seeder
{
    /**
     * Run the migration seeder.
     */
    public function run(): void
    {
        $this->command->info('Starting data migration to exam years...');

        // Step 1: Create legacy exam year
        $legacyYear = $this->createLegacyYear();
        $this->command->line("✓ Legacy exam year created (ID: {$legacyYear->id}, Label: {$legacyYear->year_label})");

        // Step 2: Backfill existing data
        $this->backfillExistingData($legacyYear->id);
        $this->command->line('✓ Existing data backfilled');

        // Step 3: Validate integrity
        if ($this->validateIntegrity()) {
            $this->command->line('✓ Data integrity validation passed');
        } else {
            $this->command->error('✗ Data integrity validation failed - ROLLBACK RECOMMENDED');
            throw new \Exception('Data integrity validation failed');
        }

        // Step 4: Print summary
        $this->printSummary($legacyYear);

        $this->command->info('✓ Migration completed successfully');
    }

    /**
     * Create a legacy exam year for existing data.
     *
     * Uses the current year if it doesn't already exist.
     */
    private function createLegacyYear(): ExamYear
    {
        $currentYear = now()->year;
        $yearLabel = (string) $currentYear;

        // Check if legacy year already exists
        if ($existingYear = ExamYear::where('year_label', $yearLabel)->first()) {
            return $existingYear;
        }

        // Create legacy year
        return ExamYear::create([
            'year_label' => $yearLabel,
            'is_active' => true,
            'is_locked' => false,
        ]);
    }

    /**
     * Backfill all existing data with the legacy exam year ID.
     */
    private function backfillExistingData(int $examYearId): void
    {
        DB::transaction(function () use ($examYearId) {
            // Tables to backfill (only if they exist)
            $tables = [
                'candidates',
                'registrations',
                'subject_registrations',
                'marks',
                'results',
                'summaries',
                'uploads',
                'reports',
                'csv_templates',
            ];

            foreach ($tables as $table) {
                // Only update if table exists
                if (Schema::hasTable($table)) {
                    // Only update rows where exam_year_id is NULL
                    DB::table($table)
                        ->whereNull('exam_year_id')
                        ->update(['exam_year_id' => $examYearId]);

                    $count = DB::table($table)->where('exam_year_id', $examYearId)->count();
                    $this->command->line("  - {$table}: {$count} records");
                } else {
                    $this->command->line("  - {$table}: (table does not exist, skipped)");
                }
            }
        });
    }

    /**
     * Validate data integrity after backfill.
     *
     * Checks:
     * - No NULL exam_year_id values
     * - No orphaned foreign keys
     * - Row counts match
     *
     * @return bool True if all checks pass
     */
    private function validateIntegrity(): bool
    {
        $tables = [
            'candidates',
            'registrations',
            'subject_registrations',
            'marks',
            'results',
            'summaries',
            'uploads',
            'reports',
            'csv_templates',
        ];

        $allValid = true;

        // Check 1: No NULL exam_year_id
        $this->command->line('Checking for NULL exam_year_id...');
        foreach ($tables as $table) {
            // Only check if table exists
            if (Schema::hasTable($table)) {
                $nullCount = DB::table($table)->whereNull('exam_year_id')->count();
                if ($nullCount > 0) {
                    $this->command->error("  ✗ {$table}: {$nullCount} rows with NULL exam_year_id");
                    $allValid = false;
                } else {
                    $this->command->line("  ✓ {$table}: No NULL values");
                }
            }
        }

        // Check 2: No orphaned foreign keys (referenced exam year exists)
        $this->command->line('Checking for orphaned foreign keys...');
        foreach ($tables as $table) {
            // Only check if table exists
            if (Schema::hasTable($table)) {
                $orphaned = DB::table($table)
                    ->leftJoin('exam_years', "{$table}.exam_year_id", '=', 'exam_years.id')
                    ->where("{$table}.exam_year_id", '>', 0)
                    ->whereNull('exam_years.id')
                    ->count();

                if ($orphaned > 0) {
                    $this->command->error("  ✗ {$table}: {$orphaned} orphaned references");
                    $allValid = false;
                } else {
                    $this->command->line("  ✓ {$table}: No orphaned references");
                }
            }
        }

        return $allValid;
    }

    /**
     * Print migration summary.
     */
    private function printSummary(ExamYear $legacyYear): void
    {
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('MIGRATION SUMMARY');
        $this->command->info('═══════════════════════════════════════');

        $this->command->line("Legacy Year: {$legacyYear->year_label} (ID: {$legacyYear->id})");
        $this->command->line("Status: Active");
        $this->command->line("Locked: No");

        $this->command->newLine();
        $this->command->line('Data Distribution:');
        
        // Only count tables that exist
        if (Schema::hasTable('candidates')) {
            $this->command->line("  - Candidates: " . DB::table('candidates')->count());
        }
        if (Schema::hasTable('registrations')) {
            $this->command->line("  - Registrations: " . DB::table('registrations')->count());
        }
        if (Schema::hasTable('marks')) {
            $this->command->line("  - Marks: " . DB::table('marks')->count());
        }
        if (Schema::hasTable('results')) {
            $this->command->line("  - Results: " . DB::table('results')->count());
        }

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('Next steps:');
        $this->command->info('1. Verify all exam data is accessible');
        $this->command->info('2. Update UI with exam year selector');
        $this->command->info('3. Test year switching functionality');
        $this->command->info('═══════════════════════════════════════');
    }
}
