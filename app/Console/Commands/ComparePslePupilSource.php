<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Subject;
use App\Services\PsleCandidateRosterService;
use App\Models\ExamType;

class ComparePslePupilSource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psle:compare-pupil-source
                            {--exam-year= : The ID of the exam year}
                            {--school= : The ID of the primary school}
                            {--subject= : The ID of the subject}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely compare the candidate count and details between the Admin Pupil Register and the Mark Entry sheet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $examYearId = $this->option('exam-year');
        $schoolId = $this->option('school');
        $subjectId = $this->option('subject');

        if (!$examYearId || !$schoolId) {
            $this->error('Missing required options: --exam-year and --school are required.');
            return 1;
        }

        $examYear = ExamYear::find($examYearId);
        if (!$examYear) {
            $this->error("Exam Year with ID $examYearId not found.");
            return 1;
        }

        $school = School::find($schoolId);
        if (!$school) {
            $this->error("School with ID $schoolId not found.");
            return 1;
        }

        $this->info("Exam Year: {$examYear->year_label} (ID: {$examYear->id})");
        $this->info("School: {$school->name} (Code: {$school->code}, ID: {$school->id})");

        // 1. Fetch Admin Pupil Register Source candidates
        $adminQuery = PsleCandidateRosterService::rosterQuery((int)$examYearId, (int)$schoolId)
            ->orderBy('candidate_id');
        $adminCandidates = $adminQuery->get();

        // 2. Fetch Mark Entry sheet candidates (simulated logic from PsleMarkEntryController)
        $psleExamType = ExamType::where('code', 'PSLE')->first();
        $psleExamTypeId = $psleExamType ? $psleExamType->id : 4;
        
        $meoQuery = PsleCandidateRosterService::rosterQuery((int)$examYearId, (int)$schoolId)
            ->with(['examRegistrations' => fn($q) => $q->where('exam_type_id', $psleExamTypeId)->where('exam_year_id', $examYearId)])
            ->orderBy('candidates.candidate_id', 'asc');
        $meoCandidates = $meoQuery->get();

        $rawAdminCount = $adminCandidates->count();

        // Deduplicate
        $schoolCode = $school->code;
        $dedupAdmin = PsleCandidateRosterService::deduplicate($adminCandidates, $schoolCode);
        $dedupMeo = PsleCandidateRosterService::deduplicate($meoCandidates, $schoolCode);

        $adminCount = $dedupAdmin->count();
        $meoCount = $dedupMeo->count();

        $this->info("Admin pupil count: $adminCount");
        $this->info("Entry sheet candidate count: $meoCount");

        // Compute duplicate metrics
        $uniqueCandidateIds = $adminCandidates->pluck('candidate_id')->unique()->count();
        $dupCandidateIds = $rawAdminCount - $uniqueCandidateIds;

        $rawPrems = $adminCandidates->whereNotNull('prem_no')->pluck('prem_no');
        $uniquePrems = $rawPrems->unique()->count();
        $dupPrems = $rawPrems->count() - $uniquePrems;

        $dupRenderedRows = $rawAdminCount - $adminCount;

        $this->info("Official unique candidate IDs: $adminCount");
        $this->info("Rendered entry sheet rows: $meoCount");
        $this->info("Duplicate candidate IDs in official source: $dupCandidateIds");
        $this->info("Duplicate PREM numbers: $dupPrems");
        $this->info("Duplicate rendered rows: $dupRenderedRows");

        // Compute missing or extra
        $adminIds = $dedupAdmin->pluck('candidate_id')->toArray();
        $meoIds = $dedupMeo->pluck('candidate_id')->toArray();

        $missingInMeo = array_diff($adminIds, $meoIds);
        $extraInMeo = array_diff($meoIds, $adminIds);

        $this->info("Missing in entry sheet: " . count($missingInMeo));
        $this->info("Extra in entry sheet: " . count($extraInMeo));

        if ($adminCount > 0) {
            $firstCandidate = $dedupAdmin->first();
            $lastCandidate = $dedupAdmin->last();
            $this->info("First candidate: {$firstCandidate->candidate_id} ({$firstCandidate->full_name})");
            $this->info("Last candidate: {$lastCandidate->candidate_id} ({$lastCandidate->full_name})");
        } else {
            $this->info("First candidate: None");
            $this->info("Last candidate: None");
        }

        // Count how many candidates display a REG- style number on the manual entry sheet.
        // After our fix, the entry sheet displays candidate_id, so it should be 0.
        $regStyleCount = 0;
        foreach ($dedupMeo as $c) {
            $displayIndexNumber = $c->candidate_id;
            if ($displayIndexNumber && str_starts_with($displayIndexNumber, 'REG-')) {
                $regStyleCount++;
            }
        }
        $this->info("REG-style candidates in entry sheet: $regStyleCount");

        return 0;
    }
}
