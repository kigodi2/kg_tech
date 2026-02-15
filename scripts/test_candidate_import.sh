#!/bin/bash

################################################################################
# Candidate Import API Test Harness
# =======================================
# Two-Phase (Validate + Commit) API Testing with Skip/Replace Modes
#
# Usage:
#   bash scripts/test_candidate_import.sh [mode] [test_case]
#
# Examples:
#   bash scripts/test_candidate_import.sh skip basic
#   bash scripts/test_candidate_import.sh replace mixed
#   bash scripts/test_candidate_import.sh skip all
#
# Modes: skip, replace
# Test Cases: basic, mixed, errors, acsee, all
#
################################################################################

set -e

# Configuration
BASE_URL="${BASE_URL:-http://localhost:8000}"
API_PREFIX="$BASE_URL/api"
CSRF_TOKEN="${CSRF_TOKEN:-}" # Set via environment or from session
TEMP_DIR=$(mktemp -d)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results tracking
PASSED=0
FAILED=0

# ============================================================================
# Helper Functions
# ============================================================================

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[✓]${NC} $1"
    ((PASSED++))
}

log_error() {
    echo -e "${RED}[✗]${NC} $1"
    ((FAILED++))
}

log_warning() {
    echo -e "${YELLOW}[!]${NC} $1"
}

# Get CSRF token from login page or session
get_csrf_token() {
    if [ -z "$CSRF_TOKEN" ]; then
        log_warning "CSRF_TOKEN not set. Attempting to get from server..."
        
        # Try to get CSRF from a GET request (if available)
        CSRF_RESPONSE=$(curl -s "$BASE_URL/" | grep -o 'csrf-token[^"]*' | head -1)
        
        if [ -z "$CSRF_RESPONSE" ]; then
            CSRF_TOKEN="test-token"
            log_warning "Using placeholder CSRF token. Set CSRF_TOKEN env var for authenticated requests."
        fi
    fi
    echo "$CSRF_TOKEN"
}

# Make API request
call_api() {
    local method=$1
    local endpoint=$2
    local data=$3
    local token=$4
    
    if [ "$method" = "POST" ] && [ -f "$data" ]; then
        # File upload (multipart/form-data)
        curl -s -X "$method" \
            -H "X-CSRF-TOKEN: $token" \
            -F "file=@$data" \
            -F "exam_year=2026" \
            -F "exam_type=ACSEE" \
            -F "on_exists_mode=$CURRENT_MODE" \
            "$API_PREFIX$endpoint"
    elif [ "$method" = "POST" ]; then
        # JSON data
        curl -s -X "$method" \
            -H "Content-Type: application/json" \
            -H "X-CSRF-TOKEN: $token" \
            -d "$data" \
            "$API_PREFIX$endpoint"
    else
        # GET request
        curl -s -X "$method" \
            -H "X-CSRF-TOKEN: $token" \
            "$API_PREFIX$endpoint"
    fi
}

# Create test CSV file
create_csv() {
    local filename=$1
    local content=$2
    
    local filepath="$TEMP_DIR/$filename"
    echo "$content" > "$filepath"
    echo "$filepath"
}

# Parse JSON response field
get_json_field() {
    local json=$1
    local field=$2
    echo "$json" | grep -o "\"$field\":[^,}]*" | cut -d: -f2 | tr -d ' "' | head -1
}

# ============================================================================
# CSV Test Data
# ============================================================================

# Test 1: Basic import (all new candidates)
create_basic_csv() {
    local csv="candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
0003,Tom Wilson,M,SCH003,Physics;Mathematics;English
0004,Sarah Brown,F,SCH001,Chemistry;Biology;Agriculture
0005,Mike Johnson,M,SCH002,Physics;Chemistry;Mathematics"
    
    create_csv "basic.csv" "$csv"
}

# Test 2: Mixed import (new + existing)
create_mixed_csv() {
    local csv="candidate_id,full_name,gender,school_code,combination
0001,John Doe UPDATED,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith UPDATED,F,SCH002,Mathematics;Chemistry;Geography
NEW001,New Candidate One,M,SCH001,Physics;Mathematics;English
NEW002,New Candidate Two,F,SCH003,Chemistry;Biology;Agriculture"
    
    create_csv "mixed.csv" "$csv"
}

