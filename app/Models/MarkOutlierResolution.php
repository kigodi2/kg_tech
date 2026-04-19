<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkOutlierResolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_mark_id',
        'issue_type',
        'resolution_action',
        'note',
        'resolved_by',
        'resolved_at',
        'resolution_correlation_id',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function rawMark()
    {
        return $this->belongsTo(RawMark::class, 'raw_mark_id');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

