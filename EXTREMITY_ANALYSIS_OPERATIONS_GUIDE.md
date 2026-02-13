# Candidate Extremity Analysis - Operations Guide

## Executive Summary

The Candidate Extremity Analysis system is a statistical quality assurance tool designed to identify candidates with suspicious cross-subject score patterns. This guide provides operators (exam administrators, exam coordinators) with step-by-step instructions for daily operations.

## System Access

### Who Can Use?
- System administrators
- Exam coordinators
- Exam supervisors
- Data verification officers

**Required Role**: Admin access in system

### How to Access
1. Log in to IRMS
2. In sidebar, find **Evaluations** section
3. Click **ACSEE**
4. Look for **Candidate Performance Anomalies** card
5. Or navigate directly to: `https://irms.local/admin/candidate-extremity`

## Daily Operations Workflow

### Morning Routine (Start of Day)
1. **Check Pending Reviews**
   - Open dashboard
   - Set Status filter to "Pending Review"
   - Note count of candidates awaiting decision
   
2. **Review Analysis Status**
   - Check if latest analysis is complete
   - If not, monitor progress
   - If older than yesterday, consider re-running

### During Mark Entry
1. **After Mark Import**
   - If marks were updated, run new analysis
   - Click "Run Analysis" button
   - Select same exam year and type
   - Wait for completion (verify in logs)

2. **Monitor For Issues**
   - Check High Risk count
   - If >5% of candidates, investigate
   - May indicate data entry problems

### End of Day
1. **Complete Outstanding Reviews**
   - All High Risk flagged must be reviewed before results publication
   - Priority: 1) High Risk 2) Moderate Risk 3) Low Risk

2. **Document Findings**
   - Keep notes on systematic issues found
   - Report patterns to exam supervisors

## Step-by-Step: Running First Analysis

### Scenario: New exam year 2026, marks have been entered

#### Step 1: Navigate to Dashboard
```
Dashboard → Evaluations → ACSEE → 
Candidate Performance Anomalies (scroll down in sidebar)
```

#### Step 2: Open Analysis Modal
Click blue **"Run Analysis"** button (top right)

#### Step 3: Select Parameters
- **Exam Year**: 2026 (or your exam year)
- **Exam Type**: ACSEE (usually pre-selected)

#### Step 4: Confirm
Click green **"Analyze"** button

#### Step 5: Monitor Progress
- System shows loading indicator
- Processing time: 30 seconds to 2 minutes
- Check browser console (F12) for any errors
- You'll see success message when complete

#### Step 6: Review Results
Dashboard automatically refreshes showing:
- Summary cards updated with counts
- Table populated with flagged candidates
- Default: sorted by Risk Level (High first)

## Understanding the Dashboard

### Summary Cards (Top)

**High Risk** (Red)
- Number of candidates with suspicious patterns
- Requires investigation
- May indicate systematic problems

**Moderate Risk** (Yellow)
- Candidates with some anomalies
- Should be reviewed
- Often legitimate performance variation

**Low Risk** (Green)
- Candidates with minor variation
- Monitor only
- Usually no action needed

**Total Flagged** (Blue)
- All candidates with at least one outlier
- Total to investigate

**Pending Review** (Purple)
- Unreviewed flagged candidates
- Start here

### Candidate Table

| Column | What It Shows | Action if High |
|--------|---------------|----------------|
| Index | Candidate ID | Cross-check with enrollment |
| Name | Full name | Verify against official records |
| School | Candidate's school | Check if school data correct |
| Combo | Subject combination | Verify matches registration |
| Avg | Average score across subjects | Compare to school average |
| Std Dev | Score spread (variance) | >15 = very inconsistent |
| Outliers | Count of suspicious subjects | >1 = multiple problems |
| Flags | Red warning messages | Always investigate |
| Risk | Overall risk classification | High = urgent review |

## Step-by-Step: Reviewing a Candidate

### Scenario: Reviewing High Risk candidate

#### Step 1: Click "View"
On the candidate row, click **View** button

#### Step 2: Read Candidate Info
Top section shows:
- Index Number: Unique ID
- Name: Full name
- School: Candidate's school
- Combination: Subject code (e.g., PCM)

#### Step 3: Analyze Metrics
Four cards show analysis:
- **Avg Score**: Candidate's average across all subjects
- **Std Dev**: How much scores vary
  - Low (< 5): Suspiciously uniform
  - Normal (5-10): Expected
  - High (> 15): Very inconsistent

