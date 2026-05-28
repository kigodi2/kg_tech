<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkEntryActiveSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'device_hash',
        'ip_address',
        'user_agent',
        'last_seen_at',
        'locked_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
