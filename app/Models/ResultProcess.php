<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ResultProcess
 *
 * Tracks result processing batches: draft runs and final runs.
 * Records total candidates, processed count, status, and audit trail.
 */
class ResultProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_type_id',
        'exam_year_id',
        'type',
        'status',
        'config_fingerprint',
        'input_fingerprint',
        'scope_type',
        'scope_id',
        'user_id',
        'total_candidates',
        'processed_count',
        'error_count',
        'error_message',
        'processed_at',
        'started_at',
        'completed_at',
        'finished_at',
        'metadata',
        'stats',
    ];

    protected $casts = [
        'metadata' => 'array',
        'stats' => 'array',
        'processed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get processing progress percentage
     */
    public function getProgressPercentage()
    {
        if ($this->total_candidates === 0) {
            return 0;
        }

        return (int)(($this->processed_count / $this->total_candidates) * 100);
    }

    /**
     * Check if processing is complete
     */
    public function isComplete()
    {
        return $this->status === 'completed' && $this->processed_count === $this->total_candidates;
    }

    /**
     * Mark as completed
     */
    public function markComplete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Scope: draft runs
     */
    public function scopeDraft($query)
    {
        return $query->where('type', 'draft');
    }

    /**
     * Scope: final runs
     */
    public function scopeFinal($query)
    {
        return $query->where('type', 'final');
    }

    /**
     * Scope: successful
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }
}
