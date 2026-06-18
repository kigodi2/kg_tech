<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('governance_audit_logs', function (Blueprint $table) {
            // Change action from enum to string to allow new actions like 'candidate_reclaimed'
            // without breaking SQLite CHECK constraints or requiring frequent migrations.
            $table->string('action', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('governance_audit_logs', function (Blueprint $table) {
            $table->enum('action', [
                'user_created',
                'user_role_assigned',
                'user_scope_assigned',
                'user_suspended',
                'user_activated',
                'password_reset',
                'password_changed',
                'login_successful',
                'login_failed',
                'import_initiated',
                'import_completed',
                'import_failed',
                'backup_created',
                'backup_downloaded',
                'backup_deleted',
                'restore_initiated',
                'restore_completed',
                'restore_failed',
            ])->change();
        });
    }
};
