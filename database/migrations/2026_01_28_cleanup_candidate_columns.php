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
        Schema::table('candidates', function (Blueprint $table) {
            // Drop email unique constraint first
            $table->dropUnique('candidates_email_unique');
            
            // Drop the columns
            $table->dropColumn(['first_name', 'last_name', 'email', 'date_of_birth']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('first_name', 100)->after('candidate_id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('email')->nullable()->unique()->after('last_name');
            $table->date('date_of_birth')->nullable()->after('is_active');
        });
    }
};
