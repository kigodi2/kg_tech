<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // SQLite doesn't support changing enums easily, so we just let it be.
            // But actually we can just drop and recreate the column if we want.
            // However, most Laravel SQLite drivers create a CHECK constraint for enums.
        }

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN portal_role ENUM('admin','user','mock_dao','mock_headteacher','mock_rao') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN portal_role ENUM('admin','user','mock_dao','mock_headteacher') NULL");
        }
    }
};