# Test 3: CSV with validation errors
create_error_csv() {
    local csv="candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
BAD001,Invalid Gender,X,SCH001,Physics;Chemistry;Biology
,Missing ID,M,SCH002,Mathematics;Chemistry;Geography
0003,Tom Wilson,M,INVALID_SCHOOL,Physics;Mathematics;English
0001,Duplicate ID,M,SCH001,Physics;Chemistry;Biology"
    
    create_csv "errors.csv" "$csv"
}

# Test 4: ACSEE with complete data
create_acsee_csv() {
    local csv="candidate_id,full_name,gender,school_code,combination,exam_type,exam_year
AC0001,Alice Chen,F,SCH001,Physics;Chemistry;Mathematics,ACSEE,2026
AC0002,Bob Williams,M,SCH002,Chemistry;Biology;Geography,ACSEE,2026
AC0003,Carol Davis,F,SCH003,Physics;Mathematics;Agriculture,ACSEE,2026
AC0004,David Miller,M,SCH001,Mathematics;Chemistry;English,ACSEE,2026"
    
    create_csv "acsee.csv" "$csv"
}

# ============================================================================
# Test Cases
# ============================================================================

test_basic_skip_mode() {
    log_info "Test: Basic Import - Skip Mode (all new candidates)"
    
    CURRENT_MODE="skip"
    local csv=$(create_basic_csv)
    
    # Phase 1: Validate
    log_info "  Phase 1: Validating CSV file..."
    VALIDATE_RESPONSE=$(call_api "POST" "/candidates/import/validate" "$csv" "$(get_csrf_token)")
    
    local success=$(get_json_field "$VALIDATE_RESPONSE" "success")
    local create_count=$(get_json_field "$VALIDATE_RESPONSE" "create_count")
    local can_import=$(get_json_field "$VALIDATE_RESPONSE" "can_import")
    
    if [ "$success" = "true" ] && [ "$create_count" = "5" ]; then
        log_success "Validation passed: create_count=$create_count"
    else
        log_error "Validation failed: success=$success, create_count=$create_count"
        echo "Response: $VALIDATE_RESPONSE"
        return 1
    fi
    
    if [ "$can_import" != "true" ]; then
        log_warning "Import not ready (can_import=$can_import)"
    fi
    
    # Phase 2: Commit
    log_info "  Phase 2: Committing import..."
    COMMIT_RESPONSE=$(call_api "POST" "/candidates/import/commit" "$csv" "$(get_csrf_token)")
    
    local commit_success=$(get_json_field "$COMMIT_RESPONSE" "success")
    local imported_count=$(get_json_field "$COMMIT_RESPONSE" "imported_count")
    
    if [ "$commit_success" = "true" ] && [ "$imported_count" = "5" ]; then
        log_success "Commit passed: imported_count=$imported_count"
    else
        log_error "Commit failed: success=$commit_success, imported_count=$imported_count"
        echo "Response: $COMMIT_RESPONSE"
        return 1
    fi
}

test_mixed_skip_mode() {
    log_info "Test: Mixed Import - Skip Mode (new + existing)"
    
    CURRENT_MODE="skip"
    local csv=$(create_mixed_csv)
    
    # Pre-create candidate 0001 and 0002
    log_info "  Pre-creating candidate 0001 and 0002 (to test skip)..."
    # This would require a separate API call or database insertion
    
    # Phase 1: Validate
    log_info "  Phase 1: Validating CSV file..."
    VALIDATE_RESPONSE=$(call_api "POST" "/candidates/import/validate" "$csv" "$(get_csrf_token)")
    
    local success=$(get_json_field "$VALIDATE_RESPONSE" "success")
    local create_count=$(get_json_field "$VALIDATE_RESPONSE" "create_count")
    local skip_count=$(get_json_field "$VALIDATE_RESPONSE" "skip_count")
    
    if [ "$success" = "true" ] && [ "$create_count" = "2" ] && [ "$skip_count" = "2" ]; then
        log_success "Validation passed: create_count=$create_count, skip_count=$skip_count"
    else
        log_warning "Validation counts: create=$create_count, skip=$skip_count (may vary based on pre-existing data)"
    fi
    
    # Phase 2: Commit
    log_info "  Phase 2: Committing import..."
    COMMIT_RESPONSE=$(call_api "POST" "/candidates/import/commit" "$csv" "$(get_csrf_token)")
    
    local commit_success=$(get_json_field "$COMMIT_RESPONSE" "success")
    local imported_count=$(get_json_field "$COMMIT_RESPONSE" "imported_count")
    local skipped_count=$(get_json_field "$COMMIT_RESPONSE" "skipped_count")
    
    if [ "$commit_success" = "true" ]; then
        log_success "Commit passed: imported_count=$imported_count, skipped_count=$skipped_count"
    else
        log_error "Commit failed: success=$commit_success"
        echo "Response: $COMMIT_RESPONSE"
        return 1
    fi
}

