<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarkEntryAssignment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active_lock' => 'integer',
    ];

    public function examYear(): BelongsTo
    {
        return $this->belongsTo(ExamYear::class);
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
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

    public function markingCentre(): BelongsTo
    {
        return $this->belongsTo(MarkingCentre::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MarkImportBatch::class, 'assignment_id');
    }

    public function marks(): HasMany
    {
        return $this->hasMany(RawMark::class, 'assignment_id');
    }
}
