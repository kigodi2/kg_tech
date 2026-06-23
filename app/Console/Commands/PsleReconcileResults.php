<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\ExamYear;
use App\Models\AuditLog;
use App\Models\User;

class PsleReconcileResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'irms:psle-reconcile-results {year? : The exam year} {--exam-type=psle} {--fix : Apply the database updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely reconcile PSLE schools having null district_id by mapping them via council_id';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $yearInput = $this->argument('year') ?: '2026';
        $examType = strtolower($this->option('exam-type'));
        $isFix = $this->option('fix');

        if ($examType !== 'psle') {
            $this->error("Only PSLE exam type is supported by this command.");
            return 1;
        }

        $examYear = ExamYear::where('year_label', $yearInput)->first();
        if (!$examYear) {
            $this->error("Exam year '{$yearInput}' not found in the database.");
            return 1;
        }

        $this->info("Exam Year: {$examYear->year_label} (ID: {$examYear->id})");
        $this->info("Mode: " . ($isFix ? "FIX/APPLY (Writing changes to DB)" : "DRY-RUN (No database updates)"));
        $this->line("--------------------------------------------------");

        $targetSchoolsToFix = [
            9702 => [
                'old_name' => 'TIMKENI PRIMARY SCHOOL',
                'new_name' => 'TIMKETI PRIMARY SCHOOL',
            ],
            9703 => [
                'old_name' => 'FARM NYAMWEZI PRIMARY SCHOOL',
                'new_name' => 'FARM NYAMWEZI PRIMARY SCHOOL',
            ],
            9704 => [
                'old_name' => 'HENGELE PRIMARY SCHOOL',
                'new_name' => 'HENGLE PRIMARY SCHOOL',
            ],
            9705 => [
                'old_name' => 'LAND HILL PRE AND PRIMARY SCHOOL',
                'new_name' => 'LAND HILL PRIMARY SCHOOL',
            ],
            9664 => [
                'old_name' => 'KISWAHILINI PIMARY SCHOOL',
                'new_name' => 'KISWAHILINI PRIMARY SCHOOL',
            ]
        ];

        $targetSchoolsNeedUpdate = [];
        foreach ($targetSchoolsToFix as $schoolId => $fix) {
            $schoolRecord = DB::table('schools')->where('id', $schoolId)->first();
            if ($schoolRecord) {
                if ($schoolRecord->name !== $fix['new_name'] || $schoolRecord->source_system !== 'NECTA_PSLE_2025') {
                    $targetSchoolsNeedUpdate[$schoolId] = $fix;
                }
            }
        }

        // 1. Find all schools with null district_id and valid council_id
        $schools = DB::table('schools')
            ->whereNull('district_id')
            ->whereNotNull('council_id')
            ->get();

        if ($schools->isEmpty() && empty($targetSchoolsNeedUpdate)) {
            $this->info("No schools found requiring district_id mapping, and all target schools spelling and source_system are already up to date.");
            return 0;
        }

        $this->info("Found " . $schools->count() . " schools requiring reconciliation.");

        $reconciledSchools = [];
        $unmappableSchools = [];
        
        $regionCounts = [];
        $districtCounts = [];
        $councilCounts = [];

        foreach ($schools as $school) {
            $council = DB::table('district_councils')->where('id', $school->council_id)->first();
            if (!$council) {
                $unmappableSchools[] = [
                    'school' => $school,
                    'reason' => "Council ID {$school->council_id} does not exist in district_councils"
                ];
                continue;
            }

            // Note: district_councils does not have a direct district_id column.
            // We use code-to-code matching (councils.code matches districts.code).
            $district = DB::table('districts')->where('code', $council->code)->first();
            if (!$district) {
                $unmappableSchools[] = [
                    'school' => $school,
                    'reason' => "No district found matching council code '{$council->code}'"
                ];
                continue;
            }

            $reconciledSchools[] = [
                'school' => $school,
                'council' => $council,
                'district' => $district
            ];

            // Tally counts for reporting
            $regionName = DB::table('regions')->where('id', $school->region_id)->value('name') ?: "Region #{$school->region_id}";
            $regionCounts[$regionName] = ($regionCounts[$regionName] ?? 0) + 1;

            $districtCounts[$district->name] = ($districtCounts[$district->name] ?? 0) + 1;
            $councilCounts[$council->name] = ($councilCounts[$council->name] ?? 0) + 1;
        }

        // Show diagnostic details for target schools
        $targetNames = [
            'TIMKETI PRIMARY SCHOOL',
            'HENGLE PRIMARY SCHOOL',
            'FARM NYAMWEZI PRIMARY SCHOOL',
            'LAND HILL PRIMARY SCHOOL',
            'KISWAHILINI PRIMARY SCHOOL',
            'TIMKENI PRIMARY SCHOOL',
            'HENGELE PRIMARY SCHOOL',
            'FARM NYAMWEZI PRIMARY SC',
            'LAND HILL PRE AND PRIMARY SCHOOL',
            'KISWAHILINI PIMARY SCHOOL'
        ];

        $this->info("\n--- TARGET SCHOOL DIAGNOSTIC RESULT ---");
        $targetMatchedCount = 0;
        foreach ($reconciledSchools as $item) {
            $schoolName = $item['school']->name;
            if (in_array(strtoupper(trim($schoolName)), $targetNames)) {
                $targetMatchedCount++;
                $this->line("Target School: '{$schoolName}' (ID: {$item['school']->id})");
                $this->line("  -> Map to District: '{$item['district']->name}' (ID: {$item['district']->id}, Code: {$item['district']->code})");
                $this->line("  -> Via Council: '{$item['council']->name}' (ID: {$item['council']->id}, Code: {$item['council']->code})");
            }
        }
        if ($targetMatchedCount === 0) {
            $this->warn("None of the specific target schools were found in the reconcilable list (they may be already reconciled).");
        }

        // Show summary of all schools that will change
        $this->info("\n--- PROPOSED SCHOOL RECONCILIATIONS ---");
        $headers = ['School ID', 'School Name', 'Council Name', 'Target District Name', 'Target District ID'];
        $rows = [];
        foreach ($reconciledSchools as $item) {
            $rows[] = [
                $item['school']->id,
                $item['school']->name,
                $item['council']->name,
                $item['district']->name,
                $item['district']->id,
            ];
        }
        // Limit output to first 15 entries to prevent terminal flooding
        $this->table($headers, array_slice($rows, 0, 15));
        if (count($rows) > 15) {
            $this->line("... and " . (count($rows) - 15) . " more schools.");
        }

        if (count($unmappableSchools) > 0) {
            $this->error("\n--- UNMAPPABLE SCHOOLS ({" . count($unmappableSchools) . "}) ---");
            foreach ($unmappableSchools as $item) {
                $this->line("School: '{$item['school']->name}' (ID: {$item['school']->id}) - Reason: {$item['reason']}");
            }
        }

        // Output affected counts by region, district, and council
        $this->info("\n--- AFFECTED COUNTS BY REGION ---");
        foreach ($regionCounts as $region => $count) {
            $this->line("Region '{$region}': {$count} schools");
        }

        $this->info("\n--- AFFECTED COUNTS BY DISTRICT ---");
        foreach ($districtCounts as $district => $count) {
            $this->line("District '{$district}': {$count} schools");
        }

        $this->info("\n--- AFFECTED COUNTS BY COUNCIL ---");
        foreach ($councilCounts as $council => $count) {
            $this->line("Council '{$council}': {$count} schools");
        }

        // Target schools spelling corrections are defined at the start of the handle method

        $this->info("\n--- TARGET SCHOOLS SPELLING & SOURCE_SYSTEM UPDATES ---");
        foreach ($targetSchoolsToFix as $schoolId => $fix) {
            $schoolRecord = DB::table('schools')->where('id', $schoolId)->first();
            if ($schoolRecord) {
                $this->line("School ID: {$schoolId}");
                $this->line("  Current Name: '{$schoolRecord->name}' (Target Name: '{$fix['new_name']}')");
                $this->line("  Current Source System: '{$schoolRecord->source_system}' (Target: 'NECTA_PSLE_2025')");
            } else {
                $this->warn("School ID {$schoolId} not found in database.");
            }
        }

        if (!$isFix) {
            $this->info("\nDry-run completed. Run with `--fix` to apply updates to the database.");
            return 0;
        }

        // Apply changes
        $this->info("\nApplying changes inside a database transaction...");

        $adminUser = User::where('is_admin', true)->first();
        $adminId = $adminUser ? $adminUser->id : 1;

        $updatedCount = 0;
        $reconciledNames = [];

        DB::transaction(function () use ($reconciledSchools, $targetSchoolsToFix, &$updatedCount, &$reconciledNames) {
            foreach ($reconciledSchools as $item) {
                DB::table('schools')
                    ->where('id', $item['school']->id)
                    ->update([
                        'district_id' => $item['district']->id,
                        'updated_at' => now()
                    ]);

                $updatedCount++;
                $reconciledNames[] = $item['school']->name;
            }

            foreach ($targetSchoolsToFix as $schoolId => $fix) {
                $schoolRecord = DB::table('schools')->where('id', $schoolId)->first();
                if ($schoolRecord) {
                    DB::table('schools')
                        ->where('id', $schoolId)
                        ->update([
                            'name' => $fix['new_name'],
                            'source_system' => 'NECTA_PSLE_2025',
                            'updated_at' => now()
                        ]);
                    $this->info("Updated School ID {$schoolId}: Spelling corrected to '{$fix['new_name']}', source_system set to 'NECTA_PSLE_2025'.");
                }
            }
        });

        // Write to audit logs
        try {
            AuditLog::create([
                'user_id' => $adminId,
                'exam_year_id' => $examYear->id,
                'module' => 'results',
                'action' => 'psle_school_reconciliation',
                'details' => "Reconciled PSLE results. Mapped missing district_ids for {$updatedCount} schools, and updated spelling/source_system for target schools.",
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Artisan Command',
                'metadata' => [
                    'reconciled_count' => $updatedCount,
                    'schools' => array_slice($reconciledNames, 0, 100),
                    'target_fixes' => $targetSchoolsToFix
                ]
            ]);
            $this->info("Audit log written successfully.");
        } catch (\Exception $e) {
            $this->warn("Failed to write audit log: " . $e->getMessage());
        }

        $this->info("Successfully reconciled {$updatedCount} schools.");
        return 0;
    }
}
