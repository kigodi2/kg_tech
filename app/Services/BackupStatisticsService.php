<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * BackupStatisticsService
 * 
 * Manages calculation and caching of backup directory statistics.
 * Prevents timeout issues by caching results instead of scanning directories
 * on every request.
 */
class BackupStatisticsService
{
    const CACHE_KEY = 'backup_storage_stats';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get total backup storage size from cache
     * Falls back to calculating if not cached
     * 
     * @return int Total size in bytes
     */
    public static function getTotalBackupSize(): int
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::calculateBackupSize();
        });
    }

    /**
     * Calculate backup size without timeout risk
     * Uses direct file iteration with limits
     * 
     * @return int Total size in bytes
     */
    private static function calculateBackupSize(): int
    {
        $totalSize = 0;
        $backupDir = storage_path('backups/sqlite');

        // Check if directory exists
        if (!is_dir($backupDir)) {
            return 0;
        }

        // Use scandir instead of glob for better control
        // Suppress errors in case of permission issues
        $files = @scandir($backupDir, SCANDIR_SORT_NONE);
        
        if ($files === false) {
            \Log::warning('BackupStatisticsService: Failed to scan backup directory: ' . $backupDir);
            return 0;
        }

        // Limit iteration to first 1000 files for safety
        // This prevents timeout on directories with many backups
        $fileCount = 0;
        foreach ($files as $file) {
            if (++$fileCount > 1000) {
                \Log::warning('BackupStatisticsService: Backup directory has >1000 files, stopping count');
                break;
            }

            // Skip "." and ".." directory entries
            if ($file === '.' || $file === '..') {
                continue;
            }

            // Only count .enc files (encrypted backups)
            if (!str_ends_with($file, '.enc')) {
                continue;
            }

            $filePath = $backupDir . '/' . $file;
            
            // Verify file exists and is readable
            if (is_file($filePath) && is_readable($filePath)) {
                $fileSize = @filesize($filePath);
                if ($fileSize !== false) {
                    $totalSize += $fileSize;
                }
            }
        }

        return $totalSize;
    }

    /**
     * Clear cache when a backup is created/deleted
     * Ensures the next request recalculates current size
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Format bytes as human-readable string
     * 
     * @param int $bytes Size in bytes
     * @return string Formatted size string (e.g., "2.45 GB")
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
