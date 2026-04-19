<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_portal_items')) {
            Schema::create('result_portal_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('result_portal_link_id');
                $table->string('label');
                $table->string('region_slug')->nullable();
                $table->unsignedBigInteger('file_id')->nullable();
                $table->string('file_path')->nullable();
                $table->string('sort_key');
                $table->timestamps();

                $table->index(['result_portal_link_id', 'sort_key']);

                $table->foreign('result_portal_link_id')
                    ->references('id')
                    ->on('result_portal_links')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('result_portal_items');
    }
};
