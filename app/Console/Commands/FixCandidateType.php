<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Services\IndexNumber\IndexNumberValidator;
use Illuminate\Console\Command;

class FixCandidateType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'candidates:fix-type 
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Fix candidate_type for all candidates based on their index number prefix (S=SCHOOL, P=PRIVATE)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $validator = new IndexNumberValidator();

        // Get all candidates that might have wrong candidate_type
        $candidates = Candidate::whereNotNull('candidate_id')
            ->where('candidate_id', '!=', '')
            ->get();

        $toFix = [];
        $alreadyCorrect = 0;
        $unparseable = 0;

        $this->info("Analyzing " . $candidates->count() . " candidates...\n");

        foreach ($candidates as $candidate) {
            $parsed = $validator->parse($candidate->candidate_id);
            
            if (!$parsed) {
                $this->warn("Could not parse: {$candidate->candidate_id}");
                $unparseable++;
                continue;
            }

            $expectedType = $parsed->candidate_type;
            $actualType = $candidate->candidate_type;

            if ($expectedType !== $actualType) {
                $toFix[] = [
                    'id' => $candidate->id,
                    'index_number' => $candidate->candidate_id,
                    'current_type' => $actualType,
                    'expected_type' => $expectedType,
                ];
            } else {
                $alreadyCorrect++;
            }
        }

        // Display summary
        $this->line("\n--- Summary ---");
        $this->info("Already correct: $alreadyCorrect");
        $this->warn("To be fixed: " . count($toFix));
        $this->error("Unparseable: $unparseable");

        if (count($toFix) === 0) {
            $this->info("\nNo candidates need fixing!");
            return 0;
        }

        // Display candidates that need fixing
        $this->line("\n--- Candidates to Fix ---");
        foreach ($toFix as $item) {
            $this->line("ID: {$item['id']}, Index: {$item['index_number']}, Current: {$item['current_type']} → Expected: {$item['expected_type']}");
        }

        // Dry run check
        if ($dryRun) {
            $this->info("\nDry-run mode: No changes made.");
            return 0;
        }

        // Confirmation
        if (!$force && !$this->confirm("\nApply fixes to " . count($toFix) . " candidates?")) {
            $this->info("Cancelled.");
            return 1;
        }

        // Apply fixes
        $fixed = 0;
        $failed = 0;

        foreach ($toFix as $item) {
            try {
                Candidate::where('id', $item['id'])
                    ->update(['candidate_type' => $item['expected_type']]);
                $fixed++;
                $this->info("Fixed: {$item['index_number']} → {$item['expected_type']}");
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to fix ID {$item['id']}: " . $e->getMessage());
            }
        }

        // Final summary
        $this->line("\n--- Results ---");
        $this->info("Fixed: $fixed");
        if ($failed > 0) {
            $this->error("Failed: $failed");
        }

        return 0;
    }
}
