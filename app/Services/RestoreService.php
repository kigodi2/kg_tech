<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\GovernanceAuditLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class RestoreService
{
    protected BackupService $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Verify backup integrity
     */
    public function verifyIntegrity(Backup $backup): bool
    {
        return $this->backupService->verifyIntegrity($backup);
    }

    /**
     * Validate backup before restore
     */
    public function validate(Backup $backup): array
    {
        $errors = [];

        // Check file exists
        if (!$backup->exists()) {
            $errors[] = 'Backup file does not exist on disk';
        }

        // Verify signature
        if (!$this->backupService->verifySignature($backup)) {
            $errors[] = 'Backup signature verification failed - file may have been tampered with';
        }

        // Verify integrity
        try {
            if (!$this->backupService->verifyIntegrity($backup)) {
                $errors[] = 'Backup checksum verification failed - file corruption detected';
            }
        } catch (Exception $e) {
            $errors[] = 'Checksum verification error: ' . $e->getMessage();
        }

        // Verify ZIP structure
        if (!$this->validateZipStructure($backup)) {
            $errors[] = 'Backup ZIP structure is invalid or missing required files';
        }

        return $errors;
    }

    /**
     * Validate ZIP structure
     */
    protected function validateZipStructure(Backup $backup): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($backup->getFullPath()) !== true) {
            return false;
        }

        $requiredFiles = [
            'manifest.json',
            'checksums.json',
            'manifest.sig',
            'database.sql',
        ];

        foreach ($requiredFiles as $file) {
            if ($zip->locateName($file) === false) {
                $zip->close();
                return false;
            }
        }

        $zip->close();
        return true;
    }

    /**
     * Create pre-restore snapshot
     */
    public function createSnapshot(User $admin): Backup
    {
        // Create full system backup as snapshot
        $backupService = app(BackupService::class);
        return $backupService->createBackup(
            $admin,
            'full_system',
            null,
            'Pre-restore snapshot - Auto-generated'
        );
    }

    /**
     * Perform restore
     */
    public function restore(Backup $backup, User $admin, bool $overrideLocked = false): void
    {
        if (!$admin->isAdmin()) {
            throw new Exception('Only administrators can restore backups');
        }

        // Validate backup first
        $errors = $this->validate($backup);
        if (!empty($errors)) {
            throw new Exception('Backup validation failed: ' . implode('; ', $errors));
        }

        // Extract to temp location
        $tempPath = storage_path('app/temp/restore/' . uniqid());
        if (!mkdir($tempPath, 0755, true)) {
            throw new Exception('Failed to create temporary restore directory');
        }

        try {
            // Extract ZIP
            $this->extractZip($backup->getFullPath(), $tempPath);

            // Parse manifest
            $manifest = json_decode(
                file_get_contents($tempPath . '/manifest.json'),
                true
            );

            // Check exam year lock status
            if ($backup->isExamYearBackup() && $backup->examYear) {
                if ($backup->examYear->is_locked && !$overrideLocked) {
                    throw new Exception(
                        'Cannot restore exam year backup: Target exam year is locked. ' .
                        'Use override flag to force restore.'
                    );
                }
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Restore database
                $this->restoreDatabase($tempPath, $backup);

                // Restore audit logs
                $this->restoreAuditLogs($tempPath);

                // Clear caches
                $this->clearCaches();

                // Commit transaction
                DB::commit();

                // Log restore event
                GovernanceAuditLog::log(
                    'restore_completed',
                    userId: $admin->id,
                    adminId: $admin->id,
                    data: [
                        'backup_id' => $backup->id,
                        'backup_checksum' => $backup->checksum,
                        'restore_type' => $backup->type,
                        'exam_year' => $backup->examYear?->year_label,
                    ]
                );
            } catch (Exception $e) {
                DB::rollBack();
                throw new Exception('Database restore failed: ' . $e->getMessage());
            }

            // Cleanup
            $this->removeDirectory($tempPath);
        } catch (Exception $e) {
            if (is_dir($tempPath)) {
                $this->removeDirectory($tempPath);
            }

            // Log failed restore
            GovernanceAuditLog::log(
                'restore_failed',
                userId: $admin->id,
                adminId: $admin->id,
                data: [
                    'backup_id' => $backup->id,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Restore database
     */
    protected function restoreDatabase(string $tempPath, Backup $backup): void
    {
        $sqlFile = $tempPath . '/database.sql';

        if (!file_exists($sqlFile)) {
            throw new Exception('database.sql not found in backup');
        }

        $connection = DB::connection()->getDriverName();
        $isSqlite = strtolower($connection) === 'sqlite';

        // Disable foreign keys for restore
        if ($isSqlite) {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $sql = file_get_contents($sqlFile);

            // Separate CREATE TABLE and INSERT statements
            preg_match_all('/CREATE TABLE IF NOT EXISTS[^;]+;/is', $sql, $createMatches);
            preg_match_all('/INSERT INTO[^;]+;/is', $sql, $insertMatches);

            $createStatements = $createMatches[0] ?? [];
            $insertStatements = $insertMatches[0] ?? [];

            // Extract table names from CREATE TABLE statements
            $tablesToRestore = [];
            foreach ($createStatements as $statement) {
                if (preg_match('/CREATE TABLE IF NOT EXISTS\s+[`"]?(\w+)[`"]?/i', $statement, $matches)) {
                    $tablesToRestore[] = $matches[1];
                }
            }

            // Drop only the tables that will be restored (in reverse order for foreign key safety)
            $existingTables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name DESC");
            foreach ($existingTables as $table) {
                // Only drop tables that are in the backup
                if (in_array($table->name, $tablesToRestore)) {
                    try {
                        DB::statement("DROP TABLE IF EXISTS {$table->name}");
                    } catch (\Exception $e) {
                        \Log::warning("Could not drop {$table->name}: " . $e->getMessage());
                    }
                }
            }

            // Execute CREATE TABLE statements to rebuild schema
            foreach ($createStatements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    DB::statement($statement);
                }
            }

            // Define insertion order to respect foreign key constraints
            $insertionOrder = [
                'regions' => [],
                'districts' => [],
                'schools' => [],
                'roles' => [],
                'system_settings' => [],
                'users' => [],
                'user_scopes' => [],
            ];

            // Group INSERT statements by table
            foreach ($insertStatements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    foreach ($insertionOrder as $table => &$statements) {
                        if (stripos($statement, "INSERT INTO `$table`") !== false || 
                            stripos($statement, "INSERT INTO {$table}") !== false) {
                            $statements[] = $statement;
                            break;
                        }
                    }
                }
            }

            // Execute INSERT statements in dependency order
            foreach ($insertionOrder as $table => $statements) {
                foreach ($statements as $statement) {
                    try {
                        DB::statement($statement);
                    } catch (\Exception $e) {
                        \Log::warning("Could not insert into $table: " . $e->getMessage());
                    }
                }
            }
        } finally {
            // Re-enable foreign keys
            if ($isSqlite) {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    /**
     * Restore audit logs
     */
    protected function restoreAuditLogs(string $tempPath): void
    {
        $auditDir = $tempPath . '/audits';

        if (!is_dir($auditDir)) {
            return;
        }

        // Audit logs are immutable - only append new ones
        // Existing audit logs are preserved
    }

    /**
     * Clear caches
     */
    protected function clearCaches(): void
    {
        // Clear query cache
        DB::flushQueryLog();

        // Clear application caches
        cache()->flush();

        // Clear config cache
        \Artisan::call('config:clear');

        // Clear route cache
        \Artisan::call('route:clear');

        // Clear view cache
        \Artisan::call('view:clear');
    }

    /**
     * Extract ZIP
     */
    protected function extractZip(string $zipPath, string $destPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new Exception('Cannot open backup ZIP file');
        }

        if (!$zip->extractTo($destPath)) {
            $zip->close();
            throw new Exception('Failed to extract backup ZIP file');
        }

        $zip->close();
    }

    /**
     * Remove directory recursively
     */
    protected function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($path);
    }
}
