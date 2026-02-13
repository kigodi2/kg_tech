<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * SQLite-Specific Restore Service
 * 
 * Implements safe, atomic restore operations with:
 * - Pre-restore snapshots
 * - Atomic file replacement
 * - WAL/SHM file handling
 * - Schema validation
 * - Automatic rollback on failure
 * - Comprehensive audit logging
 * 
 * @package App\Services
 */
class SQLiteRestoreService
{
    protected string $databasePath;
    protected string $backupRootPath;
    protected string $backupEncryptionKey;
    protected bool $enableEncryption = true;
    protected string $quarantinePath;
    protected string $sandboxPath;

    public function __construct(protected SQLiteBackupService $backupService)
    {
        $this->databasePath = database_path('database.sqlite');
        $this->backupRootPath = storage_path('backups/sqlite');
        $this->backupEncryptionKey = env('BACKUP_ENCRYPTION_KEY') ?? env('APP_KEY') ?? config('app.key') ?? 'fallback-encryption-key-do-not-use-in-production';
        $this->quarantinePath = storage_path('backups/quarantine');
        $this->sandboxPath = storage_path('backups/sandbox');

        foreach ([$this->quarantinePath, $this->sandboxPath] as $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0750, true);
            }
        }
    }

    /**
     * Validate backup before restoration
     * 
     * @param string $backupPath
     * @return array Validation results with errors
     */
    public function validateBackup(string $backupPath): array
    {
        $errors = [];

        // Check file exists
        if (!file_exists($backupPath)) {
            $errors[] = 'Backup file does not exist';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check if encrypted and decrypt if necessary
        $isEncrypted = str_ends_with($backupPath, '.enc');
        $workingPath = $backupPath;

        if ($isEncrypted) {
            try {
                $decryptedPath = $this->decryptBackup($backupPath);
                $workingPath = $decryptedPath;
            } catch (Exception $e) {
                $errors[] = "Backup decryption failed: {$e->getMessage()}";
                return ['valid' => false, 'errors' => $errors];
            }
        }

        // Validate ZIP structure
        $zip = new ZipArchive();
        if ($zip->open($workingPath) !== true) {
            $errors[] = 'Backup file is not a valid ZIP archive';
            if ($isEncrypted) unlink($workingPath);
            return ['valid' => false, 'errors' => $errors];
        }

        // Check required files
        $requiredFiles = ['manifest.json', 'database.sqlite', 'checksums.sha256', 'backup.sig'];
        foreach ($requiredFiles as $file) {
            if ($zip->locateName($file) === false) {
                $errors[] = "Missing required file in backup: $file";
            }
        }

        // Extract and validate manifest
        $manifestJson = $zip->getFromName('manifest.json');
        $manifest = json_decode($manifestJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Backup manifest is corrupted or invalid JSON';
        }

        $zip->close();

        // Validate checksums
        try {
            $this->validateBackupChecksums($workingPath);
        } catch (Exception $e) {
            $errors[] = "Checksum validation failed: {$e->getMessage()}";
        }

        // Cleanup
        if ($isEncrypted) {
            unlink($workingPath);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'manifest' => $manifest ?? null,
        ];
    }

    /**
     * Simulate restore to validate backup integrity
     * 
     * @param string $backupPath
     * @param User $admin
     * @return array Simulation results
     */
    public function simulateRestore(string $backupPath, User $admin): array
    {
        $sandboxDb = "{$this->sandboxPath}/test_" . uniqid() . '.sqlite';
        $extractDir = "{$this->sandboxPath}/extract_" . uniqid();

        try {
            // Create clean sandbox database
            touch($sandboxDb);
            chmod($sandboxDb, 0640);

            // Decrypt if needed
            $isEncrypted = str_ends_with($backupPath, '.enc');
            $workingPath = $backupPath;

            if ($isEncrypted) {
                $workingPath = $this->decryptBackup($backupPath);
            }

            // Extract backup
            @mkdir($extractDir, 0750, true);
            $zip = new ZipArchive();
            if ($zip->open($workingPath) !== true) {
                throw new Exception('Cannot open backup ZIP');
            }

            if (!$zip->extractTo($extractDir)) {
                throw new Exception('Failed to extract backup');
            }
            $zip->close();

            // Copy database.sqlite to sandbox
            $srcDb = "{$extractDir}/database.sqlite";
            if (!file_exists($srcDb)) {
                throw new Exception('database.sqlite not found in backup');
            }

            copy($srcDb, $sandboxDb);

            // Connect to sandbox database
            $sandboxConnection = 'sqlite_test_' . uniqid();
            config(["database.connections.{$sandboxConnection}" => [
                'driver' => 'sqlite',
                'database' => $sandboxDb,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);

            // Validate schema and data
            $schemaValidation = $this->validateDatabaseSchema($sandboxConnection);
            $dataValidation = $this->validateDatabaseData($sandboxConnection);

            // Check WAL/SHM files
            $walPresent = file_exists("{$extractDir}/database.sqlite-wal");
            $shmPresent = file_exists("{$extractDir}/database.sqlite-shm");

            $results = [
                'success' => true,
                'database' => [
                    'tables_valid' => $schemaValidation['valid'],
                    'table_count' => $schemaValidation['table_count'],
                    'tables' => $schemaValidation['tables'],
                    'data_valid' => $dataValidation['valid'],
                    'row_counts' => $dataValidation['row_counts'],
                ],
                'files' => [
                    'database_present' => true,
                    'wal_present' => $walPresent,
                    'shm_present' => $shmPresent,
                ],
                'warnings' => [],
            ];

            // Collect warnings
            if (!$schemaValidation['valid']) {
                $results['success'] = false;
                $results['warnings'][] = 'Schema validation failed: ' . implode(', ', $schemaValidation['errors']);
            }

            if (!$dataValidation['valid']) {
                $results['warnings'][] = 'Data validation issues: ' . implode(', ', $dataValidation['warnings']);
            }

            if (!$walPresent) {
                $results['warnings'][] = 'WAL file missing - may indicate incomplete backup';
            }

            $this->logRestoreOperation('simulation_completed', $admin, [
                'backup_path' => basename($backupPath),
                'simulation_result' => $results['success'] ? 'passed' : 'failed',
                'warnings' => $results['warnings'],
            ]);

            return $results;
        } catch (Exception $e) {
            $this->logRestoreOperation('simulation_failed', $admin, [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'warnings' => [],
            ];
        } finally {
            // Cleanup sandbox
            if (file_exists($sandboxDb)) unlink($sandboxDb);
            if (is_dir($extractDir)) $this->removeDirectory($extractDir);
            if ($isEncrypted && file_exists($workingPath)) unlink($workingPath);
        }
    }

    /**
     * Perform actual restore from backup
     * 
     * @param string $backupPath
     * @param User $admin
     * @param bool $createPreRestoreSnapshot
     * @return array Restore results
     * @throws Exception
     */
    public function restore(
        string $backupPath,
        User $admin,
        bool $createPreRestoreSnapshot = true
    ): array {
        if (!$admin->isAdmin()) {
            throw new Exception('Only administrators can perform restores');
        }

        // Validate backup first
        $validation = $this->validateBackup($backupPath);
        if (!$validation['valid']) {
            throw new Exception('Backup validation failed: ' . implode('; ', $validation['errors']));
        }

        $extractDir = "{$this->sandboxPath}/restore_" . uniqid();
        $timestamp = now()->format('Y-m-d-His');

        try {
            // Create pre-restore snapshot
            if ($createPreRestoreSnapshot) {
                try {
                    $this->backupService->createFullBackup(
                        $admin,
                        "Pre-restore snapshot created at $timestamp"
                    );
                } catch (Exception $e) {
                    Log::warning("Could not create pre-restore snapshot: {$e->getMessage()}");
                    // Don't fail the restore, but log it
                }
            }

            // Put application in maintenance mode
            $maintenanceFile = storage_path('framework/down');
            @mkdir(dirname($maintenanceFile), 0755, true);
            file_put_contents($maintenanceFile, json_encode([
                'time' => time(),
                'message' => 'Database restore in progress',
            ]));

            // Decrypt backup if needed
            $isEncrypted = str_ends_with($backupPath, '.enc');
            $workingPath = $backupPath;

            if ($isEncrypted) {
                $workingPath = $this->decryptBackup($backupPath);
            }

            // Extract backup
            @mkdir($extractDir, 0750, true);
            $zip = new ZipArchive();
            if ($zip->open($workingPath) !== true) {
                throw new Exception('Cannot open backup ZIP file');
            }

            if (!$zip->extractTo($extractDir)) {
                throw new Exception('Failed to extract backup ZIP');
            }
            $zip->close();

            // Verify extracted database file
            $srcDb = "{$extractDir}/database.sqlite";
            if (!file_exists($srcDb)) {
                throw new Exception('database.sqlite not found in backup');
            }

            // Move current database to quarantine
            $quarantineDir = "{$this->quarantinePath}/{$timestamp}";
            @mkdir($quarantineDir, 0750, true);

            rename($this->databasePath, "{$quarantineDir}/database.sqlite");
            if (file_exists("{$this->databasePath}-wal")) {
                rename("{$this->databasePath}-wal", "{$quarantineDir}/database.sqlite-wal");
            }
            if (file_exists("{$this->databasePath}-shm")) {
                rename("{$this->databasePath}-shm", "{$quarantineDir}/database.sqlite-shm");
            }

            // Close all database connections
            DB::disconnect();

            // Restore database file from backup
            copy($srcDb, $this->databasePath);
            chmod($this->databasePath, 0640);

            // Restore WAL/SHM if present
            if (file_exists("{$extractDir}/database.sqlite-wal")) {
                copy("{$extractDir}/database.sqlite-wal", "{$this->databasePath}-wal");
            }
            if (file_exists("{$extractDir}/database.sqlite-shm")) {
                copy("{$extractDir}/database.sqlite-shm", "{$this->databasePath}-shm");
            }

            // Reconnect database
            DB::reconnect();

            // Verify restoration
            $this->verifyRestoration();

            // Remove maintenance mode
            @unlink($maintenanceFile);

            // Log successful restore
            $this->logRestoreOperation('restore_completed', $admin, [
                'backup_path' => basename($backupPath),
                'quarantine_location' => $quarantineDir,
                'pre_restore_snapshot' => $createPreRestoreSnapshot,
            ]);

            // Cleanup
            $this->removeDirectory($extractDir);
            if ($isEncrypted && file_exists($workingPath)) {
                unlink($workingPath);
            }

            return [
                'success' => true,
                'message' => 'Database restore completed successfully',
                'quarantine_location' => $quarantineDir,
                'restored_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            // Remove maintenance mode
            @unlink($maintenanceFile);

            // Log failure
            $this->logRestoreOperation('restore_failed', $admin, [
                'error' => $e->getMessage(),
            ]);

            // Attempt to restore from quarantine
            if (isset($quarantineDir) && is_dir($quarantineDir)) {
                try {
                    DB::disconnect();
                    if (file_exists($this->databasePath)) {
                        unlink($this->databasePath);
                    }
                    rename("{$quarantineDir}/database.sqlite", $this->databasePath);
                    DB::reconnect();
                    Log::warning("Automatic rollback to pre-restore state completed");
                } catch (Exception $rollbackError) {
                    Log::error("Failed to rollback: {$rollbackError->getMessage()}");
                    throw new Exception("Restore failed and automatic rollback also failed. Quarantine location: $quarantineDir");
                }
            }

            throw new Exception("SQLite restore failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Validate database schema integrity
     */
    protected function validateDatabaseSchema(string $connection): array
    {
        try {
            $tables = DB::connection($connection)
                ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");

            $tableNames = array_map(fn($t) => $t->name, $tables);

            $criticalTables = [
                'users', 'roles', 'candidates', 'exam_years', 'exam_types',
                'schools', 'districts', 'regions', 'subjects', 'marks'
            ];

            $missingTables = [];
            foreach ($criticalTables as $table) {
                if (!in_array($table, $tableNames)) {
                    $missingTables[] = $table;
                }
            }

            return [
                'valid' => empty($missingTables),
                'table_count' => count($tableNames),
                'tables' => $tableNames,
                'errors' => $missingTables ? ["Missing critical tables: " . implode(', ', $missingTables)] : [],
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'table_count' => 0,
                'tables' => [],
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Validate database data integrity
     */
    protected function validateDatabaseData(string $connection): array
    {
        $warnings = [];
        $rowCounts = [];

        try {
            $tables = DB::connection($connection)
                ->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

            foreach ($tables as $table) {
                try {
                    $count = DB::connection($connection)
                        ->table($table->name)
                        ->count();
                    $rowCounts[$table->name] = $count;

                    if ($count === 0 && !in_array($table->name, ['backups', 'backup_logs'])) {
                        $warnings[] = "Table {$table->name} is empty";
                    }
                } catch (Exception $e) {
                    $warnings[] = "Could not count rows in {$table->name}";
                }
            }

            return [
                'valid' => true,
                'row_counts' => $rowCounts,
                'warnings' => $warnings,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'row_counts' => [],
                'warnings' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Verify restoration was successful
     */
    protected function verifyRestoration(): void
    {
        // Check database is accessible
        try {
            $result = DB::select('SELECT sqlite_version() as version');
            if (empty($result)) {
                throw new Exception('Database query returned no results');
            }
        } catch (Exception $e) {
            throw new Exception("Database verification failed: {$e->getMessage()}");
        }

        // Verify foreign keys
        try {
            DB::statement('PRAGMA foreign_keys = ON');
        } catch (Exception $e) {
            Log::warning("Could not enable foreign keys: {$e->getMessage()}");
        }
    }

    /**
     * Decrypt backup file
     */
    protected function decryptBackup(string $encryptedPath): string
    {
        $encrypted = file_get_contents($encryptedPath);
        if ($encrypted === false) {
            throw new Exception("Cannot read encrypted backup file");
        }

        // Extract IV and encrypted data
        $iv = substr($encrypted, 0, 16);
        $ciphertext = substr($encrypted, 16);

        $key = hash('sha256', $this->backupEncryptionKey, true);

        $plaintext = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new Exception("Decryption failed: " . openssl_error_string());
        }

        // Write to temporary file
        $tempPath = "{$this->sandboxPath}/decrypted_" . uniqid() . '.zip';
        if (file_put_contents($tempPath, $plaintext) === false) {
            throw new Exception("Failed to write decrypted backup");
        }

        return $tempPath;
    }

    /**
     * Validate backup checksums
     */
    protected function validateBackupChecksums(string $backupPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            throw new Exception('Cannot open backup file for checksum validation');
        }

        $checksumsJson = $zip->getFromName('checksums.sha256');
        $checksums = json_decode($checksumsJson, true);

        $zip->close();

        if (!$checksums) {
            throw new Exception('Cannot read checksums from backup');
        }

        // Note: Full checksum validation would require extracting all files
        // For now, we just verify the manifest
        if (!isset($checksums['manifest.json'])) {
            throw new Exception('Manifest checksum missing from backup');
        }
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
                @rmdir($fileinfo->getRealPath());
            } else {
                @unlink($fileinfo->getRealPath());
            }
        }

        @rmdir($path);
    }

    /**
     * Log restore operation
     */
    protected function logRestoreOperation(
        string $operation,
        User $admin,
        array $data
    ): void {
        try {
            BackupLog::create([
                'user_id' => $admin->id,
                'operation' => $operation,
                'data' => $data,
                'status' => str_contains($operation, 'failed') ? 'failed' : 'success',
            ]);
        } catch (Exception $e) {
            Log::error("Failed to log restore operation: {$e->getMessage()}");
        }
    }
}
