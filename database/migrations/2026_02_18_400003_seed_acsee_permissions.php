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
        if (!Schema::hasTable('acsee_role_permissions')) {
            Schema::create('acsee_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->string('permission', 100);
                $table->boolean('granted')->default(true);
                $table->unsignedBigInteger('granted_by')->nullable();
                $table->timestamp('granted_at')->nullable();
                $table->timestamps();

                $table->unique(['role_id', 'permission']);
                $table->index('permission');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acsee_role_permissions');
    }
};
