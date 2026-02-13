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
        Schema::table('raw_marks', function (Blueprint $table) {
            // Row locking fields
            $table->boolean('is_locked')->default(false)->after('processed_at');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete()->after('locked_at');
            
            // Add index for efficiency
            $table->index('is_locked');
        });

        // Create mark_import_checksums table for template integrity verification
        Schema::create('mark_import_checksums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')->constrained('mark_import_batches')->onDelete('cascade');
            $table->string('checksum')->comment('SHA-256 checksum of CSV template');
            $table->unsignedInteger('candidate_count')->comment('Number of eligible candidates at template generation');
            $table->json('candidate_index_numbers')->comment('Array of candidate index numbers in template order');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
            
            // For quick lookup
            $table->index('mark_import_batch_id');
            $table->index('checksum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_import_checksums');
        
        Schema::table('raw_marks', function (Blueprint $table) {
            $table->dropIndex(['is_locked']);
            $table->dropForeign(['locked_by']);
            $table->dropColumn(['is_locked', 'locked_at', 'locked_by']);
        });
    }
};
