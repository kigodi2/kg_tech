# Candidate Import API - curl Examples

Quick reference for testing the Candidate Import API with curl commands.

## Prerequisites

```bash
# Set environment variables
export BASE_URL="http://localhost:8000"
export CSRF_TOKEN="your-csrf-token-here"
```

## 1. Phase 1: Validate Import (Skip Mode)

### Command
```bash
curl -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "All rows valid",
  "total_rows": 5,
  "create_count": 5,
  "update_count": 0,
  "skip_count": 0,
  "error_count": 0,
  "errors": [],
  "rows": [
    {
      "row_number": 2,
      "candidate_id": "0001",
      "full_name": "John Doe",
      "status": "NEW",
      "messages": []
    }
  ],
  "summary": {},
  "can_import": true
}
```

### With Validation Errors (200 OK)
```json
{
  "success": false,
  "message": "1 row(s) have errors",
  "total_rows": 5,
  "create_count": 4,
  "update_count": 0,
  "skip_count": 0,
  "error_count": 1,
  "errors": [
    {
      "row_number": 3,
      "candidate_id": "BAD001",
      "full_name": "Invalid Candidate",
      "gender": "X",
      "school_code": "SCH001",
      "combination": "Physics,Chemistry",
      "exam_type": "ACSEE",
      "error_messages": ["Gender must be M or F"],
      "primary_error": "Gender must be M or F"
    }
  ],
  "can_import": false
}
```

---

## 2. Phase 1: Validate Import (Replace Mode)

### Command
```bash
curl -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=replace"
```

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "All rows valid",
  "total_rows": 5,
  "create_count": 3,
  "update_count": 2,
  "skip_count": 0,
  "error_count": 0,
  "errors": [],
  "rows": [
    {
      "row_number": 2,
      "candidate_id": "0001",
      "full_name": "John Doe",
      "status": "REPLACE",
      "messages": []
    },
    {
      "row_number": 3,
      "candidate_id": "NEW001",
      "full_name": "New Candidate",
      "status": "NEW",
      "messages": []
    }
  ],
  "summary": {},
  "can_import": true
}
```

---

## 3. Phase 2: Commit Import (Skip Mode)

### Command
```bash
# Must use SAME file and on_exists_mode as validation
curl -X POST "$BASE_URL/api/candidates/import/commit" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Import completed successfully",
  "imported_count": 5,
  "skipped_count": 0,
  "updated_count": 0,
  "errors": []
}
```

### Partial Success Response (200 OK)
```json
{
  "success": true,
  "message": "Import completed with 1 error",
  "imported_count": 4,
  "skipped_count": 0,
  "updated_count": 0,
  "errors": [
    {
      "row_number": 3,
      "candidate_id": "ERR001",
      "error_message": "School not found: INVALID"
    }
  ]
}
```

---

## 4. Phase 2: Commit Import (Replace Mode)

### Command
```bash
curl -X POST "$BASE_URL/api/candidates/import/commit" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=replace"
```

### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Import completed successfully",
  "imported_count": 3,
  "updated_count": 2,
  "skipped_count": 0,
  "errors": []
}
```

---

## 5. Download Import Template

### Command
```bash
curl -X GET "$BASE_URL/api/candidates/import/template" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -o candidates-template.csv
```

### Output
```csv
candidate_id,full_name,gender,combination,school_code,exam_type,exam_year
0001,John Doe,M,Physics;Chemistry;Biology,SCH001,ACSEE,2026
```

---

## 6. Download Error Report

### Command
```bash
curl -X POST "$BASE_URL/api/candidates/import/download-errors" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -d '{
    "errors": [
      {
        "row_number": 3,
        "candidate_id": "BAD001",
        "full_name": "Invalid Candidate",
        "gender": "X",
        "school_code": "SCH001",
        "combination": "Physics,Chemistry",
        "exam_type": "ACSEE",
        "error_messages": ["Gender must be M or F"]
      }
    ]
  }' \
  -o errors-report.csv
```

### Output CSV
```csv
row_number,candidate_id,full_name,gender,school_code,combination,exam_type,error_messages
3,BAD001,Invalid Candidate,X,SCH001,Physics;Chemistry,ACSEE,Gender must be M or F
```

---

## 7. Async Bulk Import

### Command (Large File)
```bash
curl -X POST "$BASE_URL/api/candidates/import/async" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@large-candidates.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip"
```

### Success Response (202 Accepted)
```json
{
  "success": true,
  "message": "Import job dispatched. Processing in background...",
  "file_path": "imports/cJx3K2p9QqR1m.csv",
  "import_id": "import_65d2c3e4f5a6b"
}
```

---

## Creating Test CSV Files

### Basic CSV (All New)
```bash
cat > candidates.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith,F,SCH002,Mathematics;Chemistry;Geography
0003,Tom Wilson,M,SCH003,Physics;Mathematics;English
0004,Sarah Brown,F,SCH001,Chemistry;Biology;Agriculture
0005,Mike Johnson,M,SCH002,Physics;Chemistry;Mathematics
EOF
```

### Mixed CSV (New + Existing Updates)
```bash
cat > mixed-candidates.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination
0001,John Doe UPDATED,M,SCH001,Physics;Chemistry;Biology
0002,Jane Smith UPDATED,F,SCH002,Mathematics;Chemistry;Geography
NEW001,New Candidate One,M,SCH001,Physics;Mathematics;English
NEW002,New Candidate Two,F,SCH003,Chemistry;Biology;Agriculture
EOF
```

