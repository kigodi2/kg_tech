<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkEntryLocationLog extends Model
{
    protected $fillable = [
        'user_id',
        'marking_centre_id',
        'attempted_latitude',
        'attempted_longitude',
        'centre_latitude',
        'centre_longitude',
        'distance_meters',
        'accuracy_meters',
        'ip_address',
        'user_agent_hash',
        'allowed',
        'reason',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'attempted_latitude' => 'float',
        'attempted_longitude' => 'float',
        'centre_latitude' => 'float',
        'centre_longitude' => 'float',
        'distance_meters' => 'float',
        'accuracy_meters' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markingCentre(): BelongsTo
    {
        return $this->belongsTo(MarkingCentre::class);
    }
}
