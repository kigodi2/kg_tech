<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\School;
use App\Models\Subject;
use App\Services\MarkEntry\PsleMarkEntryService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SmokeTestPsleMarkEntry extends Command
{
    protected $signature = 'psle:smoke-mark-entry {--year=} {--subject=}';

    protected $description = 'Run a safe smoke test for the PSLE mark-entry validate and commit flow';

    public function handle(PsleMarkEntryService $service): int
    {
        $psle = ExamType::where('code', 'PSLE')->first();
        if (! $psle) {
            $this->error('PSLE exam type not found.');
            return self::FAILURE;
        }

        $examYear = $this->resolveExamYear();
        if (! $examYear) {
            $this->error('No PSLE exam year could be resolved.');
            return self::FAILURE;
        }

        $subject = $this->resolveSubject($psle->id);
        if (! $subject) {
            $this->error('No PSLE subject could be resolved.');
            return self::FAILURE;
        }

        $seedSchool = School::whereNotNull('district_id')->whereNotNull('region_id')->first();
        if (! $seedSchool) {
            $this->error('No base school was found to clone district/region scope from.');
            return self::FAILURE;
        }

        $timestamp = now()->format('YmdHis');
        $testSchool = null;
        $candidate = null;
        $registration = null;
        $selection = null;
        $batchIds = [];
        $tmpPath = null;

        try {
            $testSchool = School::create([
                'code' => 'TPS' . substr($timestamp, -6),
                'name' => 'PSLE SMOKE TEST SCHOOL ' . $timestamp,
                'registration_number' => 'TPS' . substr($timestamp, -6),
                'source_system' => 'PSLE_SMOKE_TEST',
                'ownership' => 'GOVERNMENT',
                'district_id' => $seedSchool->district_id,
                'region_id' => $seedSchool->region_id,
                'council_id' => $seedSchool->council_id,
                'school_type' => School::TYPE_PRIMARY,
                'education_level' => 'PRIMARY',
                'is_active' => true,
            ]);

            $candidate = Candidate::create([
                'school_id' => $testSchool->id,
                'candidate_id' => $testSchool->code . '-SMK-' . substr($timestamp, -4),
                'prem_no' => 'PREM-' . substr($timestamp, -6),
                'full_name' => 'PSLE SMOKE TEST PUPIL',
                'gender' => 'F',
                'exam_type' => 'PSLE',
                'candidate_type' => 'SCHOOL',
                'status' => 'registered',
                'is_active' => true,
            ]);

            $registration = CandidateExamRegistration::create([
                'candidate_id' => $candidate->id,
                'exam_type_id' => $psle->id,
                'exam_year_id' => $examYear->id,
                'year' => (int) $examYear->year_label,
                'registration_number' => 'REG-PSLE-SMOKE-' . substr($timestamp, -6),
                'is_active' => true,
                'is_verified' => false,
            ]);

            $selection = CandidateSubjectSelection::create([
                'candidate_id' => $candidate->id,
                'exam_type_id' => $psle->id,
                'exam_year_id' => $examYear->id,
                'subject_id' => $subject->id,
                'year' => (int) $examYear->year_label,
                'is_active' => true,
            ]);

            $csv = implode("\n", [
                'candidate_number,prem_no,pupil_name,sex,school_code,subject_code,mark',
                implode(',', [
                    $candidate->candidate_id,
                    $candidate->prem_no,
                    '"' . $candidate->full_name . '"',
                    $candidate->gender,
                    $testSchool->code,
                    $subject->code,
                    '87',
                ]),
            ]);

            $tmpPath = tempnam(sys_get_temp_dir(), 'psle_smoke_');
            file_put_contents($tmpPath, $csv);
            $file = new UploadedFile($tmpPath, 'psle_smoke_test.csv', 'text/csv', null, true);

            $validation = $service->validateSingleCsv($file, (string) $examYear->year_label, (int) $testSchool->id, (int) $subject->id);

            if (! ($validation['success'] ?? false) || ! ($validation['can_commit'] ?? false)) {
                $this->error('Validation failed.');
                $this->line(json_encode($validation));
                return self::FAILURE;
            }

            $commit = $service->commitSingleCsv($file, (string) $examYear->year_label, (int) $testSchool->id, (int) $subject->id, auth()->id() ?? 1);

            if (! ($commit['success'] ?? false)) {
                $this->error('Commit failed.');
                $this->line(json_encode($commit));
                return self::FAILURE;
            }

            $batchIds = collect([$commit['batch']['id'] ?? null])->filter()->values()->all();

            $this->info('PSLE mark-entry smoke test passed.');
            $this->line('Year: ' . $examYear->year_label);
            $this->line('Subject: ' . $subject->code . ' - ' . $subject->name);
            $this->line('School: ' . $testSchool->code . ' - ' . $testSchool->name);
            $this->line('Candidate: ' . $candidate->candidate_id);
            $this->line('Validation rows: ' . ($validation['totals']['total_rows'] ?? 0));
            $this->line('Committed batch: ' . ($commit['batch']['batch_code'] ?? '-'));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('PSLE mark-entry smoke test failed: ' . $exception->getMessage());
            return self::FAILURE;
        } finally {
            if ($tmpPath && is_file($tmpPath)) {
                @unlink($tmpPath);
            }

            if (! empty($batchIds)) {
                MarkImportBatch::whereIn('id', $batchIds)->delete();
            }

            if ($selection) {
                CandidateSubjectSelection::whereKey($selection->id)->delete();
            }

            if ($registration) {
                CandidateExamRegistration::whereKey($registration->id)->delete();
            }

            if ($candidate) {
                Candidate::whereKey($candidate->id)->delete();
            }

            if ($testSchool) {
                School::whereKey($testSchool->id)->delete();
            }
        }
    }

    private function resolveExamYear(): ?ExamYear
    {
        $requestedYear = trim((string) $this->option('year'));

        if ($requestedYear !== '') {
            return ExamYear::where('year_label', $requestedYear)->first();
        }

        return ExamYear::where('is_active', true)->first() ?: ExamYear::latest('year_label')->first();
    }

    private function resolveSubject(int $psleExamTypeId): ?Subject
    {
        $requestedSubject = trim((string) $this->option('subject'));

        if ($requestedSubject !== '') {
            return Subject::where('exam_type_id', $psleExamTypeId)
                ->where(function ($query) use ($requestedSubject) {
                    $query->where('code', strtoupper($requestedSubject))
                        ->orWhere('id', ctype_digit($requestedSubject) ? (int) $requestedSubject : 0);
                })
                ->first();
        }

        return Subject::where('exam_type_id', $psleExamTypeId)->orderBy('code')->first();
    }
}
