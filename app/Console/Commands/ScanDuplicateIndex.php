<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Candidate;
use App\Models\ExamYear;
use App\Models\ExamType;
use Illuminate\Support\Facades\DB;

class ScanDuplicateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'necta:scan-duplicate-index
        {--exam-year= : Filter by exam year (name/label, e.g., "2026")}
        {--exam-type= : Filter by exam type (code, e.g., "ACSEE")}
        {--output=table : Output format: table|json|csv}
        {--export= : Export path (if using json or csv output)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan for duplicate index numbers in the same exam context';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for duplicate index numbers...');

        $examYear = $this->option('exam-year');
        $examType = $this->option('exam-type');
        $output = $this->option('output') ?? 'table';
        $exportPath = $this->option('export');

        // Build query
        $query = Candidate::select(
            'candidates.id',
            'candidates.candidate_id',
            'candidates.school_id',
            'candidate_exam_registrations.exam_year_id',
            'candidate_exam_registrations.exam_type_id',
            'exam_years.year_label',
            'exam_types.code as exam_type_code',
            DB::raw('COUNT(*) as duplicate_count')
        )
        ->join('candidate_exam_registrations', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
        ->join('exam_years', 'candidate_exam_registrations.exam_year_id', '=', 'exam_years.id')
        ->join('exam_types', 'candidate_exam_registrations.exam_type_id', '=', 'exam_types.id');

        // Apply filters
        if ($examYear) {
            $query->where('exam_years.year_label', 'like', "%{$examYear}%");
        }

        if ($examType) {
            $query->where('exam_types.code', strtoupper($examType));
        }

        // Group by exam context and index number
        $query->groupBy(
            'candidates.candidate_id',
            'candidate_exam_registrations.exam_year_id',
            'candidate_exam_registrations.exam_type_id',
            'exam_years.year_label',
            'exam_types.code'
        )
        ->having(DB::raw('COUNT(*)'), '>', 1)
        ->orderBy('candidates.candidate_id');

        $duplicates = $query->get();

        if ($duplicates->isEmpty()) {
            $this->info('✓ No duplicates found!');
            return 0;
        }

        // Get detailed information for each duplicate group
        $results = [];
        foreach ($duplicates as $dup) {
            $group = Candidate::where('candidate_id', $dup->candidate_id)
                ->whereHas('examRegistrations', function ($q) use ($dup) {
                    $q->where('exam_year_id', $dup->exam_year_id)
                      ->where('exam_type_id', $dup->exam_type_id);
                })
                ->with(['school', 'examRegistrations' => function ($q) use ($dup) {
                    $q->where('exam_year_id', $dup->exam_year_id)
                      ->where('exam_type_id', $dup->exam_type_id);
                }])
                ->get();

            foreach ($group as $candidate) {
                $results[] = [
                    'candidate_id' => $candidate->id,
                    'index_number' => $candidate->candidate_id,
                    'school' => $candidate->school?->name ?? 'N/A',
                    'full_name' => $candidate->full_name ?? $candidate->first_name . ' ' . $candidate->last_name,
                    'exam_year' => $dup->year_label,
                    'exam_type' => $dup->exam_type_code,
                    'group_size' => $dup->duplicate_count,
                ];
            }
        }

        // Display results
        if ($output === 'table') {
            $this->table(
                ['Candidate ID', 'Index Number', 'School', 'Name', 'Exam Year', 'Exam Type', 'Duplicates'],
                $results
            );
        } elseif ($output === 'json') {
            $json = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($exportPath) {
                file_put_contents($exportPath, $json);
                $this->info("Results exported to: {$exportPath}");
            } else {
                $this->line($json);
            }
        } elseif ($output === 'csv') {
            $csv = fopen('php://output', 'w');
            fputcsv($csv, ['Candidate ID', 'Index Number', 'School', 'Name', 'Exam Year', 'Exam Type', 'Duplicates']);
            foreach ($results as $row) {
                fputcsv($csv, [
                    $row['candidate_id'],
                    $row['index_number'],
                    $row['school'],
                    $row['full_name'],
                    $row['exam_year'],
                    $row['exam_type'],
                    $row['group_size'],
                ]);
            }
            fclose($csv);

            if ($exportPath) {
                $this->info("CSV exported to: {$exportPath}");
            }
        }

        $this->warn("\n⚠ Found " . count($results) . " duplicate entries (across " . count($duplicates) . " groups)");
        $this->info('Review and resolve these manually before applying unique constraints.');

        return 0;
    }
}
