<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * IMMUTABLE audit log for:
     * - User creation
     * - Role assignment
     * - Scope assignment
     * - Password resets
     * - Account suspension/activation
     * - Login events
     * - Import actions
     */
    public function up(): void
    {
        Schema::create('governance_audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Who performed the action
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Who was affected
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // What happened
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
            ]);
            
            // Context data (immutable JSON)
            $table->json('data')->nullable();
            // Examples:
            // user_created: {name, email, role_code, scope_type, scope_id}
            // user_suspended: {reason, suspension_timestamp}
            // import_initiated: {import_id, exam_year_id, district_id, zip_checksum}
            // login_successful: {ip_address, user_agent, timestamp}
            
            // IMMUTABLE: Only created_at, never updated
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for audit queries
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['admin_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('governance_audit_logs');
    }
};
