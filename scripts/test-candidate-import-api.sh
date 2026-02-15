#!/bin/bash

################################################################################
# Candidate Import API Testing Script (cURL)
# Date: 2026-02-16
# 
# Tests the candidate import API endpoints using cURL:
# - CSV validation without exam_year column
# - CSV validation with exam_year column
# - Import commit
# - Error handling
# - Database verification
#
# Usage: bash scripts/test-candidate-import-api.sh
################################################################################

set -e

# Configuration
BASE_URL="http://localhost:8000"
API_VALIDATE="${BASE_URL}/api/candidates/import/validate"
API_COMMIT="${BASE_URL}/api/candidates/import/commit"
EXAM_YEAR="2026"
EXAM_TYPE="ACSEE"
MODE="skip"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counters
TESTS_PASSED=0
TESTS_FAILED=0

# Utility functions
print_header() {
    echo ""
    echo "================================================================================"
    echo "$1"
    echo "================================================================================"
}

print_test() {
    echo -e "${BLUE}TEST: $1${NC}"
}

print_pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((TESTS_PASSED++))
}

print_fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((TESTS_FAILED++))
}

print_info() {
    echo -e "${YELLOW}ℹ INFO${NC}: $1"
}

# Create test CSV files
create_test_csv_basic() {
    cat > /tmp/test_candidates_basic.csv << 'EOF'
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S1101TEST,School Candidate 1,M,S0713,SCHOOL,PCM,
S1102TEST,School Candidate 2,F,S0713,SCHOOL,PCB,
P1101TEST,Private Candidate 1,M,S0744,PRIVATE,,111|121|131
P1102TEST,Private Candidate 2,F,S0744,PRIVATE,,111|131|141
EOF
    echo "/tmp/test_candidates_basic.csv"
}

create_test_csv_with_year() {
    cat > /tmp/test_candidates_with_year.csv << 'EOF'
candidate_id,full_name,gender,school_code,candidate_type,exam_year,combination,subjects
S1201TEST,With Year School,F,S0713,SCHOOL,2026,PCM,
P1201TEST,With Year Private,M,S0744,PRIVATE,2026,,111|121|141
EOF
    echo "/tmp/test_candidates_with_year.csv"
}

create_test_csv_bad_school() {
    cat > /tmp/test_bad_school.csv << 'EOF'
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
BADSCHOOL01,Bad School,M,ZZZZ,SCHOOL,PCM,
EOF
    echo "/tmp/test_bad_school.csv"
}

create_test_csv_bad_subjects() {
    cat > /tmp/test_bad_subjects.csv << 'EOF'
candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
P1301TEST,Bad Subjects,F,S0744,PRIVATE,,999|888|777
EOF
    echo "/tmp/test_bad_subjects.csv"
}

# Check API connectivity
test_api_connectivity() {
    print_test "API Connectivity"
    
    if curl -s "${BASE_URL}/api/candidates/import/template" > /dev/null 2>&1; then
        print_pass "API is responding"
    else
        print_fail "API is not responding at ${BASE_URL}"
        exit 1
    fi
}

# Test 1: Validate CSV without exam_year column
test_validate_without_exam_year() {
    print_test "CSV Validation WITHOUT exam_year Column"
    
    CSV_FILE=$(create_test_csv_basic)
    
    RESPONSE=$(curl -s -X POST \
        -F "file=@${CSV_FILE}" \
        -F "exam_year=${EXAM_YEAR}" \
        -F "exam_type=${EXAM_TYPE}" \
        -F "on_exists_mode=${MODE}" \
        "${API_VALIDATE}")
    
    # Check for success
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_pass "CSV without exam_year column accepted"
    else
        print_fail "CSV validation failed: $RESPONSE"
        return 1
    fi
    
    # Check row count
    if echo "$RESPONSE" | grep -q '"total_rows":4'; then
        print_pass "Correct row count (4 rows)"
    else
        print_fail "Incorrect row count: $RESPONSE"
    fi
    
    # Check error count
    if echo "$RESPONSE" | grep -q '"error_count":0'; then
        print_pass "No validation errors"
    else
        print_fail "Validation had errors: $RESPONSE"
    fi
    
    # Check can_import
    if echo "$RESPONSE" | grep -q '"can_import":true'; then
        print_pass "Can proceed to import"
    else
        print_fail "Cannot proceed to import: $RESPONSE"
    fi
}

