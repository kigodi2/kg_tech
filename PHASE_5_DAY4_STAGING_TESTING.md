# Phase 5: Day 4 - Staging Validation Testing (Execution Log)

**Date**: 2026-02-19 (Thursday)  
**Status**: 📋 READY TO EXECUTE  
**Duration**: 8 hours (8 AM - 5 PM)  
**Team**: QA Lead, Technical Lead, Security Lead

---

## Testing Schedule

### Morning: Complete Workflow Testing (8 AM - 12 PM)

- [ ] Teacher workflow (8:00-9:00)
- [ ] HOD moderation (9:00-10:00)
- [ ] Admin submission (10:00-10:30)
- [ ] Verify audit trails (10:30-11:00)
- [ ] Test error scenarios (11:00-12:00)

### Afternoon: Edge Cases & Performance (1 PM - 5 PM)

- [ ] Concurrent operations (1:00-2:00)
- [ ] Large file handling (2:00-3:00)
- [ ] Error recovery (3:00-4:00)
- [ ] Security validation (4:00-4:30)
- [ ] Final sign-off (4:30-5:00)

---

## Test 1: Teacher Mark Upload Workflow (60 minutes)

### Test 1.1: Valid CSV Upload

**Objective**: Upload marks that pass validation

```bash
# Setup
URL: https://staging.irms.example.com
User: teacher1@example.com (seeded test account)
Password: password

Steps:
1. Login to system
2. Navigate to Mark Entry
3. Select context:
   - Exam Year: 2026
   - Region: IRINGA
   - District: IRINGA MUNICIPAL
   - School: KLERRUU SECONDARY
   - Subject: MATHEMATICS
   - Combination: CBE
4. Click "Download CSV Template"
5. Open template in Excel
6. Delete sample rows (2-4)
7. Enter 50 student records:
   - S1378-0001, STUDENT NAME 1, 75, 82, 88
   - S1378-0002, STUDENT NAME 2, 68, 71, 79
   - ... (48 more)
8. Save as CSV UTF-8
9. Upload to system
10. Wait for validation
11. Verify: "Status: VALIDATED, Errors: 0"
12. Verify: "Valid Records: 50"
13. Click "Submit to Moderation"
14. Verify: Status changed to "AWAITING_MODERATION"
```

**Expected Results**:
- ✅ CSV template downloads
- ✅ File opens in Excel
- ✅ Upload succeeds
- ✅ Validation passes
- ✅ Status: AWAITING_MODERATION

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 1.2: Invalid CSV Upload (Error Handling)

**Objective**: Upload marks that fail validation

```bash
Steps:
1. Create CSV with errors:
   - Row 1: Valid header
   - Row 2: S1378-0001, JOHN MUTUA, 150, 82, 88 (mark > 100)
   - Row 3: ,MARY JACKSON, 75, 80, 85 (missing index)
   - Row 4: S1378-0002, PETER KIPROTICH, 75, 80, 85 (valid)
   - Row 5: S1378-0002, DUPLICATE, 70, 75, 80 (duplicate index)
2. Upload file
3. Wait for validation
4. Verify: "Status: VALIDATION_FAILED"
5. Verify: "Errors: 3"
6. View error details:
   - Row 2: "Mark 150 exceeds maximum of 100"
   - Row 3: "Missing index number"
   - Row 5: "Duplicate index S1378-0002"
7. Click "Download Error Report"
8. Verify report contains detailed error information
```

**Expected Results**:
- ✅ Upload succeeds
- ✅ Validation fails
- ✅ Error count: 3
- ✅ Error details provided
- ✅ Error report downloadable
- ✅ User can fix and re-upload

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 1.3: CSV Format Validation

**Objective**: Test various CSV format issues

```bash
Test cases:
1. Wrong encoding
   - Save as ANSI instead of UTF-8
   - Result: Should detect or handle gracefully
   
2. Wrong delimiter
   - Use semicolon instead of comma
   - Result: Should fail validation with message
   
3. Extra columns
   - Add column G (extra data)
   - Result: Should handle or ignore
   
4. Merged cells
   - Merge cells A2:B2
   - Save as CSV (should unmerge)
   - Result: Should process or error appropriately
   
5. Blank rows
   - Add blank row after data
   - Result: Should skip or error
```

**Expected Results**:
- ✅ All format issues detected
- ✅ Clear error messages
- ✅ User can fix format
- ✅ No data corruption

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 2: HOD Moderation Workflow (60 minutes)

### Test 2.1: Batch Approval

**Objective**: HOD approves quality batch

