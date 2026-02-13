<?php
/**
 * Mark the restore_audit_logs migration as complete
 * 
 * Run with: php -r "include 'mark_migration_complete.php';"
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if already marked
$exists = DB::table('migrations')
    ->where('migration', '2024_12_01_000000_create_restore_audit_logs_table')
    ->exists();

if ($exists) {
    echo "✅ Migration already marked as complete\n";
} else {
    // Mark as migrated
    DB::table('migrations')->insert([
        'migration' => '2024_12_01_000000_create_restore_audit_logs_table',
        'batch' => DB::table('migrations')->max('batch') + 1,
    ]);
    echo "✅ Migration marked as complete\n";
}

// Verify table exists
$tableExists = DB::connection()->getDoctrineSchemaManager()->tablesExist('restore_audit_logs');
echo $tableExists ? "✅ Table exists\n" : "❌ Table not found\n";

// Test model
$count = \App\Models\RestoreAuditLog::count();
echo "✅ Model works: " . $count . " records\n";
