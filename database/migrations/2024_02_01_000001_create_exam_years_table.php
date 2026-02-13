<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the foundational exam_years table.
     * This is the core domain entity that all exam-related data depends on.
     */
    public function up(): void
    {
        Schema::create('exam_years', function (Blueprint $table) {
            $table->id()->comment('Unique exam year identifier');
            
            $table->string('year_label', 9)
                ->unique()
                ->comment('Academic year label (e.g., "2024", "2023-2024")');
            
            $table->boolean('is_active')
                ->default(false)
                ->comment('Only one exam year can be active at a time');
            
            $table->boolean('is_locked')
                ->default(false)
                ->comment('Locked years are read-only after publication');
            
            $table->timestamp('published_at')
                ->nullable()
                ->comment('When results were published for this year');
            
            $table->timestamp('locked_at')
                ->nullable()
                ->comment('When this year was locked');
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('is_locked');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_years');
    }
};
