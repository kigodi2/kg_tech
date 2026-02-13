<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('combination_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combination_id')
                ->constrained('combinations')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();

            // Prevent duplicate combinations
            $table->unique(['combination_id', 'subject_id']);
            
            // Add indexes for performance
            $table->index(['combination_id']);
            $table->index(['subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('combination_subject');
    }
};
