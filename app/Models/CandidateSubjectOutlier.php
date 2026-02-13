<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSubjectOutlier extends Model
{
    protected $fillable = [
        'candidate_extremity_id',
        'subject_id',
        'score',
        'candidate_average',
        'deviation_from_average',
        'deviation_percentage',
        'zscore',
        'outlier_type',
    ];

    protected $casts = [
        'score' => 'decimal:3',
        'candidate_average' => 'decimal:3',
        'deviation_from_average' => 'decimal:3',
        'zscore' => 'decimal:3',
    ];

    public function extremity(): BelongsTo
    {
        return $this->belongsTo(CandidateExtremityAnalysis::class, 'candidate_extremity_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
}
