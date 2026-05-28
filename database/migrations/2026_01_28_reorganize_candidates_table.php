<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // SQLite doesn't support modifying column order easily, so we'll create a new table
        // and copy the data over
        Schema::create('candidates_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('full_name', 255)->nullable();
            $table->enum('gender', ['M', 'F']);
            $table->string('combination')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->string('exam_type')->nullable();
            $table->string('status')->default('registered');
            $table->index(['school_id', 'is_active']);
        });

        // Copy data from old table to new table
        \Illuminate\Support\Facades\DB::statement('INSERT INTO candidates_new (id, school_id, full_name, gender, combination, is_active, created_at, updated_at, exam_type, status) 
            SELECT id, school_id, full_name, gender, combination, is_active, created_at, updated_at, exam_type, status FROM candidates');

        // Drop old table and rename new one
        Schema::drop('candidates');
        Schema::rename('candidates_new', 'candidates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a destructive migration, rollback will require manual intervention
        // For safety, we won't auto-rollback this one
    }
};
