<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * AuditLog
 *
 * Comprehensive audit trail for results processing:
 * who made changes, when, what action, and metadata.
 */
class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'action',
        'exam_year_id',
        'user_id',
        'ip_address',
        'user_agent',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const UPDATED_AT = null; // Logs are immutable

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examYear()
    {
        return $this->belongsTo(ExamYear::class);
    }

    /**
     * Record an audit log entry
     */
    public static function log($action, $examYearId = null, $module = 'results', $metadata = [])
    {
        return self::create([
            'module' => $module,
            'action' => $action,
            'exam_year_id' => $examYearId,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope: for exam year
     */
    public function scopeForExamYear($query, $examYearId)
    {
        return $query->where('exam_year_id', $examYearId);
    }

    /**
     * Scope: for user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: for action
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }
}
