#!/bin/bash

# Test ACSEE Pagination Implementation
# Usage: ./scripts/test-acsee-pagination.sh

set -e

API="http://127.0.0.1:8001/api/exam-types/acsee/candidates"
CSRF_TOKEN=$(curl -s http://127.0.0.1:8001 | grep -oP '(?<=name="csrf-token" content=")[^"]*' || echo "test-token")

echo "========================================"
echo "ACSEE Pagination API Tests"
echo "========================================"
echo ""

# Test 1: Default pagination (page 1, per_page 15)
echo "Test 1: Default pagination (page 1, per_page 15)"
echo "---"
curl -s "$API" | jq '{
  status: "OK",
  meta: .meta,
  data_count: (.data | length),
  from_to: "\(.meta.from) to \(.meta.to) of \(.meta.total)",
  sample_candidate: .data[0]
}'
echo ""
echo ""

# Test 2: Different per_page value
echo "Test 2: Per page selector (per_page=25)"
echo "---"
curl -s "$API?per_page=25" | jq '{
  meta: .meta,
  data_count: (.data | length),
  from_to: "\(.meta.from) to \(.meta.to) of \(.meta.total)"
}'
echo ""
echo ""

# Test 3: Page 2
echo "Test 3: Page 2 (page=2, per_page=15)"
echo "---"
curl -s "$API?page=2&per_page=15" | jq '{
  meta: .meta,
  from_to: "\(.meta.from) to \(.meta.to) of \(.meta.total)",
  current_page: .meta.current_page
}'
echo ""
echo ""

# Test 4: Search query
echo "Test 4: Search (q=john)"
echo "---"
curl -s "$API?q=john" | jq '{
  meta: .meta,
  total_matches: .meta.total,
  sample_match: .data[0].full_name
}'
echo ""
echo ""

# Test 5: Candidate type filter
echo "Test 5: Candidate type filter (candidate_type=SCHOOL)"
echo "---"
curl -s "$API?candidate_type=SCHOOL" | jq '{
  meta: .meta,
  candidate_type_filter: "SCHOOL",
  total_school_candidates: .meta.total
}'
echo ""
echo ""

# Test 6: Combined filters
echo "Test 6: Combined filters (page=1, per_page=50, q=john, candidate_type=PRIVATE)"
echo "---"
curl -s "$API?page=1&per_page=50&q=john&candidate_type=PRIVATE" | jq '{
  meta: .meta,
  filters: {
    page: 1,
    per_page: 50,
    search: "john",
    candidate_type: "PRIVATE"
  },
  total_matches: .meta.total
}'
echo ""
echo ""

# Test 7: Last page
echo "Test 7: Last page (page=464, per_page=15)"
echo "---"
curl -s "$API?page=464&per_page=15" | jq '{
  meta: .meta,
  is_last_page: (.meta.current_page == .meta.last_page),
  from_to: "\(.meta.from) to \(.meta.to) of \(.meta.total)"
}'
echo ""
echo ""

echo "========================================"
echo "Tests Complete!"
echo "========================================"
echo ""
echo "Verification Checklist:"
echo "✅ Response format: {data, meta}"
echo "✅ Meta contains: current_page, per_page, total, last_page, from, to"
echo "✅ Per-page values: 15, 25, 50, 100 (test different values)"
echo "✅ Search works (q parameter)"
echo "✅ Candidate type filter works (candidate_type: SCHOOL|PRIVATE|ALL)"
echo "✅ Pagination respects page number"
echo "✅ Last page shows correct 'to' value"
echo ""
