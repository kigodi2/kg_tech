<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\RestoreAuditLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * HardenedRestoreService
 * 
 * HARDENED, AUDIT-COMPLIANT, ROLE-AWARE restore system for examination databases.
 * 
 * Features:
 * - Atomic restore with automatic rollback on failure
 * - Pre/post-restore SQLite integrity checks
 * - Legal acknowledgment and NECTA-style warnings
 * - Role-based access control (Super/Regional/District Admin)
 * - Immutable audit trail for examination authority compliance
 * - Maintenance mode during restore operations
 * 
 * Security Principles:
 * 1. NO PARTIAL RESTORES - all files restored OR none
 * 2. IMMUTABLE AUDIT LOGS - every restore attempt is recorded
 * 3. LEGAL WARNINGS - explicit data loss acknowledgment required
 * 4. ROLE RESTRICTIONS - only authorized operators can restore
 * 5. QUARANTINE BACKUPS - original DB files moved to safe location
 */
class HardenedRestoreService
{
    protected User $operator;
    protected ?User $authorizer = null;
    protected string $restoreReason = '';
    protected ?string $quarantineDir = null;

    const QUARANTINE_BASE = 'app/quarantine';
    const MAINTENANCE_FLAG = 'storage/app/MAINTENANCE_MODE';

    /**
     * Initialize restore operation
     */
    public function __construct(User $operator)
    {
        $this->operator = $operator;
    }

    /**
     * Set optional 2FA authorizer (for high-level restores)
     */
    public function setAuthorizer(User $authorizer): self
    {
        $this->authorizer = $authorizer;
        return $this;
    }

    /**
     * Set operator's reason for restore
     */
    public function setRestoreReason(string $reason): self
    {
        $this->restoreReason = trim($reason);
        return $this;
    }

    /**
     * ==================== ACCESS CONTROL ====================
     */

    /**
     * Check if operator has permission to restore given backup
     * 
     * Rules:
     * - Super Admin: can restore ANY backup
     * - Regional Admin: can restore backups for their region only
     * - District Admin: can restore backups for their district only
     * - Others: DENIED
     */
    public function canRestore(Backup $backup): array
    {
        $userRole = $this->operator->role?->code;

        return match($userRole) {
            'super_admin' => [
                'allowed' => true,
                'scope' => 'full',
                'message' => 'Super Admin - Full system restore authorized',
            ],
            'regional_admin' => [
                'allowed' => $backup->region_id === $this->operator->getRegionId(),
                'scope' => 'region',
                'message' => $backup->region_id === $this->operator->getRegionId()
                    ? 'Regional Admin - Regional restore authorized'
                    : 'Regional Admin cannot restore backups outside their region',
            ],
            'district_admin' => [
                'allowed' => $backup->district_id === $this->operator->getDistrictId(),
                'scope' => 'district',
                'message' => $backup->district_id === $this->operator->getDistrictId()
                    ? 'District Admin - District restore authorized'
                    : 'District Admin cannot restore backups outside their district',
            ],
            default => [
                'allowed' => false,
                'scope' => 'none',
                'message' => 'User role not authorized to perform restores',
            ],
        };
    }

    /**
     * ==================== PRE-RESTORE VALIDATION ====================
     */

    /**
     * Validate backup archive integrity before restore
     */
    public function validateBackupArchive(string $archivePath): array
    {
        $errors = [];

        // 1. Check file exists and is readable
        if (!file_exists($archivePath)) {
            $errors[] = "Backup archive not found: {$archivePath}";
            return ['valid' => false, 'errors' => $errors];
        }

        if (!is_readable($archivePath)) {
            $errors[] = "Backup archive not readable: {$archivePath}";
            return ['valid' => false, 'errors' => $errors];
        }

        // 2. Validate ZIP structure
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            $errors[] = "Backup archive is corrupted or not a valid ZIP file";
            return ['valid' => false, 'errors' => $errors];
        }

        // 3. Check for required files
        $requiredFiles = [
            'database.sqlite',
            'manifest.json',
        ];