- **Outliers**: Number of subjects with unusual scores
- **Subjects**: Total subjects taken

#### Step 4: Review Outlier Table
Shows each suspicious subject with:
- **Subject Name**: Which subject is problematic
- **Score**: Actual score achieved
- **Avg**: Candidate's average
- **Deviation**: Points difference from average
- **Dev %**: Percentage difference
- **Z-Score**: Statistical measure
  - |Z| > 2.0 = significant outlier
  - |Z| > 3.0 = extreme outlier

- **Type**: HIGH (scored much better) or LOW (scored much worse)

#### Step 5: Make Decision
Scroll to "Review Analysis" section

Choose one action:
1. **Mark for Investigation**
   - Something looks wrong
   - Needs principal/supervisor review
   - Example: Student scored 45 in all subjects but 92 in one

2. **No Action Needed**
   - Legitimate performance pattern
   - Expected variation for this student
   - Example: Strong STEM student weak in languages

3. **Data Corrected**
   - Found and fixed the problem
   - Mark was wrong, now corrected
   - Example: Typo entry, fixed to correct score

#### Step 6: Add Optional Notes
In notes field, briefly explain decision:
- "Student's Math tutor confirms strong performance"
- "Entry error - corrected from 15 to 51"
- "Refer to principal - investigate further"

#### Step 7: Submit
Click **"Submit Review"** button
- Timestamp automatically recorded
- Your name automatically captured
- Can't change decision later (by design)

### Common Review Decisions

**Decision 1: "No Action Needed"**
When to choose:
- Subject pattern matches known student strength
- Variation within normal limits for class
- External context explains pattern
- Teacher confirms pattern is accurate

Example log:
> "Mathematics 95, Physics 88, Chemistry 85, Biology 42 is consistent with candidate's known strength in STEM and struggle with biology practicals. Teacher confirms."

**Decision 2: "Mark for Investigation"**
When to choose:
- Scores don't match known ability
- Pattern suspicious (all same, then one very different)
- School-wide issue indicated
- Needs principal decision

Example log:
> "Flagged for investigation. All subjects 70-72 (uniform), deviation threshold exceeded. Potential copying/external help."

**Decision 3: "Data Corrected"**
When to choose:
- Found data entry error
- Corrected in system
- Pattern now makes sense

Example log:
> "Found and corrected data entry error: Biology was entered as 15, should be 51. Candidate's scores now consistent with ability."

## Filtering & Exporting

### Filter by Risk Level
1. In "Risk Level" dropdown, select: High/Moderate/Low
2. Table updates immediately
3. Summary cards adjust to show filtered counts

### Filter by Exam Year
1. If multiple years present, select in "Exam Year" dropdown
2. Shows only that year's analysis
3. Useful for multi-year comparisons

### Filter by Review Status
1. "Pending Review" shows only unreviewed
2. "All" shows both reviewed and unreviewed
3. Switch to see what's done vs. remaining

### Export to CSV
1. Set filters as desired
2. Click **"Export"** button
3. Browser downloads file: `candidate_extremity_2026-02-11.csv`
4. Open in Excel/Sheets for reporting

#### CSV Columns
```
Candidate Index
Name
School
Combination
Subjects
Avg Score
Outlier Count
Risk Level
Flagged Subjects
Deviation %
```

Use for:
- Reports to supervisors
- School-by-school analysis
- Statistical summaries
- Archival records

## Troubleshooting

### Problem: "No candidates found"
**Causes:**
- No marks entered yet
- Analysis hasn't been run
- Wrong exam year selected

**Solution:**
1. Verify marks were imported
2. Try running analysis again
3. Check exam year matches where marks are

### Problem: "Analysis is taking too long"
**Causes:**
- Many candidates (500+)
- Server busy
- Network connectivity

**Solution:**
1. Wait 2-3 minutes
2. Close browser tab and retry
3. Contact system admin if persists

### Problem: "Can't see dashboard"
**Causes:**
- Not logged in
- Not admin user
- Browser cache issue

**Solution:**
1. Log out and back in
2. Clear browser cache (Ctrl+Shift+Delete)
3. Ask admin to verify your permissions

### Problem: "Review won't submit"
**Causes:**
- Required field empty (action not selected)
- Network disconnected
- Session expired

**Solution:**
1. Verify action is selected
2. Check internet connection
3. Log back in if needed
4. Try again

