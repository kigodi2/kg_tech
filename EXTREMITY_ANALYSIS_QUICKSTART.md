# Candidate Extremity Analysis - Quick Start Guide

## Overview
The Candidate Extremity Analysis system detects cross-subject score anomalies that may indicate marking errors, cheating, or data entry mistakes. It uses statistical methods (Z-scores and percentage deviations) to flag candidates with suspicious patterns.

## Access the Dashboard
1. Log in as an administrator
2. Navigate to **Evaluations → ACSEE** in the sidebar
3. Look for **Candidate Performance Anomalies** section
4. Or go directly to `/admin/candidate-extremity`

## Running Your First Analysis

### Step 1: Open Analysis Modal
- Click **"Run Analysis"** button (top right of dashboard)

### Step 2: Select Exam Year & Type
- **Exam Year**: Select the year you want to analyze (e.g., 2026)
- **Exam Type**: Select the exam type (ACSEE is most common)

### Step 3: Click "Analyze"
- The system will analyze all candidates for that exam year/type
- Processing time: ~30 seconds to 2 minutes depending on candidate count
- You'll see a success message when complete

## Understanding Results

### Summary Cards (Top of Dashboard)
- **High Risk**: Candidates with 50%+ subjects as outliers or extreme deviations
- **Moderate Risk**: Candidates with 33-50% outliers or moderate anomalies
- **Low Risk**: Candidates with <33% outliers
- **Total Flagged**: All candidates with at least one outlier
- **Pending Review**: Unreviewed flagged candidates

### Risk Level Definitions

**High Risk** - Requires immediate investigation:
- 50% or more of subjects are statistical outliers
- 3+ different warning flags
- Extreme deviations (Z-score > 3.0)
- Suspiciously uniform scores across diverse subject combination

**Moderate Risk** - Should review:
- 33-50% of subjects are outliers
- 1-2 warning flags
- Significant deviation in one or two subjects

**Low Risk** - Monitor:
- Less than 33% subjects are outliers
- No specific flags
- Occasional subject variance

## Filtering & Exporting

### Filters Available
- **Exam Year**: View results for specific years
- **Risk Level**: Show only High/Moderate/Low
- **Status**: Pending Review vs All

### Export Data
- Click **"Export"** button to download CSV
- File includes all flagged candidates and their metrics

## Reviewing Candidates

### View Details
1. Click **"View"** button on any candidate row
2. See detailed analysis with:
   - Subject-by-subject breakdown
   - Deviation percentages
   - Z-scores (statistical measure)
   - Outlier type (HIGH or LOW score)

### Mark as Reviewed
After investigation, select one of:
1. **Mark for Investigation** - Escalate to admin/principal
2. **No Action Needed** - False positive or legitimate variance
3. **Data Corrected** - Marks have been fixed

Once submitted, you can add optional notes about your review decision.

## Statistical Methods Explained

### Z-Score (Standard Deviation Method)
- Measures how many standard deviations a score is from the candidate's own average
- **Threshold**: |Z| > 2.0 = potential outlier
- Example: If a candidate averages 60, but scores 82 in one subject, the Z-score would indicate statistical anomaly

### Deviation Percentage Method
- Percentage difference from candidate's own average
- **Threshold**: >20% = potential outlier
- Example: 60 point average → score of 48 or 72 = 20% deviation

### Risk Level Algorithm
Candidates flagged as "High Risk" if:
- 50% or more subjects are outliers, OR
- 3+ warning flags, OR
- Extreme deviation detected (Z-score > 3.0)

## Common Scenarios

### Scenario 1: Legitimate High Performer
**Pattern**: Strong in STEM subjects (Math: 95, Physics: 92, Chemistry: 88), weak in Language (English: 45)
- **Action**: No Action Needed
- **Note**: Strong STEM aptitude, language weakness is normal variation

### Scenario 2: Potential Cheating
**Pattern**: All subjects around 70-72 (very uniform), subjects normally have 10-20 point spread
- **Flag**: "Uniform Performance (Suspiciously Similar Scores)"
- **Action**: Mark for Investigation

### Scenario 3: Data Entry Error
**Pattern**: Candidate scored 15 in Biology (far below other subjects: 65-68 average)
- **Flag**: "Single Subject Outlier"
- **Action**: Review and correct if needed, mark as "Data Corrected"

## Best Practices

1. **Review High Risk First**: Start with candidates marked as "High Risk"
2. **Know Your Context**: Consider:
   - Subject difficulty (some subjects harder than others)
   - Student strengths/weaknesses
   - Time of exam (if tired, performance may vary)
3. **Use External Data**: Cross-reference with:
   - Teacher assessments
   - Previous exam results
   - Class performance
4. **Document Decisions**: Always add notes explaining your review decision
5. **Regular Analysis**: Run analysis multiple times:
   - After initial mark entry
   - After any mark corrections
   - Before results publication

## API Endpoints (For Developers)

### Run Analysis
```
POST /api/admin/candidate-extremity/analyze
{
    "exam_year_id": 1,
    "exam_type_id": 2
}
```

### Get Dashboard Data
```
GET /api/admin/candidate-extremity/dashboard?exam_year_id=1&risk_level=High&reviewed_only=
```

### View Candidate Details
```
GET /api/admin/candidate-extremity/{reportId}
```

### Mark as Reviewed
```
POST /api/admin/candidate-extremity/{reportId}/mark-reviewed
{
    "action": "no_action_needed|marked_for_investigation|data_corrected",
    "notes": "Optional review notes"
}
```

### Export CSV
```
GET /api/admin/candidate-extremity/export?exam_year_id=1&risk_level=High
```

## Troubleshooting

### No Candidates Found in Analysis
- Ensure candidates are registered for the exam year
- Check that marks have been entered for the exam year
- Verify exam year/type selection

### All Candidates Show as "Low Risk"
- This is normal if marks are legitimate
- Dataset may have legitimate variation
- Run analysis on a year with potential issues

### Analysis Takes Very Long
- Normal for 500+ candidates
- Check server resources
- Consider analyzing by district to split workload

## Data Integrity Notes

- Original marks are never modified by this system
- All reviews are logged with timestamp and user
- Soft deletes preserve analysis history
- Export feature does not modify any data

## Support & Questions

For questions or issues:
1. Check the detailed logs in `candidate_extremity_logs` table
2. Contact system administrator
3. Review specific candidate details before escalating
