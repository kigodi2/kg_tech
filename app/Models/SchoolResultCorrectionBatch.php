<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolResultCorrectionBatch extends Model
{
    use HasFactory;

    protected $table = 'school_result_correction_batches';

    protected $fillable = [
        'exam_year',
        'exam_type',
        'school_id',
        'school_name_snapshot',
        'status',
        'reason',
        'opened_by',
        'opened_at',
        'corrected_by',
        'corrected_at',
        'recalculated_by',
        'recalculated_at',
        'republished_by',
        'republished_at',
        'cancelled_by',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'exam_year' => 'integer',
        'opened_at' => 'datetime',
        'corrected_at' => 'datetime',
        'recalculated_at' => 'datetime',
        'republished_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Status Constants
    const STATUS_OPEN = 'open';
    const STATUS_CORRECTED = 'corrected';
    const STATUS_RECALCULATED = 'recalculated';
    const STATUS_REPUBLISHED = 'republished';
    const STATUS_CANCELLED = 'cancelled';

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function openedByUser()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function correctedByUser()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function recalculatedByUser()
    {
        return $this->belongsTo(User::class, 'recalculated_by');
    }

    public function republishedByUser()
    {
        return $this->belongsTo(User::class, 'republished_by');
    }

    public function cancelledByUser()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
