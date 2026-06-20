<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PslePrecalculatedEvaluation extends Model
{
    use HasFactory;

    protected $table = 'psle_precalculated_evaluations';

    protected $fillable = [
        'exam_year',
        'exam_type',
        'scope_type',
        'scope_id',
        'evaluation_key',
        'snapshot_id',
        'status',
        'data',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'exam_year' => 'integer',
        'scope_id' => 'integer',
        'snapshot_id' => 'integer',
        'data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_BUILDING = 'building';
    const STATUS_READY = 'ready';
    const STATUS_FAILED = 'failed';
    const STATUS_STALE = 'stale';

    public function scopeRegion()
    {
        return $this->belongsTo(Region::class, 'scope_id');
    }
}
