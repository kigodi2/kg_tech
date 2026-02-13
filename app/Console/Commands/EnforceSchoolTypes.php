<?php

namespace App\Console\Commands;

use App\Models\School;
use Illuminate\Console\Command;

class EnforceSchoolTypes extends Command
{
    protected $signature = 'enforce-school-types {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Enforce school types based on exam registrations (PSLE=PRIMARY, CSEE/ACSEE=SECONDARY, mixed=BOTH)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $mode = $dryRun ? 'DRY RUN - ' : '';
        
        $this->info($mode . 'Starting enforcement of school types based on exam registrations...');

        $count = 0;
        $schools = School::all();

        foreach ($schools as $school) {
            $hasSecondaryExam = $school->registrations()
                ->whereHas('examType', function ($query) {
                    $query->whereIn('code', ['CSEE', 'ACSEE']);
                })
                ->exists();

            $hasPrimaryExam = $school->registrations()
                ->whereHas('examType', function ($query) {
                    $query->where('code', 'PSLE');
                })
                ->exists();

            $requiredType = null;

            if ($hasSecondaryExam && $hasPrimaryExam) {
                $requiredType = School::TYPE_BOTH;
            } elseif ($hasSecondaryExam) {
                $requiredType = School::TYPE_SECONDARY;
            } elseif ($hasPrimaryExam) {
                $requiredType = School::TYPE_PRIMARY;
            }

            if ($requiredType && $school->school_type !== $requiredType) {
                $this->line("Updating: {$school->code} - {$school->name} from {$school->school_type} to {$requiredType}");
                
                if (!$dryRun) {
                    $school->school_type = $requiredType;
                    $school->save();
                }
                
                $count++;
            }
        }

        if ($count === 0) {
            $this->info('No schools needed updating.');
        } else {
            $this->info($mode . "Updated {$count} school(s).");
        }
    }
}
