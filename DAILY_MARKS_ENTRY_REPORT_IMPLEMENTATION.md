# Daily Marks Entry Report Implementation

## Overview
Implemented a **Daily Marks Entry Report** feature in the ACSEE Evaluations module under:
- **Menu Path**: Evaluations > ENTRY REPORT > REGIONAL LEVEL > SUBJECTS
- **URL**: `http://127.0.0.1:8000/evaluations/acsee` (navigation through sidebar)

## Features Implemented

### 1. Report Table Structure
The report displays marks entry tracking with the following columns:

| Column | Purpose |
|--------|---------|
| S/N | Serial number |
| SUBJECT | Subject name |
| EXPECTED SCRIPTS | Total expected scripts for the subject |
| MARKED DAY 1 (Count + %) | Scripts marked on Day 1 and percentage |
| MARKED DAY 2 (Count + %) | Scripts marked on Day 2 and percentage |
| MARKED DAY 3 (Count + %) | Scripts marked on Day 3 and percentage |
| MARKED DAY 4 (Count + %) | Scripts marked on Day 4 and percentage |
| MARKED DAY 5 (Count + %) | Scripts marked on Day 5 and percentage |
| REMAINDER (Count + %) | Scripts marked outside workdays and percentage |
| REMARKS | Status remarks (On Track, In Progress, etc.) |

### 2. Filtering System
Users can filter reports by:
- **Exam Year**: Select from all available exam years
- **Region**: Filter by specific region
- **Subject**: Filter by specific subject (or view all subjects)
- **Entry Date**: Filter by date of entry

All filters work together dynamically - changing any filter instantly updates the report.

### 3. Data Calculation Logic

#### Expected Scripts
- Calculated from total candidates registered for a subject in the selected region
- Shows the benchmark against which daily marking is measured

#### Daily Breakdown
- **Days 1-5**: Monday through Friday (workweek)
- **Remainder**: Saturday, Sunday, and holidays
- Calculated based on `created_at` timestamp of mark entries

#### Percentages
- Each day's percentage = (Count / Expected Scripts) × 100
- Rounded to 1 decimal place for readability

#### Remarks Generation
- **100%+**: "Marking Complete"
- **75-100%**: "On Track"
- **50-75%**: "In Progress"
- **1-50%**: "Slow Progress"
- **0%**: "Not Started"

### 4. Export Functionality
- **Export CSV**: Downloads report data in CSV format
  - Filename: `daily-marks-entry-report-YYYY-MM-DD.csv`
  - Properly formatted with headers and quoted values
  
- **Print**: Opens print dialog
  - Professional layout with styling
  - Includes report date
  - Optimized for A4 paper (landscape recommended)

## Files Modified

### 1. `/resources/views/evaluations/acsee.blade.php`
**Added**:
- HTML structure for the Daily Marks Entry Report page (lines 476-611)
- Filter section with 4 dropdown/input fields
- Table structure with proper header hierarchy and colspan
- Data binding with Alpine.js x-for loops
- JavaScript function `dailyMarksEntryReport()` (lines 755-888)

**Features**:
- Responsive table layout with proper borders and colors
- Color-coded header sections (Orange: S/N, Yellow: Days, Red: Remainder, Green: Remarks)
- Hover effects on table rows
- Loading data asynchronously from API

### 2. `/app/Http/Controllers/DailyMarksEntryReportController.php`
**New Controller** with methods:

#### `getReport(Request $request)`
- Main endpoint that handles filter parameters
- Queries SubjectMarks with necessary relationships
- Applies filters for exam_year, region, subject, and entry_date

#### `generateReport($marks, Request $request)`
- Processes raw mark data into report format
- Groups by subject
- Calculates daily breakdowns
- Generates remarks

#### `getExpectedScripts($subject, Request $request)`
- Counts total registered candidates for a subject in the region
- Accounts for exam year and region filters

#### `getDayOfWeek($entryDate)`
- Maps datetime to day of marking period
- Monday-Friday = Days 1-5
- Saturday-Sunday = Remainder

#### `generateRemarks($totalMarked, $expectedScripts)`
- Generates status remarks based on completion percentage

### 3. `/routes/api.php`
**Added**:
- Import statement for `DailyMarksEntryReportController`
- Route: `GET /api/daily-marks-entry-report`
- Middleware: `['auth', 'admin']` (restricted to authenticated admin users)

## API Endpoints

