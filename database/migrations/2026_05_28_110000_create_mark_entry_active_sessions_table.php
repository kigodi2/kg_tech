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
        // 1. Drop the temporary user columns if they exist
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['mark_entry_session_id']);
            $table->dropColumn([
                'mark_entry_session_id',
                'mark_entry_device_hash',
                'mark_entry_last_seen_at',
                'mark_entry_device_locked_at'
            ]);
        });

        // 2. Create the dedicated tracking table
        Schema::create('mark_entry_active_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('session_id')->index();
            $table->string('device_hash')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_entry_active_sessions');

        Schema::table('users', function (Blueprint $table) {
            $table->string('mark_entry_session_id')->nullable()->index();
            $table->string('mark_entry_device_hash')->nullable();
            $table->timestamp('mark_entry_last_seen_at')->nullable();
            $table->timestamp('mark_entry_device_locked_at')->nullable();
        });
    }
};
