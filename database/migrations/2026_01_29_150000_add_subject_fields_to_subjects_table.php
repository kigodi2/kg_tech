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
        Schema::table('subjects', function (Blueprint $table) {
            // Add category column
            $table->enum('category', ['ARTS', 'SCIENCE', 'BUSINESS'])->default('SCIENCE')->after('name');
            
            // Add written_papers column (number of papers: 1, 2, or 3)
            $table->integer('written_papers')->default(1)->after('category');
            
            // Add component flags
            $table->boolean('has_practical')->default(false)->after('written_papers');
            $table->boolean('has_project')->default(false)->after('has_practical');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['category', 'written_papers', 'has_practical', 'has_project']);
        });
    }
};
