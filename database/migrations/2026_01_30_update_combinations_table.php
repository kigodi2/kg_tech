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
        Schema::table('combinations', function (Blueprint $table) {
            // Add category field if it doesn't exist
            if (!Schema::hasColumn('combinations', 'category')) {
                $table->string('category')->default('ARTS')
                    ->comment('ARTS, SCIENCE, BUSINESS')
                    ->after('code');
            }

            // Add description field if it doesn't exist
            if (!Schema::hasColumn('combinations', 'description')) {
                $table->text('description')->nullable()
                    ->after('category');
            }
        });

        // Add indexes if they don't exist (SQLite limitation, so we skip this step)
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('combinations', function (Blueprint $table) {
            $table->dropUnique(['exam_type_id', 'code']);
            $table->dropIndex(['exam_type_id']);
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'description']);
        });
    }
};
