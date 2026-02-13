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
            $table->string('email')->nullable()->unique()->after('last_name');
            $table->string('exam_type')->nullable()->after('date_of_birth');
            $table->string('status')->default('registered')->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique('candidates_email_unique');
            $table->dropColumn(['email', 'exam_type', 'status']);
        });
    }
};
