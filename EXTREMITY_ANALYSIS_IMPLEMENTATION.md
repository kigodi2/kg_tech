# Candidate Extremity Analysis - Implementation Manual

## System Architecture

### Components Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    WEB INTERFACE                              │
├─────────────────────────────────────────────────────────────┤
│ Dashboard: /admin/candidate-extremity                         │
│ Detail View: /admin/candidate-extremity/{reportId}            │
│ Alpine.js driven with AJAX                                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│                  API ENDPOINTS                                │
├─────────────────────────────────────────────────────────────┤
│ POST   /api/admin/candidate-extremity/analyze                │
│ GET    /api/admin/candidate-extremity/dashboard              │
│ GET    /api/admin/candidate-extremity/{report}               │
│ POST   /api/admin/candidate-extremity/{report}/mark-reviewed  │
│ GET    /api/admin/candidate-extremity/export                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│              CONTROLLER LAYER                                 │
├─────────────────────────────────────────────────────────────┤
│ CandidateExtremityController                                 │
│ - analyze(): Triggers analysis service                       │
│ - dashboard(): Fetches flagged candidates                    │
│ - show(): Detailed candidate analysis                        │
│ - markReviewed(): Records admin decision                     │
│ - export(): CSV export functionality                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│              SERVICE LAYER                                    │
├─────────────────────────────────────────────────────────────┤
│ CandidateCrossSubjectAnalysisService                         │
│ - analyzeCandidates(): Main analysis orchestrator            │
│ - analyzeCandidateSubjects(): Per-candidate analysis         │
│ - calculateCandidateStats(): Statistical calculations       │
│ - detectSubjectOutliers(): Outlier detection logic           │
│ - createReport(): Report persistence                        │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│              MODEL LAYER                                      │
├─────────────────────────────────────────────────────────────┤
│ CandidateExtremityAnalysis (main reports)                   │
│ CandidateSubjectOutlier (subject-level details)             │
│ Relationships:                                                │
│ - belongsTo: Candidate, ExamYear, ExamType, User            │
│ - hasMany: CandidateSubjectOutlier                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────┴──────────────────────────────────────┐
│              DATABASE SCHEMA                                  │
├─────────────────────────────────────────────────────────────┤
│ candidate_extremity_analysis                                 │
│ candidate_subject_outliers                                   │
│ candidate_extremity_logs                                     │
└─────────────────────────────────────────────────────────────┘
```

## Database Schema

### candidate_extremity_analysis
Stores main analysis reports per candidate per exam year/type.

**Key Columns:**
- `id`: Primary key
- `candidate_id`: FK to candidates
- `exam_year_id`: FK to exam_years
- `exam_type_id`: FK to exam_types
- `combination`: Subject combination code (e.g., "PCM")
- `subject_count`: Number of subjects analyzed
- `average_score`: Mean score across subjects
- `median_score`: Median score
- `std_dev_across_subjects`: Standard deviation (measures score spread)
- `min_score`, `max_score`: Range
- `outlier_subject_count`: Number of flagged subjects
- `outlier_subjects`: JSON array of outlier metadata
- `risk_level`: "Low", "Moderate", "High"
- `flags`: JSON array of warning messages
- `reviewed`: Boolean (admin reviewed?)
- `reviewed_at`: Timestamp of review
- `reviewed_by`: FK to users
- `review_notes`: JSON with action and notes

**Indexes:**
- `candidate_id, exam_year_id` (frequent query)
- `exam_year_id, exam_type_id`
- `risk_level` (filtering)
- `reviewed` (pending reviews)

### candidate_subject_outliers
Stores detailed outlier information per subject per analysis.

**Key Columns:**
- `id`: Primary key
- `candidate_extremity_id`: FK to candidate_extremity_analysis
- `subject_id`: FK to subjects
- `score`: Actual score in this subject
- `candidate_average`: Candidate's average excluding this subject
- `deviation_from_average`: Absolute difference from average
- `deviation_percentage`: Percentage deviation from average
- `zscore`: Statistical Z-score
- `outlier_type`: "high" or "low"

### candidate_extremity_logs
Audit trail for analysis runs.

**Key Columns:**
- `exam_year_id`, `exam_type_id`: Analysis scope
- `candidates_analyzed`: Count of candidates analyzed
- `high_risk_count`, `moderate_risk_count`, `low_risk_count`: Risk distribution
- `total_outliers_detected`: Total outlier count
- `analysis_started_at`, `analysis_completed_at`: Timing
- `status`: "pending", "processing", "completed", "failed"
- `error_message`: If failed
- `triggered_by`: FK to users

## Statistical Algorithm

### 1. Data Preparation
For each candidate in the exam year:
- Fetch all subject selections
- Fetch all subject marks (from acsee_results or similar)
- Filter: Only candidates with 3+ subjects with marks

### 2. Calculate Candidate Statistics
```
mean = sum(scores) / count(scores)
median = middle value when sorted
variance = sum((score - mean)²) / count
std_dev = sqrt(variance)
```

### 3. Outlier Detection
For each subject, calculate:
```
deviation = score - mean
deviation_percent = (deviation / mean) * 100
zscore = deviation / std_dev  (if std_dev > 0)

