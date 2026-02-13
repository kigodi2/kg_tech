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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('username');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->nullable()->after('last_name');
            $table->string('irms_role')->default('DATA_ENTRY')->after('email');
            $table->boolean('is_active')->default(true)->after('irms_role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
            $table->foreignId('district_council_id')->nullable()->constrained('district_councils')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['school_id']);
            $table->dropForeignKeyIfExists(['district_council_id']);
            $table->dropColumn(['username', 'first_name', 'last_name', 'phone', 'irms_role', 'is_active', 'last_login_at', 'school_id', 'district_council_id']);
        });
    }
};
