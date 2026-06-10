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
        Schema::table('mark_entry_changes', function (Blueprint $table) {
            $table->string('old_value')->nullable()->change();
            $table->string('new_value')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mark_entry_changes', function (Blueprint $table) {
            $table->decimal('old_value', 6, 2)->nullable()->change();
            $table->decimal('new_value', 6, 2)->nullable()->change();
        });
    }
};
