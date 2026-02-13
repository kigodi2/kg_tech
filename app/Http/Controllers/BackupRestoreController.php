<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Services\SQLiteRestoreService;
use App\Services\SQLiteBackupService;
use Illuminate\Http\Request;

class BackupRestoreController extends Controller
{
    public function showRestoreForm($id)
    {
        $backup = BackupLog::findOrFail($id);
        $backupId = $backup->data['backup_id'] ?? null;
        $filePath = storage_path('backups/sqlite/' . $backupId . '.zip.enc');

        if (!$backupId || !file_exists($filePath)) {
            return back()->with('error', 'Backup file not found');
        }

        return view('backup.restore-form', [
            'backup' => $backup,
            'backupId' => $backupId,
        ]);
    }

    public function executeRestore(Request $request, $id)
    {
        $backup = BackupLog::findOrFail($id);
        
        $request->validate([
            'confirmation' => 'required|in:RESTORE',
        ]);

        try {
            $backupId = $backup->data['backup_id'] ?? null;
            $filePath = storage_path('backups/sqlite/' . $backupId . '.zip.enc');

            if (!$backupId || !file_exists($filePath)) {
                return back()->with('error', 'Backup file not found');
            }

            // Perform restore
            $backupService = new SQLiteBackupService();
            $restoreService = new SQLiteRestoreService($backupService);
            $result = $restoreService->restore(
                $filePath,
                auth()->user(),
                true // Create pre-restore snapshot
            );

            if (isset($result['success']) && $result['success']) {
                return redirect()->route('filament.admin.pages.manage-backups')
                    ->with('success', 'Backup restored successfully. The system is back online.');
            } else {
                return back()->with('error', $result['error'] ?? 'Restore failed');
            }
        } catch (\Exception $e) {
            // Ensure maintenance mode is off on error
            @unlink(storage_path('framework/down'));

            return redirect()->route('filament.admin.pages.manage-backups')
                ->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }
}
