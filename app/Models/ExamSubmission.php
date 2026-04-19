<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSubmission extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'exam_type_id',
        'exam_year_id',
        'subject_id',
        'school_id',
        'exam_paper_path',
        'original_filename',
        'status',
        'validation_results',
        'rejection_reason',
        'submitted_at',
        'validated_at',
    ];

    protected $casts = [
        'validation_results' => 'array',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examYear(): BelongsTo
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function getExamPaperUrlAttribute(): string
    {
        return asset('storage/' . $this->exam_paper_path);
    }
}
