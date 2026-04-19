<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mark_outlier_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_mark_id')->constrained('raw_marks')->cascadeOnDelete();
            $table->string('issue_type', 100);
            $table->string('resolution_action', 100)->default('APPROVED');
            $table->text('note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolution_correlation_id', 80)->nullable()->index();
            $table->timestamps();

            $table->unique(['raw_mark_id', 'issue_type'], 'uniq_raw_mark_issue_type');
            $table->index(['issue_type', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_outlier_resolutions');
    }
};

