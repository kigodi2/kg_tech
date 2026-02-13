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
        Schema::table('users', function (Blueprint $table) {
            // Add password reset requirement flag
            $table->boolean('password_reset_required')->default(true)->after('password');
            
            // Replace is_active with status enum
            $table->string('status')->default('active')->after('password_reset_required');
            // Values: active, suspended (no soft deletes - users are never deleted)
            
            // Add role FK (replaces irms_role string column)
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('restrict');
            
            // Remove old role column after migration
            // (will drop in down())
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['role_id']);
            $table->dropColumn(['password_reset_required', 'status', 'role_id']);
        });
    }
};