### GET `/api/daily-marks-entry-report`
**Query Parameters**:
```
- exam_year_id (optional): Integer, exam year ID
- region_id (optional): Integer, region ID
- subject_id (optional): Integer, subject ID
- entry_date (optional): String (YYYY-MM-DD), date of entry
```

**Response** (JSON Array):
```json
[
  {
    "subject_id": 1,
    "subject_name": "Mathematics",
    "expected_scripts": 150,
    "day1_count": 45,
    "day1_percentage": 30.0,
    "day2_count": 35,
    "day2_percentage": 23.3,
    "day3_count": 40,
    "day3_percentage": 26.7,
    "day4_count": 25,
    "day4_percentage": 16.7,
    "day5_count": 5,
    "day5_percentage": 3.3,
    "remainder_count": 0,
    "remainder_percentage": 0,
    "total_marked": 150,
    "remarks": "Marking Complete"
  }
]
```

## Database Relationships Used

### Models:
1. **SubjectMarks**
   - Foreign keys: candidate_id, exam_type_id, subject_id
   - Has timestamps (created_at tracks entry date)

2. **Candidate**
   - Relationships: school, registrations

3. **Subject**
   - Simple lookup for subject names

4. **School**
   - Relationship: region (for regional filtering)

5. **CandidateExamRegistration** (implicit)
   - Links candidates to subjects and exam years

## Design Considerations & Recommendations

### Current Implementation
✅ **Strengths**:
- Clean, organized table layout matching the provided image
- Real-time filtering with immediate updates
- Admin-level security with middleware
- Comprehensive data calculations
- Export and print capabilities
- Responsive design

### Potential Improvements (Optional Enhancements)

#### 1. **Historical Tracking**
Instead of just counting entries by day, consider:
- Storing a `marked_date` column specifically for marking completion
- Tracking cumulative progress over time
- Creating trend charts

**Implementation**:
```sql
ALTER TABLE subject_marks ADD COLUMN marked_date DATE;
ALTER TABLE subject_marks ADD COLUMN marked_time TIMESTAMP;
```

#### 2. **Regional Aggregation**
Currently shows subject-level only. Could add:
- Regional summary row (totals across all subjects)
- Comparison across multiple regions in same view
- Heat map showing which regions are ahead/behind

**Example View**:
```
Regional Summary | Day 1: 2,450 (45%) | Day 2: 1,890 (35%) | etc.
---
Mathematics     | Day 1: 450 (30%)   | Day 2: 350 (23%)   | etc.
English         | Day 1: 500 (50%)   | Day 2: 300 (30%)   | etc.
```

#### 3. **Target Benchmarks**
Add configurable daily targets:
- Expected marks/day based on marking rate
- Visual indicators (green/yellow/red) for target status
- Alerts if behind schedule

#### 4. **User Activity Logging**
Track WHO marked what on which day:
- Add `marked_by_user_id` to SubjectMarks
- Show marking officer in report
- Create accountability trail

#### 5. **Advanced Charts**
Beyond basic table:
- Line chart showing cumulative marking progress
- Bar chart comparing days
- Pie chart showing distribution

## Testing Checklist

- [ ] Navigate to Evaluations > ENTRY REPORT > REGIONAL LEVEL > SUBJECTS
- [ ] Page loads without errors
- [ ] All filter dropdowns populate correctly
- [ ] Change exam year filter - report updates
- [ ] Change region filter - report updates
- [ ] Change subject filter - report updates
- [ ] Set entry date filter - report updates
- [ ] Click "Export CSV" - file downloads
- [ ] CSV file opens correctly in Excel/Sheets
- [ ] Click "Print" - print dialog appears
- [ ] Table displays with proper formatting
- [ ] Percentages calculate correctly
- [ ] Remarks display appropriate status
- [ ] No data message appears when no results
- [ ] Mobile responsive layout works

## Deployment Steps

1. **Backup database** (recommended)
2. **Deploy files**:
   - `resources/views/evaluations/acsee.blade.php`
   - `app/Http/Controllers/DailyMarksEntryReportController.php`
   - `routes/api.php`

3. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```

4. **No migrations needed** (uses existing tables)

5. **Test in development** before production

## Performance Notes

- Query uses indexes on `exam_type_id`, `year`, `subject_id`, `created_at`
- No N+1 problems due to eager loading with `with()`
- Large datasets (10k+ marks) may benefit from pagination (future enhancement)

## Support Notes

- Report requires admin authentication
- Date format in filters follows browser locale (ISO 8601 in API)
- Empty expected_scripts handled (prevents division by zero)
- All calculations use decimal precision for accuracy
