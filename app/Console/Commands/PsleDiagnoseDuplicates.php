<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Candidate;
use App\Models\RawMark;

class PsleDiagnoseDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psle:diagnose-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan and list duplicate PSLE candidates based on candidate numbers, PREMs, and schools, indicating attached marks.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("=== PSLE CANDIDATE DUPLICATES DIAGNOSTIC REPORT ===");
        $this->line("Scanning candidates for duplicates under various groupings...");

        // Grouping 1: candidate_id + exam_year_id + exam_type_id
        $this->diagnoseGroup(
            "1. Grouped by: candidate_id + exam_year_id + exam_type_id",
            function() {
                return DB::table('candidates as c')
                    ->join('candidate_exam_registrations as cer', 'cer.candidate_id', '=', 'c.id')
                    ->select('c.candidate_id', 'cer.exam_year_id', 'cer.exam_type_id', DB::raw('COUNT(*) as cnt'))
                    ->groupBy('c.candidate_id', 'cer.exam_year_id', 'cer.exam_type_id')
                    ->having('cnt', '>', 1)
                    ->get();
            },
            function($dup) {
                return Candidate::where('candidate_id', $dup->candidate_id)
                    ->whereHas('examRegistrations', function($q) use ($dup) {
                        $q->where('exam_year_id', $dup->exam_year_id)
                          ->where('exam_type_id', $dup->exam_type_id);
                    })
                    ->get();
            }
        );

        // Grouping 2: prem_no + exam_year_id + exam_type_id
        $this->diagnoseGroup(
            "2. Grouped by: prem_no + exam_year_id + exam_type_id",
            function() {
                return DB::table('candidates as c')
                    ->join('candidate_exam_registrations as cer', 'cer.candidate_id', '=', 'c.id')
                    ->select('c.prem_no', 'cer.exam_year_id', 'cer.exam_type_id', DB::raw('COUNT(*) as cnt'))
                    ->whereNotNull('c.prem_no')
                    ->where('c.prem_no', '!=', '')
                    ->groupBy('c.prem_no', 'cer.exam_year_id', 'cer.exam_type_id')
                    ->having('cnt', '>', 1)
                    ->get();
            },
            function($dup) {
                return Candidate::where('prem_no', $dup->prem_no)
                    ->whereHas('examRegistrations', function($q) use ($dup) {
                        $q->where('exam_year_id', $dup->exam_year_id)
                          ->where('exam_type_id', $dup->exam_type_id);
                    })
                    ->get();
            }
        );

        // Grouping 3: school_id + candidate_id
        $this->diagnoseGroup(
            "3. Grouped by: school_id + candidate_id",
            function() {
                return DB::table('candidates')
                    ->select('school_id', 'candidate_id', DB::raw('COUNT(*) as cnt'))
                    ->groupBy('school_id', 'candidate_id')
                    ->having('cnt', '>', 1)
                    ->get();
            },
            function($dup) {
                return Candidate::where('school_id', $dup->school_id)
                    ->where('candidate_id', $dup->candidate_id)
                    ->get();
            }
        );

        // Grouping 4: school_id + prem_no
        $this->diagnoseGroup(
            "4. Grouped by: school_id + prem_no",
            function() {
                return DB::table('candidates')
                    ->select('school_id', 'prem_no', DB::raw('COUNT(*) as cnt'))
                    ->whereNotNull('prem_no')
                    ->where('prem_no', '!=', '')
                    ->groupBy('school_id', 'prem_no')
                    ->having('cnt', '>', 1)
                    ->get();
            },
            function($dup) {
                return Candidate::where('school_id', $dup->school_id)
                    ->where('prem_no', $dup->prem_no)
                    ->get();
            }
        );

        $this->info("=== DIAGNOSTICS COMPLETE ===");
        $this->line("Recommendations:");
        $this->line(" - Records carrying marks should ALWAYS be kept.");
        $this->line(" - If only one has marks, keep it and migrate bio data from the other.");
        $this->line(" - If two or more duplicates have marks, flag for manual admin review.");
        $this->line(" - Never delete candidates with marks automatically.");

        return 0;
    }

    /**
     * Run diagnostics for a specific group of duplicates
     */
    private function diagnoseGroup(string $title, callable $findDuplicates, callable $fetchCandidates): void
    {
        $this->info("\n" . str_repeat("-", 80));
        $this->comment($title);
        $this->info(str_repeat("-", 80));

        $duplicates = $findDuplicates();

        if ($duplicates->isEmpty()) {
            $this->line("No duplicates found under this category.");
            return;
        }

        $headers = ['ID', 'Candidate No', 'PREM No', 'Name', 'School', 'Council', 'Year', 'Has Marks?', 'Marks Count', 'Verdict'];
        $tableData = [];

        foreach ($duplicates as $dup) {
            $candidates = $fetchCandidates($dup);
            
            // Analyze candidates to recommend which one to keep
            $candidateMarks = [];
            foreach ($candidates as $c) {
                $marksCount = RawMark::where('candidate_id', $c->id)->count();
                $candidateMarks[$c->id] = $marksCount;
            }

            // Decision logic:
            // 1. If only one has marks, keep that one.
            // 2. If both have 0 marks, keep the one with correct school prefix (longer/valid prefix).
            // 3. If both have marks, flag as "ADMIN REVIEW REQUIRED".
            $keepId = null;
            $hasMarksCount = collect($candidateMarks)->filter(fn($m) => $m > 0)->count();

            if ($hasMarksCount === 1) {
                $keepId = collect($candidateMarks)->search(fn($m) => $m > 0);
            } elseif ($hasMarksCount > 1) {
                $keepId = 'CONFLICT_REVIEW';
            } else {
                // Keep the one with the longer candidate_id or correct prefix
                $keepCandidate = $candidates->sortByDesc(fn($c) => strlen($c->candidate_id ?? ''))->first();
                $keepId = $keepCandidate?->id;
            }

            foreach ($candidates as $c) {
                $marksCount = $candidateMarks[$c->id];
                $hasMarks = $marksCount > 0 ? 'YES' : 'NO';
                
                $verdict = '';
                if ($keepId === 'CONFLICT_REVIEW') {
                    $verdict = 'FLAGGED FOR ADMIN REVIEW (Multiple candidates have marks!)';
                } elseif ($c->id === $keepId) {
                    $verdict = 'KEEP (Safe)';
                } else {
                    $verdict = 'DUPLICATE (Safe to merge bio & prune)';
                }

                $reg = $c->examRegistrations()->first();
                $year = $reg ? $reg->year : ($c->exam_year ?: 'N/A');

                $tableData[] = [
                    $c->id,
                    $c->candidate_id ?: 'N/A',
                    $c->prem_no ?: 'N/A',
                    $c->full_name,
                    $c->school?->name ?: 'N/A',
                    $c->school?->council?->name ?: 'N/A',
                    $year,
                    $hasMarks,
                    $marksCount,
                    $verdict
                ];
            }
            // Insert an empty line between groups for readability
            $tableData[] = array_fill(0, 10, '---');
        }

        // Pop last empty divider
        array_pop($tableData);

        $this->table($headers, $tableData);
    }
}
