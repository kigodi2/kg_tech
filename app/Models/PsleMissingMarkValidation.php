<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsleMissingMarkValidation extends Model
{
    use HasFactory;

    protected $table = 'psle_missing_mark_validations';

    protected $fillable = [
        'exam_year_id',
        'region_id',
        'district_id',
        'school_id',
        'candidate_id',
        'subject_id',
        'classification',
        'decision',
        'reason',
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
        'committed_by',
        'committed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    // Allowed values for decision
    const DECISION_PENDING = 'pending';
    const DECISION_APPROVED_ABS = 'approved_abs';
    const DECISION_REJECTED = 'rejected';
    const DECISION_COMMITTED = 'committed';

    // Allowed values for classification
    const CLASSIFICATION_ABS = 'ABS';
    const CLASSIFICATION_INC = 'INC';

    public function examYear(): BelongsTo
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function committedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }
}
