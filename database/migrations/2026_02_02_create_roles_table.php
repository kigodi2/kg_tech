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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // admin, regional_officer, district_data_entry_officer, etc.
            $table->string('name'); // Display name
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default roles
        \DB::table('roles')->insert([
            ['code' => 'admin', 'name' => 'Administrator', 'description' => 'Full system access and user management'],
            ['code' => 'regional_officer', 'name' => 'Regional Officer', 'description' => 'Oversees data quality across region'],
            ['code' => 'district_data_entry_officer', 'name' => 'District Data Entry Officer', 'description' => 'Imports marks for district schools'],
            ['code' => 'district_supervisor', 'name' => 'District Supervisor', 'description' => 'Supervises data entry at district level'],
            ['code' => 'school_registrar', 'name' => 'School Registrar', 'description' => 'Registers candidates at school level'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
