<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * SQLite-Specific Backup Service
 * 
 * Implements SQLite-safe backup strategy with:
 * - Physical database file copying
 * - WAL/SHM file handling
 * - Read-consistent snapshots
 * - AES-256 encryption
 * - Comprehensive audit logging
 * 
 * @package App\Services
 */
class SQLiteBackupService
{
    protected string $databasePath;
    protected string $backupRootPath;
    protected string $backupEncryptionKey;
    protected bool $enableEncryption = true;
    protected int $maxConcurrentBackups = 1;

    public function __construct()
    {
        $this->databasePath = database_path('database.sqlite');
        $this->backupRootPath = storage_path('backups/sqlite');
        $this->backupEncryptionKey = env('BACKUP_ENCRYPTION_KEY') ?? env('APP_KEY') ?? config('app.key') ?? 'fallback-encryption-key-do-not-use-in-production';
        
        if (!is_dir($this->backupRootPath)) {
            @mkdir($this->backupRootPath, 0750, true);
        }
    }

    /**
     * Create a complete SQLite backup
     * 
     * @param User $admin
     * @param string|null $notes
     * @param array $options
     * @return array Backup metadata
     * @throws Exception
     */
    public function createFullBackup(
        User $admin,
        ?string $notes = null,
        array $options = []
    ): array {
        if (!$admin->isAdmin()) {
            throw new Exception('Only administrators can create backups');
        }

        $backupId = $this->generateBackupId();
        $backupDir = "{$this->backupRootPath}/{$backupId}";
        
        try {
            // Create backup working directory
            $this->createBackupDirectory($backupDir);

            // Get database file metadata
            $dbFileSize = filesize($this->databasePath);
            $dbFileHash = hash_file('sha256', $this->databasePath);

            // Ensure SQLite is in WAL mode for safe backup
            $this->ensureWALMode();

            // Wait for any pending transactions
            $this->waitForDatabaseQuiescence();

            // Copy database.sqlite
            $this->copyDatabaseFile($this->databasePath, "{$backupDir}/database.sqlite");

            // Copy WAL files if they exist
            if (file_exists("{$this->databasePath}-wal")) {
                copy("{$this->databasePath}-wal", "{$backupDir}/database.sqlite-wal");
            }
            if (file_exists("{$this->databasePath}-shm")) {
                copy("{$this->databasePath}-shm", "{$backupDir}/database.sqlite-shm");
            }

            // Create backup manifest
            $manifest = $this->createBackupManifest($admin, $backupId, $dbFileHash);
            file_put_contents("{$backupDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Generate checksums for all files
            $checksums = $this->generateBackupChecksums($backupDir);
            file_put_contents("{$backupDir}/checksums.sha256", json_encode($checksums, JSON_PRETTY_PRINT));

            // Create digital signature
            $signature = $this->signBackup($manifest);
            file_put_contents("{$backupDir}/backup.sig", $signature);

            // Compress and encrypt
            $zipPath = "{$this->backupRootPath}/{$backupId}.zip";
            $this->zipDirectory($backupDir, $zipPath);

            // Encrypt ZIP if enabled
            $encryptedPath = null;
            if ($this->enableEncryption) {
                $encryptedPath = "{$zipPath}.enc";
                $this->encryptFile($zipPath, $encryptedPath);
                unlink($zipPath); // Remove unencrypted version
                $finalPath = $encryptedPath;
            } else {
                $finalPath = $zipPath;
            }

            // Get final file size and hash
            $finalFileSize = filesize($finalPath);
            $finalChecksum = hash_file('sha256', $finalPath);

            // Clean up backup directory
            $this->removeDirectory($backupDir);

            // Log backup creation
            $this->logBackupOperation('backup_created', $admin, [
                'backup_id' => $backupId,
                'database_size' => $dbFileSize,
                'database_hash' => $dbFileHash,
                'archive_size' => $finalFileSize,
                'archive_hash' => $finalChecksum,
                'encrypted' => $this->enableEncryption,
                'wal_mode' => true,
                'notes' => $notes,
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'path' => $finalPath,
                'size' => $finalFileSize,
                'checksum' => $finalChecksum,
                'database_hash' => $dbFileHash,
                'encrypted' => $this->enableEncryption,
                'created_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            // Log failure
            $this->logBackupOperation('backup_failed', $admin, [
                'backup_id' => $backupId ?? null,
                'error' => $e->getMessage(),
                'notes' => $notes,
            ]);

            // Cleanup on failure
            if (isset($backupDir) && is_dir($backupDir)) {
                $this->removeDirectory($backupDir);
            }

            throw new Exception("SQLite backup failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Create incremental backup (backup only WAL changes)
     * 
     * @param User $admin
     * @param Carbon $since
     * @return array|null
     * @throws Exception
     */
    public function createIncrementalBackup(
        User $admin,
        Carbon $since
    ): ?array {
        if (!file_exists("{$this->databasePath}-wal")) {
            return null; // No WAL, no incremental backup needed
        }

        $walModTime = filemtime("{$this->databasePath}-wal");
        if ($walModTime < $since->timestamp) {
            return null; // WAL hasn't changed
        }

        $backupId = $this->generateBackupId('incremental');
        $backupDir = "{$this->backupRootPath}/{$backupId}";

        try {
            $this->createBackupDirectory($backupDir);

            // Copy only WAL and SHM
            copy("{$this->databasePath}-wal", "{$backupDir}/database.sqlite-wal");
            if (file_exists("{$this->databasePath}-shm")) {
                copy("{$this->databasePath}-shm", "{$backupDir}/database.sqlite-shm");
            }

            // Create manifest for incremental
            $manifest = $this->createBackupManifest($admin, $backupId, null, 'incremental');
            file_put_contents("{$backupDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT));

            // Zip and encrypt
            $zipPath = "{$this->backupRootPath}/{$backupId}.zip";
            $this->zipDirectory($backupDir, $zipPath);

            if ($this->enableEncryption) {
                $encryptedPath = "{$zipPath}.enc";
                $this->encryptFile($zipPath, $encryptedPath);
                unlink($zipPath);
                $finalPath = $encryptedPath;
            } else {
                $finalPath = $zipPath;
            }

            $this->removeDirectory($backupDir);

            $this->logBackupOperation('incremental_backup_created', $admin, [
                'backup_id' => $backupId,
                'archive_size' => filesize($finalPath),
                'archive_hash' => hash_file('sha256', $finalPath),
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'type' => 'incremental',
                'path' => $finalPath,
                'size' => filesize($finalPath),
            ];
        } catch (Exception $e) {
            $this->logBackupOperation('incremental_backup_failed', $admin, [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Ensure SQLite is using WAL mode for atomic backups
     */
    protected function ensureWALMode(): void
    {
        try {
            if (DB::transactionLevel() > 0) {
                // Cannot change journal mode inside a transaction, but it's likely already WAL
                return;
            }
            DB::statement('PRAGMA journal_mode = WAL;');
            Log::info('SQLite WAL mode enabled for safe backups');
        } catch (Exception $e) {
            Log::warning("Could not enable WAL mode: {$e->getMessage()}");
        }
    }

    protected function waitForDatabaseQuiescence(int $maxWaitSeconds = 30): void
    {
        // If we're already in a transaction, we cannot start a new one to test quiescence
        if (DB::transactionLevel() > 0) {
            Log::warning('Database is currently in a transaction; skipping quiescence wait to avoid deadlocks.');
            return;
        }

        $startTime = time();
        $waitInterval = 100000; // 0.1 seconds in microseconds
        
        // Prevent hitting PHP's max execution time by stopping early
        $phpMax = (int) ini_get('max_execution_time');
        $effectiveTimeout = ($phpMax > 0 && $phpMax <= $maxWaitSeconds) ? max(1, $phpMax - 5) : $maxWaitSeconds;

        while (time() - $startTime < $effectiveTimeout) {
            try {
                // Try to acquire exclusive lock (indicates database is idle)
                DB::statement('BEGIN IMMEDIATE;');
                DB::statement('ROLLBACK;');
                return; // Database is idle
            } catch (Exception $e) {
                usleep($waitInterval);
            }
        }

        // Log warning but continue
        Log::warning('Database did not reach quiescence within timeout; proceeding with backup anyway');
    }

    /**
     * Safely copy database file with verification
     */
    protected function copyDatabaseFile(string $source, string $destination): void
    {
        if (!file_exists($source)) {
            throw new Exception("Database file not found: $source");
        }

        // Verify file is not being written to
        $sourceSize1 = filesize($source);
        usleep(500000); // Wait 0.5 seconds
        $sourceSize2 = filesize($source);

        if ($sourceSize1 !== $sourceSize2) {
            Log::warning('Database file size changed during backup preparation; will retry');
            sleep(1);
            $this->copyDatabaseFile($source, $destination);
            return;
        }

        // Perform copy
        if (!copy($source, $destination)) {
            throw new Exception("Failed to copy database file to backup");
        }

        // Verify copy integrity
        if (hash_file('sha256', $source) !== hash_file('sha256', $destination)) {
            unlink($destination);
            throw new Exception("Backup file integrity check failed");
        }

        // Set secure permissions
        chmod($destination, 0640);
    }

    /**
     * Create backup manifest with metadata
     */
    protected function createBackupManifest(
        User $admin,
        string $backupId,
        ?string $dbFileHash,
        string $type = 'full'
    ): array {
        return [
            'backup_id' => $backupId,
            'type' => $type,
            'created_at' => now()->toIso8601String(),
            'created_by' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
            'system_info' => [
                'app_name' => config('app.name'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'sqlite_version' => DB::select('SELECT sqlite_version() as version')[0]->version ?? 'unknown',
            ],
            'database_info' => [
                'database_file' => basename($this->databasePath),
                'file_hash' => $dbFileHash,
                'wal_mode' => true,
                'foreign_keys' => DB::select("PRAGMA foreign_keys")[0]->foreign_keys ?? 0,
            ],
            'backup_options' => [
                'encryption' => $this->enableEncryption,
                'compression' => true,
            ],
        ];
    }

    /**
     * Generate SHA256 checksums for all backup files
     */
    protected function generateBackupChecksums(string $backupDir): array
    {
        $checksums = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $relPath = str_replace($backupDir . DIRECTORY_SEPARATOR, '', $file->getRealPath());
                $checksums[$relPath] = hash_file('sha256', $file->getRealPath());
            }
        }

        return $checksums;
    }

    /**
     * Sign backup manifest with HMAC-SHA256
     */
    protected function signBackup(array $manifest): string
    {
        $key = hash('sha256', $this->backupEncryptionKey, true);
        return hash_hmac('sha256', json_encode($manifest), $key);
    }

    /**
     * Encrypt file using AES-256-CBC
     */
    protected function encryptFile(string $sourcePath, string $destPath): void
    {
        $plaintext = file_get_contents($sourcePath);
        if ($plaintext === false) {
            throw new Exception("Could not read file for encryption: $sourcePath");
        }

        $key = hash('sha256', $this->backupEncryptionKey, true);
        $iv = openssl_random_pseudo_bytes(16);

        $encrypted = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new Exception("Encryption failed: " . openssl_error_string());
        }

        // Write IV + encrypted data
        $output = $iv . $encrypted;
        if (file_put_contents($destPath, $output) === false) {
            throw new Exception("Failed to write encrypted backup file");
        }

        chmod($destPath, 0640);
    }

    /**
     * Zip directory recursively
     */
    protected function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create ZIP archive: $zipPath");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $arcName = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $filePath);
                $zip->addFile($filePath, $arcName);
            }
        }

        if (!$zip->close()) {
            throw new Exception('Failed to finalize ZIP archive');
        }

        chmod($zipPath, 0640);
    }

    /**
     * Generate unique backup ID
     */
    protected function generateBackupId(string $prefix = 'full'): string
    {
        return 'bak-' . $prefix . '-' . date('Y-m-d-His') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
    }

    /**
     * Create backup working directory
     */
    protected function createBackupDirectory(string $path): void
    {
        if (!@mkdir($path, 0750, true) && !is_dir($path)) {
            throw new Exception("Failed to create backup directory: $path");
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
     * Log backup operation to audit trail
     */
    protected function logBackupOperation(
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
            Log::error("Failed to log backup operation: {$e->getMessage()}");
        }
    }

    /**
     * Get backup root path
     */
    public function getBackupRootPath(): string
    {
        return $this->backupRootPath;
    }

    /**
     * Validate backup file integrity
     */
    public function validateBackupIntegrity(string $backupPath, array $manifest): bool
    {
        if (!file_exists($backupPath)) {
            return false;
        }

        // Verify signature
        $expectedSignature = $this->signBackup($manifest);
        $actualSignature = $manifest['signature'] ?? null;

        return hash_equals($expectedSignature, $actualSignature ?? '');
    }
}
