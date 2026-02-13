<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthenticationAuditLog extends Model
{
    use HasFactory;

    protected $table = 'authentication_audit_logs';

    protected $fillable = [
        'user_id',
        'username',
        'event_type',
        'success',
        'ip_address',
        'user_agent',
        'details',
    ];

    protected $casts = [
        'success' => 'boolean',
        'details' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const EVENT_LOGIN = 'LOGIN';
    const EVENT_LOGOUT = 'LOGOUT';
    const EVENT_PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    const EVENT_PASSWORD_RESET = 'PASSWORD_RESET';
    const EVENT_FAILED_LOGIN = 'FAILED_LOGIN';
    const EVENT_USER_CREATED = 'USER_CREATED';
    const EVENT_USER_MODIFIED = 'USER_MODIFIED';
    const EVENT_USER_DELETED = 'USER_DELETED';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByEvent($query, $event)
    {
        return $query->where('event_type', $event);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
