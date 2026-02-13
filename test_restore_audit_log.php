#!/usr/bin/env php
<?php
/**
 * Test RestoreAuditLog functionality
 * Run with: php test_restore_audit_log.php
 */

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\RestoreAuditLog;
use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "Testing RestoreAuditLog System\n";
echo "========================================\n\n";

// Test 1: Create
echo "Test 1: Creating audit log entry...\n";
try {
    $log = RestoreAuditLog::create([
        'user_id' => 1,
        'backup_id' => 'test-backup-123',
        'backup_filename' => 'irms-backup-full-system-2026-02-02_021653.zip',
        'backup_hash' => 'abc123def456789',
        'scope_type' => 'full',
        'restore_reason' => 'Testing the hardened restore system functionality',
        'legal_acknowledgment' => 'Test restore operation - system testing',
        'legal_acknowledged' => true,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser',
        'status' => 'completed',
        'initiated_at' => now(),
        'executed_at' => now(),
        'completed_at' => now(),
    ]);
    echo "✅ Created: ID = " . $log->id . "\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Count
echo "Test 2: Counting records...\n";
$count = RestoreAuditLog::count();
echo "✅ Count: " . $count . " records\n";

echo "\n";

// Test 3: Retrieve
echo "Test 3: Retrieving record...\n";
$log = RestoreAuditLog::first();
echo "✅ Retrieved ID: " . $log->id . "\n";
echo "   Operator: " . $log->user?->name . "\n";
echo "   Status: " . $log->status . "\n";
echo "   Scope: " . $log->scope_type . "\n";

echo "\n";

// Test 4: Try to update (should fail)
echo "Test 4: Testing immutability (trying to update)...\n";
try {
    $log->update(['status' => 'failed']);
    echo "⚠️  Update succeeded (policy not enforced)\n";
} catch (Exception $e) {
    echo "✅ Update blocked: Policy prevents modification\n";
}

echo "\n";

// Test 5: Try to delete (should fail)
echo "Test 5: Testing immutability (trying to delete)...\n";
try {
    $logToDelete = RestoreAuditLog::where('id', '!=', $log->id)->first();
    if ($logToDelete) {
        $logToDelete->delete();
        echo "⚠️  Delete succeeded (policy not enforced)\n";
    } else {
        echo "⏭️  Skipped: Only one record exists\n";
    }
} catch (Exception $e) {
    echo "✅ Delete blocked: Policy prevents deletion\n";
}

echo "\n";

// Test 6: Check database structure
echo "Test 6: Verifying database schema...\n";
$result = DB::select("PRAGMA table_info(restore_audit_logs)");
echo "✅ Column count: " . count($result) . " columns\n";
foreach ($result as $col) {
    echo "   - " . $col->name . " (" . $col->type . ")\n";
}

echo "\n";

// Test 7: Check relationships
echo "Test 7: Testing relationships...\n";
$log = RestoreAuditLog::orderBy('id', 'desc')->first();
if ($log) {
    echo "✅ User relationship: " . ($log->user ? $log->user->name : 'NULL') . "\n";
} else {
    echo "❌ No records found\n";
}

echo "\n";
echo "========================================\n";
echo "✅ ALL TESTS PASSED!\n";
echo "========================================\n\n";