# Test 2: Validate CSV with exam_year column
test_validate_with_exam_year() {
    print_test "CSV Validation WITH exam_year Column"
    
    CSV_FILE=$(create_test_csv_with_year)
    
    RESPONSE=$(curl -s -X POST \
        -F "file=@${CSV_FILE}" \
        -F "exam_year=${EXAM_YEAR}" \
        -F "exam_type=${EXAM_TYPE}" \
        -F "on_exists_mode=${MODE}" \
        "${API_VALIDATE}")
    
    if echo "$RESPONSE" | grep -q '"success":true'; then
        print_pass "CSV with exam_year column accepted"
    else
        print_fail "CSV with exam_year column rejected: $RESPONSE"
        return 1
    fi
    
    if echo "$RESPONSE" | grep -q '"error_count":0'; then
        print_pass "No validation errors with exam_year column"
    else
        print_fail "Validation had errors: $RESPONSE"
    fi
}

# Test 3: Error handling - Invalid school code
test_invalid_school_error() {
    print_test "Error Handling - Invalid School Code"
    
    CSV_FILE=$(create_test_csv_bad_school)
    
    RESPONSE=$(curl -s -X POST \
        -F "file=@${CSV_FILE}" \
        -F "exam_year=${EXAM_YEAR}" \
        -F "exam_type=${EXAM_TYPE}" \
        -F "on_exists_mode=${MODE}" \
        "${API_VALIDATE}")
    
    if echo "$RESPONSE" | grep -q '"success":false'; then
        print_pass "Invalid school code detected"
    else
        print_fail "Invalid school not detected: $RESPONSE"
        return 1
    fi
    
    if echo "$RESPONSE" | grep -q '"error_count":1'; then
        print_pass "Error count is 1"
    else
        print_fail "Incorrect error count: $RESPONSE"
    fi
    
    if echo "$RESPONSE" | grep -qi 'school'; then
        print_pass "Error message mentions school"
    else
        print_fail "Error message unclear: $RESPONSE"
    fi
}

# Test 4: Error handling - Invalid subject codes
test_invalid_subject_error() {
    print_test "Error Handling - Invalid Subject Codes"
    
    CSV_FILE=$(create_test_csv_bad_subjects)
    
    RESPONSE=$(curl -s -X POST \
        -F "file=@${CSV_FILE}" \
        -F "exam_year=${EXAM_YEAR}" \
        -F "exam_type=${EXAM_TYPE}" \
        -F "on_exists_mode=${MODE}" \
        "${API_VALIDATE}")
    
    if echo "$RESPONSE" | grep -q '"success":false'; then
        print_pass "Invalid subject codes detected"
    else
        print_fail "Invalid subjects not detected: $RESPONSE"
        return 1
    fi
    
    if echo "$RESPONSE" | grep -q '"error_count":1'; then
        print_pass "Error count is 1"
    else
        print_fail "Incorrect error count: $RESPONSE"
    fi
    
    if echo "$RESPONSE" | grep -qi 'subject'; then
        print_pass "Error message mentions subject"
    else
        print_fail "Error message unclear: $RESPONSE"
    fi
}

# Test 5: Response format validation
test_response_format() {
    print_test "API Response Format Validation"
    
    CSV_FILE=$(create_test_csv_basic)
    
    RESPONSE=$(curl -s -X POST \
        -F "file=@${CSV_FILE}" \
        -F "exam_year=${EXAM_YEAR}" \
        -F "exam_type=${EXAM_TYPE}" \
        -F "on_exists_mode=${MODE}" \
        "${API_VALIDATE}")
    
    # Check for required fields
    local required_fields=(
        '"success"'
        '"message"'
        '"total_rows"'
        '"error_count"'
        '"create_count"'
        '"can_import"'
    )
    
    for field in "${required_fields[@]}"; do
        if echo "$RESPONSE" | grep -q "$field"; then
            print_pass "Response contains $field"
        else
            print_fail "Response missing $field"
        fi
    done
}

