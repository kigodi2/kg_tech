<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('mark_entry_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_mark_id')
                ->constrained('raw_marks')->onDelete('cascade');
            $table->foreignId('changed_by')
                ->constrained('users')->onDelete('cascade');
            $table->enum('change_type', ['upload', 'edit', 'validation_fix', 'admin_correction']);
            $table->string('field_name');
            $table->decimal('old_value', 6, 2)->nullable();
            $table->decimal('new_value', 6, 2)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index('raw_mark_id');
            $table->index('changed_by');
            $table->index('changed_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_entry_changes');
    }
};
