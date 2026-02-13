<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use App\Services\SQLiteBackupService;
use Illuminate\Http\Request;
use Filament\Notifications\Notification;

class BackupManagementController extends Controller
{
    public function index()
    {
        $backups = BackupLog::orderByDesc('created_at')->paginate(25);
        return view('backups.index', ['backups' => $backups]);
    }

    public function create(Request $request)
    {
        try {
            $service = app(SQLiteBackupService::class);
            $result = $service->createFullBackup(auth()->user());

            if ($result['success']) {
                return redirect()->back()->with('success', 'Backup ' . $result['backup_id'] . ' created successfully.');
            } else {
                return redirect()->back()->with('error', $result['error'] ?? 'Backup failed.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $backup = BackupLog::find($id);
            if (!$backup) {
                return redirect()->back()->with('error', 'Backup not found.');
            }

            // Get backup ID from data
            $backupId = $backup->data['backup_id'] ?? null;
            if (!$backupId) {
                return redirect()->back()->with('error', 'Invalid backup record.');
            }

            // Try to delete the encrypted backup file
            $filePath = storage_path('backups/sqlite/' . $backupId . '.zip.enc');
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }

            // Delete the log record
            $backup->delete();
            return redirect()->back()->with('success', 'Backup deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }
}
