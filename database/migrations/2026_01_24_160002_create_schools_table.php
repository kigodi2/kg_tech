<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->foreignId('region_id')->constrained()->onDelete('cascade');
            $table->foreignId('council_id')->nullable()->constrained('district_councils')->onDelete('set null');
            $table->enum('school_type', ['PRIMARY', 'SECONDARY', 'BOTH'])->default('PRIMARY');
            $table->enum('education_level', ['PRIMARY', 'SECONDARY'])->default('PRIMARY');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('principal_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
