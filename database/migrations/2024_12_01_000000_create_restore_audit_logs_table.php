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
        if (Schema::hasTable('restore_audit_logs')) {
            return;
        }

        Schema::create('restore_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('authorized_by_id')->nullable()->constrained('users', 'id')->onDelete('restrict');
            
            // Backup information
            $table->string('backup_id');
            $table->string('backup_filename');
            $table->string('backup_hash'); // SHA-256 of archive
            
            // Scope information (RBAC)
            $table->enum('scope_type', ['full', 'region', 'district']);
            // regions/districts are created later in this migration set, so their
            // foreign keys are added by a follow-up compatibility migration.
            $table->unsignedBigInteger('region_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            
            // Legal and operational details
            $table->longText('restore_reason');
            $table->longText('legal_acknowledgment');
            $table->boolean('legal_acknowledged')->default(false);
            
            // Network and user agent
            $table->ipAddress('ip_address');
            $table->text('user_agent');
            
            // Status tracking
            $table->enum('status', [
                'initiated',      // Restore requested
                'confirmed',      // Awaiting execution
                'in_progress',    // Restore running
                'completed',      // Success
                'failed',         // Error occurred
                'rolled_back',    // Auto-rollback from quarantine
            ])->default('initiated');
            
            $table->longText('error_message')->nullable();
            
            // Timeline
            $table->timestamp('initiated_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            // Immutable audit log - no updates after creation
            $table->timestamps();
            
            // Indexes for audit reporting
            $table->index('user_id');
            $table->index('authorized_by_id');
            $table->index('status');
            $table->index('scope_type');
            $table->index('created_at');
            $table->index(['region_id', 'created_at']);
            $table->index(['district_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restore_audit_logs');
    }
};
