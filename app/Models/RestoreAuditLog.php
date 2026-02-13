<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * RestoreAuditLog Model
 * 
 * Immutable, NECTA-compliant audit trail for restore operations.
 * Records EVERY restore request, confirmation, and outcome.
 * Records are NEVER deleted or modified - only appended.
 * 
 * This model provides legal accountability for examination data operations.
 * 
 * @property int $id
 * @property int $user_id                    User who initiated restore
 * @property int|null $authorized_by_id      Optional: User who authorized restore (if 2FA used)
 * @property string $backup_id               Backup identifier being restored
 * @property string $backup_filename         Original backup filename
 * @property string $backup_hash             SHA-256 hash of restored archive
 * @property string $scope_type              'full'|'region'|'district'
 * @property int|null $region_id             Region ID (for regional restores)
 * @property int|null $district_id           District ID (for district restores)
 * @property text $restore_reason            Operator's explanation for restore
 * @property text $legal_acknowledgment      Operator's legal acceptance text
 * @property boolean $legal_acknowledged     Checkbox: "I understand and accept responsibility"
 * @property string $ip_address              IP address of requester
 * @property string $user_agent              User agent string
 * @property string $status                  'initiated'|'confirmed'|'in_progress'|'completed'|'failed'|'rolled_back'
 * @property string|null $error_message      If failed, the error message
 * @property datetime $initiated_at          When request was created
 * @property datetime|null $confirmed_at     When operator confirmed (if 2FA)
 * @property datetime|null $executed_at      When restore actually started
 * @property datetime|null $completed_at     When restore finished
 * @property \Carbon\Carbon $created_at      Record creation (immutable)
 */
class RestoreAuditLog extends Model
{
    protected $table = 'restore_audit_logs';
    
    public $timestamps = true;
    const UPDATED_AT = null; // Immutable - no updates allowed

    protected $fillable = [
        'user_id',
        'authorized_by_id',
        'backup_id',
        'backup_filename',
        'backup_hash',
        'scope_type',
        'region_id',
        'district_id',
        'restore_reason',
        'legal_acknowledgment',
        'legal_acknowledged',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
        'initiated_at',
        'confirmed_at',
        'executed_at',
        'completed_at',
    ];

    protected $casts = [
        'legal_acknowledged' => 'boolean',
        'initiated_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'executed_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    // ==================== SCOPES ====================

    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRolledBack($query)
    {
        return $query->where('status', 'rolled_back');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFullScope($query)
    {
        return $query->where('scope_type', 'full');
    }

    public function scopeRegionalScope($query)
    {
        return $query->where('scope_type', 'region');
    }

    public function scopeDistrictScope($query)
    {
        return $query->where('scope_type', 'district');
    }

    // ==================== HELPERS ====================

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRolledBack(): bool
    {
        return $this->status === 'rolled_back';
    }

    public function getStatusBadge(): string
    {
        return match($this->status) {
            'completed' => 'success',
            'failed', 'rolled_back' => 'danger',
            'in_progress' => 'warning',
            'confirmed', 'initiated' => 'info',
            default => 'secondary',
        };
    }

    public function getScopeLabel(): string
    {
        return match($this->scope_type) {
            'full' => 'Full System Restore',
            'region' => "Regional Restore: {$this->region?->name}",
            'district' => "District Restore: {$this->district?->name}",
            default => 'Unknown',
        };
    }

    public function getOperatorRole(): string
    {
        return $this->user?->role?->name ?? 'Unknown';
    }

    public function getAuthorizationStatus(): string
    {
        if (!$this->authorized_by_id) {
            return 'Self-Authorized (Admin)';
        }
        return "Authorized by: {$this->authorizedBy?->name}";
    }

    /**
     * Format legal acknowledgment for display/export
     */
    public function getFormattedLegalAcknowledgment(): string
    {
        $acknowledged = $this->legal_acknowledged ? 'YES' : 'NO';
        $executedAt = $this->executed_at ? $this->executed_at->format('Y-m-d H:i:s') : 'N/A';
        $userName = $this->user?->name ?? 'Unknown';
        
        return <<<TEXT
This operation will REPLACE the ENTIRE examination database.
All current results, registrations, and marks will be LOST.
This action is irreversible and must be authorized
according to examination data governance regulations.

Restore Reason: {$this->restore_reason}
Operator: {$userName}
Operator Role: {$this->getOperatorRole()}
IP Address: {$this->ip_address}
Date & Time: {$executedAt}
Backup: {$this->backup_filename}
Hash: {$this->backup_hash}

Operator Acknowledgment:
"I understand and accept full responsibility for this restore operation.
I have verified this is necessary according to examination governance protocols.
All consequences of this action are my responsibility."

Acknowledged: {$acknowledged}
TEXT;
    }

    /**
     * Generate audit log export for examination authority records
     */
    public function toAuditExport(): array
    {
        return [
            'audit_id' => $this->id,
            'timestamp' => $this->executed_at?->toIso8601String(),
            'operator' => $this->user?->name,
            'operator_role' => $this->getOperatorRole(),
            'authorization' => $this->getAuthorizationStatus(),
            'scope' => $this->getScopeLabel(),
            'backup_restored' => $this->backup_id,
            'backup_hash' => $this->backup_hash,
            'restore_reason' => $this->restore_reason,
            'status' => $this->status,
            'error' => $this->error_message,
            'ip_address' => $this->ip_address,
            'legal_acknowledged' => $this->legal_acknowledged ? 'Confirmed' : 'Not Confirmed',
            'duration_seconds' => $this->completed_at && $this->executed_at
                ? $this->completed_at->diffInSeconds($this->executed_at)
                : null,
        ];
    }
}
