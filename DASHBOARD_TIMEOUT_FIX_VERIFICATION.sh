#!/bin/bash

echo "================================================================================"
echo "Dashboard Timeout Fix - Verification Script"
echo "================================================================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Check if BackupStatisticsService exists
echo -e "${YELLOW}[1/5] Checking BackupStatisticsService...${NC}"
if [ -f "app/Services/BackupStatisticsService.php" ]; then
    echo -e "${GREEN}✅ BackupStatisticsService found${NC}"
else
    echo -e "${RED}❌ BackupStatisticsService NOT found${NC}"
    exit 1
fi

# Step 2: Verify service has required methods
echo -e "${YELLOW}[2/5] Verifying BackupStatisticsService methods...${NC}"
if grep -q "public static function getTotalBackupSize" app/Services/BackupStatisticsService.php && \
   grep -q "public static function clearCache" app/Services/BackupStatisticsService.php && \
   grep -q "public static function formatBytes" app/Services/BackupStatisticsService.php; then
    echo -e "${GREEN}✅ All required methods present${NC}"
else
    echo -e "${RED}❌ Missing required methods${NC}"
    exit 1
fi

# Step 3: Check view update
echo -e "${YELLOW}[3/5] Checking manage-backups view update...${NC}"
if grep -q "BackupStatisticsService::getTotalBackupSize" resources/views/filament/admin/pages/manage-backups.blade.php; then
    echo -e "${GREEN}✅ View updated to use BackupStatisticsService${NC}"
else
    echo -e "${RED}❌ View not updated${NC}"
    exit 1
fi

# Step 4: Verify no glob calls remain
echo -e "${YELLOW}[4/5] Verifying glob() call removed...${NC}"
if ! grep -q 'glob(storage_path' resources/views/filament/admin/pages/manage-backups.blade.php; then
    echo -e "${GREEN}✅ glob() call removed from view${NC}"
else
    echo -e "${RED}❌ glob() call still present in view${NC}"
    exit 1
fi

# Step 5: Check controller cache clearing
echo -e "${YELLOW}[5/5] Checking controller cache clearing...${NC}"
if grep -q "BackupStatisticsService::clearCache" app/Http/Controllers/BackupManagementController.php; then
    echo -e "${GREEN}✅ Controller properly clears cache${NC}"
else
    echo -e "${RED}❌ Controller doesn't clear cache${NC}"
    exit 1
fi

echo ""
echo "================================================================================"
echo -e "${GREEN}✅ ALL VERIFICATIONS PASSED${NC}"
echo "================================================================================"
echo ""
echo "Next steps:"
echo "1. php artisan cache:clear"
echo "2. php artisan view:clear"
echo "3. php artisan optimize:clear"
echo "4. Test: curl http://localhost:8000/dashboard"
echo ""
