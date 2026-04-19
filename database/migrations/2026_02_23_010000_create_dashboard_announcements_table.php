<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboard_announcements')) {
            Schema::create('dashboard_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20); // event | news
                $table->string('title');
                $table->date('publish_date');
                $table->string('link_url')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['type', 'is_active', 'publish_date']);
                $table->index(['type', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_announcements');
    }
};
