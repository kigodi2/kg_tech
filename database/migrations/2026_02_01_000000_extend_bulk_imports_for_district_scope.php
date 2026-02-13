<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add scope_type and scope_id columns to bulk_imports for district-level imports
     */
    public function up(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            // Make school_id nullable (for district-level imports)
            $table->unsignedBigInteger('school_id')->nullable()->change();

            // Add scope columns
            $table->enum('scope_type', ['school', 'district'])->default('school')->after('exam_year_id');
            $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');

            // Add status value for partial completion
            $table->unsignedBigInteger('district_id')->nullable()->after('school_id');

            // Track schools processed in district import
            $table->integer('total_schools')->default(0)->after('total_files');
            $table->integer('processed_schools')->default(0)->after('processed_files');

            // Add index for district queries
            $table->index(['district_id', 'exam_year_id']);
            $table->index(['scope_type', 'scope_id']);

            // Foreign key for district
            $table->foreign('district_id')
                ->references('id')
                ->on('districts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulk_imports', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['district_id']);

            // Drop new columns
            $table->dropIndex(['district_id', 'exam_year_id']);
            $table->dropIndex(['scope_type', 'scope_id']);
            $table->dropColumn(['district_id', 'scope_type', 'scope_id', 'total_schools', 'processed_schools']);

            // Restore school_id as non-nullable
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
        });
    }
};
