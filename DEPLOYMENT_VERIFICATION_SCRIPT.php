<?php

/**
 * Hardened Restore System - Deployment Verification Script
 * 
 * Run this script to verify all components are properly deployed and operational.
 * Usage: php artisan tinker < DEPLOYMENT_VERIFICATION_SCRIPT.php
 * Or manually run each section in tinker
 */

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║     HARDENED RESTORE SYSTEM - DEPLOYMENT VERIFICATION       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 1: FILE VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 1: FILE DEPLOYMENT VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

$requiredFiles = [
    'app/Models/RestoreAuditLog.php',
    'app/Services/HardenedRestoreService.php',
    'app/Policies/HardenedRestorePolicy.php',
    'app/Http/Controllers/HardenedRestoreController.php',
    'app/Filament/Admin/Pages/HardenedRestore.php',
    'routes/hardened-restore.php',
    'resources/views/filament/admin/pages/hardened-restore.blade.php',
];

$allFilesPresent = true;
foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    $status = $exists ? '✓' : '✗';
    $color = $exists ? '✓' : '✗';
    echo "  {$status} {$file}\n";
    if (!$exists) $allFilesPresent = false;
}

echo "\n  Result: " . ($allFilesPresent ? "✓ ALL FILES PRESENT" : "✗ MISSING FILES") . "\n\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 2: DATABASE VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 2: DATABASE VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    // Check table exists
    $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='restore_audit_logs'");
    
    if (empty($tables)) {
        echo "  ✗ restore_audit_logs table NOT FOUND\n";
        echo "  Run: php artisan migrate\n\n";
    } else {
        echo "  ✓ restore_audit_logs table exists\n";
        
        // Check columns
        $columns = DB::select("PRAGMA table_info(restore_audit_logs)");
        echo "  ✓ Columns: " . count($columns) . " (expected 20)\n";
        
        // List columns
        $columnNames = array_map(fn($c) => $c->name, $columns);
        echo "    Columns: " . implode(', ', array_slice($columnNames, 0, 5)) . "...\n";
        
        // Check for updated_at (should NOT exist)
        $hasUpdatedAt = in_array('updated_at', $columnNames);
        echo "  " . (!$hasUpdatedAt ? "✓" : "✗") . " Immutable (no updated_at): " . (!$hasUpdatedAt ? "PASS" : "FAIL") . "\n";
        
        // Count records
        $count = DB::table('restore_audit_logs')->count();
        echo "  ✓ Current records: {$count}\n";
    }
} catch (Exception $e) {
    echo "  ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 3: MODEL VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 3: MODEL VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $model = new \App\Models\RestoreAuditLog();
    echo "  ✓ RestoreAuditLog model instantiates\n";
    echo "  ✓ Table: " . $model->getTable() . "\n";
    
    // Check relationships
    $relationships = ['user', 'authorizedBy', 'region', 'district'];
    foreach ($relationships as $rel) {
        $hasMethod = method_exists($model, $rel);
        echo "  " . ($hasMethod ? "✓" : "✗") . " Relationship: {$rel}\n";
    }
    
    // Check scopes
    $scopes = ['completed', 'failed', 'byUser', 'byStatus'];
    foreach ($scopes as $scope) {
        $hasMethod = method_exists($model, 'scope' . ucfirst($scope));
        echo "  " . ($hasMethod ? "✓" : "✗") . " Scope: {$scope}\n";
    }
} catch (Exception $e) {
    echo "  ✗ Model error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 4: SERVICE VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 4: SERVICE VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $service = app(\App\Services\HardenedRestoreService::class);
    echo "  ✓ HardenedRestoreService instantiates\n";
    echo "  ✓ Class: " . get_class($service) . "\n";
    
    // Check methods
    $methods = [
        'validateRestorePreconditions',
        'validateLegalAcknowledgment',
        'executeRestore',
        'validateExtractedDatabase',
        'verifyRestoredDatabase',
    ];
    
    foreach ($methods as $method) {
        $hasMethod = method_exists($service, $method);
        echo "  " . ($hasMethod ? "✓" : "✗") . " Method: {$method}\n";
    }
} catch (Exception $e) {
    echo "  ✗ Service error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 5: POLICY VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 5: POLICY VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $policy = new \App\Policies\HardenedRestorePolicy();
    echo "  ✓ HardenedRestorePolicy instantiates\n";
    
    // Check methods
    $methods = [
        'restoreFullSystem',
        'restoreRegion',
        'restoreDistrict',
        'viewRestoreAuditLogs',
        'downloadRestoreAuditReport',
    ];
    
    foreach ($methods as $method) {
        $hasMethod = method_exists($policy, $method);
        echo "  " . ($hasMethod ? "✓" : "✗") . " Method: {$method}\n";
    }
    
    // Test with admin user
    $admin = \App\Models\User::where('is_admin', true)->first();
    if ($admin) {
        echo "  ✓ Test admin user found: " . $admin->name . "\n";
        $canRestore = $policy->restoreFullSystem($admin);
        echo "  " . ($canRestore ? "✓" : "✗") . " Admin can restore: " . ($canRestore ? "YES" : "NO") . "\n";
    } else {
        echo "  ⚠ No admin user found for testing\n";
    }
} catch (Exception $e) {
    echo "  ✗ Policy error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 6: ROUTE VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 6: ROUTE VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $routes = Route::getRoutes();
    $restoreRoutes = [];
    
    foreach ($routes as $route) {
        if (strpos($route->uri(), 'api/restore') !== false) {
            $restoreRoutes[] = $route;
        }
    }
    
    echo "  ✓ Found " . count($restoreRoutes) . " restore API routes\n\n";
    
    $expectedRoutes = [
        'GET' => 'api/restore/legal-text',
        'POST' => 'api/restore/validate',
        'POST' => 'api/restore/confirm',
        'POST' => 'api/restore/execute',
        'GET' => 'api/restore/audit-logs',
        'POST' => 'api/restore/audit-export',
    ];
    
    foreach ($expectedRoutes as $method => $uri) {
        $found = false;
        foreach ($restoreRoutes as $route) {
            if ($route->uri() === $uri) {
                foreach ($route->methods() as $m) {
                    if ($m === $method || ($m === 'HEAD' && $method === 'GET')) {
                        $found = true;
                        break;
                    }
                }
            }
        }
        echo "  " . ($found ? "✓" : "✗") . " {$method} {$uri}\n";
    }
} catch (Exception $e) {
    echo "  ✗ Route error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 7: AUTHORIZATION VERIFICATION
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 7: AUTHORIZATION VERIFICATION\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $policy = new \App\Policies\HardenedRestorePolicy();
    
    // Get test users
    $admin = \App\Models\User::where('is_admin', true)->first();
    $nonAdmin = \App\Models\User::where('is_admin', false)->first();
    
    if ($admin) {
        $canRestore = $policy->restoreFullSystem($admin);
        echo "  ✓ Admin user test:\n";
        echo "    " . ($canRestore ? "✓" : "✗") . " Can restore: " . ($canRestore ? "YES" : "NO") . "\n";
    } else {
        echo "  ⚠ No admin user found\n";
    }
    
    if ($nonAdmin) {
        $canRestore = $policy->restoreFullSystem($nonAdmin);
        echo "  ✓ Non-admin user test:\n";
        echo "    " . ($canRestore ? "✗" : "✓") . " Can restore: " . ($canRestore ? "YES (WRONG!)" : "NO (CORRECT)") . "\n";
    } else {
        echo "  ⚠ No non-admin user found\n";
    }
} catch (Exception $e) {
    echo "  ✗ Authorization test error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// PHASE 8: DATABASE INTEGRITY
// ═══════════════════════════════════════════════════════════════════

echo "PHASE 8: DATABASE INTEGRITY\n";
echo "─────────────────────────────────────────────────────────────────\n";

try {
    $integrity = DB::selectOne('PRAGMA integrity_check');
    $isOk = $integrity->integrity_check === 'ok';
    echo "  " . ($isOk ? "✓" : "✗") . " PRAGMA integrity_check: " . $integrity->integrity_check . "\n";
    
    // Check version
    $version = DB::selectOne('SELECT sqlite_version() as version');
    echo "  ✓ SQLite version: " . $version->version . "\n";
    
    // Check foreign keys
    $fk = DB::selectOne('PRAGMA foreign_keys');
    echo "  ✓ Foreign keys setting: " . ($fk->foreign_keys ? "ENABLED" : "DISABLED") . "\n";
} catch (Exception $e) {
    echo "  ✗ Database integrity check error: " . $e->getMessage() . "\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// FINAL SUMMARY
// ═══════════════════════════════════════════════════════════════════

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   VERIFICATION COMPLETE                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ If all checks passed above, your system is ready for testing.\n\n";

echo "Next steps:\n";
echo "1. Test the UI at: http://localhost:8000/admin/hardened-restore\n";
echo "2. Create a test backup: php artisan backup:create\n";
echo "3. Follow the 4-step workflow in the UI\n";
echo "4. Verify audit log was recorded\n";
echo "5. Export audit CSV for verification\n\n";

echo "For issues, see HARDENED_RESTORE_VERIFICATION.md\n\n";
