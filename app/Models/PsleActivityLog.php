<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsleActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_year_id',
        'region_id',
        'district_id',
        'school_id',
        'subject_id',
        'user_id',
        'event_type',
        'title',
        'description',
        'affected_candidates',
        'affected_marks',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'affected_candidates' => 'integer',
        'affected_marks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
