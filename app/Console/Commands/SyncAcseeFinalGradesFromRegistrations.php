<?php

namespace App\Console\Commands;

use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\FinalGrade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncAcseeFinalGradesFromRegistrations extends Command
{
    protected $signature = 'acsee:sync-final-grades {--exam-year=2026}';

    protected $description = 'Sync ACSEE final_grades from candidate_exam_registrations for a given exam year.';

    public function handle(): int
    {
        $yearLabel = (string) $this->option('exam-year');

        $acsee = ExamType::query()->where('code', 'ACSEE')->first();
        if (!$acsee) {
            $this->error('ACSEE exam type not found.');
            return self::FAILURE;
        }

        $examYear = ExamYear::query()->where('year_label', $yearLabel)->first();
        if (!$examYear) {
            $this->error("Exam year {$yearLabel} not found.");
            return self::FAILURE;
        }

        $regs = CandidateExamRegistration::query()
            ->where('exam_type_id', $acsee->id)
            ->where('exam_year_id', $examYear->id)
            ->get(['candidate_id', 'total_marks', 'total_points', 'gpa', 'division', 'grade']);

        $updated = 0;
        $created = 0;
        $hasGrade = Schema::hasColumn('final_grades', 'grade');
        $hasGradeName = Schema::hasColumn('final_grades', 'grade_name');
        $hasFinalPercentage = Schema::hasColumn('final_grades', 'final_percentage');

        foreach ($regs as $reg) {
            $existing = FinalGrade::query()
                ->where('candidate_id', $reg->candidate_id)
                ->where('exam_type_id', $acsee->id)
                ->where('year', (int) $yearLabel)
                ->first();

            $payload = [
                'gpa' => $reg->gpa ?? 0,
                'division' => (string) ($reg->division ?? '0'),
            ];
            if ($hasGrade) {
                $payload['grade'] = (string) ($reg->grade ?? '');
            }
            if ($hasGradeName) {
                $payload['grade_name'] = (string) ($reg->grade ?? '');
            }
            if ($hasFinalPercentage) {
                $payload['final_percentage'] = $existing?->final_percentage ?? 0;
            }

            if ($existing) {
                $breakdown = (array) ($existing->grading_breakdown ?? []);
                $breakdown['aggt_points'] = $reg->total_points ?? 0;
                $payload['grading_breakdown'] = $breakdown;
                $existing->update($payload);
                $updated++;
            } else {
                // Legacy schema has required columns (e.g. grading_profile_id) that cannot be inferred reliably here.
                // Skip create; this command is a sync of existing final grade rows.
            }
        }

        $this->info("Synced final grades for {$yearLabel}: updated={$updated}, created={$created}");
        return self::SUCCESS;
    }
}