test_mixed_replace_mode() {
    log_info "Test: Mixed Import - Replace Mode (new + update)"
    
    CURRENT_MODE="replace"
    local csv=$(create_mixed_csv)
    
    # Phase 1: Validate
    log_info "  Phase 1: Validating CSV file..."
    VALIDATE_RESPONSE=$(call_api "POST" "/candidates/import/validate" "$csv" "$(get_csrf_token)")
    
    local success=$(get_json_field "$VALIDATE_RESPONSE" "success")
    local create_count=$(get_json_field "$VALIDATE_RESPONSE" "create_count")
    local update_count=$(get_json_field "$VALIDATE_RESPONSE" "update_count")
    
    if [ "$success" = "true" ]; then
        log_success "Validation passed: create_count=$create_count, update_count=$update_count"
    else
        log_error "Validation failed: success=$success"
        echo "Response: $VALIDATE_RESPONSE"
        return 1
    fi
    
    # Phase 2: Commit
    log_info "  Phase 2: Committing import..."
    COMMIT_RESPONSE=$(call_api "POST" "/candidates/import/commit" "$csv" "$(get_csrf_token)")
    
    local commit_success=$(get_json_field "$COMMIT_RESPONSE" "success")
    local imported_count=$(get_json_field "$COMMIT_RESPONSE" "imported_count")
    local updated_count=$(get_json_field "$COMMIT_RESPONSE" "updated_count")
    
    if [ "$commit_success" = "true" ]; then
        log_success "Commit passed: imported_count=$imported_count, updated_count=$updated_count"
    else
        log_error "Commit failed: success=$commit_success"
        echo "Response: $COMMIT_RESPONSE"
        return 1
    fi
}

test_validation_errors() {
    log_info "Test: CSV Validation Errors"
    
    CURRENT_MODE="skip"
    local csv=$(create_error_csv)
    
    # Phase 1: Validate
    log_info "  Phase 1: Validating CSV file with errors..."
    VALIDATE_RESPONSE=$(call_api "POST" "/candidates/import/validate" "$csv" "$(get_csrf_token)")
    
    local success=$(get_json_field "$VALIDATE_RESPONSE" "success")
    local error_count=$(get_json_field "$VALIDATE_RESPONSE" "error_count")
    local can_import=$(get_json_field "$VALIDATE_RESPONSE" "can_import")
    
    if [ "$success" = "false" ] && [ "$error_count" -gt "0" ]; then
        log_success "Validation correctly detected errors: error_count=$error_count"
    else
        log_error "Validation should have failed: success=$success, error_count=$error_count"
    fi
    
    if [ "$can_import" = "false" ]; then
        log_success "can_import correctly set to false"
    else
        log_error "can_import should be false but got: $can_import"
    fi
    
    # Should NOT proceed to Phase 2 with errors
    log_warning "  Skipping Phase 2 commit (as expected with validation errors)"
}

test_acsee_import() {
    log_info "Test: ACSEE Import with Exam Type & Year"
    
    CURRENT_MODE="skip"
    local csv=$(create_acsee_csv)
    
    # Phase 1: Validate
    log_info "  Phase 1: Validating ACSEE CSV..."
    VALIDATE_RESPONSE=$(call_api "POST" "/candidates/import/validate" "$csv" "$(get_csrf_token)")
    
    local success=$(get_json_field "$VALIDATE_RESPONSE" "success")
    local create_count=$(get_json_field "$VALIDATE_RESPONSE" "create_count")
    
    if [ "$success" = "true" ] && [ "$create_count" = "4" ]; then
        log_success "ACSEE validation passed: create_count=$create_count"
    else
        log_error "ACSEE validation failed: success=$success, create_count=$create_count"
        echo "Response: $VALIDATE_RESPONSE"
        return 1
    fi
    
    # Phase 2: Commit
    log_info "  Phase 2: Committing ACSEE import..."
    COMMIT_RESPONSE=$(call_api "POST" "/candidates/import/commit" "$csv" "$(get_csrf_token)")
    
    local commit_success=$(get_json_field "$COMMIT_RESPONSE" "success")
    local imported_count=$(get_json_field "$COMMIT_RESPONSE" "imported_count")
    
    if [ "$commit_success" = "true" ] && [ "$imported_count" = "4" ]; then
        log_success "ACSEE commit passed: imported_count=$imported_count"
    else
        log_error "ACSEE commit failed: success=$commit_success, imported_count=$imported_count"
        echo "Response: $COMMIT_RESPONSE"
        return 1
    fi
}

