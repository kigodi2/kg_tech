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
            $table->string('mark_entry_session_id')->nullable()->index();
            $table->string('mark_entry_device_hash')->nullable();
            $table->timestamp('mark_entry_last_seen_at')->nullable();
            $table->timestamp('mark_entry_device_locked_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['mark_entry_session_id']);
            $table->dropColumn([
                'mark_entry_session_id',
                'mark_entry_device_hash',
                'mark_entry_last_seen_at',
                'mark_entry_device_locked_at',
            ]);
        });
    }
};
