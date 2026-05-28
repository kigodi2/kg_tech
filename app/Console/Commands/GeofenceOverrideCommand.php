<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MarkEntryGeofenceOverride;
use App\Models\MarkEntryLocationLog;
use Carbon\Carbon;

class GeofenceOverrideCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mark-entry:geo-override
                            {user : The email address or ID of the user}
                            {--minutes=60 : The override duration in minutes}
                            {--reason= : The reason for the override}';

    /**
     * The console command description.
     */
    protected $description = 'Grant temporary geofence bypass override for a Mark Entry Officer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $minutes = (int) $this->option('minutes');
        $reason = $this->option('reason');

        if (!$reason) {
            $reason = $this->ask('Please enter the reason for this geofence override');
            if (empty($reason)) {
                $this->error('A valid reason is required for geofence override auditing.');
                return 1;
            }
        }

        // 1. Find the User
        $user = User::where('email', $userIdentifier)
            ->orWhere('id', $userIdentifier)
            ->first();

        if (!$user) {
            $this->error("User '{$userIdentifier}' not found.");
            return 1;
        }

        // 2. Validate MEO role
        if (!$user->isMarkEntryOfficer()) {
            $this->error("User '{$user->email}' is not a Mark Entry Officer. Geofence overrides are only applicable to Mark Entry Officers.");
            return 1;
        }

        // 3. Create or update the override
        $expiresAt = Carbon::now()->addMinutes($minutes);
        $admin = auth()->user() ?? User::where('is_admin', true)->first(); // Fallback to first admin if run from CLI

        $override = MarkEntryGeofenceOverride::updateOrCreate(
            ['user_id' => $user->id],
            [
                'override_by' => $admin ? $admin->id : $user->id, // Audit who did it
                'reason' => $reason,
                'expires_at' => $expiresAt
            ]
        );

        // 4. Log the override in geofence location logs for compliance audit trail
        MarkEntryLocationLog::create([
            'user_id' => $user->id,
            'marking_centre_id' => $user->marking_centre_id,
            'allowed' => true,
            'reason' => "Admin override granted by " . ($admin ? $admin->email : 'CLI') . " for {$minutes} mins. Reason: {$reason}"
        ]);

        $this->info("Geofence override successfully granted!");
        $this->line("User:        {$user->email} (ID: {$user->id})");
        $this->line("Duration:    {$minutes} minutes");
        $this->line("Expires At:  {$expiresAt->toDateTimeString()}");
        $this->line("Reason:      {$reason}");

        return 0;
    }
}
