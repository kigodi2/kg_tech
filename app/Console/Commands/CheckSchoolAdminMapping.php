<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\District;
use App\Models\DistrictCouncil;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSchoolAdminMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psle:check-school-admin-mapping {--fix-safe : Attempt to safely repair mismatched school administrative mappings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit and safely fix school administrative (Region / District / Council) mapping inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fixSafe = $this->option('fix-safe');
        $mode = $fixSafe ? 'REPAIR MODE' : 'AUDIT MODE';

        $this->info("=== PSLE School Administrative Mapping Audit [{$mode}] ===");
        $this->info("Active DB: " . DB::connection()->getDatabaseName());
        $this->newLine();

        // 1. Audit region/council mismatches
        $regionCouncilMismatches = School::with(['region', 'council'])
            ->where('is_active', true)
            ->whereNotNull('council_id')
            ->whereHas('council', function ($query) {
                $query->whereColumn('region_id', '<>', 'schools.region_id');
            })
            ->get();

        $this->warn(sprintf("Found %d schools with Region/Council mismatches.", $regionCouncilMismatches->count()));
        foreach ($regionCouncilMismatches as $school) {
            $this->line(sprintf(
                "  - School: %s (%s) | School Region: %s (ID %s) | Council: %s | Council Region: %s (ID %s)",
                $school->name,
                $school->code,
                $school->region ? $school->region->name : 'N/A',
                $school->region_id,
                $school->council ? $school->council->name : 'N/A',
                $school->council && $school->council->region ? $school->council->region->name : 'N/A',
                $school->council ? $school->council->region_id : 'N/A'
            ));
        }
        $this->newLine();

        // 2. Audit region/district mismatches
        $regionDistrictMismatches = School::with(['region', 'district'])
            ->where('is_active', true)
            ->whereNotNull('district_id')
            ->whereHas('district', function ($query) {
                $query->whereColumn('region_id', '<>', 'schools.region_id');
            })
            ->get();

        $this->warn(sprintf("Found %d schools with Region/District mismatches.", $regionDistrictMismatches->count()));
        foreach ($regionDistrictMismatches as $school) {
            $this->line(sprintf(
                "  - School: %s (%s) | School Region: %s (ID %s) | District: %s | District Region: %s (ID %s)",
                $school->name,
                $school->code,
                $school->region ? $school->region->name : 'N/A',
                $school->region_id,
                $school->district ? $school->district->name : 'N/A',
                $school->district && $school->district->region ? $school->district->region->name : 'N/A',
                $school->district ? $school->district->region_id : 'N/A'
            ));
        }
        $this->newLine();

        // 3. Audit Null mappings
        $nullCouncilSchools = School::where('is_active', true)->whereNull('council_id')->get();
        $nullDistrictSchools = School::where('is_active', true)->whereNull('district_id')->get();

        $this->warn(sprintf("Found %d schools with null council_id.", $nullCouncilSchools->count()));
        $this->warn(sprintf("Found %d schools with null district_id.", $nullDistrictSchools->count()));
        $this->newLine();

        // 4. District and Council name mismatches (e.g. UPPER(TRIM(districts.name)) <> UPPER(TRIM(district_councils.name)))
        $innerMismatches = School::with(['district', 'council'])
            ->where('is_active', true)
            ->whereNotNull('district_id')
            ->whereNotNull('council_id')
            ->get()
            ->filter(function ($school) {
                if (!$school->district || !$school->council) {
                    return false;
                }
                return strtoupper(trim($school->district->name)) !== strtoupper(trim($school->council->name));
            });

        $this->warn(sprintf("Found %d schools with District/Council name mismatches.", $innerMismatches->count()));
        foreach ($innerMismatches as $school) {
            $this->line(sprintf(
                "  - School: %s (%s) | District: %s | Council: %s",
                $school->name,
                $school->code,
                $school->district->name,
                $school->council->name
            ));
        }
        $this->newLine();

        // If not running in fix mode, we stop here
        if (!$fixSafe) {
            $this->info("Audit completed. To apply safe fixes, run with the --fix-safe option.");
            return self::SUCCESS;
        }

        // Collect all schools that need fixing
        $mismatchedSchoolIds = collect()
            ->concat($regionCouncilMismatches->pluck('id'))
            ->concat($regionDistrictMismatches->pluck('id'))
            ->concat($nullCouncilSchools->pluck('id'))
            ->concat($nullDistrictSchools->pluck('id'))
            ->concat($innerMismatches->pluck('id'))
            ->unique();

        if ($mismatchedSchoolIds->isEmpty()) {
            $this->info("No mismatched or corrupted schools found. Your administrative mappings are 100% correct!");
            return self::SUCCESS;
        }

        $this->info(sprintf("Attempting to safely repair %d schools...", $mismatchedSchoolIds->count()));

        $repairedCount = 0;
        $failedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($mismatchedSchoolIds as $schoolId) {
                $school = School::find($schoolId);
                if (!$school) {
                    continue;
                }

                $originalState = [
                    'region_id' => $school->region_id,
                    'district_id' => $school->district_id,
                    'council_id' => $school->council_id,
                ];

                $correctCouncil = null;

                // 1. Try to find a council in the school's region with the same name as the existing council
                if ($school->council_id) {
                    $currentCouncil = DistrictCouncil::find($school->council_id);
                    if ($currentCouncil) {
                        $correctCouncil = DistrictCouncil::where('region_id', $school->region_id)
                            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($currentCouncil->name))])
                            ->first();
                    }
                }

                // 2. Try to find a council in the school's region with the same name as the existing geographical district
                if (!$correctCouncil && $school->district_id) {
                    $currentDistrict = District::find($school->district_id);
                    if ($currentDistrict) {
                        $correctCouncil = DistrictCouncil::where('region_id', $school->region_id)
                            ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($currentDistrict->name))])
                            ->first();
                    }
                }

                // 3. Try to resolve using school code prefix (e.g. PS0404001 => PSLE25-0404)
                if (!$correctCouncil && preg_match('/^PS(\d{4})/i', $school->code, $matches)) {
                    $suffix = $matches[1];
                    $correctCouncil = DistrictCouncil::where('region_id', $school->region_id)
                        ->where('code', 'like', '%' . $suffix)
                        ->first();
                }

                // 4. Try name fallback: if the school name matches a known council partially or district partially
                if (!$correctCouncil) {
                    // Try to find any council in this region
                    $councilsInRegion = DistrictCouncil::where('region_id', $school->region_id)->get();
                    foreach ($councilsInRegion as $c) {
                        if (str_contains(strtoupper($school->name), strtoupper($c->name))) {
                            $correctCouncil = $c;
                            break;
                        }
                    }
                }

                if (!$correctCouncil) {
                    $this->error(sprintf("  [ERROR] Cannot safely resolve council for school: %s (%s)", $school->name, $school->code));
                    $failedCount++;
                    continue;
                }

                // Resolve geographical district matching the council name in the school's region
                $correctDistrict = District::where('region_id', $school->region_id)
                    ->whereRaw('UPPER(TRIM(name)) = ?', [strtoupper(trim($correctCouncil->name))])
                    ->first();

                if (!$correctDistrict) {
                    // Fallback to partial name match
                    $correctDistrict = District::where('region_id', $school->region_id)
                        ->where('name', 'like', '%' . $correctCouncil->name . '%')
                        ->first();
                }

                if (!$correctDistrict) {
                    $this->error(sprintf("  [ERROR] Cannot safely resolve geographical district for council: %s in region ID %s", $correctCouncil->name, $school->region_id));
                    $failedCount++;
                    continue;
                }

                // Update the school
                // Note: We bypass the model boot saving validation temporarily or let it pass because we are mapping them CORRECTLY.
                // Since they are now correct, the booted hook will actually pass perfectly!
                $school->council_id = $correctCouncil->id;
                $school->district_id = $correctDistrict->id;
                $school->save();

                $this->info(sprintf(
                    "  [FIXED] School: %s (%s) | Region ID %s | Updated District: %s (ID %s) | Council: %s (ID %s)",
                    $school->name,
                    $school->code,
                    $school->region_id,
                    $correctDistrict->name,
                    $correctDistrict->id,
                    $correctCouncil->name,
                    $correctCouncil->id
                ));
                $repairedCount++;
            }

            DB::commit();
            $this->info(sprintf("Safe repair completed. Successfully repaired %d schools. Failed to resolve %d schools.", $repairedCount, $failedCount));
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Transaction rolled back due to error: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