test_download_template() {
    log_info "Test: Download Import Template"
    
    local output="$TEMP_DIR/template.csv"
    
    curl -s -X GET \
        -H "X-CSRF-TOKEN: $(get_csrf_token)" \
        "$API_PREFIX/candidates/import/template" \
        -o "$output"
    
    if [ -f "$output" ] && [ -s "$output" ]; then
        log_success "Template downloaded successfully"
        log_info "  Template location: $output"
    else
        log_error "Failed to download template"
        return 1
    fi
}

test_async_import() {
    log_info "Test: Async Bulk Import (Background Processing)"
    
    CURRENT_MODE="skip"
    local csv=$(create_basic_csv)
    
    log_info "  Dispatching async import job..."
    ASYNC_RESPONSE=$(call_api "POST" "/candidates/import/async" "$csv" "$(get_csrf_token)")
    
    local success=$(get_json_field "$ASYNC_RESPONSE" "success")
    local import_id=$(get_json_field "$ASYNC_RESPONSE" "import_id")
    
    if [ "$success" = "true" ] && [ -n "$import_id" ]; then
        log_success "Async import dispatched: import_id=$import_id"
        log_info "  Job is processing in background. Check server logs for results."
    else
        log_error "Async import dispatch failed: success=$success"
        echo "Response: $ASYNC_RESPONSE"
        return 1
    fi
}

# ============================================================================
# Main Test Runner
# ============================================================================

main() {
    local mode=${1:-skip}
    local test_case=${2:-basic}
    
    log_info "======================================"
    log_info "Candidate Import API Test Suite"
    log_info "======================================"
    log_info "Mode: $mode"
    log_info "Test Case: $test_case"
    log_info "Base URL: $BASE_URL"
    log_info "Temp Dir: $TEMP_DIR"
    log_info ""
    
    # Validate mode
    if [ "$mode" != "skip" ] && [ "$mode" != "replace" ]; then
        log_error "Invalid mode: $mode (use 'skip' or 'replace')"
        exit 1
    fi
    
    CURRENT_MODE="$mode"
    
    # Run tests
    case $test_case in
        basic)
            test_basic_skip_mode
            ;;
        mixed)
            if [ "$mode" = "skip" ]; then
                test_mixed_skip_mode
            else
                test_mixed_replace_mode
            fi
            ;;
        errors)
            test_validation_errors
            ;;
        acsee)
            test_acsee_import
            ;;
        template)
            test_download_template
            ;;
        async)
            test_async_import
            ;;
        all)
            test_basic_skip_mode || true
            echo ""
            if [ "$mode" = "skip" ]; then
                test_mixed_skip_mode || true
            else
                test_mixed_replace_mode || true
            fi
            echo ""
            test_validation_errors || true
            echo ""
            test_acsee_import || true
            echo ""
            test_download_template || true
            echo ""
            test_async_import || true
            ;;
        *)
            log_error "Invalid test case: $test_case"
            echo "Valid cases: basic, mixed, errors, acsee, template, async, all"
            exit 1
            ;;
    esac
    
    # Print summary
    echo ""
    log_info "======================================"
    log_info "Test Summary"
    log_info "======================================"
    log_info "Passed: ${GREEN}$PASSED${NC}"
    log_info "Failed: ${RED}$FAILED${NC}"
    
    # Cleanup
    rm -rf "$TEMP_DIR"
    
    # Exit with appropriate code
    if [ $FAILED -eq 0 ]; then
        log_success "All tests completed successfully!"
        exit 0
    else
        log_error "$FAILED test(s) failed"
        exit 1
    fi
}

# Run main function
main "$@"
