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
        Schema::create('restore_audit_logs', function (Blueprint $table) {
            $table->id();

            // Operator information
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who initiated the restore');

            $table->foreignId('authorized_by_id')
                ->nullable()
                ->constrained('users', 'id')
                ->nullableOnDelete()
                ->comment('User who authorized the restore (if multi-auth)');

            // Backup identification
            $table->string('backup_id')
                ->comment('Backup identifier');

            $table->string('backup_filename')
                ->comment('Original backup filename');

            $table->char('backup_hash', 64)
                ->comment('SHA-256 hash of restored archive');

            // Scope: full|region|district
            $table->enum('scope_type', ['full', 'region', 'district'])
                ->default('full')
                ->comment('Scope of restore operation');

            $table->foreignId('region_id')
                ->nullable()
                ->constrained('regions')
                ->nullableOnDelete()
                ->comment('Region ID for regional restores');

            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullableOnDelete()
                ->comment('District ID for district restores');

            // Operator input
            $table->text('restore_reason')
                ->comment('Operator provided reason for restore');

            $table->text('legal_acknowledgment')
                ->comment('Full legal acknowledgment text presented to operator');

            $table->boolean('legal_acknowledged')
                ->default(false)
                ->comment('Operator confirmed acknowledgment checkbox');

            // Technical information
            $table->string('ip_address')
                ->comment('IP address of requester');

            $table->string('user_agent')
                ->comment('User agent string of requester');

            // Status tracking
            $table->enum('status', [
                'initiated',      // Request created
                'confirmed',      // Legal acknowledgment confirmed
                'in_progress',    // Restore started
                'completed',      // Restore finished successfully
                'failed',         // Restore failed
                'rolled_back',    // Failed and rolled back
            ])->default('initiated')
             ->comment('Current status of restore operation');

            $table->longText('error_message')
                ->nullable()
                ->comment('Error message if operation failed');

            // Timeline
            $table->timestamp('initiated_at')
                ->useCurrent()
                ->comment('When operator initiated restore');

            $table->timestamp('confirmed_at')
                ->nullable()
                ->comment('When operator confirmed legal acknowledgment');

            $table->timestamp('executed_at')
                ->nullable()
                ->comment('When restore actually started');

            $table->timestamp('completed_at')
                ->nullable()
                ->comment('When restore finished');

            // Audit trail (immutable)
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at - immutable records

            // Indexes for audit queries
            $table->index('user_id');
            $table->index('authorized_by_id');
            $table->index('status');
            $table->index('scope_type');
            $table->index('region_id');
            $table->index('district_id');
            $table->index('created_at');
            $table->index('initiated_at');
            $table->index('executed_at');
            $table->index(['scope_type', 'region_id']);
            $table->index(['scope_type', 'district_id']);

            // Unique index on backup hash per date (prevents duplicate restores same day)
            $table->unique(['backup_hash', 'scope_type'], 'unique_backup_hash_per_scope');

            $table->comment('Immutable audit trail for all restore operations. NECTA-compliant governance record.');
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
