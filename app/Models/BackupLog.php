<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BackupLog Model
 * 
 * Immutable audit trail for all backup and restore operations.
 * Records are NEVER deleted or modified - only appended.
 * 
 * @property int $id
 * @property int $user_id
 * @property string $operation (backup_created|backup_failed|incremental_backup_created|restore_completed|restore_failed|simulation_completed|simulation_failed)
 * @property array $data
 * @property string $status (success|failed)
 * @property \Carbon\Carbon $created_at
 */
class BackupLog extends Model
{
    protected $table = 'backup_logs';
    
    public $timestamps = true;
    const UPDATED_AT = null; // Immutable - no updates allowed

    protected $fillable = [
        'user_id',
        'operation',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==================== SCOPES ====================

    public function scopeBackupOperations($query)
    {
        return $query->whereIn('operation', ['backup_created', 'backup_failed', 'incremental_backup_created']);
    }

    public function scopeRestoreOperations($query)
    {
        return $query->whereIn('operation', ['restore_completed', 'restore_failed']);
    }

    public function scopeSimulationOperations($query)
    {
        return $query->whereIn('operation', ['simulation_completed', 'simulation_failed']);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==================== HELPERS ====================

    public function isBackupOperation(): bool
    {
        return str_contains($this->operation, 'backup');
    }

    public function isRestoreOperation(): bool
    {
        return str_contains($this->operation, 'restore');
    }

    public function isSimulation(): bool
    {
        return str_contains($this->operation, 'simulation');
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getOperationLabel(): string
    {
        return match ($this->operation) {
            'backup_created' => 'Backup Created',
            'backup_failed' => 'Backup Failed',
            'incremental_backup_created' => 'Incremental Backup Created',
            'restore_completed' => 'Restore Completed',
            'restore_failed' => 'Restore Failed',
            'simulation_completed' => 'Simulation Completed',
            'simulation_failed' => 'Simulation Failed',
            default => $this->operation,
        };
    }

    public function getStatusBadge(): string
    {
        return $this->status === 'success' ? 'success' : 'danger';
    }
}