```bash
Setup:
User: hod1@example.com (seeded HOD account)

Steps:
1. Login to staging system
2. Navigate to "My Moderation Queue"
3. See batch from Test 1.1 (AWAITING_MODERATION)
4. Click batch to view details
5. Review:
   - All 50 records displayed
   - Statistics shown (avg, min, max)
   - No validation errors
6. Spot-check 5 records randomly
7. Verify: Marks align with expectations
8. Click "Create Moderation Review"
9. Enter notes: "Data quality excellent. All records verified. Ready for submission."
10. Select decision: "APPROVE"
11. Click "Submit Review"
12. Verify: Status changed to "APPROVED"
13. Verify: HOD name shown as reviewer
14. Verify: Timestamp recorded
```

**Expected Results**:
- ✅ Batch list shows correctly
- ✅ Batch details display properly
- ✅ Statistics calculated
- ✅ Moderation form works
- ✅ Status changes to APPROVED
- ✅ Audit trail recorded

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 2.2: Batch Rejection with Feedback

**Objective**: HOD rejects batch with feedback

```bash
Steps:
1. Create new invalid batch (Test 1.2 scenario)
2. HOD reviews batch
3. Identifies issues:
   - 3 validation errors
   - 5 records with suspicious data
4. Click "Create Moderation Review"
5. Enter detailed feedback:
   "Batch rejected for following reasons:
   1. 3 records have validation errors (see error report)
   2. 5 students show marks significantly higher than previous year
      - Please verify these scores
   3. One student missing Paper 2 entirely
   
   Please correct these issues and resubmit.
   Contact me if you have questions."
6. Select decision: "REJECT"
7. Click "Submit Review"
8. Verify: Status changed to "REJECTED"
9. Verify: Teacher receives notification
10. Verify: Feedback visible in batch details
```

**Expected Results**:
- ✅ Rejection recorded
- ✅ Feedback captured
- ✅ Teacher notified
- ✅ Can resubmit

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 2.3: Request Changes

**Objective**: HOD requests clarification

```bash
Steps:
1. Create batch with minor issues
2. HOD reviews
3. Click "Create Moderation Review"
4. Enter: "Please verify the following:
   1. Student S1378-0045 scored 98 but usually averages 70 - please confirm this is correct
   2. Paper 1 scores show unusual clustering at 75 - please explain
   
   After receiving clarification, I can finalize approval."
5. Select decision: "REQUEST CHANGES"
6. Submit
7. Verify: Status shows "PENDING_CLARIFICATION"
8. Verify: Teacher can respond
9. Teacher provides explanation
10. HOD receives update notification
11. HOD reviews explanation
12. HOD approves or rejects
```

**Expected Results**:
- ✅ Request recorded
- ✅ Teacher notified
- ✅ Two-way communication works
- ✅ Final decision recorded

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 3: Admin Submission Workflow (30 minutes)

### Test 3.1: Submit Approved Batch to NECTA

**Objective**: Admin submits approved batch

```bash
Setup:
User: admin@example.com

Steps:
1. Login to staging system
2. Navigate to "Approved Batches"
3. See batch that was approved in Test 2.1
4. Click batch
5. View final summary:
   - School: KLERRUU SECONDARY
   - Subject: MATHEMATICS
   - Year: 2026
   - Records: 50
   - Status: APPROVED
   - Approver: HOD name
6. Click "Submit to NECTA"
7. Confirm submission
8. Wait for processing
9. Verify: Status changed to "SUBMITTED"
10. Verify: Timestamp recorded
11. Verify: Batch archived
12. Verify: Entry in submission log
```

**Expected Results**:
- ✅ Approved batches listed
- ✅ Submission succeeds
- ✅ Status updates to SUBMITTED
- ✅ Submission logged
- ✅ Batch archived

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 4: Audit Trail Verification (30 minutes)

### Complete Lifecycle Audit

**Objective**: Verify all transitions logged

```bash
Steps:
1. View batch that completed full lifecycle
2. Click "View Audit Trail"
3. Verify entries for:
   - Created: [timestamp], by teacher
   - Uploaded: [timestamp], [file info]
   - Validated: [timestamp], 50 records
   - Submitted to moderation: [timestamp], by teacher
   - Reviewed: [timestamp], by HOD
   - Approved: [timestamp], by HOD
   - Submitted to NECTA: [timestamp], by admin

4. Each entry should show:
   - Action
   - User performing action
   - Exact timestamp
   - Relevant details

5. Verify: Cannot be edited after creation

6. Export audit trail:
   - Click "Export"
   - Receive CSV with full history
   - Verify completeness
```