## Best Practices for Reviewers

### DO:
✅ Review High Risk first (most suspicious)
✅ Use context clues:
   - Student's previous performance
   - Teacher assessments
   - School data patterns
✅ Document decisions:
   - Why you made the decision
   - Any investigation results
   - Follow-up actions needed
✅ Escalate when uncertain:
   - Mark for Investigation if unsure
   - Let supervisor decide
✅ Report patterns:
   - If many students from one school flagged
   - If one subject always problematic
   - Unusual school-wide patterns

### DON'T:
❌ Make final decisions alone on "Data Corrected"
   - Coordinate with marks entry officer first
❌ Ignore High Risk candidates
   - These need investigation
❌ Be too quick to dismiss as "No Action Needed"
   - Be thorough
   - Consider multiple factors
❌ Rush through reviews
   - Take time for each candidate
   - Quality over speed
❌ Modify marks in review
   - Marks are modified elsewhere
   - This is for analysis only

## Reporting

### Daily Status Report
End of day, track:
- Candidates analyzed: ___
- High Risk found: ___
- Moderate Risk found: ___
- Reviewed today: ___
- Remaining to review: ___
- Issues found: ___

Template:
```
DATE: 2026-02-11
EXAM YEAR: 2026
EXAM TYPE: ACSEE

Analysis Run Time: 2:15 PM
Total Candidates: 2,456
Flagged for Review: 245 (10%)

Risk Distribution:
- High Risk: 23 (9.4% of flagged)
- Moderate Risk: 98 (40% of flagged)
- Low Risk: 124 (50.6% of flagged)

Reviews Completed: 45
Remaining: 200

Common Issues Found:
- School A: 8 candidates with uniform scores
- Subject: Biology - 12 outliers (possible marking issue)
- Pattern: Gender bias in Physics marks (investigate)

Actions Taken:
- Flagged School A patterns for principal review
- Recommend Biology scripts re-check
- Requested gender-balanced sample from School A

Reviewed By: John Doe
Time Spent: 3 hours
```

### Weekly Summary
1. Export all reviewed candidates
2. Calculate percentages
3. Identify patterns
4. Report to supervisors
5. Archive for audit trail

### Monthly Review
1. Analyze all analyses run
2. Identify systematic improvements
3. Update procedures if needed
4. Report statistics to management

## System Maintenance (For Admins)

### Daily
- Monitor analysis logs
- Check for failures
- Restart if needed

### Weekly
- Clear old data (optional)
- Verify backups working
- Check disk space

### Monthly
- Archive old analyses
- Performance review
- Update statistics

## FAQ

**Q: Can I change my review decision?**
A: No, reviews are immutable for audit purposes. Contact admin if errors found.

**Q: What if I mark something as "Data Corrected" but the data wasn't actually fixed?**
A: Contact system admin with details. They can investigate logs and marks history.

**Q: How many candidates should be High Risk?**
A: Normally 2-8% depending on rigor. >10% suggests systematic problem.

**Q: Can students see this analysis?**
A: No, this is internal quality assurance only.

**Q: How long are results kept?**
A: Indefinitely. Soft deletes preserve history for audit trail.

**Q: What if new marks are entered after analysis?**
A: Old analysis becomes outdated. Run new analysis to refresh.

**Q: Can I analyze multiple exam years at once?**
A: No, run analysis separately for each year/type combination.

## Contact & Support

| Question | Who | Contact |
|----------|-----|---------|
| Technical issues | System Admin | admin@irms.local |
| Data questions | Database Team | data@irms.local |
| Operational questions | Exam Coordinator | coordinator@irms.local |
| Escalations | Director | director@irms.local |

## Quick Reference Card

### Access
- Dashboard: `/admin/candidate-extremity`
- Analysis: Click "Run Analysis" button

### Risk Levels
- 🔴 **High**: Investigate immediately
- 🟡 **Moderate**: Should review
- 🟢 **Low**: Monitor

### Review Actions
- 🔍 **Investigation**: Needs attention
- ✅ **No Action**: Legitimate pattern
- 📝 **Corrected**: Data was fixed

### Export
- CSV: Click "Export" button
- Use for reports and archival

### Performance
- <100 candidates: <10 seconds
- 500 candidates: ~30 seconds
- 1000+ candidates: ~2 minutes

---

**Last Updated**: February 11, 2026
**Version**: 1.0
**Status**: Production
