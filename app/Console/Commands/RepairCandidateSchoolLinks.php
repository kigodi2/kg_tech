<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairCandidateSchoolLinks extends Command
{
    protected $signature = 'psle:repair-candidate-school-links
        {--commit : Commit changes to the database}';

    protected $description = 'Safely detect and repair PSLE candidates linked to mismatched school IDs.';

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $driver = DB::connection()->getDriverName();

        $this->info("Scanning candidate-to-school linkages using driver: {$driver}...");

        if ($driver === 'mysql') {
            $query = Candidate::query()
                ->join('schools', 'schools.id', '=', 'candidates.school_id')
                ->where('candidates.exam_type', 'PSLE')
                ->whereRaw("SUBSTRING_INDEX(candidates.candidate_id, '-', 1) <> schools.code")
                ->select('candidates.*');
        } else {
            // SQLite support
            $query = Candidate::query()
                ->join('schools', 'schools.id', '=', 'candidates.school_id')
                ->where('candidates.exam_type', 'PSLE')
                ->whereRaw("SUBSTR(candidates.candidate_id, 1, INSTR(candidates.candidate_id, '-') - 1) <> schools.code")
                ->select('candidates.*');
        }

        $mismatchedCandidates = $query->with('school')->get();

        if ($mismatchedCandidates->isEmpty()) {
            $this->info('All candidates are correctly linked to their matching school codes.');
            return self::SUCCESS;
        }

        $this->warn("Found {$mismatchedCandidates->count()} candidate(s) with mismatched school codes.");

        $headers = ['Candidate No', 'Current School', 'Correct School', 'Safe/Blocked Reason'];
        $tableData = [];
        $actions = [];

        // Summary Counters
        $countMissingTargetSchool = 0;
        $countFormatMismatch = 0;
        $countAcceptablePrefixVariant = 0;
        $countSafeToRepair = 0;
        $countHasMarks = 0;
        $countTargetInactive = 0;
        $countDuplicateTarget = 0;
        $countMultipleTargetSchools = 0;

        foreach ($mismatchedCandidates as $candidate) {
            $currentSchoolCode = $candidate->school?->code ?? 'N/A';
            $currentSchoolName = $candidate->school?->name ?? 'N/A';

            // Find correct school using prefix before dash
            $correctSchoolCode = strtok($candidate->candidate_id, '-');
            $correctSchools = School::where('code', $correctSchoolCode)->get();

            // Case 1: Target School code does not exist exactly
            if ($correctSchools->isEmpty()) {
                // Check if it is a prefix variant of current school (e.g. current school code starts with prefix or vice-versa)
                if (str_starts_with($currentSchoolCode, $correctSchoolCode) || str_starts_with($correctSchoolCode, $currentSchoolCode)) {
                    $tableData[] = [
                        $candidate->candidate_id,
                        "{$currentSchoolName} ({$currentSchoolCode})",
                        "{$correctSchoolCode} (Benign Prefix Variant)",
                        "ALREADY_ACCEPTABLE_PREFIX_VARIANT"
                    ];
                    $countAcceptablePrefixVariant++;
                } else if (levenshtein($correctSchoolCode, $currentSchoolCode) <= 3) {
                    $tableData[] = [
                        $candidate->candidate_id,
                        "{$currentSchoolName} ({$currentSchoolCode})",
                        "{$correctSchoolCode} (Format/Typo Mismatch)",
                        "BLOCKED_CODE_FORMAT_MISMATCH"
                    ];
                    $countFormatMismatch++;
                } else {
                    $tableData[] = [
                        $candidate->candidate_id,
                        "{$currentSchoolName} ({$currentSchoolCode})",
                        "{$correctSchoolCode} (Not Found)",
                        "BLOCKED_MISSING_TARGET_SCHOOL"
                    ];
                    $countMissingTargetSchool++;
                }
                continue;
            }

            // Case 2: Multiple schools with the target code exist
            if ($correctSchools->count() > 1) {
                $tableData[] = [
                    $candidate->candidate_id,
                    "{$currentSchoolName} ({$currentSchoolCode})",
                    "{$correctSchoolCode} (Duplicate Target School Code)",
                    "BLOCKED_MULTIPLE_TARGET_SCHOOLS"
                ];
                $countMultipleTargetSchools++;
                continue;
            }

            $correctSchool = $correctSchools->first();

            // Case 3: Target school exists but is inactive
            if (!$correctSchool->is_active) {
                $tableData[] = [
                    $candidate->candidate_id,
                    "{$currentSchoolName} ({$currentSchoolCode})",
                    "{$correctSchool->name} ({$correctSchool->code})",
                    "BLOCKED_TARGET_INACTIVE"
                ];
                $countTargetInactive++;
                continue;
            }

            // Case 4: Candidate already has marks under current wrong school
            $hasMarks = $candidate->marks()->exists() || $candidate->rawMarks()->exists();
            if ($hasMarks) {
                $tableData[] = [
                    $candidate->candidate_id,
                    "{$currentSchoolName} ({$currentSchoolCode})",
                    "{$correctSchool->name} ({$correctSchool->code})",
                    "BLOCKED_HAS_MARKS"
                ];
                $countHasMarks++;
                continue;
            }

            // Case 5: Candidate is already duplicated under correct school
            $isDuplicated = Candidate::where('candidate_id', $candidate->candidate_id)
                ->where('school_id', $correctSchool->id)
                ->exists();

            if ($isDuplicated) {
                $tableData[] = [
                    $candidate->candidate_id,
                    "{$currentSchoolName} ({$currentSchoolCode})",
                    "{$correctSchool->name} ({$correctSchool->code})",
                    "Blocked: Candidate ID already exists in correct school"
                ];
                $countDuplicateTarget++;
                continue;
            }

            // Safe to repair!
            $tableData[] = [
                $candidate->candidate_id,
                "{$currentSchoolName} ({$currentSchoolCode})",
                "{$correctSchool->name} ({$correctSchool->code})",
                "Safe to Repair"
            ];
            $countSafeToRepair++;

            $actions[] = [
                'candidate' => $candidate,
                'correct_school' => $correctSchool
            ];
        }

        $this->table($headers, array_slice($tableData, 0, 50));
        if (count($tableData) > 50) {
            $this->info("... and " . (count($tableData) - 50) . " more mismatched candidates.");
        }

        // Print Candidate Linkage Diagnostic Summary
        $this->info("\nCandidate Linkage Diagnostic Summary:");
        $this->line("  Missing target school codes: " . ($countMissingTargetSchool + $countFormatMismatch));
        $this->line("  Prefix variant already linked to active school: " . $countAcceptablePrefixVariant);
        $this->line("  Wrong school and safe to move: " . $countSafeToRepair);
        $this->line("  Wrong school but has marks: " . $countHasMarks);
        $this->line("  Target inactive: " . $countTargetInactive);

        if (empty($actions)) {
            $this->info("\nNo candidates are safe to repair automatically.");
            return self::SUCCESS;
        }

        if (!$commit) {
            $this->info("\n[DRY RUN] Run with --commit to repair the safe " . count($actions) . " school links.");
            return self::SUCCESS;
        }

        $this->info("\nCommitting safe repairs...");
        DB::beginTransaction();
        try {
            $repairedCount = 0;
            foreach ($actions as $action) {
                $candidate = $action['candidate'];
                $correctSchool = $action['correct_school'];

                $candidate->update([
                    'school_id' => $correctSchool->id,
                    'updated_at' => now(),
                ]);
                $repairedCount++;
            }

            DB::commit();
            $this->info("Successfully repaired {$repairedCount} candidate school link(s).");
            Log::info("Repaired {$repairedCount} mismatched PSLE candidate school links.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to repair candidate school links: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
