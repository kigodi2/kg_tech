<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use Illuminate\Console\Command;

class RegisterUnregisteredCandidatesForACSEE extends Command
{
    protected $signature = 'candidates:register-acsee-2026 {--year=2026}';
    protected $description = 'Register all unregistered candidates for ACSEE exam';

    public function handle()
    {
        $year = $this->option('year');
        $acsee = ExamType::where('code', 'ACSEE')->first();
        $examYear = ExamYear::where('year_label', $year)->first();

        if (!$acsee || !$examYear) {
            $this->error('ACSEE exam type or exam year not found');
            return 1;
        }

        // Find all unregistered candidates
        $unregistered = Candidate::whereDoesntHave('examRegistrations', function($q) use ($acsee, $examYear) {
            $q->where('exam_type_id', $acsee->id)
              ->where('exam_year_id', $examYear->id);
        })->whereNotNull('combination')->get();

        $count = $unregistered->count();
        $this->info("Found {$count} unregistered candidates");

        if ($count === 0) {
            $this->info('No candidates to register');
            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $registered = 0;
        $schoolRegNumbers = []; // Track registration numbers per school

        foreach ($unregistered as $candidate) {
            try {
                // Get school code for registration number
                $schoolCode = $candidate->school->code;
                
                // Initialize counter for this school if needed
                if (!isset($schoolRegNumbers[$schoolCode])) {
                    $schoolRegNumbers[$schoolCode] = \App\Models\CandidateExamRegistration::query()
                        ->whereHas('candidate', fn($q) => $q->where('school_id', $candidate->school_id))
                        ->where('exam_type_id', $acsee->id)
                        ->where('exam_year_id', $examYear->id)
                        ->count();
                }

                // Generate registration number
                $schoolRegNumbers[$schoolCode]++;
                $regNumber = sprintf(
                    'REG-%s-%s-%06d',
                    $schoolCode,
                    $year,
                    $schoolRegNumbers[$schoolCode]
                );

                // Register for exam
                CandidateExamRegistration::firstOrCreate([
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $acsee->id,
                    'exam_year_id' => $examYear->id,
                ], [
                    'year' => (int)$year,
                    'registration_number' => $regNumber,
                    'status' => 'registered',
                    'registered_at' => now(),
                ]);

                // Register subjects based on combination
                if ($candidate->combination) {
                    $subjects = $this->parseSubjects($candidate->combination, $acsee->id);
                    foreach ($subjects as $subject) {
                        CandidateSubjectSelection::firstOrCreate([
                            'candidate_id' => $candidate->id,
                            'subject_id' => $subject->id,
                            'exam_type_id' => $acsee->id,
                            'exam_year_id' => $examYear->id,
                        ], [
                            'year' => (int)$year,
                        ]);
                    }
                }

                $registered++;
            } catch (\Exception $e) {
                $this->error("Error registering {$candidate->candidate_id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully registered {$registered} candidates for ACSEE {$year}");

        return 0;
    }

    private function parseSubjects($combination, $examTypeId)
    {
        $combination = strtoupper(trim($combination));
        
        // Split by comma, space, or hyphen
        $parts = preg_split('/[,\s\-]+/', $combination, -1, PREG_SPLIT_NO_EMPTY);
        
        $subjects = collect();
        
        foreach ($parts as $part) {
            // Try to find by code first
            $subject = \App\Models\Subject::where('exam_type_id', $examTypeId)
                ->where('code', $part)
                ->first();
            
            if (!$subject) {
                // Try by name
                $subject = \App\Models\Subject::where('exam_type_id', $examTypeId)
                    ->where('name', 'LIKE', '%' . $part . '%')
                    ->first();
            }
            
            if ($subject) {
                $subjects->push($subject);
            }
        }
        
        return $subjects->unique('id');
    }
}
