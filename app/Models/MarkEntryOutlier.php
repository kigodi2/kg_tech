<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkEntryOutlier extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'verified_at' => 'datetime',
        'resolved_at' => 'datetime',
        'observed_value' => 'decimal:2',
    ];

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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function rawMark(): BelongsTo
    {
        return $this->belongsTo(RawMark::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(MarkEntryAssignment::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MarkImportBatch::class);
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