**Expected Results**:
- ✅ All transitions logged
- ✅ User context recorded
- ✅ Timestamps accurate
- ✅ Details preserved
- ✅ Audit trail exportable
- ✅ Immutable

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 5: Concurrent Operations (60 minutes)

### Test 5.1: Multiple Teachers Uploading

**Objective**: Verify system handles concurrent uploads

```bash
Setup: 
- 5 test teacher accounts
- 5 different batches (different schools/subjects)

Steps:
1. Have 5 different people/tabs login as different teachers
2. Each simultaneously:
   - Navigate to Mark Entry
   - Download template
   - Upload CSV (different file)
3. All 5 uploads in parallel
4. Monitor system:
   - No errors
   - All uploads complete
   - All validations run
   - No data corruption

Expected timing:
- All 5 uploads complete within 5 minutes
- No timeout errors
- Database handles load
```

**Expected Results**:
- ✅ All uploads succeed
- ✅ No conflicts
- ✅ No data loss
- ✅ No performance degradation

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 5.2: Multiple HODs Reviewing

**Objective**: Concurrent moderation

```bash
Setup:
- 5 pending batches (from different teachers)
- 5 HOD accounts (or HOD users)

Steps:
1. Each HOD:
   - Login
   - View moderation queue
   - Review different batch
   - Approve/reject simultaneously
2. Monitor:
   - No data corruption
   - Status updates correct
   - Notifications sent
   - Audit trails recorded

Expected timing:
- All reviews complete within 5 minutes
- No conflicts or errors
- Database handles load
```

**Expected Results**:
- ✅ All operations succeed
- ✅ No data corruption
- ✅ Status updates correct
- ✅ Notifications sent

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 6: Large File Handling (60 minutes)

### Test 6.1: Upload 5,000 Records

**Objective**: Test with production-scale data

```bash
Steps:
1. Create CSV with 5,000 student records
2. File size: ~200 KB
3. Upload to system
4. Monitor:
   - Upload progress
   - Validation duration (target: < 10 seconds)
   - System responsiveness
5. Verify: All records processed
6. Verify: No timeout errors
7. Verify: Status shows VALIDATED
```

**Expected Results**:
- ✅ Upload succeeds
- ✅ Validation completes
- ✅ All records processed
- ✅ Time < 10 seconds
- ✅ No errors

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 6.2: PDF Generation Load

**Objective**: Generate PDF from large batch

```bash
Steps:
1. From batch with 1,000+ records
2. Click "Generate Scoresheet PDF"
3. Monitor generation:
   - Start time
   - Progress indicator
   - CPU/memory usage
4. Wait for completion (target: < 30 seconds)
5. Download PDF
6. Verify:
   - File exists
   - File is valid PDF
   - All pages present
   - Data correct
```

**Expected Results**:
- ✅ PDF generation < 30 seconds
- ✅ PDF valid and complete
- ✅ Data accurate
- ✅ No memory issues

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 6.3: CSV Export Load

**Objective**: Export 50,000 records

```bash
Steps:
1. Prepare batch with 50,000 marks
2. Click "Export CSV"
3. Monitor:
   - Export start
   - Duration (target: < 60 seconds)
   - Memory usage
4. Download file
5. Verify:
   - File size appropriate
   - All records included
   - Format valid
   - Can open in Excel
```

**Expected Results**:
- ✅ Export < 60 seconds
- ✅ File complete
- ✅ Format valid
- ✅ Memory efficient

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 7: Error Recovery (60 minutes)

### Test 7.1: Database Connection Loss

**Objective**: System recovers from DB connection failure

```bash
Steps:
1. During active batch review:
   - Stop database connection
   - Attempt to save review
2. Expected:
   - Error message displayed
   - User data not lost
   - Clear error message
   - Can retry
3. Restart connection
4. Retry operation
5. Verify: Operation succeeds
```

**Expected Results**:
- ✅ Error handled gracefully
- ✅ Data not lost
- ✅ Clear error message
- ✅ Can recover
- ✅ No corruption

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

### Test 7.2: Network Timeout

**Objective**: Handle slow/dropped network

```bash
Steps:
1. During CSV upload:
   - Simulate slow network
   - Upload large file
2. Expected:
   - Upload continues despite slow connection
   - Progress indicator shows status
   - Can retry if fails
   - Timeout error if > 5 min with no progress
3. Test recovery:
   - Reconnect
   - Retry upload
```

**Expected Results**:
- ✅ Handles slow network
- ✅ Retryable
- ✅ No data loss
- ✅ Clear messaging

**Test Result**: [ ] PASS [ ] FAIL

**Notes**: _____________________________

