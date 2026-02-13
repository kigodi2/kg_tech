<?php

namespace App\Http\Controllers;

use App\Models\ExamYear;
use App\Models\Region;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyMarksEntryReportController extends Controller
{
    /**
     * Get daily marks entry report data
     * Groups marks entry by date and subject at regional level
     */
    public function getReport(Request $request)
    {
        $query = SubjectMarks::query()
            ->with(['subject', 'candidate.school.region', 'examType'])
            ->select(
                'subject_marks.*',
                DB::raw('DATE(subject_marks.created_at) as entry_date')
            );

        // Apply filters
        if ($request->has('exam_year_id') && $request->exam_year_id) {
            $query->where('subject_marks.year', $request->exam_year_id);
        }

        if ($request->has('region_id') && $request->region_id) {
            $query->whereHas('candidate.school', function ($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        if ($request->has('subject_id') && $request->subject_id) {
            $query->where('subject_marks.subject_id', $request->subject_id);
        }

        if ($request->has('entry_date') && $request->entry_date) {
            $query->whereDate('subject_marks.created_at', $request->entry_date);
        }

        $marks = $query->get();

        // Group data by subject and entry date
        $report = $this->generateReport($marks, $request);

        return response()->json($report);
    }

    /**
     * Generate report data with daily breakdown
     */
    private function generateReport($marks, Request $request)
    {
        $reportData = [];
        $groupedBySubject = $marks->groupBy('subject_id');

        foreach ($groupedBySubject as $subjectId => $subjectMarks) {
            $subject = $subjectMarks->first()->subject;
            
            // Get expected scripts for this subject in the region
            $expectedScripts = $this->getExpectedScripts($subject, $request);

            // Group by date
            $markedByDay = [
                'day1' => 0,
                'day2' => 0,
                'day3' => 0,
                'day4' => 0,
                'day5' => 0,
                'remainder' => 0
            ];

            foreach ($subjectMarks as $mark) {
                $day = $this->getDayOfWeek($mark->created_at);
                $markedByDay[$day]++;
            }

            $total = $subjectMarks->count();

            $reportData[] = [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'expected_scripts' => $expectedScripts,
                'day1_count' => $markedByDay['day1'],
                'day1_percentage' => $expectedScripts > 0 ? ($markedByDay['day1'] / $expectedScripts) * 100 : 0,
                'day2_count' => $markedByDay['day2'],
                'day2_percentage' => $expectedScripts > 0 ? ($markedByDay['day2'] / $expectedScripts) * 100 : 0,
                'day3_count' => $markedByDay['day3'],
                'day3_percentage' => $expectedScripts > 0 ? ($markedByDay['day3'] / $expectedScripts) * 100 : 0,
                'day4_count' => $markedByDay['day4'],
                'day4_percentage' => $expectedScripts > 0 ? ($markedByDay['day4'] / $expectedScripts) * 100 : 0,
                'day5_count' => $markedByDay['day5'],
                'day5_percentage' => $expectedScripts > 0 ? ($markedByDay['day5'] / $expectedScripts) * 100 : 0,
                'remainder_count' => $markedByDay['remainder'],
                'remainder_percentage' => $expectedScripts > 0 ? ($markedByDay['remainder'] / $expectedScripts) * 100 : 0,
                'total_marked' => $total,
                'remarks' => $this->generateRemarks($total, $expectedScripts)
            ];
        }

        return $reportData;
    }

    /**
     * Get expected scripts for a subject in a region
     */
    private function getExpectedScripts($subject, Request $request)
    {
        $query = Candidate::query()
            ->whereHas('examRegistrations', function ($q) use ($subject) {
                $q->whereHas('subjectRegistrations', function ($sq) use ($subject) {
                    $sq->where('subject_id', $subject->id);
                });
            });

        if ($request->has('region_id') && $request->region_id) {
            $query->whereHas('school', function ($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        if ($request->has('exam_year_id') && $request->exam_year_id) {
            $query->whereHas('examRegistrations', function ($q) use ($request) {
                $q->where('exam_year_id', $request->exam_year_id);
            });
        }

        return $query->distinct()->count();
    }

    /**
     * Determine which day of marking period (1-5) based on entry date
     */
    private function getDayOfWeek($entryDate)
    {
        // If entry date is within the reference marking period, assign to day 1-5
        // Otherwise, assign to remainder
        
        $dayOfWeek = $entryDate->dayOfWeek;
        
        // Monday-Friday maps to days 1-5, Saturday-Sunday maps to remainder
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
            return 'day' . $dayOfWeek;
        }
        
        return 'remainder';
    }

    /**
     * Generate remarks based on marking progress
     */
    private function generateRemarks($totalMarked, $expectedScripts)
    {
        if ($expectedScripts === 0) {
            return 'No expected scripts';
        }

        $percentage = ($totalMarked / $expectedScripts) * 100;

        if ($percentage >= 100) {
            return 'Marking Complete';
        } elseif ($percentage >= 75) {
            return 'On Track';
        } elseif ($percentage >= 50) {
            return 'In Progress';
        } elseif ($percentage > 0) {
            return 'Slow Progress';
        } else {
            return 'Not Started';
        }
    }
}
