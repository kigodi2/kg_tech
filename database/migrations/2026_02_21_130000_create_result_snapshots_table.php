<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_snapshots')) {
            Schema::create('result_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('exam_type', 20);
                $table->unsignedBigInteger('exam_year_id');
                $table->unsignedBigInteger('process_id')->nullable();
                $table->string('version', 20);
                $table->boolean('is_active')->default(false);
                $table->unsignedBigInteger('published_by')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->string('snapshot_hash', 64)->nullable();
                $table->text('publish_notes')->nullable();
                $table->unsignedBigInteger('unlocked_by')->nullable();
                $table->timestamp('unlocked_at')->nullable();
                $table->text('unlock_reason')->nullable();
                $table->timestamps();

                $table->unique(['exam_type', 'exam_year_id', 'version']);
                $table->index(['exam_type', 'exam_year_id', 'is_active']);
                $table->index(['process_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('result_snapshots');
    }
};

