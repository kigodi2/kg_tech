<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('report_jobs')) {
            return;
        }

        Schema::create('report_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type', 120);
            $table->string('status', 30)->default('pending');
            $table->json('parameters')->nullable();
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'report_jobs_user_created_idx');
            $table->index(['status', 'created_at'], 'report_jobs_status_created_idx');
            $table->index(['report_type', 'created_at'], 'report_jobs_type_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_jobs');
    }
};
