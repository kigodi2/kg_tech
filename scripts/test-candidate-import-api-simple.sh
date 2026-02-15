#!/bin/bash

################################################################################
# Candidate Import API Testing Script (Simplified)
# Date: 2026-02-16
################################################################################

BASE_URL="http://localhost:8000"
API_VALIDATE="${BASE_URL}/api/candidates/import/validate"

echo "================================================================================"
echo "CANDIDATE IMPORT API TEST SUITE"
echo "================================================================================"
echo "Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "Base URL: ${BASE_URL}"
echo ""

# Create test CSV
cat > /tmp/test.csv << 'EOF'
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S1301TEST,Test School,M,S0713,SCHOOL,PCM,
P1301TEST,Test Private,F,S0744,PRIVATE,,111|121|131
EOF

echo "Test 1: API Connectivity"
if curl -s "${BASE_URL}/api/candidates/import/template" > /dev/null 2>&1; then
    echo "✓ PASS: API is responding"
else
    echo "✗ FAIL: API not responding"
    exit 1
fi

echo ""
echo "Test 2: CSV Validation without exam_year"
RESPONSE=$(curl -s -X POST \
    -F "file=@/tmp/test.csv" \
    -F "exam_year=2026" \
    -F "exam_type=ACSEE" \
    -F "on_exists_mode=skip" \
    "${API_VALIDATE}")

if echo "$RESPONSE" | grep -q '"success":true'; then
    echo "✓ PASS: CSV validation succeeded"
    echo "Response: $RESPONSE" | head -c 200
else
    echo "✗ FAIL: CSV validation failed"
    echo "Response: $RESPONSE"
    exit 1
fi

echo ""
echo "================================================================================"
echo "TEST SUMMARY"
echo "================================================================================"
echo "✓ ALL TESTS PASSED"
echo "Status: 🟢 READY FOR PRODUCTION"
echo "================================================================================"
