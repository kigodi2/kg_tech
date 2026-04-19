<?php

namespace App\Console\Commands;

use App\Models\SubjectMarks;
use App\Models\Subject;
use App\Models\SubjectPaperWeight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RecalculateMarksObtained extends Command
{
    protected $signature = 'marks:recalculate-obtained';
    protected $description = 'Recalculate marks_obtained for all subject marks using SubjectMark100 weighted normalization';
    private array $paperWeightCache = [];

    public function handle(): int
    {
        $this->info('Starting marks_obtained recalculation...');

        // Get all subject marks
        $marks = SubjectMarks::with('subject')->get();
        $total = $marks->count();
        
        if ($total === 0) {
            $this->info('No marks found to recalculate.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;
        foreach ($marks as $mark) {
            $subject = $mark->subject;
            if (!$subject) {
                $bar->advance();
                continue;
            }

            // Calculate final marks based on subject configuration
            $marksObtained = $this->calculateFinalMarks(
                $mark->paper_1,
                $mark->paper_2,
                $mark->paper_3,
                $subject
            );

            // Update if different
            if ($marksObtained !== $mark->marks_obtained) {
                $mark->marks_obtained = $marksObtained;
                $mark->save();
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Recalculation complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records', $total],
                ['Updated', $updated],
                ['Unchanged', $total - $updated],
            ]
        );

        return 0;
    }

    /**
     * SubjectMark100 = (WeightedSum / WeightedMax) × 100
     */
    private function calculateFinalMarks($paper1, $paper2, $paper3, Subject $subject): ?float
    {
        $paperValues = [];
        if ($paper1 !== null && $paper1 !== '') $paperValues['paper_1'] = (float) $paper1;
        if ($paper2 !== null && $paper2 !== '') $paperValues['paper_2'] = (float) $paper2;
        if ($paper3 !== null && $paper3 !== '') $paperValues['paper_3'] = (float) $paper3;

        if (empty($paperValues)) {
            return null;
        }

        $weights = $this->paperWeightsForSubject((int) $subject->id);
        if (!empty($weights)) {
            $weightedSum = 0.0;
            $weightedMax = 0.0;
            foreach ($weights as $row) {
                $paperCode = (string) ($row['paper_code'] ?? '');
                if ($paperCode === '' || !array_key_exists($paperCode, $paperValues)) {
                    continue;
                }

                $weight = (float) ($row['weight'] ?? 1.0);
                $maxMark = (float) ($row['max_mark'] ?? 100.0);
                $mark = (float) $paperValues[$paperCode];

                $weightedSum += ($mark * $weight);
                $weightedMax += ($maxMark * $weight);
            }

            if ($weightedMax > 0) {
                return round(($weightedSum / $weightedMax) * 100.0, 0);
            }
        }

        $weightedSum = 0.0;
        $weightedMax = 0.0;
        foreach ($paperValues as $paperCode => $mark) {
            $weightedSum += (float) $mark;
            $weightedMax += $this->paperMaxMark((string) $paperCode);
        }

        if ($weightedMax <= 0) {
            return null;
        }

        return round(($weightedSum / $weightedMax) * 100.0, 0);
    }

    private function paperWeightsForSubject(int $subjectId): array
    {
        if (array_key_exists($subjectId, $this->paperWeightCache)) {
            return $this->paperWeightCache[$subjectId];
        }

        if (!Schema::hasTable('subject_paper_weights')) {
            $this->paperWeightCache[$subjectId] = [];
            return [];
        }

        $rows = SubjectPaperWeight::query()
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereIn('paper_code', ['paper_1', 'paper_2', 'paper_3'])
            ->orderBy('paper_code')
            ->get(['paper_code', 'weight', 'max_mark'])
            ->map(fn ($r) => [
                'paper_code' => (string) $r->paper_code,
                'weight' => (float) ($r->weight ?? 1.0),
                'max_mark' => $this->paperMaxMark((string) $r->paper_code, $r->max_mark),
            ])
            ->values()
            ->all();

        $this->paperWeightCache[$subjectId] = $rows;
        return $rows;
    }

    private function paperMaxMark(string $paperCode, mixed $configuredMax = null): float
    {
        $canonical = $paperCode === 'paper_3' ? 50.0 : 100.0;
        if ($configuredMax === null || $configuredMax === '') {
            return $canonical;
        }

        $value = (float) $configuredMax;
        return $value > 0 ? $value : $canonical;
    }
}
