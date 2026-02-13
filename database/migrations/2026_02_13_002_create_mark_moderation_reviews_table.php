<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('mark_moderation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')
                ->constrained('mark_import_batches')->onDelete('cascade');
            $table->foreignId('reviewer_id')
                ->constrained('users')->onDelete('cascade');
            $table->enum('review_type', ['school_hod', 'district_supervisor', 'admin']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'conditional'])
                ->default('pending');
            $table->longText('feedback')->nullable();
            $table->json('flagged_issues')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();
            $table->index('mark_import_batch_id');
            $table->index('reviewer_id');
            $table->index('status');
            $table->index('reviewed_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_moderation_reviews');
    }
};