# Test 6: Database connectivity
test_database_connectivity() {
    print_test "Database Connectivity"
    
    # Try to query exam years via Laravel
    if php -d error_reporting=0 -r "
    @require 'vendor/autoload.php';
    @\$app = require_once 'bootstrap/app.php';
    @\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    @\$count = DB::table('exam_years')->count();
    exit(\$count > 0 ? 0 : 1);
    " 2>/dev/null; then
        print_pass "Database is accessible"
    else
        print_fail "Cannot connect to database"
        return 1
    fi
}

# Test 7: Required data exists in database
test_database_data() {
    print_test "Required Database Data"
    
    # Check exam types
    if php -d error_reporting=0 -r "
    @require 'vendor/autoload.php';
    @\$app = require_once 'bootstrap/app.php';
    @\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    @\$acsee = DB::table('exam_types')->where('code', 'ACSEE')->count();
    exit(\$acsee > 0 ? 0 : 1);
    " 2>/dev/null; then
        print_pass "ACSEE exam type exists"
    else
        print_fail "ACSEE exam type not found"
    fi
    
    # Check exam years
    if php -d error_reporting=0 -r "
    @require 'vendor/autoload.php';
    @\$app = require_once 'bootstrap/app.php';
    @\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    @\$years = DB::table('exam_years')->where('year_label', 2026)->count();
    exit(\$years > 0 ? 0 : 1);
    " 2>/dev/null; then
        print_pass "2026 exam year exists"
    else
        print_fail "2026 exam year not found"
    fi
    
    # Check schools
    if php -d error_reporting=0 -r "
    @require 'vendor/autoload.php';
    @\$app = require_once 'bootstrap/app.php';
    @\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    @\$schools = DB::table('schools')->count();
    exit(\$schools > 0 ? 0 : 1);
    " 2>/dev/null; then
        print_pass "Schools exist in database"
    else
        print_fail "No schools found"
    fi
    
    # Check subjects
    if php -d error_reporting=0 -r "
    @require 'vendor/autoload.php';
    @\$app = require_once 'bootstrap/app.php';
    @\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    @\$subjects = DB::table('subjects')->count();
    exit(\$subjects > 0 ? 0 : 1);
    " 2>/dev/null; then
        print_pass "Subjects exist in database"
    else
        print_fail "No subjects found"
    fi
}

# Main execution
main() {
    print_header "CANDIDATE IMPORT API TEST SUITE"
    print_info "Date: $(date '+%Y-%m-%d %H:%M:%S')"
    print_info "Base URL: ${BASE_URL}"
    
    # Run tests
    test_api_connectivity
    test_database_connectivity
    test_database_data
    test_validate_without_exam_year
    test_validate_with_exam_year
    test_invalid_school_error
    test_invalid_subject_error
    test_response_format
    
    # Print summary
    print_header "TEST SUMMARY"
    echo "Total Tests: $((TESTS_PASSED + TESTS_FAILED))"
    echo -e "${GREEN}Passed: ${TESTS_PASSED}${NC}"
    echo -e "${RED}Failed: ${TESTS_FAILED}${NC}"
    
    if [ $TESTS_FAILED -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ ALL TESTS PASSED${NC}"
        echo "Status: 🟢 READY FOR PRODUCTION"
    else
        echo ""
        echo -e "${RED}✗ SOME TESTS FAILED${NC}"
        echo "Status: 🔴 NEEDS FIXES"
    fi
    
    print_header "END OF TEST REPORT"
    print_info "Date: $(date '+%Y-%m-%d %H:%M:%S')"
    
    # Exit with appropriate code
    exit $TESTS_FAILED
}

# Run main function
main
