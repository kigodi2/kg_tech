#!/bin/bash

echo "============================================"
echo "Bulk Import Fix - Verification Script"
echo "============================================"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0

# Function to check
check() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} $1"
        ((FAILED++))
    fi
}

echo "1. Checking code changes..."

# Check hardcoded exam_year_id removed
grep -r "exam_year_id: 1" . --include="*.blade.php" --include="*.php" > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${GREEN}✓${NC} No hardcoded exam_year_id values found"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Found hardcoded exam_year_id values"
    ((FAILED++))
fi

# Check MIME validation changed
grep "zip_file.*required.*file'" app/Http/Controllers/BulkImportController.php > /dev/null 2>&1
check "MIME validation changed to lenient validation"

# Check extension validation added
grep "getClientOriginalExtension" app/Http/Controllers/BulkImportController.php > /dev/null 2>&1
check "Extension validation added"

# Check error handling improved
grep "try {" app/Http/Controllers/BulkImportController.php | wc -l | grep -E "[5-9]|[1-9][0-9]" > /dev/null
check "Multiple error handling blocks added"

echo ""
echo "2. Checking documentation..."

# Check if documentation files exist
[ -f "BULK_IMPORT_QUICKSTART.md" ] && echo -e "${GREEN}✓${NC} BULK_IMPORT_QUICKSTART.md created" && ((PASSED++)) || (echo -e "${RED}✗${NC} BULK_IMPORT_QUICKSTART.md missing" && ((FAILED++)))
[ -f "BULK_IMPORT_CONTROLLER_IMPROVEMENTS.md" ] && echo -e "${GREEN}✓${NC} BULK_IMPORT_CONTROLLER_IMPROVEMENTS.md created" && ((PASSED++)) || (echo -e "${RED}✗${NC} Missing" && ((FAILED++)))
[ -f "IMPORT_TROUBLESHOOTING_GUIDE.md" ] && echo -e "${GREEN}✓${NC} IMPORT_TROUBLESHOOTING_GUIDE.md created" && ((PASSED++)) || (echo -e "${RED}✗${NC} Missing" && ((FAILED++)))

echo ""
echo "3. Checking application state..."

# Check if cache was cleared (recent cache directory)
cache_time=$(find bootstrap/cache -type f -mmin -5 2>/dev/null | wc -l)
if [ "$cache_time" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Cache appears to be recently cleared"
    ((PASSED++))
else
    echo -e "${YELLOW}⚠${NC} Cache may not be recently cleared (optional)"
fi

echo ""
echo "4. Testing Preview Endpoint Validation..."

# Check file storage error handling
grep -A 5 "try {" app/Http/Controllers/BulkImportController.php | grep -A 3 "store('temp'" > /dev/null 2>&1
check "File storage error handling added"

# Check preview service error handling
grep -A 5 "preview(.*fullPath" app/Http/Controllers/BulkImportController.php | grep "try {" > /dev/null 2>&1
check "Preview service error handling added"

echo ""
echo "5. Testing Import Start Methods..."

# Check session validation separation
grep -c "if (!.*zipPath)" app/Http/Controllers/BulkImportController.php | grep -E "[2-9]" > /dev/null 2>&1
check "ZIP session validation checks separated"

# Check exam year extraction in frontend
grep "examYears.find.*year_label" resources/views/mark-entry/index.blade.php > /dev/null 2>&1
check "Exam year extraction implemented in frontend"

echo ""
echo "============================================"
echo "Summary"
echo "============================================"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! Ready for testing.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some checks failed. Please review.${NC}"
    exit 1
fi
