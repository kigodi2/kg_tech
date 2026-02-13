<?php

namespace App\Services\ExamYears;

use App\Models\ExamYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * ExamYearService
 *
 * Business logic for exam year management.
 *
 * Responsibilities:
 * - Create and manage exam years
 * - Activate/deactivate years
 * - Publish results (triggers locking)
 * - Enforce year isolation and locking rules
 */
class ExamYearService
{
    /**
     * Create a new exam year.
     *
     * @param array $data Array with 'year_label' key
     * @return ExamYear The created exam year
     * @throws Exception If year_label already exists
     */
    public function create(array $data): ExamYear
    {
        // Check if year already exists
        if (ExamYear::where('year_label', $data['year_label'])->exists()) {
            throw new Exception("Exam year {$data['year_label']} already exists");
        }

        return ExamYear::create([
            'year_label' => $data['year_label'],
            'is_active' => $data['is_active'] ?? false,
            'is_locked' => false,
        ]);
    }

    /**
     * Get all exam years.
     *
     * @return Collection All exam years ordered by year_label descending
     */
    public function all(): Collection
    {
        return ExamYear::orderByDesc('year_label')->get();
    }

    /**
     * Get the currently active exam year.
     *
     * @return ExamYear|null The active year or null if none exists
     */
    public function getActive(): ?ExamYear
    {
        return ExamYear::active()->first();
    }

    /**
     * Activate an exam year.
     *
     * Deactivates all other years to maintain the constraint
     * that only one year can be active.
     *
     * @param int $examYearId
     * @return bool True if successful
     * @throws Exception If year doesn't exist
     */
    public function activate(int $examYearId): bool
    {
        $examYear = ExamYear::findOrFail($examYearId);

        return DB::transaction(function () use ($examYear) {
            // Deactivate all other years
            ExamYear::where('id', '!=', $examYear->id)->update([
                'is_active' => false,
            ]);

            // Activate this year
            return $examYear->update(['is_active' => true]);
        });
    }

    /**
     * Publish results for an exam year.
     *
     * This is a critical operation that:
     * 1. Sets the published_at timestamp
     * 2. Locks the year (is_locked = true)
     * 3. Prevents all further modifications
     *
     * @param int $examYearId
     * @return bool True if successful
     * @throws Exception If year already published or doesn't exist
     */
    public function publishResults(int $examYearId): bool
    {
        $examYear = ExamYear::findOrFail($examYearId);

        // Prevent double publishing
        if ($examYear->isPublished()) {
            throw new Exception(
                "Exam year {$examYear->year_label} is already published and locked"
            );
        }

        return DB::transaction(function () use ($examYear) {
            return $examYear->publish();
        });
    }

    /**
     * Get statistics for an exam year.
     *
     * Returns counts of candidates, registrations, marks, results for display.
     *
     * @param int $examYearId
     * @return array Statistics array
     */
    public function getStatistics(int $examYearId): array
    {
        $examYear = ExamYear::findOrFail($examYearId);

        return [
            'exam_year_id' => $examYear->id,
            'year_label' => $examYear->year_label,
            'is_active' => $examYear->is_active,
            'is_locked' => $examYear->is_locked,
            'is_published' => $examYear->isPublished(),
            'candidates_count' => $examYear->candidates()->count(),
            'registrations_count' => $examYear->registrations()->count(),
            'marks_count' => $examYear->marks()->count(),
            'results_count' => $examYear->results()->count(),
            'published_at' => $examYear->published_at,
            'locked_at' => $examYear->locked_at,
        ];
    }

    /**
     * Check if an exam year is locked.
     *
     * @param int $examYearId
     * @return bool True if locked
     */
    public function isLocked(int $examYearId): bool
    {
        $examYear = ExamYear::find($examYearId);
        return $examYear ? $examYear->isLocked() : false;
    }

    /**
     * Check if an exam year is active.
     *
     * @param int $examYearId
     * @return bool True if active
     */
    public function isActive(int $examYearId): bool
    {
        $examYear = ExamYear::find($examYearId);
        return $examYear ? $examYear->isActive() : false;
    }

    /**
     * Get exam year by label.
     *
     * @param string $label
     * @return ExamYear|null
     */
    public function getByLabel(string $label): ?ExamYear
    {
        return ExamYear::where('year_label', $label)->first();
    }
}
