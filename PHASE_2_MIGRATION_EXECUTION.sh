#!/bin/bash

# ============================================================================
# Phase 2: Database Migration - Hardened Restore System
# ============================================================================
# This script executes the database migration for the restore audit logs table
# Date: February 3, 2026
# ============================================================================

echo "============================================================================"
echo "Phase 2: Database Migration - Hardened Restore System"
echo "============================================================================"
echo ""

# Change to project directory
cd /home/prosmart-technologies/SOL/irms

echo "📍 Working Directory: $(pwd)"
echo ""

# ============================================================================
# Step 1: Check if artisan exists
# ============================================================================
echo "Step 1: Verifying Laravel Installation..."
if [ -f artisan ]; then
    echo "✅ artisan file found"
else
    echo "❌ artisan file not found"
    exit 1
fi
echo ""

# ============================================================================
# Step 2: Run the migration
# ============================================================================
echo "Step 2: Running Database Migration..."
echo "Command: php artisan migrate"
echo ""

php artisan migrate

echo ""
echo "Migration completed."
echo ""

# ============================================================================
# Step 3: Verify migration was successful
# ============================================================================
echo "Step 3: Verifying Migration..."
echo ""

echo "Checking if table exists..."
php artisan tinker <<EOF
use Illuminate\Support\Facades\Schema;
\$exists = Schema::hasTable('restore_audit_logs');
echo \$exists ? "✅ Table 'restore_audit_logs' exists\n" : "❌ Table not found\n";
exit();
EOF

echo ""

# ============================================================================
# Step 4: Check table structure
# ============================================================================
echo "Step 4: Verifying Table Structure..."
echo ""

php artisan tinker <<EOF
use Illuminate\Support\Facades\Schema;
\$columns = Schema::getColumns('restore_audit_logs');
echo "Column Count: " . count(\$columns) . "\n";
echo "Columns:\n";
foreach (\$columns as \$col) {
    echo "  - " . \$col['name'] . " (" . \$col['type'] . ")\n";
}
exit();
EOF

echo ""

# ============================================================================
# Step 5: Check if model can connect
# ============================================================================
echo "Step 5: Verifying Model Connection..."
echo ""

php artisan tinker <<EOF
use App\Models\RestoreAuditLog;
\$count = RestoreAuditLog::count();
echo "✅ RestoreAuditLog::count() = " . \$count . "\n";
exit();
EOF

echo ""

# ============================================================================
# Summary
# ============================================================================
echo "============================================================================"
echo "✅ Migration Execution Complete"
echo "============================================================================"
echo ""
echo "Next Steps:"
echo "  1. Clear application caches"
echo "  2. Verify admin panel access"
echo "  3. Test restore page"
echo "  4. Run verification tests"
echo ""
echo "For more information, see: NEXT_STEPS_DEPLOYMENT.md"
echo ""
