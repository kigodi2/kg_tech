<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds a registration_number field to schools table.
     * This is the official school registration number (e.g., S0108, S0109)
     * separate from the internal system code.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Add registration_number column after code
            $table->string('registration_number')
                ->nullable()
                ->unique()
                ->index()
                ->comment('Official school registration number (e.g., S0108, S0109)')
                ->after('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('registration_number');
        });
    }
};