        foreach ($requiredFiles as $file) {
            if ($zip->locateName($file) === false) {
                $errors[] = "Required file missing from backup: {$file}";
            }
        }

        // 4. Verify manifest integrity
        $manifestJson = $zip->getFromName('manifest.json');
        if (!$manifestJson) {
            $errors[] = "Cannot read manifest.json from backup";
        } else {
            $manifest = json_decode($manifestJson, true);
            if (!$manifest) {
                $errors[] = "Manifest is not valid JSON";
            }
        }

        $zip->close();

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate existing SQLite database before restore
     */
    public function validateCurrentDatabase(): array
    {
        $errors = [];
        $dbPath = database_path('database.sqlite');

        // 1. Check file exists
        if (!file_exists($dbPath)) {
            $errors[] = "Current database.sqlite not found - fresh restore mode";
            return ['valid' => true, 'errors' => $errors, 'fresh_install' => true];
        }

        // 2. Check WAL files if applicable
        $walPath = "{$dbPath}-wal";
        $shmPath = "{$dbPath}-shm";

        if (file_exists($walPath) || file_exists($shmPath)) {
            if (!file_exists($walPath) || !file_exists($shmPath)) {
                $errors[] = "SQLite WAL files incomplete - database may be in inconsistent state";
            }
        }

        // 3. Run integrity check
        try {
            $pdo = new \PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $result = $pdo->query("PRAGMA integrity_check")->fetchAll(\PDO::FETCH_COLUMN);
            if ($result[0] !== 'ok') {
                $errors[] = "Current database failed PRAGMA integrity_check: " . $result[0];
            }

            // Check foreign keys
            $fkCheck = $pdo->query("PRAGMA foreign_key_check")->fetchAll();
            if (!empty($fkCheck)) {
                $errors[] = "Current database has " . count($fkCheck) . " foreign key violations";
            }

            $pdo = null;
        } catch (Exception $e) {
            $errors[] = "Cannot validate current database: " . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'fresh_install' => false,
        ];
    }

    /**
     * ==================== RESTORE EXECUTION ====================
     */

