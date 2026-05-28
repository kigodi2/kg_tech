<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\GovernanceAuditLog;
use App\Models\MarkEntryActiveSession;
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
    protected $description = 'Resets and clears a stuck Mark Entry Officer active session record';

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
            $this->warn("User exists but is not recognized as a Mark Entry Officer. Checking session record anyway.");
        }

        // Find active session
        $activeSession = MarkEntryActiveSession::where('user_id', $user->id)->first();

        if (!$activeSession) {
            $this->info("No active Mark Entry session found for user: {$user->email}");
            return Command::SUCCESS;
        }

        $oldIp = $activeSession->ip_address;
        $lastSeen = $activeSession->last_seen_at ? $activeSession->last_seen_at->format('Y-m-d H:i:s') : 'Never';
        $oldSessionId = $activeSession->session_id;

        // Delete active session record
        $activeSession->delete();

        GovernanceAuditLog::log(
            GovernanceAuditLog::ACTION_LOGIN_FAILED,
            userId: $user->id,
            adminId: null,
            data: [
                'event' => 'mark_entry_session_cleared_by_admin',
                'cleared_session_hash' => $oldSessionId,
                'user_email' => $user->email,
                'active_ip' => $oldIp,
                'last_seen' => $lastSeen,
            ]
        );

        $this->line("Mark Entry session cleared.");
        $this->line("User: {$user->email}");
        $this->line("Previous active IP: {$oldIp}");
        $this->line("Last seen: {$lastSeen}");

        return Command::SUCCESS;
    }
}