isOutlier = |zscore| > 2.0 OR |deviation_percent| > 20
```

### 4. Risk Classification
```
outlier_percent = (outlier_count / subject_count) * 100

if outlier_percent >= 50 OR zscore > 3.0:
    risk_level = "High"
    add flag: "Multiple Subject Outliers" or "Extreme Subject Deviation"
    
else if outlier_percent >= 33:
    risk_level = "Moderate"
    add flag: "Several Subject Anomalies"
    
else if outlier_count > 0:
    risk_level = "Moderate"
    add flag: "Single Subject Outlier"
    
else:
    risk_level = "Low"

if std_dev < 5 AND outlier_count > 0:
    risk_level = "High"
    add flag: "Uniform Performance (Suspiciously Similar Scores)"
```

## Code Flow - Analyze Request

### 1. Request Flow
```
POST /api/admin/candidate-extremity/analyze
├─ CandidateExtremityController::analyze()
│  ├─ Validate: exam_year_id, exam_type_id exist
│  ├─ Create analysis log entry
│  └─ Dispatch service
│
└─ CandidateCrossSubjectAnalysisService::analyzeCandidates()
   ├─ Fetch candidates with registrations for year/type
   ├─ For each candidate:
   │  ├─ Fetch subject selections
   │  ├─ Fetch subject marks
   │  ├─ Calculate statistics
   │  ├─ Detect outliers
   │  ├─ Create report (CandidateExtremityAnalysis)
   │  └─ Create outlier records (CandidateSubjectOutlier)
   └─ Update analysis log with summary stats
```

### 2. Report Persistence
```
CandidateExtremityAnalysis::create([
    'candidate_id' => $id,
    'exam_year_id' => $year,
    'exam_type_id' => $type,
    'average_score' => $stats['average_score'],
    'std_dev_across_subjects' => $stats['std_dev'],
    'outlier_subject_count' => count($outliers),
    'risk_level' => $riskLevel,
    'flags' => json_encode($flags),
    'reviewed' => false,
])

foreach ($outliers as $outlier) {
    CandidateSubjectOutlier::create([
        'candidate_extremity_id' => $reportId,
        'subject_id' => $outlier['subject_id'],
        'score' => $outlier['score'],
        'deviation_percentage' => $outlier['dev_pct'],
        'zscore' => $outlier['zscore'],
        'outlier_type' => 'high'|'low',
    ])
}
```

## API Reference

### POST /api/admin/candidate-extremity/analyze

**Request:**
```json
{
    "exam_year_id": 1,
    "exam_type_id": 2
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Cross-subject analysis completed"
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "Analysis failed: [error details]"
}
```

### GET /api/admin/candidate-extremity/dashboard

**Query Parameters:**
```
exam_year_id=1    (optional)
risk_level=High   (optional: High, Moderate, Low)
reviewed_only=    (optional: empty=pending only, true=all)
```

**Response:**
```json
{
    "success": true,
    "summary": {
        "total_flagged": 145,
        "high_risk": 23,
        "moderate_risk": 67,
        "low_risk": 55,
        "pending_review": 89
    },
    "reports": {
        "data": [
            {
                "id": 1,
                "candidate": {
                    "candidate_id": "P001",
                    "full_name": "John Doe",
                    "school": { "name": "School A" }
                },
                "combination": "PCM",
                "average_score": "62.5",
                "std_dev_across_subjects": "8.2",
                "outlier_subject_count": 2,
                "flags": ["Multiple Subject Outliers"],
                "risk_level": "High",
                "reviewed": false
            }
        ],
        "pagination": { ... }
    }
}
```

### GET /api/admin/candidate-extremity/{reportId}

**Response:**
```json
{
    "success": true,
    "candidate": {
        "id": 1,
        "index_number": "P001",
        "name": "John Doe",
        "school": { "name": "School A" }
    },
    "analysis": {
        "combination": "PCM",
        "subjects_count": 3,
        "average_score": "62.5",
        "std_dev": "8.2",
        "outlier_subjects": [
            {
                "subject_id": 1,
                "subject_code": "MAT",
                "subject_name": "Mathematics",
                "score": "45.0",
                "candidate_average": "62.5",
                "deviation": "-17.5",
                "deviation_percentage": "-28.0",
                "zscore": "-2.13",
                "type": "low"
            }
        ],
        "flags": ["Multiple Subject Outliers"],
        "risk_level": "High"
    },
    "review": {
        "reviewed": false,
        "reviewed_at": null,
        "reviewed_by": null,
        "notes": null
    }
}
```

### POST /api/admin/candidate-extremity/{reportId}/mark-reviewed

**Request:**
```json
{
    "action": "marked_for_investigation",
    "notes": "Needs manual verification"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Analysis marked as reviewed"
}
```

### GET /api/admin/candidate-extremity/export

**Query Parameters:**
```
exam_year_id=1
risk_level=High
```

**Response:** CSV file (text/csv)
```
Candidate Index,Name,School,Combination,Subjects,Avg Score,Outlier Count,Risk Level,Flagged Subjects,Deviation %
"P001","John Doe","School A","PCM",3,62.5,2,High,"MAT (low);PHY (high)","−28.0%;+15.0%"
```

## Integration Points

### Database Requirements
- `candidates` table (with candidate_id, full_name)
- `exam_years` table
- `exam_types` table
- `subjects` table
- `acsee_results` or similar marks storage (checked in service)
- `candidate_subject_selections` table

### Model Relationships Expected
```php
Candidate::
    - hasMany('subjectSelections', 'exam_year_id')
    - hasOne('school')
    
