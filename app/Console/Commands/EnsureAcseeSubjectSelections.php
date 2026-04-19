<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\Combination;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use Illuminate\Console\Command;

class EnsureAcseeSubjectSelections extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'acsee:ensure-subject-selections {--exam-year=2026}';

    /**
     * The console command description.
     */
    protected $description = 'Ensure all ACSEE candidates have subject selections based on their combinations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $examYearLabel = $this->option('exam-year');
        
        $acsee = ExamType::where('code', 'ACSEE')->first();
        if (!$acsee) {
            $this->error('ACSEE exam type not found');
            return 1;
        }

        $examYear = ExamYear::where('year_label', $examYearLabel)->first();
        if (!$examYear) {
            $this->error("Exam year {$examYearLabel} not found");
            return 1;
        }

        $this->info("Processing ACSEE candidates for {$examYearLabel}...\n");

        $gsId = Subject::query()->where('code', '111')->value('id');
        $bamId = Subject::query()->where('code', '141')->value('id');

        // Get all ACSEE candidates with registrations for this year
        $candidates = Candidate::query()
            ->whereHas('examRegistrations', function ($query) use ($acsee, $examYear) {
                $query->where('exam_type_id', $acsee->id)
                      ->where('exam_year_id', $examYear->id);
            })
            ->where('combination', '!=', '')
            ->get();

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalCandidates = $candidates->count();

        $this->info("Found {$totalCandidates} candidates to process\n");

        foreach ($candidates as $index => $candidate) {
            // Get the combination
            $combination = Combination::where('code', $candidate->combination)
                ->where('exam_type_id', $acsee->id)
                ->first();

            if (!$combination) {
                $this->warn("  [{$index}] {$candidate->candidate_id}: Combination '{$candidate->combination}' not found");
                $totalSkipped++;
                continue;
            }

            // Get subjects for this combination
            $subjects = $combination->subjects()
                ->where('is_active', true)
                ->get();

            if ($subjects->isEmpty()) {
                $this->warn("  [{$index}] {$candidate->candidate_id}: No subjects found for combination '{$candidate->combination}'");
                $totalSkipped++;
                continue;
            }

            // Create subject selections
            foreach ($subjects as $subject) {
                $existing = CandidateSubjectSelection::where('candidate_id', $candidate->id)
                    ->where('subject_id', $subject->id)
                    ->where('exam_year_id', $examYear->id)
                    ->first();

                if (!$existing) {
                    CandidateSubjectSelection::create([
                        'candidate_id' => $candidate->id,
                        'exam_type_id' => $acsee->id,
                        'exam_year_id' => $examYear->id,
                        'subject_id' => $subject->id,
                        'year' => (int)$examYear->year_label,
                        'is_principal' => (int) $subject->id !== (int) $gsId && (int) $subject->id !== (int) $bamId,
                        'is_active' => true,
                    ]);
                    $totalCreated++;
                }
            }

            if (($index + 1) % 100 == 0) {
                $this->line("  Processed {$index} candidates...");
            }
        }

        $this->info("\n✅ Completed!");
        $this->info("   Created: {$totalCreated} subject selections");
        $this->info("   Skipped: {$totalSkipped} candidates");

        return 0;
    }
}
