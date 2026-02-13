<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ExamYear Model
 *
 * Represents an academic year in the IRMS.
 * This is a core domain entity that all exam-related data depends on.
 *
 * Key constraints:
 * - Only one active year at a time
 * - Locked years are immutable (read-only)
 * - All exam records must belong to an exam year
 */
class ExamYear extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'year_label',
        'is_active',
        'is_locked',
        'published_at',
        'locked_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get all candidates for this exam year.
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    /**
     * Get all candidate exam registrations for this exam year.
     */
    public function candidateExamRegistrations(): HasMany
    {
        return $this->hasMany(CandidateExamRegistration::class);
    }

    /**
     * Get all registrations for this exam year.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get all subject registrations for this exam year.
     */
    public function subjectRegistrations(): HasMany
    {
        return $this->hasMany(SubjectRegistration::class);
    }

    /**
     * Get all marks for this exam year.
     */
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    /**
     * Get all results for this exam year.
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    /**
     * Get all summaries for this exam year.
     */
    public function summaries(): HasMany
    {
        return $this->hasMany(Summary::class);
    }

    /**
     * Get all uploads for this exam year.
     */
    public function uploads(): HasMany
    {
        return $this->hasMany(Upload::class);
    }

    /**
     * Get all reports for this exam year.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Get all CSV templates for this exam year.
     */
    public function csvTemplates(): HasMany
    {
        return $this->hasMany(CsvTemplate::class);
    }

    // ==================== SCOPES ====================

    /**
     * Get the currently active exam year.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get locked exam years.
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Get unlocked exam years.
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Get published exam years.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get the active exam year or throw exception.
     */
    public static function activeOrFail(): self
    {
        return static::active()->firstOrFail();
    }

    /**
     * Get the currently active exam year.
     */
    public static function current(): ?self
    {
        return static::active()->first();
    }

    // ==================== METHODS ====================

    /**
     * Activate this exam year.
     *
     * Deactivates all other years to maintain the constraint
     * that only one exam year can be active at a time.
     */
    public function activate(): bool
    {
        // Deactivate all other years
        ExamYear::where('id', '!=', $this->id)->update(['is_active' => false]);

        // Activate this year
        return $this->update(['is_active' => true]);
    }

    /**
     * Publish results for this exam year.
     *
     * Sets published_at timestamp, which triggers locking.
     */
    public function publish(): bool
    {
        if ($this->published_at !== null) {
            throw new \Exception("Exam year {$this->year_label} is already published");
        }

        return $this->update([
            'published_at' => now(),
            'is_locked' => true,
            'locked_at' => now(),
        ]);
    }

    /**
     * Check if this year is locked.
     */
    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    /**
     * Check if this year is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if this year is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Get candidates count for this year.
     */
    public function getCandidateCount(): int
    {
        return $this->candidates()->count();
    }

    /**
     * Get marks count for this year.
     */
    public function getMarksCount(): int
    {
        return $this->marks()->count();
    }

    /**
     * Get results count for this year.
     */
    public function getResultsCount(): int
    {
        return $this->results()->count();
    }

    /**
     * Get status for display (e.g., "Active", "Locked", "Draft").
     */
    public function getStatusLabel(): string
    {
        if ($this->is_locked) {
            return '🔒 Locked';
        }

        if ($this->is_active) {
            return '✓ Active';
        }

        return 'Inactive';
    }

    /**
     * Get status badge class for Tailwind.
     */
    public function getStatusBadgeClass(): string
    {
        if ($this->is_locked) {
            return 'bg-red-100 text-red-800';
        }

        if ($this->is_active) {
            return 'bg-green-100 text-green-800';
        }

        return 'bg-gray-100 text-gray-800';
    }
}
