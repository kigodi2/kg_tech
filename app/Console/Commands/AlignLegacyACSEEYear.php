<?php

namespace App\Console\Commands;

use App\Models\ExamYear;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * AlignLegacyACSEEYear
 *
 * One-time command to assign legacy ACSEE candidates to an explicit exam year.
 *
 * Usage: php artisan acsee:align-legacy-year
 *
 * This command:
 * 1. Shows available exam years
 * 2. Requires explicit year selection (no auto-assign)
 * 3. Lists affected candidates/subjects
 * 4. Requires confirmation before execution
 * 5. Logs affected records for audit trail
 * 6. Creates exam_year_audit_logs entry
 *
 * SAFETY CONSTRAINTS:
 * ❌ Does NOT auto-run on production
 * ❌ Does NOT assume default year
 * ✅ Requires explicit confirmation
 * ✅ Provides clear audit trail
 */
class AlignLegacyACSEEYear extends Command
{
    protected $signature = 'acsee:align-legacy-year';

    protected $description = 'Assign legacy ACSEE candidates to an explicit exam year. (One-time legacy data alignment)';

    public function handle()
    {
        $this->info('=== ACSEE Legacy Year Alignment ===');
        $this->newLine();

        // Step 1: Show available exam years
        $examYears = ExamYear::orderByDesc('year_label')->get();

        if ($examYears->isEmpty()) {
            $this->error('No exam years found in database. Please create exam years first.');
            return 1;
        }

        $this->info('Available Exam Years:');
        foreach ($examYears as $year) {
            $status = $year->is_active ? '(ACTIVE)' : ($year->is_locked ? '(LOCKED)' : '');
            $this->line("  [{$year->id}] {$year->year_label} {$status}");
        }
        $this->newLine();

        // Step 2: Get user input for target year
        $yearId = $this->ask('Enter exam year ID to assign legacy candidates to');

        if (!is_numeric($yearId)) {
            $this->error('Invalid exam year ID');
            return 1;
        }

        $targetYear = ExamYear::find($yearId);
        if (!$targetYear) {
            $this->error("Exam year with ID {$yearId} not found");
            return 1;
        }

        // Step 3: Check if year is locked
        if ($targetYear->is_locked) {
            $this->error("Cannot assign candidates to locked year: {$targetYear->year_label}");
            return 1;
        }

        // Step 4: Find legacy records
        $legacyRegistrations = CandidateExamRegistration::whereNull('exam_year_id')
            ->with('candidate', 'examType')
            ->get();

        $legacySelections = CandidateSubjectSelection::whereNull('exam_year_id')
            ->with('candidate', 'subject')
            ->get();

        $totalAffected = $legacyRegistrations->count() + $legacySelections->count();

        if ($totalAffected === 0) {
            $this->info('No legacy records found. All records are already aligned.');
            return 0;
        }

        // Step 5: Show preview
        $this->warn("Found {$totalAffected} legacy records:");
        $this->info("  Registrations: {$legacyRegistrations->count()}");
        $this->info("  Subject Selections: {$legacySelections->count()}");
        $this->newLine();

        $this->line('Sample registrations:');
        foreach ($legacyRegistrations->take(5) as $reg) {
            $this->line("  - Candidate: {$reg->candidate->candidate_id} | Exam Type: {$reg->examType->code} | Year: {$reg->year}");
        }

        if ($legacyRegistrations->count() > 5) {
            $this->line("  ... and " . ($legacyRegistrations->count() - 5) . " more");
        }

        $this->newLine();

        // Step 6: Require explicit confirmation
        if (!$this->confirm("Assign all {$totalAffected} records to year {$targetYear->year_label}?")) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Step 7: Execute alignment
        try {
            DB::beginTransaction();

            // Update registrations
            $regUpdated = CandidateExamRegistration::whereNull('exam_year_id')
                ->update(['exam_year_id' => $targetYear->id]);

            // Update selections
            $selUpdated = CandidateSubjectSelection::whereNull('exam_year_id')
                ->update(['exam_year_id' => $targetYear->id]);

            // Log to audit table (if it exists)
            if (DB::getSchemaBuilder()->hasTable('exam_year_audit_logs')) {
                DB::table('exam_year_audit_logs')->insert([
                    'exam_year_id' => $targetYear->id,
                    'user_id' => auth()->id(),
                    'action' => 'LEGACY_ALIGNMENT',
                    'affected_records' => $regUpdated + $selUpdated,
                    'details' => json_encode([
                        'registrations_updated' => $regUpdated,
                        'selections_updated' => $selUpdated,
                        'command' => 'acsee:align-legacy-year',
                    ]),
                    'executed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            // Step 8: Success message
            $this->info("✓ Alignment complete!");
            $this->line("  Registrations updated: {$regUpdated}");
            $this->line("  Subject selections updated: {$selUpdated}");
            $this->line("  Total records affected: " . ($regUpdated + $selUpdated));
            $this->newLine();
            $this->info("All legacy ACSEE data is now aligned to {$targetYear->year_label}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during alignment: ' . $e->getMessage());
            return 1;
        }
    }
}
