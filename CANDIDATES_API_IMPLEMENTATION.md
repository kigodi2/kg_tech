# Candidates Management CRUD Implementation

## Summary
The Candidates Management page (`/registration/candidates`) has been fully implemented with CRUD operations, bulk operations, and complete API integration following the pattern established in the Schools Management page.

## API Endpoints

### Collection Operations
- **GET `/api/candidates`** - List all candidates with pagination, search, and filtering
  - Query Parameters:
    - `page` - Page number (default: 1)
    - `page_size` - Results per page (default: 10)
    - `search` - Search by name, email, or candidate ID
    - `school_id` - Filter by school
  - Response: 
    ```json
    {
      "data": [...],
      "pagination": {
        "total_count": 0,
        "total_pages": 0,
        "current_page": 1,
        "per_page": 10
      }
    }
    ```

### CRUD Operations
- **POST `/api/candidates`** - Create a new candidate
  - Body: 
    ```json
    {
      "school_id": 1,
      "candidate_id": "CAND-000001",  // Auto-generated if omitted
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "gender": "M" | "F",
      "date_of_birth": "2000-01-01",
      "exam_type": "KCSE",
      "status": "registered"
    }
    ```

- **PUT `/api/candidates/{id}`** - Update a candidate
  - Same body as POST (except candidate_id must be unique)

- **DELETE `/api/candidates/{id}`** - Delete a single candidate

### Bulk Operations
- **POST `/api/candidates/bulk-delete`** - Delete multiple candidates
  - Body: 
    ```json
    {
      "ids": [1, 2, 3]
    }
    ```

### Import/Export Operations
- **POST `/api/candidates/import`** - Import candidates from CSV
  - Expected CSV columns (in order):
    1. Candidate ID (auto-generated if blank)
    2. First Name
    3. Last Name
    4. Email
    5. School ID
    6. Exam Type

## Frontend Features Implemented

### Toolbar & Filters
- Region/School filter dropdown
- Search by name, email, or candidate ID
- Tools dropdown with CSV options
- Add Candidate button

### Table Display
- Multi-select checkboxes for bulk operations
- View, Edit, Delete buttons for individual candidates
- Status badge (registered/pending)
- Pagination info

### CRUD Modal
- **View Mode**: Read-only display of candidate details
- **Add Mode**: Create new candidate with auto-generated ID
- **Edit Mode**: Update candidate details

### Bulk Operations
- Select/deselect all functionality
- Bulk delete with confirmation
- Displays count of selected items

### CSV Tools
- **Download Template**: Download empty CSV template
- **Import CSV**: Upload CSV file to bulk import candidates
- **Export CSV**: Export filtered candidates to CSV

## Database Fields
- `id` - Primary key
- `school_id` - Foreign key to schools table
- `candidate_id` - Unique identifier (auto-generated format: CAND-XXXXXX)
- `first_name` - Candidate's first name
- `last_name` - Candidate's last name
- `email` - Candidate's email address
- `gender` - Gender (M or F)
- `date_of_birth` - Optional date of birth
- `exam_type` - Exam type (e.g., KCSE)
- `status` - Registration status
- `is_active` - Active/inactive flag
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## Validation Rules
- `school_id`: Required, must exist in schools table
- `candidate_id`: Optional, must be unique if provided (auto-generated if omitted)
- `first_name`: Required, max 255 characters
- `last_name`: Required, max 255 characters
- `email`: Required, valid email format, max 255 characters
- `gender`: Optional, must be M or F
- `date_of_birth`: Optional, must be valid date
- `exam_type`: Optional, max 255 characters
- `status`: Optional, max 255 characters

## Key Files Modified
1. `/routes/api.php` - Added all API endpoints
2. `/resources/views/registration/candidates.blade.php` - Updated frontend with CRUD UI
3. `/app/Models/Candidate.php` - Model with relationships and scopes

## Testing Instructions

### Create a Candidate
```bash
curl -X POST http://localhost:8000/api/candidates \
  -H "Content-Type: application/json" \
  -d '{
    "school_id": 1,
    "first_name": "Jane",
    "last_name": "Smith",
    "email": "jane@example.com",
    "gender": "F",
    "exam_type": "KCSE"
  }'
```

### List Candidates
```bash
curl "http://localhost:8000/api/candidates?page=1&page_size=10&search=jane&school_id=1"
```

### Update a Candidate
```bash
curl -X PUT http://localhost:8000/api/candidates/1 \
  -H "Content-Type: application/json" \
  -d '{
    "school_id": 1,
    "first_name": "Jane",
    "last_name": "Johnson",
    "email": "jane.j@example.com",
    "gender": "F",
    "exam_type": "KCSE"
  }'
```

### Delete a Candidate
```bash
curl -X DELETE http://localhost:8000/api/candidates/1
```

### Bulk Delete
```bash
curl -X POST http://localhost:8000/api/candidates/bulk-delete \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [1, 2, 3]
  }'
```

## Status: ✅ COMPLETE
All CRUD operations, bulk operations, and API integration are fully functional and tested.
