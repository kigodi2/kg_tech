<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovernanceAuditLog extends Model
{
    use HasFactory;

    protected $table = 'governance_audit_logs';

    protected $fillable = [
        'admin_id',
        'user_id',
        'action',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
    ];

    // Only created_at, never updated_at
    public $timestamps = false;
    protected $guarded = [];

    // Action constants
    const ACTION_USER_CREATED = 'user_created';
    const ACTION_USER_ROLE_ASSIGNED = 'user_role_assigned';
    const ACTION_USER_SCOPE_ASSIGNED = 'user_scope_assigned';
    const ACTION_USER_SUSPENDED = 'user_suspended';
    const ACTION_USER_ACTIVATED = 'user_activated';
    const ACTION_PASSWORD_RESET = 'password_reset';
    const ACTION_PASSWORD_CHANGED = 'password_changed';
    const ACTION_LOGIN_SUCCESSFUL = 'login_successful';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_IMPORT_INITIATED = 'import_initiated';
    const ACTION_IMPORT_COMPLETED = 'import_completed';
    const ACTION_IMPORT_FAILED = 'import_failed';
    const ACTION_BACKUP_CREATED = 'backup_created';
    const ACTION_BACKUP_DOWNLOADED = 'backup_downloaded';
    const ACTION_BACKUP_DELETED = 'backup_deleted';
    const ACTION_RESTORE_INITIATED = 'restore_initiated';
    const ACTION_RESTORE_COMPLETED = 'restore_completed';
    const ACTION_RESTORE_FAILED = 'restore_failed';
    const ACTION_CANDIDATE_RECLAIMED = 'candidate_reclaimed';

    /**
     * Relationships
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Static helper to log an action (immutable append-only)
     */
    public static function log(
        string $action,
        ?int $userId = null,
        ?int $adminId = null,
        ?array $data = null
    ): self {
        return self::create([
            'action' => $action,
            'user_id' => $userId,
            'admin_id' => $adminId,
            'data' => $data,
        ]);
    }
}
