<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\ExamYear;
use App\Models\GovernanceAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupService
{
    protected string $backupDir = 'backups';
    protected string $tempDir = 'temp/backups';

    /**
     * Create a backup
     */
    public function createBackup(
        User $admin,
        string $type = 'exam_year',
        ?ExamYear $examYear = null,
        ?string $notes = null
    ): Backup {
        if (!$admin->isAdmin()) {
            throw new Exception('Only administrators can create backups');
        }

        $tempPath = storage_path('app/' . $this->tempDir . '/' . uniqid());
        if (!mkdir($tempPath, 0755, true)) {
            throw new Exception('Failed to create temporary backup directory');
        }

        try {
            // Create manifest
            $manifest = $this->createManifest($type, $examYear, $admin);

            // Create database dump
            $this->dumpDatabase($tempPath, $type, $examYear);

            // Export audit logs
            $this->exportAuditLogs($tempPath, $type, $examYear);

            // Export imports if applicable
            if ($type !== 'metadata_only') {
                $this->exportImports($tempPath, $examYear);
            }

            // Export metadata
            $this->exportMetadata($tempPath);

            // Create manifest.json
            file_put_contents(
                $tempPath . '/manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Generate checksums
            $checksums = $this->generateChecksums($tempPath);
            file_put_contents(
                $tempPath . '/checksums.json',
                json_encode($checksums, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Sign manifest
            $signature = $this->signManifest($manifest);
            file_put_contents($tempPath . '/manifest.sig', $signature);

            // Create ZIP archive
            $filename = $this->generateFilename($type, $examYear);
            $zipPath = 'backups/' . $filename;
            $fullZipPath = storage_path('app/' . $zipPath);

            if (!is_dir(dirname($fullZipPath))) {
                mkdir(dirname($fullZipPath), 0755, true);
            }

            $this->zipDirectory($tempPath, $fullZipPath);

            $fileSize = filesize($fullZipPath);
            $checksum = hash_file('sha256', $fullZipPath);

            // Create backup record
            $backup = Backup::create([
                'admin_id' => $admin->id,
                'type' => $type,
                'exam_year_id' => $examYear?->id,
                'filename' => $filename,
                'path' => $zipPath,
                'manifest' => $manifest,
                'checksum_algo' => 'SHA256',
                'checksum' => $checksum,
                'signature' => $signature,
                'size_bytes' => $fileSize,
                'verified' => true,
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'notes' => $notes,
            ]);

            // Audit log
            GovernanceAuditLog::log(
                'backup_created',
                userId: $admin->id,
                adminId: $admin->id,
                data: [
                    'backup_id' => $backup->id,
                    'type' => $type,
                    'exam_year' => $examYear?->year_label,
                    'filename' => $filename,
                    'checksum' => $checksum,
                    'size_bytes' => $fileSize,
                ]
            );

            // Cleanup temp directory
            $this->removeDirectory($tempPath);

            return $backup;
        } catch (Exception $e) {
            // Cleanup on failure
            if (is_dir($tempPath)) {
                $this->removeDirectory($tempPath);
            }

            throw $e;
        }
    }

    /**
     * Create backup manifest
     */
    protected function createManifest(
        string $type,
        ?ExamYear $examYear,
        User $admin
    ): array {
        $exam = $examYear?->exam_type;
        $examName = match ($exam) {
            'ACSEE' => 'ACSEE',
            'NACTVET' => 'NACTVET',
            default => 'Unknown',
        };

        return [
            'backup_type' => $type,
            'exam' => $examName,
            'exam_year' => $examYear?->year_label,
            'created_at' => now()->toIso8601String(),
            'created_by' => $admin->id,
            'created_by_name' => $admin->name,
            'system_version' => 'IRMS v1.0',
            'checksum_algo' => 'SHA256',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    /**
     * Dump database to SQL
     */
    protected function dumpDatabase(string $tempPath, string $type, ?ExamYear $examYear): void
    {
        $tables = $this->getTablesToDump($type);
        $existingTables = $this->getExistingTables();
        $connection = DB::connection()->getDriverName();

        $sql = "-- IRMS Backup SQL Dump\n";
        $sql .= "-- Generated: " . now()->toIso8601String() . "\n";
        $sql .= "-- Backup Type: $type\n";
        $sql .= "-- Exam Year: " . $examYear?->year_label . "\n\n";

        // Use appropriate syntax for the database type
        if (strtolower($connection) === 'sqlite') {
            $sql .= "PRAGMA foreign_keys = OFF;\n\n";
        } else {
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        }

        foreach ($tables as $table) {
            // Only dump tables that exist
            if (in_array($table, $existingTables)) {
                $sql .= $this->dumpTable($table, $type, $examYear);
            }
        }

        $sql .= "\n";
        if (strtolower($connection) === 'sqlite') {
            $sql .= "PRAGMA foreign_keys = ON;\n";
        } else {
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        }

        file_put_contents($tempPath . '/database.sql', $sql);
    }

    /**
     * Get list of existing tables in the database
     */
    protected function getExistingTables(): array
    {
        $connection = DB::connection()->getDriverName();
        if (strtolower($connection) === 'sqlite') {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            return array_map(fn($t) => $t->name, $tables);
        } else {
            // MySQL/PostgreSQL
            $tables = DB::select('SHOW TABLES');
            $key = array_key_first((array) $tables[0] ?? []);
            return array_map(fn($t) => $t->$key, $tables);
        }
    }

    /**
     * Get tables to dump based on backup type
     */
    protected function getTablesToDump(string $type): array
    {
        $allTables = [
            'users',
            'roles',
            'user_scopes',
            'regions',
            'districts',
            'schools',
            'exam_years',
            'exam_types',
            'candidates',
            'marks',
            'combinations',
            'bulk_imports',
            'authentication_audit_logs',
            'governance_audit_logs',
            'system_settings',
        ];

        return match ($type) {
            'full_system' => $allTables,
            'metadata_only' => ['users', 'roles', 'user_scopes', 'regions', 'districts', 'schools', 'system_settings'],
            'exam_year' => $allTables,
            default => $allTables,
        };
    }

    /**
     * Dump single table
     */
    protected function dumpTable(string $table, string $type, ?ExamYear $examYear): string
    {
        // Tables that are exam-year scoped
        $examYearScopedTables = [
            'candidates',
            'marks',
            'combinations',
            'bulk_imports',
        ];

        $query = DB::table($table);

        // Apply exam year filter if applicable
        if ($type === 'exam_year' && in_array($table, $examYearScopedTables) && $examYear) {
            $query->where('exam_year_id', $examYear->id);
        }

        $sql = "";

        // Get CREATE TABLE statement (SQLite compatible)
        try {
            $connection = DB::connection()->getDriverName();
            if (strtolower($connection) === 'sqlite') {
                $createTableQuery = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$table]);
                if (!empty($createTableQuery) && isset($createTableQuery[0]->sql) && !empty($createTableQuery[0]->sql)) {
                    $createStmt = $createTableQuery[0]->sql;
                    // Replace CREATE TABLE with CREATE TABLE IF NOT EXISTS
                    $createStmt = preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $createStmt);
                    $sql .= $createStmt . ";\n\n";
                }
            } else {
                // MySQL/other databases
                $createTableQuery = DB::select('SHOW CREATE TABLE `' . $table . '`');
                if (!empty($createTableQuery)) {
                    $createStmt = $createTableQuery[0]->{'Create Table'};
                    // Replace CREATE TABLE with CREATE TABLE IF NOT EXISTS
                    $createStmt = preg_replace('/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $createStmt);
                    $sql .= $createStmt . ";\n\n";
                }
            }
        } catch (\Exception $e) {
            // If we can't get CREATE statement, skip it (this is safe for backups)
            \Log::warning("Could not get CREATE TABLE for $table: " . $e->getMessage());
        }

        // Get data
        $rows = $query->get();

        if ($rows->count() > 0) {
            $sql .= "INSERT INTO `$table` VALUES\n";

            $rowSql = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ((array) $row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $rowSql[] = '(' . implode(',', $values) . ')';
            }

            $sql .= implode(",\n", $rowSql) . ";\n\n";
        }

        return $sql;
    }

    /**
     * Export audit logs
     */
    protected function exportAuditLogs(string $tempPath, string $type, ?ExamYear $examYear): void
    {
        $logsDir = $tempPath . '/audits';
        mkdir($logsDir, 0755, true);

        $existingTables = $this->getExistingTables();

        // Export authentication logs if table exists
        if (in_array('authentication_audit_logs', $existingTables)) {
            $authLogs = DB::table('authentication_audit_logs')->get();
            file_put_contents(
                $logsDir . '/authentication.json',
                json_encode($authLogs, JSON_PRETTY_PRINT)
            );
        }

        // Export governance logs if table exists
        if (in_array('governance_audit_logs', $existingTables)) {
            $govLogs = DB::table('governance_audit_logs')->get();
            file_put_contents(
                $logsDir . '/governance.json',
                json_encode($govLogs, JSON_PRETTY_PRINT)
            );
        }
    }

    /**
     * Export imports
     */
    protected function exportImports(string $tempPath, ?ExamYear $examYear): void
    {
        $importsDir = $tempPath . '/imports';
        mkdir($importsDir, 0755, true);

        $imports = DB::table('bulk_imports')
            ->when($examYear, fn($q) => $q->where('exam_year_id', $examYear->id))
            ->get();

        file_put_contents(
            $importsDir . '/bulk_imports.json',
            json_encode($imports, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Export metadata
     */
    protected function exportMetadata(string $tempPath): void
    {
        $metadataDir = $tempPath . '/metadata';
        mkdir($metadataDir, 0755, true);

        $metadata = [
            'users' => DB::table('users')->get(),
            'roles' => DB::table('roles')->get(),
            'regions' => DB::table('regions')->get(),
            'districts' => DB::table('districts')->get(),
            'schools' => DB::table('schools')->get(),
            'system_settings' => DB::table('system_settings')->get(),
        ];

        file_put_contents(
            $metadataDir . '/metadata.json',
            json_encode($metadata, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Generate checksums for all files
     */
    protected function generateChecksums(string $tempPath): array
    {
        $checksums = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempPath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $relPath = str_replace($tempPath, '', $file->getRealPath());
                $checksums[$relPath] = hash_file('sha256', $file->getRealPath());
            }
        }

        return $checksums;
    }

    /**
     * Sign manifest with private key
     */
    protected function signManifest(array $manifest): string
    {
        // For now, use HMAC signature (in production, use RSA)
        $privateKey = config('app.backup_key') ?? env('BACKUP_KEY', 'fallback-key-change-in-production');
        return hash_hmac('sha256', json_encode($manifest), $privateKey);
    }

    /**
     * Verify backup signature
     */
    public function verifySignature(Backup $backup): bool
    {
        $manifest = is_array($backup->manifest) ? $backup->manifest : $backup->manifest->toArray();
        $expectedSignature = $this->signManifest($manifest);
        return hash_equals($backup->signature, $expectedSignature);
    }

    /**
     * Verify backup integrity
     */
    public function verifyIntegrity(Backup $backup): bool
    {
        if (!$backup->exists()) {
            throw new Exception('Backup file does not exist');
        }

        if (!$this->verifySignature($backup)) {
            return false;
        }

        $actualChecksum = hash_file('sha256', $backup->getFullPath());
        return hash_equals($backup->checksum, $actualChecksum);
    }

    /**
     * Generate filename
     */
    protected function generateFilename(string $type, ?ExamYear $examYear): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $typeSuffix = match ($type) {
            'full_system' => 'full-system',
            'metadata_only' => 'metadata-only',
            default => $examYear ? 'acsee-' . $examYear->year_label : 'exam-year',
        };

        return "irms-backup-{$typeSuffix}-{$timestamp}.zip";
    }

    /**
     * Zip directory
     */
    protected function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot open ZIP file: $zipPath");
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($sourceDir . DIRECTORY_SEPARATOR, '', $filePath);
                $zip->addFile($filePath, $relativePath);
            }
        }

        if (!$zip->close()) {
            throw new Exception('Failed to close ZIP file');
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
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($path);
    }
}
