<?php

namespace App\Models\Traits;

use App\Models\ExamYear;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BelongsToExamYear Trait
 *
 * Add this trait to all models that belong to an exam year.
 *
 * Usage:
 * class Candidate extends Model {
 *     use BelongsToExamYear;
 * }
 *
 * This provides:
 * - examYear() relationship
 * - scopeByExamYear() scope
 * - scopeCurrentYear() scope
 * - Helper methods
 */
trait BelongsToExamYear
{
    /**
     * Get the exam year this model belongs to.
     */
    public function examYear(): BelongsTo
    {
        return $this->belongsTo(ExamYear::class);
    }

    /**
     * Scope: Filter by specific exam year.
     *
     * Usage: Candidate::byExamYear(1)->get()
     */
    public function scopeByExamYear($query, $examYearId)
    {
        return $query->where('exam_year_id', $examYearId);
    }

    /**
     * Scope: Filter by current (active) exam year.
     *
     * Usage: Candidate::currentYear()->get()
     */
    public function scopeCurrentYear($query)
    {
        $activeYear = ExamYear::active()->first();
        return $activeYear ? $query->where('exam_year_id', $activeYear->id) : $query;
    }

    /**
     * Scope: Filter by year, excluding locked years.
     *
     * Usage: Mark::editableYear()->get()
     */
    public function scopeEditableYear($query)
    {
        return $query->join('exam_years', 'exam_year_id', '=', 'exam_years.id')
            ->where('exam_years.is_locked', false)
            ->select($this->getTable() . '.*');
    }

    /**
     * Check if this record belongs to a locked year.
     */
    public function isInLockedYear(): bool
    {
        return $this->examYear?->isLocked() ?? false;
    }

    /**
     * Check if this record belongs to the current year.
     */
    public function isInCurrentYear(): bool
    {
        $activeYear = ExamYear::active()->first();
        return $this->exam_year_id === $activeYear?->id;
    }
}