---

## Test 8: Security Validation (30 minutes)

### Test 8.1: Authentication Required

**Objective**: Verify unauthenticated access blocked

```bash
Steps:
1. Try accessing /mark-entry without login
   - Expected: Redirected to login page
2. Try direct URL: /mark-entry/batch/1
   - Expected: Redirected to login page
3. Try with expired token
   - Expected: Logout, redirected to login
4. Try with invalid token
   - Expected: Redirected to login
```

**Expected Results**:
- ✅ No unauthenticated access
- ✅ Redirects to login
- ✅ Session management works

**Test Result**: [ ] PASS [ ] FAIL

---

### Test 8.2: Authorization Enforcement

**Objective**: Verify role-based access control

```bash
Steps:
1. As teacher, try to:
   - Access moderation queue (should fail)
   - View admin panel (should fail)
   - Edit other teacher's batch (should fail)
   
2. As HOD, try to:
   - Approve batch from different HOD's department (should fail)
   - Access admin panel (should fail)
   
3. As admin, verify:
   - Can access all areas
   - Can view all batches
   - Can perform all actions

Expected: Role-based restrictions work correctly
```

**Expected Results**:
- ✅ Role checks working
- ✅ Cross-role access blocked
- ✅ Correct roles have access

**Test Result**: [ ] PASS [ ] FAIL

---

### Test 8.3: Data Privacy

**Objective**: Verify data isolation

```bash
Steps:
1. Teacher A logs in
   - Can only see their own batches
   - Cannot see Teacher B's batches
2. HOD A logs in
   - Can see batches from their department
   - Cannot see other department batches
3. Verify:
   - Database queries filtered correctly
   - API returns only authorized data
   - No data leakage
```

**Expected Results**:
- ✅ Data properly isolated
- ✅ Only authorized data visible
- ✅ No data leakage

**Test Result**: [ ] PASS [ ] FAIL

---

## Test 9: Final Sign-Off Checklist

### Functionality

- [ ] Teacher upload workflow works
- [ ] CSV validation works correctly
- [ ] HOD moderation works
- [ ] Admin submission works
- [ ] PDF generation works (< 30s)
- [ ] CSV export works (< 60s)
- [ ] Audit trails complete
- [ ] Error handling graceful
- [ ] Concurrent operations safe

### Performance

- [ ] Page load times < 3 seconds
- [ ] CSV validation < 10 seconds
- [ ] PDF generation < 30 seconds
- [ ] CSV export < 60 seconds
- [ ] 50 concurrent users handled
- [ ] Database responsive
- [ ] No memory leaks
- [ ] No performance degradation

### Security

- [ ] Authentication enforced
- [ ] Authorization working
- [ ] Data isolation verified
- [ ] SQL injection prevented
- [ ] XSS protection enabled
- [ ] CSRF tokens working
- [ ] Audit logging complete

### Data Integrity

- [ ] All transitions logged
- [ ] User context recorded
- [ ] Timestamps accurate
- [ ] No data corruption
- [ ] Backups working
- [ ] Recovery procedures tested

### User Experience

- [ ] Error messages clear
- [ ] Navigation intuitive
- [ ] Forms easy to use
- [ ] Feedback provided
- [ ] Help available
- [ ] Training effective

---

## Issues Found During Testing

### Critical Issues (Must Fix Before Go-Live)

```
Issue 1: _______________________
Description: ___________________
Impact: CRITICAL
Status: [ ] Open [ ] Fixed [ ] Testing
Reopened: [ ] Yes [ ] No
```

### Major Issues (Should Fix Before Go-Live)

```
Issue 1: _______________________
Status: [ ] Open [ ] Fixed [ ] Testing
```

### Minor Issues (Can Fix After Go-Live)

```
Issue 1: _______________________
Status: [ ] Open [ ] Fixed [ ] Testing
```

---

## Day 4 Summary

**Start Time**: 8:00 AM  
**End Time**: _____ PM  
**Tests Completed**: _____ / 9  
**Tests Passed**: _____ / 9  
**Critical Issues**: _____  
**Major Issues**: _____  
**Minor Issues**: _____  

**Overall Status**: 
- [ ] Ready for go-live
- [ ] Ready with known issues
- [ ] Not ready (fix required)

---

## Sign-Off

**QA Lead**: _________________ Date: _______ Status: ________

**Technical Lead**: _________________ Date: _______ Status: ________

**Security Lead**: _________________ Date: _______ Status: ________

---

**Next**: [Day 5: Production Go-Live](PHASE_5_DAY5_PRODUCTION_GOLIVE.md)
