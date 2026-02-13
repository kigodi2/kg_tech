<?php

namespace App\Console\Commands;

use App\Models\SubjectMarks;
use App\Models\Subject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateMarksObtained extends Command
{
    protected $signature = 'marks:recalculate-obtained';
    protected $description = 'Recalculate marks_obtained for all subject marks based on subject configuration (average multi-paper, keep single-paper)';

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
     * Calculate final marks based on subject configuration
     */
    private function calculateFinalMarks($paper1, $paper2, $paper3, Subject $subject): ?float
    {
        // Collect non-null paper marks
        $paperMarks = [];
        
        if (!empty($paper1)) {
            $paperMarks[] = (float)$paper1;
        }
        if (!empty($paper2)) {
            $paperMarks[] = (float)$paper2;
        }
        if (!empty($paper3)) {
            $paperMarks[] = (float)$paper3;
        }

        // If no marks provided, return null
        if (empty($paperMarks)) {
            return null;
        }

        // Count total expected papers for this subject
        $totalPapers = ($subject->written_papers ?? 1) + 
                      ($subject->has_practical ? 1 : 0) + 
                      ($subject->has_project ? 1 : 0);

        // If subject has multiple papers, calculate average
        if ($totalPapers > 1) {
            // Multi-paper subject: average the marks
            return round(array_sum($paperMarks) / count($paperMarks), 2);
        } else {
            // Single paper subject: use the mark as-is
            return $paperMarks[0] ?? null;
        }
    }
}