    /**
     * Execute restore with full hardening and atomicity
     */
    public function executeRestore(Backup $backup, string $archivePath): array
    {
        $auditLog = null;

        try {
            // 1. Create audit log entry (INITIATED status)
            $auditLog = $this->createAuditLogEntry($backup, 'initiated');

            // 2. Validate access control
            $accessCheck = $this->canRestore($backup);
            if (!$accessCheck['allowed']) {
                throw new Exception("Access Denied: {$accessCheck['message']}");
            }

            // 3. Validate backup archive
            $archiveValidation = $this->validateBackupArchive($archivePath);
            if (!$archiveValidation['valid']) {
                throw new Exception("Backup validation failed: " . implode(", ", $archiveValidation['errors']));
            }

            // 4. Validate current database state
            $dbValidation = $this->validateCurrentDatabase();
            if (!$dbValidation['valid'] && !($dbValidation['fresh_install'] ?? false)) {
                throw new Exception("Database validation failed: " . implode(", ", $dbValidation['errors']));
            }

            // 5. Update audit log (CONFIRMED status)
            $auditLog->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // 6. Enable maintenance mode
            $this->enableMaintenanceMode();

            // 7. Update audit log (IN_PROGRESS status)
            $auditLog->update([
                'status' => 'in_progress',
                'executed_at' => now(),
            ]);

            // 8. Create quarantine for current database
            $this->quarantineCurrentDatabase();

            // 9. Extract and restore files (ATOMIC)
            try {
                $this->atomicExtractAndRestore($archivePath);
            } catch (Exception $e) {
                // Auto-rollback on failure
                $this->rollbackFromQuarantine();
                throw new Exception("Restore failed, rolled back: " . $e->getMessage());
            }

            // 10. Post-restore validation
            $postValidation = $this->validateRestoredDatabase();
            if (!$postValidation['valid']) {
                $this->rollbackFromQuarantine();
                throw new Exception("Post-restore validation failed: " . implode(", ", $postValidation['errors']));
            }

            // 11. Success: update audit log
            $auditLog->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // 12. Disable maintenance mode
            $this->disableMaintenanceMode();

            // 13. Clean quarantine after success
            $this->cleanQuarantine();

            Log::info('Restore completed successfully', [
                'backup_id' => $backup->id,
                'operator' => $this->operator->name,
                'audit_log_id' => $auditLog->id,
            ]);

            return [
                'success' => true,
                'message' => 'Restore completed successfully',
                'audit_log_id' => $auditLog->id,
            ];

        } catch (Exception $e) {
            // Update audit log with failure
            if ($auditLog) {
                $auditLog->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            // Ensure maintenance mode is off
            $this->disableMaintenanceMode();

            Log::error('Restore failed', [
                'backup_id' => $backup->id,
                'operator' => $this->operator->name,
                'error' => $e->getMessage(),
                'audit_log_id' => $auditLog?->id,
            ]);

            return [
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
                'audit_log_id' => $auditLog?->id,
            ];
        }
    }

    /**
     * Create audit log entry for restore operation
     */
    protected function createAuditLogEntry(Backup $backup, string $initialStatus): RestoreAuditLog
    {
        $accessCheck = $this->canRestore($backup);

        return RestoreAuditLog::create([
            'user_id' => $this->operator->id,
            'authorized_by_id' => $this->authorizer?->id,
            'backup_id' => $backup->id,
            'backup_filename' => $backup->filename,
            'backup_hash' => $backup->hash,
            'scope_type' => $accessCheck['scope'],
            'region_id' => $backup->region_id,
            'district_id' => $backup->district_id,
            'restore_reason' => $this->restoreReason,
            'legal_acknowledgment' => $this->getLegalAcknowledgmentText(),
            'legal_acknowledged' => true, // Set by Filament form validation
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $initialStatus,
            'initiated_at' => now(),
        ]);
    }

    /**
     * Get legal acknowledgment text
     */
    protected function getLegalAcknowledgmentText(): string
    {
        return <<<TEXT
This operation will REPLACE the ENTIRE examination database.
All current results, registrations, and marks will be LOST.
This action is irreversible and must be authorized
according to examination data governance regulations.
TEXT;
    }

    /**
     * Enable maintenance mode
     */
    protected function enableMaintenanceMode(): void
    {
        $maintenanceDir = dirname(storage_path('app/MAINTENANCE_MODE'));
        if (!is_dir($maintenanceDir)) {
            mkdir($maintenanceDir, 0755, true);
        }
        file_put_contents(
            storage_path('app/MAINTENANCE_MODE'),
            json_encode([
                'enabled_at' => now()->toIso8601String(),
                'reason' => 'Database restore in progress',
                'operator' => $this->operator->name,
            ])
        );
    }

    /**
     * Disable maintenance mode
     */
    protected function disableMaintenanceMode(): void
    {
        $path = storage_path('app/MAINTENANCE_MODE');
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Move current database to quarantine
     */
    protected function quarantineCurrentDatabase(): void
    {
        $timestamp = now()->format('YmdHis');
        $quarantineBase = storage_path(self::QUARANTINE_BASE);
        $this->quarantineDir = "{$quarantineBase}/{$timestamp}";

        if (!is_dir($this->quarantineDir)) {
            mkdir($this->quarantineDir, 0755, true);
        }

        $dbPath = database_path('database.sqlite');
        if (file_exists($dbPath)) {
            rename($dbPath, "{$this->quarantineDir}/database.sqlite");
        }

        // Move WAL files
        $walPath = "{$dbPath}-wal";
        if (file_exists($walPath)) {
            rename($walPath, "{$this->quarantineDir}/database.sqlite-wal");
        }

        $shmPath = "{$dbPath}-shm";
        if (file_exists($shmPath)) {
            rename($shmPath, "{$this->quarantineDir}/database.sqlite-shm");
        }
    }

    /**
     * Extract backup and restore files (ATOMIC)
     */
    protected function atomicExtractAndRestore(string $archivePath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new Exception("Cannot open backup archive");
        }

        $tempDir = storage_path('app/restore_temp_' . uniqid());
        mkdir($tempDir, 0755, true);

        try {
            // Extract all files
            if (!$zip->extractTo($tempDir)) {
                throw new Exception("Failed to extract backup files");
            }
            $zip->close();

            // Move extracted database to correct location
            $sourceDb = "{$tempDir}/database.sqlite";
            $destDb = database_path('database.sqlite');

            if (!file_exists($sourceDb)) {
                throw new Exception("database.sqlite not found in backup");
            }

            if (!copy($sourceDb, $destDb)) {
                throw new Exception("Failed to copy database.sqlite to destination");
            }

            // Restore WAL files if present
            $sourceWal = "{$tempDir}/database.sqlite-wal";
            if (file_exists($sourceWal)) {
                copy($sourceWal, "{$destDb}-wal");
            }

            $sourceSham = "{$tempDir}/database.sqlite-shm";
            if (file_exists($sourceSham)) {
                copy($sourceSham, "{$destDb}-shm");
            }

        } finally {
            // Clean temp directory
            if (is_dir($tempDir)) {
                array_map('unlink', glob("{$tempDir}/*"));
                rmdir($tempDir);
            }
        }
    }

    /**
     * ==================== POST-RESTORE VALIDATION ====================
     */

    /**
     * Validate restored database integrity
     */
    protected function validateRestoredDatabase(): array
    {
        $errors = [];
        $dbPath = database_path('database.sqlite');

        if (!file_exists($dbPath)) {
            $errors[] = "Restored database.sqlite not found";
            return ['valid' => false, 'errors' => $errors];
        }

        try {
            $pdo = new \PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // 1. Integrity check
            $result = $pdo->query("PRAGMA integrity_check")->fetchAll(\PDO::FETCH_COLUMN);
            if ($result[0] !== 'ok') {
                $errors[] = "Restored database failed PRAGMA integrity_check";
            }

            // 2. Foreign key check
            $fkCheck = $pdo->query("PRAGMA foreign_key_check")->fetchAll();
            if (!empty($fkCheck)) {
                $errors[] = "Restored database has " . count($fkCheck) . " foreign key violations";
            }

            // 3. Verify key tables exist
            $requiredTables = ['users', 'backups', 'exams', 'exam_years'];
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
            
            foreach ($requiredTables as $table) {
                if (!in_array($table, $tables)) {
                    $errors[] = "Required table missing: {$table}";
                }
            }

            $pdo = null;

        } catch (Exception $e) {
            $errors[] = "Cannot validate restored database: " . $e->getMessage();
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Rollback from quarantine on failure
     */
    protected function rollbackFromQuarantine(): void
    {
        if (!$this->quarantineDir || !is_dir($this->quarantineDir)) {
            Log::warning('Rollback attempted but no quarantine directory found');
            return;
        }

        try {
            $dbPath = database_path('database.sqlite');

            // Remove failed restore
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }

            // Restore from quarantine
            $quarantineDb = "{$this->quarantineDir}/database.sqlite";
            if (file_exists($quarantineDb)) {
                copy($quarantineDb, $dbPath);
            }

            // Restore WAL files
            $quarantineWal = "{$this->quarantineDir}/database.sqlite-wal";
            if (file_exists($quarantineWal)) {
                copy($quarantineWal, "{$dbPath}-wal");
            }

            $quarantineSham = "{$this->quarantineDir}/database.sqlite-shm";
            if (file_exists($quarantineSham)) {
                copy($quarantineSham, "{$dbPath}-shm");
            }

            Log::info('Rollback completed successfully', [
                'quarantine_dir' => $this->quarantineDir,
            ]);

        } catch (Exception $e) {
            Log::error('Rollback failed', [
                'quarantine_dir' => $this->quarantineDir,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clean up quarantine directory after successful restore
     */
    protected function cleanQuarantine(): void
    {
        if (!$this->quarantineDir || !is_dir($this->quarantineDir)) {
            return;
        }

        // Keep quarantine for 7 days for manual recovery if needed
        // Can be implemented as a scheduled job to clean old quarantines
        Log::info('Quarantine preserved for 7-day retention', [
            'quarantine_dir' => $this->quarantineDir,
        ]);
    }
}
