<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CandidateExtremityAnalysis extends Model
{
    use SoftDeletes;

    protected $table = 'candidate_extremity_analysis';

    protected $fillable = [
        'candidate_id',
        'exam_year_id',
        'exam_type_id',
        'combination',
        'subject_count',
        'average_score',
        'median_score',
        'std_dev_across_subjects',
        'min_score',
        'max_score',
        'outlier_subject_count',
        'outlier_subjects',
        'expected_score',
        'subject_analysis',
        'risk_level',
        'flags',
        'analysis_notes',
        'reviewed',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'outlier_subjects' => 'array',
        'subject_analysis' => 'array',
        'flags' => 'array',
        'reviewed_at' => 'datetime',
        'average_score' => 'decimal:3',
        'median_score' => 'decimal:3',
        'std_dev_across_subjects' => 'decimal:3',
    ];

    // Relationships
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function examYear(): BelongsTo
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function subjectOutliers(): HasMany
    {
        return $this->hasMany(CandidateSubjectOutlier::class, 'candidate_extremity_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', 'High');
    }

    public function scopeModerateRisk($query)
    {
        return $query->where('risk_level', 'Moderate');
    }

    public function scopeUnreviewed($query)
    {
        return $query->where('reviewed', false);
    }

    // Methods
    public function markReviewed(User $user, string $notes = null): void
    {
        $this->update([
            'reviewed' => true,
            'reviewed_at' => now(),
            'reviewed_by' => $user->id,
            'review_notes' => $notes,
        ]);

        \Log::info('Candidate extremity analysis reviewed', [
            'analysis_id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'reviewed_by' => $user->id,
        ]);
    }

    public function getOutlierPercentage(): float
    {
        return $this->subject_count > 0 
            ? ($this->outlier_subject_count / $this->subject_count) * 100 
            : 0;
    }

    public function getFlagsAttribute()
    {
        return $this->attributes['flags'] ? json_decode($this->attributes['flags'], true) : [];
    }
}
