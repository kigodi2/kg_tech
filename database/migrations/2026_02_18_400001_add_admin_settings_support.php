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
        if (Schema::hasTable('system_settings')) {
            Schema::table('system_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('system_settings', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->after('description');
                }
                if (!Schema::hasColumn('system_settings', 'group')) {
                    $table->string('group')->nullable()->default('general')->after('description');
                }
            });
        }

        if (!Schema::hasTable('acsee_settings_history')) {
            Schema::create('acsee_settings_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('setting_id')->constrained('system_settings')->cascadeOnDelete();
                $table->longText('old_value')->nullable();
                $table->longText('new_value')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['setting_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acsee_settings_history');

        if (Schema::hasTable('system_settings')) {
            Schema::table('system_settings', function (Blueprint $table) {
                if (Schema::hasColumn('system_settings', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
                if (Schema::hasColumn('system_settings', 'group')) {
                    $table->dropColumn('group');
                }
            });
        }
    }
};
