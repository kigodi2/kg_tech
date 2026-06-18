<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Recreates the governance_audit_logs table to strip SQLite CHECK constraints.
     */
    public function up(): void {
        // SQLite doesn't handle enum changes well via change(), so we manually recreate the table
        
        // 1. Create a backup of existing data
        Schema::dropIfExists('gov_logs_backup');
        DB::statement('CREATE TABLE gov_logs_backup AS SELECT * FROM governance_audit_logs');
        
        // 2. Drop the old table with the restrictive CHECK constraint
        Schema::dropIfExists('governance_audit_logs');

        // 3. Create the new table with a flexible string action column
        Schema::create('governance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('action', 100); 
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['admin_id', 'created_at']);
        });

        // 4. Restore the data
        DB::statement('INSERT INTO governance_audit_logs (id, admin_id, user_id, action, data, created_at) SELECT id, admin_id, user_id, action, data, created_at FROM gov_logs_backup');
        
        // 5. Cleanup backup
        Schema::dropIfExists('gov_logs_backup');
    }
    
    public function down(): void {}
};
