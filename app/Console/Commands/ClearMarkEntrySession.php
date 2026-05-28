<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\GovernanceAuditLog;
use Illuminate\Console\Command;

class ClearMarkEntrySession extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mark-entry:clear-session {user : The ID or email of the Mark Entry Officer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets and clears a stuck Mark Entry Officer session ID and device hash';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userIdentifier = $this->argument('user');

        // Find user by ID or Email
        $user = null;
        if (is_numeric($userIdentifier)) {
            $user = User::find($userIdentifier);
        } else {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($userIdentifier))])->first();
        }

        if (!$user) {
            $this->error("User not found with identifier: {$userIdentifier}");
            return Command::FAILURE;
        }

        // Verify if user has the Mark Entry Officer role/status
        if (!$user->isMarkEntryOfficer()) {
            $this->warn("User exists but is not recognized as a Mark Entry Officer. Proceeding with clear anyway.");
        }

        $oldSessionId = $user->mark_entry_session_id;

        $user->update([
            'mark_entry_session_id' => null,
            'mark_entry_device_hash' => null,
            'mark_entry_last_seen_at' => null,
            'mark_entry_device_locked_at' => null,
        ]);

        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_LOGIN_FAILED, // Reusing action type or general audit logging
            userId: $user->id,
            adminId: null,
            data: [
                'event' => 'admin_cleared_meo_session_via_artisan',
                'cleared_session_id_hash' => $oldSessionId ? hash('sha256', $oldSessionId) : null,
                'user_email' => $user->email,
            ]
        );

        $this->info("Successfully cleared stuck Mark Entry Officer session for user: {$user->email} (ID: {$user->id}).");
        return Command::SUCCESS;
    }
}