ExamYear::
    - hasMany('candidateExtremityAnalysis')
    
ExamType::
    - hasMany('candidateExtremityAnalysis')
    
Subject::
    - hasMany('subjectOutliers')
```

### Required Middleware
- `auth`: User must be authenticated
- `admin`: User must have admin role

## Performance Considerations

### Analysis Performance
- **100 candidates**: ~5 seconds
- **500 candidates**: ~15-30 seconds
- **1000+ candidates**: 1-2 minutes

### Query Optimization
- Analysis uses eager loading: `with(['candidate.school', 'examYear', 'examType'])`
- Database indexes on `exam_year_id`, `exam_type_id`, `risk_level`
- Pagination: 50 records per page

### Scaling Tips
1. **Analyze by district**: Split large datasets
2. **Batch processing**: Consider background jobs for large analysis
3. **Archive old data**: Soft deletes preserve history, consider cleanup
4. **Database indexes**: Ensure indexes on FK and search columns

## Error Handling

### Common Errors

**404 - Report Not Found**
```
GET /api/admin/candidate-extremity/999
Response: 404 Not Found
```

**422 - Validation Error**
```
POST /api/admin/candidate-extremity/analyze (missing exam_year_id)
Response: {"message": "The exam year id field is required"}
```

**500 - Server Error**
```
Analysis fails if:
- Invalid candidate data structure
- Missing subject marks
- Database connection issues

Check logs: storage/logs/laravel.log
```

## Testing

### Unit Test Example
```php
test('extremity analysis detects outliers', function () {
    $candidate = Candidate::factory()->create();
    $examYear = ExamYear::factory()->create();
    $examType = ExamType::factory()->create();
    
    // Create subject selections and marks
    SubjectSelection::factory()
        ->for($candidate)
        ->for($examYear)
        ->for($examType)
        ->count(3)
        ->create()
        ->each(function ($selection) use ($examYear) {
            AcseeResult::factory()
                ->for($selection->subject)
                ->for($selection->candidate)
                ->for($examYear)
                ->marks(random_int(50, 90))
                ->create();
        });
    
    $service = new CandidateCrossSubjectAnalysisService();
    $service->analyzeCandidates($examYear, $examType);
    
    $analysis = CandidateExtremityAnalysis::where('candidate_id', $candidate->id)->first();
    
    expect($analysis)->not()->toBeNull();
    expect($analysis->risk_level)->toBeIn(['Low', 'Moderate', 'High']);
});
```

## Maintenance

### Regular Tasks
1. **Archive old analyses**: Delete analyses older than 1 year quarterly
2. **Review logs**: Check `candidate_extremity_logs` for failed analyses
3. **Performance monitoring**: Track analysis duration trends
4. **Data validation**: Periodically verify marks integrity

### Backup Strategy
- Extremity tables included in standard database backups
- Soft deletes preserve review history
- Analysis logs enable re-running analysis if needed

## Future Enhancements

1. **Subject-wise thresholds**: Different deviation thresholds by subject difficulty
2. **Historical comparison**: Compare current performance to previous exam years
3. **School normalization**: Account for school-wide performance trends
4. **Automated actions**: Auto-flag based on severity thresholds
5. **ML integration**: Use historical patterns to improve detection
6. **Batch API**: Analyze multiple years/types in single request
7. **Webhooks**: Notify stakeholders of high-risk detections

## Conclusion

The Extremity Analysis system provides robust statistical detection of anomalous candidate performance patterns. With proper configuration and regular review, it helps maintain data integrity and identify potential issues early in the results verification process.
