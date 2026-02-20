<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SystemEventLog extends Model
{
    protected $table = 'system_event_logs';

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'category', 'action', 'status',
        'correlation_id', 'context', 'message',
        'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = [
        'context' => 'json',
        'created_at' => 'datetime',
    ];

    const CAT_IMPORT = 'import';
    const CAT_MODERATION = 'moderation';
    const CAT_SUBMISSION = 'submission';
    const CAT_LOCKING = 'locking';
    const CAT_EXPORT = 'export';
    const CAT_ADMIN = 'admin';
    const CAT_SYSTEM = 'system';

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_WARNING = 'warning';

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCorrelation($query, $correlationId)
    {
        return $query->where('correlation_id', $correlationId);
    }

    public function scopeByActor($query, $userId)
    {
        return $query->where('actor_user_id', $userId);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) $query->where('created_at', '>=', $from);
        if ($to) $query->where('created_at', '<=', $to);
        return $query;
    }

    /**
     * Log a system event (append-only)
     */
    public static function record(
        string $category,
        string $action,
        string $status,
        string $message,
        ?array $context = null,
        ?string $correlationId = null,
        ?int $actorUserId = null
    ): self {
        return self::create([
            'actor_user_id' => $actorUserId ?? (auth()->check() ? auth()->id() : null),
            'category' => $category,
            'action' => $action,
            'status' => $status,
            'correlation_id' => $correlationId ?? Str::uuid()->toString(),
            'context' => $context,
            'message' => $message,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'created_at' => now(),
        ]);
    }
}