### CSV with Errors
```bash
cat > error-candidates.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination
0001,John Doe,M,SCH001,Physics;Chemistry;Biology
BAD001,Invalid Gender,X,SCH001,Physics;Chemistry;Biology
,Missing ID,M,SCH002,Mathematics;Chemistry;Geography
0003,Tom Wilson,M,INVALID_SCHOOL,Physics;Mathematics;English
0001,Duplicate ID,M,SCH001,Physics;Chemistry;Biology
EOF
```

### ACSEE with Exam Year
```bash
cat > acsee-candidates.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year
AC0001,Alice Chen,F,SCH001,Physics;Chemistry;Mathematics,ACSEE,2026
AC0002,Bob Williams,M,SCH002,Chemistry;Biology;Geography,ACSEE,2026
AC0003,Carol Davis,F,SCH003,Physics;Mathematics;Agriculture,ACSEE,2026
EOF
```

---

## Complete Two-Phase Test Sequence

### 1. Create Test File
```bash
cat > test.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination
TEST001,Test Candidate,M,SCH001,Physics;Chemistry;Biology
TEST002,Another Test,F,SCH002,Mathematics;Chemistry;Geography
EOF
```

### 2. Run Validation
```bash
VALIDATE=$(curl -s -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@test.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip")

echo "$VALIDATE" | jq '.'

# Check if can_import is true
CAN_IMPORT=$(echo "$VALIDATE" | jq -r '.can_import')
if [ "$CAN_IMPORT" != "true" ]; then
    echo "Validation failed, skipping commit"
    exit 1
fi
```

### 3. Run Commit
```bash
COMMIT=$(curl -s -X POST "$BASE_URL/api/candidates/import/commit" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@test.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip")

echo "$COMMIT" | jq '.'

# Extract counts
IMPORTED=$(echo "$COMMIT" | jq -r '.imported_count')
echo "Successfully imported: $IMPORTED candidates"
```

---

## Error Scenarios

### File Validation Error
```bash
curl -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@empty.csv"
```

Response (400 Bad Request):
```json
{
  "success": false,
  "message": "CSV file is empty",
  "total_rows": 0,
  "create_count": 0,
  "update_count": 0,
  "skip_count": 0,
  "error_count": 0,
  "errors": [],
  "rows": [],
  "summary": [],
  "can_import": false
}
```

### Missing CSRF Token
```bash
curl -X POST "$BASE_URL/api/candidates/import/validate" \
  -F "file=@test.csv"
```

Response (419 Token Mismatch):
```json
{
  "message": "CSRF token mismatch.",
  ...
}
```

### File Upload Error
```bash
curl -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@nonexistent.csv"
```

Response (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "File validation failed",
  "errors": {
    "file": ["The file field is required."]
  }
}
```

---

## Advanced: Handling Responses with jq

### Pretty Print
```bash
RESPONSE=$(curl -s -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@test.csv" \
  -F "on_exists_mode=skip")

echo "$RESPONSE" | jq '.'
```

### Extract Specific Fields
```bash
echo "$RESPONSE" | jq '.create_count'
echo "$RESPONSE" | jq '.can_import'
echo "$RESPONSE" | jq '.error_count'
```

### Loop Through Errors
```bash
echo "$RESPONSE" | jq -r '.errors[] | "\(.row_number): \(.error_messages[])"'
```

### Extract Row Details
```bash
echo "$RESPONSE" | jq '.rows[] | select(.status == "ERROR")'
```

---

## Bash Script Template

```bash
#!/bin/bash

BASE_URL="http://localhost:8000"
CSRF_TOKEN="your-token"

# Validate
echo "Validating..."
VALIDATE=$(curl -s -X POST "$BASE_URL/api/candidates/import/validate" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=skip")

echo "$VALIDATE" | jq '.'
CAN_IMPORT=$(echo "$VALIDATE" | jq -r '.can_import')

if [ "$CAN_IMPORT" != "true" ]; then
  echo "❌ Validation failed"
  exit 1
fi

echo "✓ Validation passed"

# Commit
echo "Committing..."
COMMIT=$(curl -s -X POST "$BASE_URL/api/candidates/import/commit" \
  -H "X-CSRF-TOKEN: $CSRF_TOKEN" \
  -F "file=@candidates.csv" \
  -F "on_exists_mode=skip")

echo "$COMMIT" | jq '.'
SUCCESS=$(echo "$COMMIT" | jq -r '.success')

if [ "$SUCCESS" != "true" ]; then
  echo "❌ Commit failed"
  exit 1
fi

IMPORTED=$(echo "$COMMIT" | jq -r '.imported_count')
echo "✓ Successfully imported $IMPORTED candidates"
```

---

## Notes

1. **CSRF Token**: Replace `$CSRF_TOKEN` with actual token from session cookies
2. **File Path**: Use absolute or relative path to CSV file after `@`
3. **on_exists_mode**: Must match between validate and commit calls
4. **Exam Year**: Use 4-digit format (2026, 2025, etc.)
5. **Exam Type**: Must be PSLE, CSEE, or ACSEE
6. **Mode Options**: skip (default) or replace

---

## Common Issues

### "file field is required"
Ensure you're using `-F "file=@path/to/file.csv"`

### Timeout on large files
Use the async endpoint: `POST /api/candidates/import/async`

### "can_import: false" after validation
Check error details in response.errors array

### Mismatched modes
Validate with `on_exists_mode=skip` but commit with `on_exists_mode=replace` will cause issues
