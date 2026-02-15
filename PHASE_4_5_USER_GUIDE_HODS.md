# Phase 4.5: User Guide for HODs - Moderation & Approval

**Document Type**: User Guide  
**Target Audience**: Heads of Department (HODs)  
**Phase**: 4.5 - Documentation & Training  
**Last Updated**: 2026-02-13  

---

## Table of Contents

1. [Overview](#overview)
2. [System Access](#system-access)
3. [Complete Workflow - Step by Step](#complete-workflow---step-by-step)
4. [Moderation Checklist](#moderation-checklist)
5. [Decision Guide](#decision-guide)
6. [Troubleshooting](#troubleshooting)
7. [FAQs](#faqs)
8. [Contact Support](#contact-support)

---

## Overview

As a Head of Department (HOD), you are responsible for:

✅ **Reviewing** mark batches submitted by your teachers  
✅ **Validating** data quality and completeness  
✅ **Approving** or **Rejecting** batches based on standards  
✅ **Providing feedback** for rejected batches  

This guide walks you through the complete moderation workflow.

### Key Points
- **Responsibility**: Ensure mark accuracy before final submission
- **Authority**: You can approve/reject/request changes
- **Timeline**: Review batches within 1-3 working days
- **Quality Gate**: Your approval is critical to data integrity
- **Support**: Contact your District/Region NECTA Officer if clarification needed

### Your Role in the Lifecycle

```
Teacher Upload (DRAFT)
        ↓
    Teacher Validates (VALIDATED)
        ↓
    Teacher Submits (AWAITING_MODERATION) ← YOUR WORK STARTS HERE
        ↓
    ┌─────────────────────────────────┐
    │  HOD MODERATION (YOU)           │
    │  • Review data quality          │
    │  • Check for anomalies          │
    │  • Verify completeness          │
    │  • Make approval decision       │
    └─────────────────────────────────┘
        ↓
    ┌──────────────┬────────────────┐
    │  APPROVE ✓   │   REJECT ✗     │
    │  (APPROVED)  │ (REJECTED)     │
    │      ↓       │      ↓         │
    │   Admin      │  Teacher       │
    │  submits to  │  resubmits     │
    │   NECTA      │               │
    └──────────────┴────────────────┘
```

---

## System Access

### Login
1. Open your web browser and navigate to the IRMS system URL
2. Enter your **username** (usually your employee ID or email)
3. Enter your **password**
4. Click **Login**

### After Login
- You'll see the **IRMS Dashboard**
- Look for the **"Mark Entry"** menu in the left sidebar
- Click **"Mark Entry"** to access your dashboard

### User Roles
The system identifies you as an **HOD** with the following permissions:
- ✅ Can view all mark batches from your department
- ✅ Can create moderation reviews
- ✅ Can approve or reject batches
- ✅ Can provide feedback/rejection reasons
- ✅ Can view audit trail and history
- ❌ Cannot upload marks (teacher responsibility)
- ❌ Cannot submit to NECTA (admin responsibility)

### Your Department Scope
- **Department**: All subjects/classes under your HOD authority
- **Visibility**: Only batches from your school/department
- **Access**: Read-only to sensitive data (no export)

---

## Complete Workflow - Step by Step

### PHASE 1: VIEW PENDING BATCHES

#### Step 1.1: Access Moderation Dashboard
1. Click **"Mark Entry"** in the left menu
2. Click **"My Moderation Queue"** or **"Pending Reviews"**
3. You see a list of batches awaiting your review

#### Step 1.2: Batch List Display
The list shows:
```
┌────────────────────────────────────────────────────────────┐
│ PENDING MODERATION BATCHES                                │
├────────────────────────────────────────────────────────────┤
│ Subject: MATHEMATICS  │ Year: 2026 │ Status: AWAITING_MOD │
│ Teacher: Mr. Mwaliko  │ School: KLERRUU SECONDARY          │
│ Submitted: 2026-02-12 14:30 │ Days Pending: 1             │
│ Records: 450          │ Valid: 450 │ Errors: 0            │
├────────────────────────────────────────────────────────────┤
│ Subject: ENGLISH      │ Year: 2026 │ Status: AWAITING_MOD │
│ Teacher: Ms. Mwamba   │ School: KLERRUU SECONDARY          │
│ Submitted: 2026-02-11 10:15 │ Days Pending: 2             │
│ Records: 380          │ Valid: 380 │ Errors: 0            │
└────────────────────────────────────────────────────────────┘
```

#### Step 1.3: Sort and Filter
- **By Date**: Oldest first (prioritize older submissions)
- **By Subject**: All subjects (or filter by subject)
- **By Teacher**: See specific teacher's submissions
- **By Status**: Only show "Awaiting Moderation"

#### Step 1.4: Click on a Batch
1. Click the batch you want to review
2. You enter the **Batch Detail View**

---

### PHASE 2: REVIEW BATCH DETAILS

#### Step 2.1: View Batch Header Information
```
┌─────────────────────────────────────────────────────────┐
│ BATCH DETAILS - MATHEMATICS CBE 2026                   │
├─────────────────────────────────────────────────────────┤
│ School:        KLERRUU SECONDARY SCHOOL                 │
│ Exam Year:     2026                                     │
│ Subject:       MATHEMATICS                              │
│ Combination:   CBE (Cluster-Based Examination)         │
│ Teacher:       Mr. John Mwaliko                         │
│ Submitted:     2026-02-12 14:30:45                      │
│ Status:        AWAITING MODERATION                      │
│ Total Records: 450                                      │
│ Valid Records: 450                                      │
│ Validation Errors: 0                                    │
│ Batch Code:    BATCH_2026_MATH_CBE_KLS_00001           │
└─────────────────────────────────────────────────────────┘
```

#### Step 2.2: View Mark Data Table
The system displays a table with all student marks:
```
┌──────────────┬────────────────────┬─────────┬─────────┬──────────┐
│ Index Number │ Candidate Name     │ Paper 1 │ Paper 2 │ Paper 3  │
├──────────────┼────────────────────┼─────────┼─────────┼──────────┤
│ S1378-0501   │ JOHN MUTUA         │ 85      │ 88      │ 92       │
│ S1378-0502   │ MARY JACKSON       │ 78      │ 82      │ 85       │
│ S1378-0503   │ PETER KIPROTICH    │ 92      │ 89      │ 94       │
│ ... (447 more rows)                                              │
└──────────────┴────────────────────┴─────────┴─────────┴──────────┘
```

#### Step 2.3: Review Statistics
The system auto-calculates statistics for your review:
```
Statistics Summary:
─────────────────────────────────
Average Mark:        82.3 / 100
Highest Mark:        98 / 100
Lowest Mark:         45 / 100
Median Mark:         84 / 100
Standard Deviation:  8.2

Mark Distribution:
  90-100:  15 students (3%)
  80-89:   285 students (63%)
  70-79:   120 students (27%)
  60-69:   30 students (7%)
  Below 60: 0 students (0%)
```

#### Step 2.4: View Submission Comments (if any)
Teachers can add notes when submitting:
```
Teacher's Submission Notes:
─────────────────────────────────
"Marks submitted for 450 Form 4 Mathematics students.
All three papers were conducted as per exam schedule.
Practical marks not included as per school policy.
No amendments expected."
```

---

### PHASE 3: CONDUCT MODERATION REVIEW

#### Step 3.1: Verify Data Completeness
Review the **Moderation Checklist** below:

**1. Record Completeness**
- ✅ Are all expected students present?
- ✅ Do numbers match class roll?
- ✅ Any unexplained absences?

**2. Data Quality**
- ✅ All marks in valid range (0-100)?
- ✅ No unusual patterns or anomalies?
- ✅ Marks align with school standards?

**3. Teacher Accuracy**
- ✅ Are marks consistently entered?
- ✅ Do marks reflect student ability?
- ✅ Any marks that seem outliers (too high/low)?

#### Step 3.2: Run Automated Quality Checks
The system automatically checks:
- ✅ No missing marks (all cells filled)
- ✅ No invalid marks (outside 0-100)
- ✅ No duplicate index numbers
- ✅ All index numbers properly formatted
- ✅ No encoding/character errors

If auto-checks pass, you see:
```
✓ Data Quality Checks: PASSED
  • No validation errors detected
  • All records properly formatted
  • 0 anomalies found
```

#### Step 3.3: Spot-Check Sample Records
Randomly verify a sample of records:
1. **High scores**: Do top 5 marks seem accurate for your school?
2. **Low scores**: Do bottom 5 marks have explanations (struggled students)?
3. **Mid-range**: Do most marks cluster around the average?
4. **Specific students**: Check any students you know personally

**Example Spot-Check Questions:**
- "Is S1378-0501 (John Mutua) capable of 85/100 in Math?" → YES ✓
- "Does the 98/100 mark for S1378-0523 seem reasonable?" → YES (top student) ✓
- "Why is S1378-0445 at 45/100?" → Check - this student usually scores 70+

#### Step 3.4: Check for Data Anomalies
The system highlights potential issues:
- ⚠️ **Unusually high concentration** (e.g., 400 students with exactly 75)
- ⚠️ **Sudden drops** (e.g., one student 98, others 45-60)
- ⚠️ **Suspicious patterns** (e.g., all even numbers, all multiples of 5)
- ⚠️ **Missing papers** (e.g., Paper 2 all blank)

If anomalies detected:
- Review why they exist
- Ask teacher for explanation (in rejection feedback)
- Decide: APPROVE with note, or REJECT for clarification

---

### PHASE 4: MAKE APPROVAL DECISION

#### Step 4.1: Decision Points

You have **THREE options**:

**OPTION 1: APPROVE ✓**
- Data quality is good
- All validation checks passed
- Marks seem accurate
- No anomalies or concerns
- Proceed to final submission

**OPTION 2: REJECT ✗**
- Data quality issues found
- Anomalies or suspicious patterns
- Marks don't align with expectations
- Teacher needs to resubmit with corrections

**OPTION 3: REQUEST CHANGES**
- Minor issues that need clarification
- Ask teacher to provide explanation
- May approve after explanation received

#### Step 4.2: Document Your Review

Click the **"Create Moderation Review"** button

You see a form:
```
┌─────────────────────────────────────────────┐
│ MODERATION REVIEW FORM                      │
├─────────────────────────────────────────────┤
│ Batch:      MATH CBE 2026 (450 records)    │
│ Reviewer:   [Your Name] (HOD, Mathematics) │
│ Date:       2026-02-13 (auto-filled)       │
│ Time:       10:45 AM (auto-filled)         │
│                                             │
│ MODERATION NOTES:                          │
│ ┌─────────────────────────────────────────┐│
│ │ Type your review notes here...          ││
│ │                                         ││
│ │ Examples:                               ││
│ │ - All marks verified and accurate       ││
│ │ - Data quality excellent                ││
│ │ - No concerns identified                ││
│ │                                         ││
│ │ For rejections:                         ││
│ │ - 15 records show data quality issues   ││
│ │ - Paper 2 marks look suspicious         ││
│ │ - Please resubmit with corrections      ││
│ └─────────────────────────────────────────┘│
│                                             │
│ DECISION:                                   │
│ ○ APPROVE                                   │
│ ○ REJECT                                    │
│ ○ REQUEST CHANGES                           │
│                                             │
│ [Create Review]  [Cancel]                  │
└─────────────────────────────────────────────┘
```

#### Step 4.3: Fill in Moderation Notes
Provide clear, constructive feedback:

**For APPROVAL:**
```
Moderation Notes:
"All 450 records reviewed and verified. Data quality 
excellent, no validation errors. Marks align with 
student performance and school standards. 
Ready for final submission."
```

**For REJECTION:**
```
Moderation Notes:
"Data quality issues identified:
- 3 records with marks exceeding 100 (corrected in system)
- Paper 2 shows unusual clustering (all 75-80)
- 12 students missing Paper 3 marks
- Please resubmit with corrections and explanations 
  for anomalies. Contact if unclear."
```

**For REQUEST CHANGES:**
```
Moderation Notes:
"Most records verified successfully. 
However, 5 students show marks significantly below 
their 2025 performance. Please verify and provide 
explanation or correction before final approval."
```

#### Step 4.4: Select Decision
1. Click the **decision radio button**:
   - ○ APPROVE
   - ○ REJECT
   - ○ REQUEST CHANGES

#### Step 4.5: Submit Review
1. Click **"Create Review"** button
2. The system:
   - Records your review
   - Updates batch status
   - Notifies the teacher
   - Logs timestamp and your signature

---

### PHASE 5: HANDLE DIFFERENT OUTCOMES

#### IF YOU CLICKED APPROVE:

**Batch Status Changes to: APPROVED**
```
Status: APPROVED ✓
Reviewer: [Your Name]
Review Date: 2026-02-13 10:45
Decision: Approved by HOD
Next Step: Awaiting Admin final submission
Teacher Notification: Automatic email sent
```

**What Happens Next:**
1. Teacher receives **approval notification** email
2. Batch moves to Admin queue for final submission
3. Admin will submit to NECTA
4. Teacher can view approved batch in their history

**Your Follow-up (Optional):**
- Monitor the batch until Admin submits
- If delays occur, follow up with Admin
- No further action needed

---

#### IF YOU CLICKED REJECT:

**Batch Status Changes to: REJECTED**
```
Status: REJECTED ✗
Reviewer: [Your Name]
Review Date: 2026-02-13 10:45
Decision: Rejected by HOD
Rejection Reason: See notes above
Next Step: Teacher must resubmit
Teacher Notification: Automatic email sent with feedback
```

**What Happens Next:**
1. Teacher receives **rejection notification** with your feedback
2. Teacher sees which records/issues need fixing
3. Teacher corrects the data and re-uploads
4. Batch goes back to validation step
5. You review the resubmission

**Your Follow-up:**
- Expect resubmission within 2-3 days
- Review resubmitted batch thoroughly
- Decide: Approve or reject again

---

#### IF YOU CLICKED REQUEST CHANGES:

**Status Changes to: PENDING_CLARIFICATION**
```
Status: Pending Clarification
Reviewer: [Your Name]
Review Date: 2026-02-13 10:45
Decision: Awaiting teacher response
Your Feedback: See notes above
Next Step: Teacher provides explanation
Teacher Notification: Automatic email sent with questions
```

**What Happens Next:**
1. Teacher sees your clarification request
2. Teacher provides explanations or corrections
3. System notifies you of teacher response
4. You review the explanation
5. You make final decision: Approve or Reject

**Your Follow-up:**
- Check for teacher response within 1 day
- Review explanation and marks again
- Make final approval/rejection decision

---

## Moderation Checklist

Use this checklist for every batch you review:

### Data Completeness
- ☐ All expected students included?
- ☐ Record count matches class roll?
- ☐ No unexplained absences?
- ☐ All required papers present?

### Data Quality
- ☐ All marks 0-100 range?
- ☐ No missing values/blanks?
- ☐ No duplicate records?
- ☐ Proper formatting throughout?

### Teacher Accuracy
- ☐ Marks reflect student ability?
- ☐ No suspicious patterns?
- ☐ Consistent scoring across papers?
- ☐ Matches school standards?

### System Validation
- ☐ Passed all automated checks?
- ☐ No encoding errors?
- ☐ No anomalies flagged?
- ☐ Submission properly formatted?

### Spot-Checks
- ☐ Checked 5-10 random records?
- ☐ Verified high scores are justified?
- ☐ Checked low scores for context?
- ☐ Spot-checked middle range?

### Final Decision
- ☐ Have clear reason for decision?
- ☐ Feedback is constructive?
- ☐ Decision documented?
- ☐ Notification sent?

---

## Decision Guide

### When to APPROVE

✅ **Approve if:**
- All automated validation checks passed
- Data quality is high (0 errors)
- Spot-checks reveal no anomalies
- Marks align with student performance
- Submission is complete and timely
- No suspicious patterns detected

**Approval Message Template:**
```
"Batch reviewed and approved. All validation checks 
passed. Data quality is excellent. No concerns identified. 
Ready for final submission to NECTA."
```

---

### When to REJECT

❌ **Reject if:**
- Multiple validation errors exist
- Data quality issues are significant
- Suspicious patterns detected (e.g., all same mark)
- Marks inconsistent with expectations
- Missing papers or data
- Teacher needs to provide corrections

**Rejection Message Template:**
```
"Batch rejected for the following reasons:
[Specific issues found]

Please correct these issues and resubmit:
1. [Issue 1 - what to fix]
2. [Issue 2 - what to fix]
3. [Issue 3 - what to fix]

Contact me if you have questions."
```

---

### When to Request Changes

🔄 **Request Changes if:**
- Minor anomalies need explanation
- A few records seem incorrect
- Teacher should verify specific marks
- Need clarification on methodology
- Unusual but possibly valid patterns

**Request Changes Message Template:**
```
"Batch review in progress. Most records verified 
successfully. However, I need clarification on:

1. [Specific observation] - Please explain
2. [Specific observation] - Please verify
3. [Specific observation] - Please confirm

After I receive your response, I can finalize approval."
```

---

## Common Moderation Scenarios

### Scenario 1: All High Marks (90+)
**Observation:** 400 out of 450 students scored 90+

**Possible Explanations:**
- ✅ School has excellent students (top-tier school)
- ✅ Mathematics is school's strength
- ✅ Teacher prepared students very well
- ❌ Teacher inflated marks
- ❌ Data entry error (all students got same high mark)

**Your Action:**
- Ask: "Are these marks realistic for your school's ability?"
- Compare with previous years' data
- Spot-check 10+ high-scoring students
- **Decision:** Approve if reasonable, Reject if inflated

---

### Scenario 2: All Low Marks (50 or below)
**Observation:** Most students scored below 60

**Possible Explanations:**
- ✅ Very difficult exam
- ✅ Weak class (low performing students)
- ❌ Errors in mark entry
- ❌ Marks from different year (mix-up)
- ❌ Data format issue

**Your Action:**
- Ask: "Why is performance so low this year?"
- Compare with previous years
- Verify exam difficulty with exam committee
- **Decision:** Approve if explainable, Reject if unclear

---

### Scenario 3: Suspicious Pattern (e.g., All Multiples of 5)
**Observation:** Every student mark ends in 5 or 0 (75, 80, 65, 85, etc.)

**Possible Explanations:**
- ❌ Teacher rounded marks (unprofessional)
- ❌ Teacher estimated rather than calculated
- ❌ Data entry template problem
- ❌ Teacher didn't enter actual marks

**Your Action:**
- **Request Changes:** "Please verify marks are actual values, not rounded"
- Ask for original exam papers/scripts
- **Decision:** Usually REJECT (require exact marks)

---

### Scenario 4: One Student Outlier (e.g., 98 when others 45-60)
**Observation:** S1378-0523 scored 98, but 20 other students scored 45-55

**Possible Explanations:**
- ✅ Student is genius/top performer (valid)
- ✅ Student studied much harder (valid)
- ❌ Data entry error for this student
- ❌ Marks mixed up with different exam

**Your Action:**
- Check: Do you know this student? Are they capable?
- Spot-check: Ask teacher about this student
- **Decision:** Approve if student is known to be excellent, or Reject for clarification

---

### Scenario 5: Missing Data (e.g., Paper 2 All Blank)
**Observation:** Paper 2 column is completely empty (no marks)

**Possible Explanations:**
- ❌ Paper 2 wasn't conducted
- ❌ Teacher forgot to enter Paper 2
- ❌ Data entry error
- ❌ CSV upload missed column

**Your Action:**
- Contact teacher immediately
- Ask: "Why is Paper 2 missing? Will you provide it?"
- **Decision:** REJECT (require all papers)

---

## Troubleshooting

### Q: I can't find the batch I'm looking for
**A:**
- Check the filter for "Status: Awaiting Moderation" only
- Use search by teacher name or subject
- Check date range - batch might be old
- Refresh the page

### Q: The marks table is slow to load
**A:**
- Large batches (400+ records) may take 10-15 seconds
- Don't close or refresh during loading
- Try again during off-peak hours
- Contact IT if persistent

### Q: I accidentally rejected a batch - can I undo it?
**A:**
- ❌ You cannot undo a rejection
- ✅ **Solution:** Approve the resubmission when teacher uploads corrected data
- Document your approval notes explaining the earlier issue

### Q: Teacher says they didn't receive my feedback
**A:**
- Check the batch's revision history
- Your feedback should show in "Moderation History"
- Email the teacher directly with feedback summary
- Ask IT to resend notification

### Q: I need to modify my review after submitting
**A:**
- ❌ You cannot modify a submitted review
- ✅ **Solution:** Create a new review on resubmission
- Document any additional notes in next review

### Q: How do I know if admin has submitted to NECTA?
**A:**
- Batch status changes to "SUBMITTED"
- You'll see "Final Submission Date: [date]"
- Check your notifications for admin submission confirmation

---

## FAQs

### How much time should I spend reviewing a batch?
**Typical timeline:**
- Read teacher notes: 2 minutes
- Review statistics and spot-check: 10-15 minutes
- Make decision: 2-3 minutes
- Write feedback: 3-5 minutes
- **Total:** 20-30 minutes per batch

### Can I approve a batch with "minor" errors?
**No.** All batches must pass quality standards:
- ❌ Don't approve if there are ANY validation errors
- ✅ All data must be complete and accurate
- ✅ If issues exist, request changes or reject

### Should I be strict or lenient?
**Be fair and consistent:**
- Have standard criteria (see Moderation Checklist)
- Apply same standards to all teachers
- Document your decisions
- Be constructive in feedback (not punitive)

### What if I disagree with the system's auto-checks?
**Use professional judgment:**
- Auto-checks are initial validation
- Your human review is the final authority
- If marks are valid despite flag, document reasoning
- Example: "Statistical outlier, but student is known high performer"

### Can teachers appeal my rejection?
**Yes, through proper channels:**
- Teacher can provide clarification
- District/Region officer can review
- But you remain the authority for your department

### How often should I check for new batches?
**Recommended:**
- Check daily during mark entry season
- Set aside time each morning/afternoon
- Aim to review within 1-2 days of submission
- Don't let backlog build up

### What if a batch contains errors I didn't catch?
**After approval, if errors found:**
- **Before final submission:** Request admin to hold and ask teacher for correction
- **After submission to NECTA:** Report to district NECTA officer for correction at their level
- Document what happened for future reference

### Can other HODs see my reviews?
**No, reviews are confidential:**
- Only visible to you, the teacher, and admin
- Not shared with other HODs
- Protected from student/parent access

### What should I do if I find exam cheating evidence in marks?
**Escalate immediately:**
1. Document the evidence clearly
2. Contact your District Education Officer
3. Follow proper disciplinary procedures
4. Don't block or approve - escalate instead

---

## Contact Support

### If You Need Help:

1. **System Technical Issues**: **IT Support**
   - Email: support@irms.education.tz
   - Hours: Monday-Friday, 8 AM - 5 PM
   - Include: Batch code, error message, screenshot

2. **Moderation Guidance**: **District/Region NECTA Officer**
   - They can advise on quality standards
   - Help with exam-specific questions
   - Guidance on controversial decisions

3. **Teacher/Data Issues**: **Your School Administration**
   - Discuss with the teacher directly
   - Escalate to school administration if disputes

### Information to Provide When Asking for Help:
- Batch code (e.g., BATCH_2026_MATH_CBE_KLS_00001)
- Subject and exam year
- Specific issue or error message
- Screenshots if applicable
- What you were trying to do

---

## Summary: Your Moderation Responsibilities

### Before Batch Reaches You
- ✅ Teacher uploads marks
- ✅ System validates format and ranges
- ✅ Teacher reviews and submits

### Your Review (When Batch Arrives)
1. **Check completeness** - All students? All papers?
2. **Verify quality** - Marks realistic? No patterns?
3. **Spot-check data** - Random samples OK? Known students accurate?
4. **Make decision** - Approve, Reject, or Request Changes
5. **Document feedback** - Clear, constructive notes

### After Your Decision
- **If Approved:** Batch goes to Admin for NECTA submission
- **If Rejected:** Teacher re-uploads with corrections
- **If Changes Requested:** Teacher provides clarification

### Your Standards
- All batches must be HIGH quality (zero tolerance for errors)
- All data must be complete and verified
- All decisions must be documented and defensible
- All feedback must be constructive and clear

---

**End of User Guide for HODs**

For questions or feedback on this guide, contact your District/Region NECTA Officer or System Administrator.

---

## Quick Reference Card (Print This)

**MODERATION CHECKLIST - QUICK VERSION**

```
┌─────────────────────────────────────────────────┐
│ HOD MODERATION QUICK CHECK                      │
├─────────────────────────────────────────────────┤
│ ☐ Completeness: All students present?          │
│ ☐ Quality: All validation checks passed?       │
│ ☐ Accuracy: Marks match student ability?       │
│ ☐ Patterns: Any suspicious or unusual marks?   │
│ ☐ Spot-check: 5-10 random records OK?          │
│                                                 │
│ DECISION:                                       │
│ ☐ APPROVE - All good, ready for NECTA          │
│ ☐ REJECT - Issues need fixing, teacher resubmit│
│ ☐ CLARIFY - Need explanation, teacher responds │
│                                                 │
│ Document your feedback and submit review       │
└─────────────────────────────────────────────────┘
```
