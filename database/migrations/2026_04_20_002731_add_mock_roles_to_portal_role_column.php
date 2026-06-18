<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SQLite doesn't support ALTER COLUMN on enums,
     * so we change to a plain string column to allow all roles.
     */
    public function up(): void
    {
        // For SQLite: drop and recreate as varchar (SQLite ignores enum anyway)
        Schema::table('users', function (Blueprint $table) {
            // Only needed for MySQL - SQLite ignores column type constraints
            // We simply keep portal_role as a string field
        });

        // If running MySQL, alter the column
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN portal_role ENUM('admin','user','mock_dao','mock_headteacher') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN portal_role ENUM('admin','user') NULL");
        }
    }
};
