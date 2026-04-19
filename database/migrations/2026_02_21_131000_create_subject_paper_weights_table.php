<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subject_paper_weights')) {
            Schema::create('subject_paper_weights', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id');
                $table->string('paper_code', 20); // paper_1 | paper_2 | paper_3
                $table->decimal('weight', 8, 4)->default(1.0000);
                $table->decimal('max_mark', 8, 2)->default(100.00);
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->unique(['subject_id', 'paper_code']);
                $table->index(['subject_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_paper_weights');
    }
};

