<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairCandidateRegistrations extends Command
{
    protected $signature = 'psle:repair-candidate-registrations
        {--school= : School code to filter by}
        {--commit : Commit changes to the database}';

    protected $description = 'Safely repair missing PSLE candidate exam registrations for 2026.';

    public function handle(): int
    {
        $schoolCode = $this->option('school');
        $commit = (bool) $this->option('commit');

        $psleType = ExamType::where('code', 'PSLE')->first();
        if (!$psleType) {
            $this->error('PSLE exam type not found.');
            return self::FAILURE;
        }

        $examYear = ExamYear::where('year_label', '2026')->first();
        if (!$examYear) {
            $this->error('Exam year 2026 not found.');
            return self::FAILURE;
        }

        $school = null;
        if ($schoolCode) {
            $school = School::where('code', strtoupper(trim($schoolCode)))->first();
            if (!$school) {
                $this->error("School with code {$schoolCode} not found.");
                return self::FAILURE;
            }
            $this->info("Filtering by school: {$school->name} ({$school->code})");
        }

        // Find candidates where candidate_id starts with 'PS' (Primary School / PSLE) and they do not have a registration for PSLE 2026
        $query = Candidate::query()
            ->where(function ($q) {
                $q->where('exam_type', 'PSLE')
                  ->orWhere('exam_type', 'MOCK')
                  ->orWhere('candidate_id', 'like', 'PS%');
            })
            ->active()
            ->whereNotExists(function ($q) use ($psleType, $examYear) {
                $q->select(DB::raw(1))
                    ->from('candidate_exam_registrations')
                    ->whereColumn('candidate_exam_registrations.candidate_id', 'candidates.id')
                    ->where('candidate_exam_registrations.exam_type_id', $psleType->id)
                    ->where('candidate_exam_registrations.exam_year_id', $examYear->id);
            });

        if ($school) {
            $query->where('school_id', $school->id);
        }

        $candidates = $query->with('school')->get();

        if ($candidates->isEmpty()) {
            $this->info('No candidates found missing registration links.');
            return self::SUCCESS;
        }

        $this->warn("Found {$candidates->count()} candidate(s) missing PSLE 2026 registrations.");

        // Print table header
        $headers = ['Candidate ID', 'Name', 'Gender', 'Current Exam Type', 'School Code', 'School Name'];
        $tableData = $candidates->map(function ($c) {
            return [
                $c->candidate_id,
                $c->full_name,
                $c->gender,
                $c->exam_type ?? 'N/A',
                $c->school?->code ?? 'N/A',
                $c->school?->name ?? 'N/A'
            ];
        })->toArray();

        $this->table($headers, array_slice($tableData, 0, 50));
        if ($candidates->count() > 50) {
            $this->info("... and " . ($candidates->count() - 50) . " more candidates.");
        }

        if (!$commit) {
            $this->info("\n[DRY RUN] Run with --commit to save registrations.");
            return self::SUCCESS;
        }

        $this->info("\nCommitting repairs...");
        
        DB::beginTransaction();
        try {
            $repairedCount = 0;
            foreach ($candidates as $candidate) {
                // Ensure candidate's exam_type is PSLE
                if ($candidate->exam_type !== 'PSLE') {
                    $candidate->update(['exam_type' => 'PSLE']);
                }

                // Double check legacy registrations by year to align with standard behavior
                $legacy = CandidateExamRegistration::where('candidate_id', $candidate->id)
                    ->where('exam_type_id', $psleType->id)
                    ->where('year', 2026)
                    ->first();

                if ($legacy) {
                    $legacy->update([
                        'exam_year_id' => $examYear->id,
                        'status' => $legacy->status ?: 'registered',
                        'updated_at' => now(),
                    ]);
                } else {
                    CandidateExamRegistration::create([
                        'candidate_id' => $candidate->id,
                        'exam_type_id' => $psleType->id,
                        'exam_year_id' => $examYear->id,
                        'year' => 2026,
                        'registration_number' => 'REG-' . uniqid(),
                        'is_active' => true,
                        'is_verified' => false,
                        'status' => 'registered',
                    ]);
                }
                $repairedCount++;
            }

            DB::commit();
            $this->info("Successfully repaired {$repairedCount} candidate exam registration(s).");
            Log::info("Repaired {$repairedCount} PSLE 2026 candidate registrations.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to repair registrations: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
