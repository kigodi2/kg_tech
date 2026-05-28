<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mark_entry_location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('marking_centre_id')->nullable()->constrained('marking_centres')->nullOnDelete();
            $table->decimal('attempted_latitude', 10, 7)->nullable();
            $table->decimal('attempted_longitude', 10, 7)->nullable();
            $table->decimal('centre_latitude', 10, 7)->nullable();
            $table->decimal('centre_longitude', 10, 7)->nullable();
            $table->double('distance_meters')->nullable();
            $table->double('accuracy_meters')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->boolean('allowed');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('mark_entry_geofence_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('override_by')->constrained('users')->onDelete('cascade');
            $table->string('reason');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_entry_geofence_overrides');
        Schema::dropIfExists('mark_entry_location_logs');
    }
};
