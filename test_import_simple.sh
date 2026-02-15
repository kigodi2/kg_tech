#!/bin/bash

BASE_URL="http://localhost:8000"

echo "=== Candidate Import API - Simple Test ==="
echo ""
echo "Testing endpoint connectivity..."
echo ""

# Test 1: Check if server is up
echo "1. Server connectivity..."
curl -s "$BASE_URL" > /dev/null && echo "   ✓ Server is up" || echo "   ✗ Server not responding"

# Test 2: Check if template endpoint exists
echo ""
echo "2. Template endpoint..."
TEMPLATE=$(curl -s -X GET "$BASE_URL/api/candidates/import/template" 2>&1)
if [[ $TEMPLATE == *"candidate_id"* ]] || [[ $TEMPLATE == *"full_name"* ]]; then
    echo "   ✓ Template endpoint working"
    echo "   Sample: $(echo "$TEMPLATE" | head -1)"
else
    echo "   ! Template response: $TEMPLATE"
fi

# Test 3: Create a test CSV file
echo ""
echo "3. Creating test CSV..."
cat > /tmp/test_candidates.csv << 'CSV'
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
CSV
echo "   ✓ Test CSV created at /tmp/test_candidates.csv"

# Test 4: Test validation endpoint (without CSRF - check error)
echo ""
echo "4. Testing validation endpoint..."
RESPONSE=$(curl -s -X POST "$BASE_URL/api/candidates/import/validate" \
  -F "file=@/tmp/test_candidates.csv" \
  -F "on_exists_mode=skip" 2>&1)

if [[ $RESPONSE == *"CSRF"* ]]; then
    echo "   ! CSRF token required (expected)"
    echo "   To test with authentication:"
    echo "      1. Login to the application"
    echo "      2. Get CSRF token from page source or cookie"
    echo "      3. Pass -H 'X-CSRF-TOKEN: your-token' with requests"
elif [[ $RESPONSE == *"success"* ]]; then
    echo "   ✓ API responding"
    echo "   Response: $(echo "$RESPONSE" | jq -r '.success' 2>/dev/null || echo $RESPONSE)"
elif [[ -z "$RESPONSE" ]]; then
    echo "   ! Empty response - check Laravel logs"
else
    echo "   ! Response: $RESPONSE"
fi

# Test 5: Check Laravel logs
echo ""
echo "5. Checking Laravel logs..."
if [ -f "/home/prosmart-technologies/SOL/irms/storage/logs/laravel.log" ]; then
    RECENT=$(tail -5 /home/prosmart-technologies/SOL/irms/storage/logs/laravel.log)
    if [[ $RECENT == *"error"* ]] || [[ $RECENT == *"Error"* ]]; then
        echo "   ! Errors in logs:"
        echo "$RECENT" | head -3
    else
        echo "   ✓ Logs available (last 5 lines):"
        echo "$RECENT" | tail -2
    fi
else
    echo "   ! Log file not found"
fi

echo ""
echo "=== Test Summary ==="
echo ""
echo "To properly test with CSRF authentication:"
echo ""
echo "1. Get CSRF token:"
echo "   CSRF=\$(curl -s $BASE_URL/login | grep -o 'csrf-token[^\"]*' | head -1)"
echo ""
echo "2. Test validation:"
echo "   curl -X POST $BASE_URL/api/candidates/import/validate \\"
echo "     -H \"X-CSRF-TOKEN: \$CSRF\" \\"
echo "     -F \"file=@/tmp/test_candidates.csv\" \\"
echo "     -F \"on_exists_mode=skip\""
echo ""
echo "3. Full test suite (after login):"
echo "   bash scripts/test_candidate_import.sh skip all"
echo ""
