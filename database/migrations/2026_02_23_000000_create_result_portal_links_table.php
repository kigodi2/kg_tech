<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_portal_links')) {
            Schema::create('result_portal_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id')->nullable();
                $table->unsignedBigInteger('region_id')->nullable();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->string('token_hash', 64)->index();
                $table->string('name');
                $table->json('meta_json')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'expires_at']);
                $table->index(['exam_id', 'school_id']);

                $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
                $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('result_portal_links');
    }
};
