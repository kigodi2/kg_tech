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
        if (!Schema::hasTable('system_event_logs')) {
            Schema::create('system_event_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('actor_user_id')->nullable();
                $table->string('category', 50);
                $table->string('action', 100);
                $table->string('status', 20);
                $table->string('correlation_id', 36)->nullable();
                $table->json('context')->nullable();
                $table->text('message');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['category', 'status']);
                $table->index('created_at');
                $table->index('correlation_id');
                $table->index('actor_user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_event_logs');
    }
};
