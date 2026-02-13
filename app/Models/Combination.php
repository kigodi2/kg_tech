<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Combination extends Model
{
    use HasFactory;

    protected $table = 'combinations';

    protected $fillable = [
        'exam_type_id',
        'code',
        'category',
        'description',
        'subjects',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['subject_count', 'subject_codes'];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the exam type this combination belongs to
     */
    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    /**
     * Get all subjects in this combination
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'combination_subject',
            'combination_id',
            'subject_id'
        )->withTimestamps();
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByExamType($query, $examTypeId)
    {
        return $query->where('exam_type_id', $examTypeId);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('code', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }

    // ==================== ACCESSORS ====================

    /**
     * Get the count of subjects in this combination
     */
    public function getSubjectCountAttribute(): int
    {
        return $this->subjects()->count();
    }

    /**
     * Get all subject codes as comma-separated string
     */
    public function getSubjectCodesAttribute(): string
    {
        return $this->subjects()->pluck('code')->join(', ');
    }

    // ==================== METHODS ====================

    /**
     * Sync the subjects for this combination
     */
    public function syncSubjects(?array $subjectIds): void
    {
        $this->subjects()->sync($subjectIds ?? []);
    }

    /**
     * Check if combination has a specific subject
     */
    public function hasSubject($subjectId): bool
    {
        return $this->subjects()->where('subject_id', $subjectId)->exists();
    }

    /**
     * Get subject details with codes and names
     */
    public function getSubjectsWithDetails()
    {
        return $this->subjects()
            ->select('id', 'code', 'name', 'category')
            ->get();
    }
}
