<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marking_centres', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->integer('allowed_radius_meters')->default(50)->after('longitude');
            $table->boolean('is_active_geofence')->default(true)->after('allowed_radius_meters');
        });
    }

    public function down(): void
    {
        Schema::table('marking_centres', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'allowed_radius_meters',
                'is_active_geofence'
            ]);
        });
    }
};
