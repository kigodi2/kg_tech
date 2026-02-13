<?php

namespace App\Console\Commands;

use App\Models\Combination;
use App\Models\Subject;
use Illuminate\Console\Command;

class MigrateCombinationSubjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:combination-subjects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate subjects from string format to ManyToMany relationship';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting migration of combination subjects...');

        $combinations = Combination::all();
        $migratedCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($combinations as $combination) {
            try {
                // Skip if subjects column is empty or null
                if (empty($combination->subjects)) {
                    continue;
                }

                // Parse the subjects string
                $subjectCodes = array_map('trim', explode(',', $combination->subjects));
                
                // Find matching subjects by code
                $subjectIds = [];
                foreach ($subjectCodes as $code) {
                    $subject = Subject::where('code', $code)->first();
                    if ($subject) {
                        $subjectIds[] = $subject->id;
                    } else {
                        $this->warn("Subject code '{$code}' not found for combination {$combination->code}");
                    }
                }

                // Sync to pivot table
                if (!empty($subjectIds)) {
                    $combination->subjects()->sync($subjectIds);
                    $migratedCount++;
                    $this->line("✓ Migrated combination: {$combination->code} with " . count($subjectIds) . " subjects");
                }
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Combination {$combination->code}: {$e->getMessage()}";
                $this->error("✗ Failed to migrate combination {$combination->code}: {$e->getMessage()}");
            }
        }

        // Summary
        $this->info("\n" . str_repeat('=', 50));
        $this->info("Migration Summary:");
        $this->info("✓ Successfully migrated: {$migratedCount}");
        $this->info("✗ Failed: {$failedCount}");
        $this->info("Total: " . ($migratedCount + $failedCount));
        
        if (!empty($errors)) {
            $this->warn("\nErrors encountered:");
            foreach ($errors as $error) {
                $this->warn("  - {$error}");
            }
        }

        $this->info("\nNext step: Verify all combinations migrated correctly.");
        $this->info("Then remove the old 'subjects' column from combinations table.");

        return Command::SUCCESS;
    }
}
