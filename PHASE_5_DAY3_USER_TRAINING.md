# Phase 5: Day 3 - User Training (Execution Log)

**Date**: 2026-02-18 (Wednesday)  
**Status**: 📋 READY TO EXECUTE  
**Duration**: 6 hours (8 AM - 2 PM)  
**Team**: Training Lead, Support Team, Technical Team

---

## Training Schedule

### Session 1: Teacher Training (8:00 AM - 9:30 AM)

**Duration**: 90 minutes  
**Attendees**: 20-30 teachers (Batch 1)  
**Facilitator**: Training Lead + 1 Technical Support  
**Materials**: 
- Printed: PHASE_4_5_USER_GUIDE_TEACHERS.md
- Digital: Presentation slides
- Samples: Sample CSV files
- Access: Testing/staging environment

### Session 2: HOD Training (10:00 AM - 11:30 AM)

**Duration**: 90 minutes  
**Attendees**: 10-15 HODs  
**Facilitator**: Training Lead + 1 Technical Support  
**Materials**:
- Printed: PHASE_4_5_USER_GUIDE_HODS.md
- Digital: Presentation slides
- Samples: Sample batches to review
- Access: Testing/staging environment

### Session 3: Admin/Support Training (12:00 PM - 1:00 PM)

**Duration**: 60 minutes  
**Attendees**: 5-10 admins and support staff  
**Facilitator**: Technical Lead  
**Materials**:
- Admin operations guide
- Troubleshooting procedures
- Escalation flowchart
- Emergency contacts

### Session 4: Optional - Follow-up Q&A (1:00 PM - 2:00 PM)

**Duration**: 60 minutes  
**Attendees**: All participants (optional)  
**Facilitator**: Full team  

---

## Session 1: Teacher Training (90 minutes)

### Pre-Training Setup (30 minutes before)

**Checklist**:
- [ ] Room setup complete
- [ ] Projector/screens working
- [ ] Wifi access stable
- [ ] Computers/laptops ready
- [ ] Materials printed (40 copies of guide)
- [ ] Sample CSV files available
- [ ] Staging system accessible
- [ ] Backup facilitator ready
- [ ] Sign-in sheet prepared
- [ ] Recording setup (if approved)

### Training Agenda

**8:00 AM - 8:10 AM (10 min): Welcome & Overview**
```
Welcome teachers!
Today: Learn the new IRMS Mark Entry system
Key points:
- Easy to use
- We're here to help
- Practice makes perfect
- Questions are welcome

Agenda:
1. System overview (10 min)
2. Navigation (10 min)
3. Context selection (15 min)
4. CSV preparation (15 min)
5. Upload & validation (15 min)
6. Submission (10 min)
7. Q&A (15 min)
```

**8:10 AM - 8:20 AM (10 min): System Overview**
```
What is IRMS?
- Integrated Records Management System
- Handles mark entry, moderation, approval
- Replaces manual processes
- More efficient, secure, auditable

The Mark Entry Workflow:
1. You upload marks in CSV format
2. System validates the data
3. You submit to your HOD
4. HOD reviews and approves
5. Admin submits to NECTA

Timeline:
- Upload → 5 minutes
- Validation → automatic
- Moderation → 1-3 days
- Final submission → same day as approved
```

**8:20 AM - 8:30 AM (10 min): Navigation**
```
Login to the system:
- URL: https://irms.example.com
- Username: your email
- Password: [provided]

Main dashboard:
- Menu on left side
- Click "Mark Entry" to start
- Shows your batches
- Shows status of submissions

Important areas:
- Mark Entry (main task)
- My Submissions (track status)
- Reports (view data)
- Support (get help)
```

**8:30 AM - 8:45 AM (15 min): Context Selection**

```
"Context" = Which exam/subject/school?

Steps:
1. Click "Mark Entry"
2. Select Exam Year (e.g., 2026)
3. Select Region (e.g., IRINGA)
4. Select District (e.g., IRINGA MC)
5. Select School (your school)
6. Select Subject (e.g., MATHEMATICS)
7. Select Combination (if applicable)
8. System validates automatically
9. Green checkmark = Ready to proceed

Demo: Show with live system
Practice: Each person selects context on their computer
Time: Allow 5-10 minutes for practice
```

**Live Demo**:
- Login to staging system
- Navigate through context selection
- Show each dropdown
- Show validation
- Answer: "What if my school isn't listed?"

**Practice Exercise**:
- Each participant selects context
- Support team circulates to help
- Troubleshoot common issues

**8:45 AM - 9:00 AM (15 min): CSV Preparation**

```
What is CSV?
- Simple text format
- Excel can open it
- Looks like spreadsheet with rows/columns

Steps to prepare marks:
1. Click "Download CSV Template"
2. Save file to computer
3. Open in Excel
4. See headers: Index, Name, Paper 1, Paper 2, Paper 3
5. Delete sample rows (rows 2-4)
6. Enter your student data:
   - Column A: Index number (S1378-0001, S1378-0002, etc.)
   - Column B: Student name
   - Columns C+: Marks (0-100)
7. Save file as CSV format

Format requirements:
- File type: CSV (Comma-separated values)
- Encoding: UTF-8
- No extra columns
- No merged cells
- No blank rows

Example row:
S1378-0001,JOHN MUTUA,85,88,92
```

**Demo**:
- Download template from staging system
- Open in Excel
- Show how to format
- Save as CSV
- Show what NOT to do

**Practice Exercise**:
- Download template
- Fill in sample data (5 students)
- Save as CSV
- Support team checks files
- Time: 5 minutes

**9:00 AM - 9:15 AM (15 min): Upload & Validation**

```
Uploading marks:
1. Click "Choose File" or drag-drop
2. Select your CSV file
3. Click "Upload Marks"
4. Wait (shows progress bar)
5. System validates data

Results (two scenarios):

SCENARIO A: Validation Passed ✓
- "Status: VALIDATED"
- "Errors: 0"
- "Valid Records: 450"
- "Ready to submit"

SCENARIO B: Validation Failed ✗
- "Status: VALIDATION FAILED"
- "Errors: 3"
- Error details:
  * Row 5: Invalid mark "150"
  * Row 12: Missing index number
  * Row 28: Duplicate index

Next steps:
- Download error report
- Fix errors in Excel
- Re-save as CSV
- Re-upload

Common errors:
- Invalid mark > 100: Change to valid number
- Missing index: Add student's index number
- Duplicate index: Remove duplicate row
- Wrong format: Save again as CSV UTF-8
```

**Demo**:
- Show successful upload on staging
- Show validation passed
- Show what happens on error
- Show error report
- Show how to fix and re-upload

**Practice Exercise**:
- Upload sample CSV
- View validation results
- Answer: "Can I edit after upload?" (No, must fix CSV)
- Time: 5 minutes

**9:15 AM - 9:25 AM (10 min): Submission to HOD**

```
After validation passes:
1. Review batch summary
2. Confirm all data correct
3. Click "Submit to Moderation"
4. Get confirmation message
5. Status = "AWAITING MODERATION"

What happens next:
- Your HOD receives notification
- HOD reviews your data
- HOD approves or rejects
- You'll receive email notification
- Status visible in "My Submissions"

Timeline: Usually 1-3 days

After approval:
- Admin submits to NECTA
- Your marks are finalized
- System archives batch
```

**Demo**: Show submission process on staging

**Key Points**:
- Cannot edit after submission
- HOD decides approval
- You'll be notified
- Support team here to help

**9:25 AM - 9:40 AM (15 min): Q&A**

**Common Questions**:

Q: "What if I make a mistake?"
A: "Fix it before submitting. After submission, your HOD can reject it and ask you to resubmit."

Q: "How many students can I upload?"
A: "Up to 500 per batch. For more, create multiple batches."

Q: "Do I need internet the whole time?"
A: "You need internet to upload and submit. You can fill the CSV offline."

Q: "Can I upload the same batch twice?"
A: "System prevents duplicates. If unsure, contact support."

Q: "What if my marks are all correct but HOD rejected them?"
A: "Contact your HOD to understand the reason. They'll provide feedback."

Q: "When do I need to complete uploads?"
A: "Before [DATE]. Check with your administrator for specific deadlines."

Q: "What if the system is slow?"
A: "Try again in a few minutes. Contact support if problems persist."

---

### Teacher Training Checklist

- [ ] All participants signed in
- [ ] Materials distributed
- [ ] All demonstrations completed
- [ ] Practice exercises done
- [ ] All Q&A answered
- [ ] Contact information provided
- [ ] Feedback forms distributed
- [ ] Recording saved (if applicable)
- [ ] Follow-up schedule confirmed

### Materials to Distribute

1. **Printed Guide**: PHASE_4_5_USER_GUIDE_TEACHERS.md
2. **Quick Reference Card**: Laminated 1-page cheat sheet
3. **Support Contacts**: Card with phone/email
4. **Sample CSV Files**: 2-3 examples
5. **Feedback Form**: Google Form or paper

---

## Session 2: HOD Training (90 minutes)

### Pre-Training Setup (30 minutes before)

**Checklist**:
- [ ] Room setup complete
- [ ] Projector working
- [ ] Wifi stable
- [ ] Sample batches prepared in staging
- [ ] Presentation slides ready
- [ ] Decision flowchart printed
- [ ] Scenario case studies printed
- [ ] Backup facilitator ready
- [ ] Sign-in sheet prepared

### Training Agenda

**10:00 AM - 10:10 AM (10 min): Welcome & Overview**
```
Welcome HODs!
Your role: Quality gate for mark entry
Importance: Your decisions affect final data submitted to NECTA

Responsibilities:
- Review batches from teachers
- Check data quality
- Approve or reject
- Provide feedback

Timeline: We expect you to review within 1-3 days
```

**10:10 AM - 10:25 AM (15 min): Moderation Workflow**

```
5-Phase Workflow:

PHASE 1: View Pending Batches
- Click "My Moderation Queue"
- See list of batches awaiting review
- Each batch shows: Subject, Teacher, Submission date, Status

PHASE 2: Review Batch Details
- Click batch to view
- See all student marks in table
- See statistics (average, min, max, distribution)
- See teacher's submission notes

PHASE 3: Conduct Review
- Analyze data quality
- Check for anomalies
- Spot-check sample records
- Run quality checks

PHASE 4: Make Decision
- Approve (data looks good)
- Reject (issues found, teacher must fix)
- Request Changes (need clarification)

PHASE 5: Document & Notify
- Write review notes
- Select decision
- Submit review
- Teacher gets notification
```

**10:25 AM - 10:40 AM (15 min): Quality Standards**

```
What makes data "high quality"?

Completeness:
✓ All expected students present
✓ All required papers included
✓ No unexplained gaps
✓ Marks for every student

Accuracy:
✓ Marks in valid range (0-100)
✓ Marks match student ability
✓ No obvious data entry errors
✓ Consistent across papers

Reasonableness:
✓ Average score makes sense
✓ No suspicious patterns
✓ Distribution looks normal
✓ Outliers explainable

Decision Framework:

APPROVE if:
- All quality checks pass
- No anomalies detected
- Marks align with expectations
- Data is complete and accurate

REJECT if:
- Multiple errors found
- Data quality issues
- Suspicious patterns
- Missing data

REQUEST CHANGES if:
- Minor anomalies
- Need explanation
- Unclear data points
- Verification needed
```

**Demo**: Show batch review on staging system

**10:40 AM - 10:55 AM (15 min): Decision Making**

```
Making the right decision:

SCENARIO 1: All marks 90+
Question: "Are these realistic for your students?"
- If YES: Approve. Top students exist.
- If NO: Reject. Ask teacher to verify.

SCENARIO 2: Low average (55)
Question: "Was exam difficult? Or student performance weak?"
- If YES to either: Approve
- If NO: Request changes. Ask for explanation.

SCENARIO 3: One outlier (98 when others 60)
Question: "Is this student a top performer?"
- If YES: Approve
- If NO: Request changes. Verify this student.

SCENARIO 4: Missing Paper 2
Question: "Was Paper 2 not conducted?"
- If YES: Reject. All papers required.
- If NO: Request changes. Teacher must provide.

SCENARIO 5: Duplicate records
Question: "Are these actual duplicates?"
- If YES: Reject. Remove duplicates.
- If NO: Keep. Proceed with approval.

Decision Criteria:
- Have clear reasons
- Document your thinking
- Be fair and consistent
- Provide constructive feedback
```

**Demo**: Review sample batches with different scenarios

**10:55 AM - 11:05 AM (10 min): Writing Feedback**

```
Good feedback is:
- Specific (not vague)
- Constructive (helpful)
- Clear (easy to understand)
- Professional (respectful)

Examples:

APPROVAL FEEDBACK:
"Data reviewed and approved. All validation checks passed. 
Mark distribution is appropriate for your class. 
No concerns identified. Ready for final submission."

REJECTION FEEDBACK:
"Batch rejected due to data quality issues:
1. Row 12: Missing Paper 2 marks - Please provide
2. Rows 25-27: Duplicate index numbers - Please remove
3. Row 40: Mark 105 exceeds maximum - Please correct

Please fix these issues and resubmit. Contact me if questions."

REQUEST CHANGES FEEDBACK:
"Most records verified successfully. However:
1. Student S1378-0501 scored 95 when usually 60 - Please verify
2. Paper 1 shows unusual clustering at 75 - Please confirm

Please respond with clarification before I finalize approval."
```

**11:05 AM - 11:30 AM (25 min): Case Studies & Practice**

```
Practice Exercise 1: Batch Review
- Provide sample batch to review
- Participants review in pairs
- Discuss decision (Approve/Reject/Request)
- Share reasoning

Practice Exercise 2: Scenario Discussion
- Present 5 scenarios (see earlier)
- Small group discussion
- Vote on decision
- Discuss as group

Scenarios covered:
1. All high marks
2. Low average
3. Outlier student
4. Missing paper
5. Duplicate records

Time: 15 minutes total
```

---

### HOD Training Checklist

- [ ] All HODs signed in
- [ ] Materials distributed
- [ ] Workflow explained
- [ ] Quality standards clear
- [ ] Decision framework understood
- [ ] Practice exercises completed
- [ ] All Q&A answered
- [ ] Quick reference cards provided
- [ ] Feedback forms collected

---

## Session 3: Admin/Support Training (60 minutes)

### Pre-Training Setup (30 minutes before)

**Checklist**:
- [ ] Room setup complete
- [ ] Admin panel access ready
- [ ] Troubleshooting guide printed
- [ ] Escalation procedures printed
- [ ] Contact list updated
- [ ] Emergency procedures documented

### Training Agenda

**12:00 PM - 12:10 PM (10 min): Welcome & Role Overview**
```
Welcome admins and support staff!
Your role: Keep system running, support users

Key responsibilities:
1. Monitor system health
2. Support users with questions
3. Troubleshoot issues
4. Escalate to engineering
5. Generate reports
6. Maintain data integrity

You're the frontline - users will contact you first
```

**12:10 PM - 12:30 PM (20 min): System Operations**

```
Admin Panel Access:
- URL: https://irms.example.com/admin
- Login with admin account
- Dashboard shows system status

Key Functions:

1. User Management
   - View all users
   - Create new users
   - Reset passwords
   - Assign roles

2. Batch Management
   - View all batches
   - Filter by status
   - Export data
   - Archive batches

3. Reports
   - Mark entry summary
   - Submission status
   - Performance metrics
   - Audit trail

4. System Health
   - Database status
   - Cache status
   - Error logs
   - Performance metrics
```

**Demo**: Show admin panel, key screens, important functions

**12:30 PM - 12:50 PM (20 min): Troubleshooting & Support**

```
Common User Issues:

ISSUE 1: "I can't login"
- Check if account exists
- Reset password
- Verify user is active
- Check if role is assigned

ISSUE 2: "CSV upload hangs"
- Check file size (should be < 5MB)
- Ask user to try again
- Check system load
- Escalate if persistent

ISSUE 3: "Validation always fails"
- Download error report
- Review specific errors
- Contact user with details
- Help fix CSV format

ISSUE 4: "Batch disappeared"
- Check in archive
- Search by date
- Verify user permissions
- Check logs

ISSUE 5: "PDF generation is slow"
- Check system load
- Check cache status
- Monitor database
- Escalate if slow

Escalation:
Level 1: User support (you)
Level 2: Technical team
Level 3: Engineering team
Level 4: Emergency response
```

**Distribution**: Print troubleshooting guide with decision tree

**12:50 PM - 1:00 PM (10 min): Reporting & Monitoring**

```
Daily tasks:
- Check error logs
- Monitor user issues
- Update support tickets
- Generate daily report

Weekly tasks:
- Generate performance report
- Review system metrics
- Check backup status
- Plan any optimizations

Emergency contact:
- If system down: Contact [Number]
- If data issue: Contact [Number]
- If security issue: Contact [Number]
```

---

### Admin Training Checklist

- [ ] All admins signed in
- [ ] System access verified
- [ ] Operations explained
- [ ] Troubleshooting procedures clear
- [ ] Escalation path understood
- [ ] Reporting understood
- [ ] Emergency contacts provided
- [ ] On-call rotation assigned
- [ ] Follow-up meeting scheduled

---

## End of Day 3 Activities

### Afternoon (2:00 PM - 5:00 PM)

#### Activity 1: Dry-Run with Sample Data (2:00 PM - 3:30 PM)

**Objective**: Practice in live environment

```
Teachers:
- Download template
- Fill sample data
- Upload
- Check validation
- Submit (don't worry about approval)

HODs:
- Review sample batches
- Test approval/rejection
- Write feedback
- Submit review
```

**Duration**: 90 minutes  
**Support**: Full team available

#### Activity 2: Feedback Collection (3:30 PM - 4:00 PM)

**Forms Distributed**:
- How clear was the training? (1-5 scale)
- What was confusing? (open-ended)
- Do you feel ready to use system? (yes/no)
- Any additional support needed? (open-ended)
- Contact info for follow-up training

**Collect**: Use Google Form or paper forms

#### Activity 3: Address Questions & Concerns (4:00 PM - 5:00 PM)

**Open Q&A**:
- Answer any remaining questions
- Provide 1-on-1 support
- Offer additional training if needed
- Confirm everyone feels prepared

---

## Day 3 Completion Checklist

- [ ] Session 1 (Teachers): Completed
- [ ] Session 2 (HODs): Completed
- [ ] Session 3 (Admins): Completed
- [ ] Practice exercises: All done
- [ ] Feedback forms: Collected
- [ ] Outstanding questions: Addressed
- [ ] Materials: Distributed
- [ ] Support contacts: Provided
- [ ] Follow-up plan: Confirmed
- [ ] Team debriefing: Scheduled

---

## Issues Encountered

```
Issue 1: _______________________
Solution: ______________________
Status: [ ] Resolved [ ] Pending

Issue 2: _______________________
Solution: ______________________
Status: [ ] Resolved [ ] Pending
```

---

## Day 3 Summary

**Start Time**: 8:00 AM  
**End Time**: _____ PM  
**Teachers Trained**: _____ / 20-30  
**HODs Trained**: _____ / 10-15  
**Admin Staff Trained**: _____ / 5-10  
**Feedback Forms Collected**: _____ 
**Outstanding Issues**: _____ 

**Status**: ✅ READY FOR DAY 4

---

## Sign-Off

**Training Lead**: _________________ Date: _______  
**Technical Support**: _________________ Date: _______  
**Admin Team**: _________________ Date: _______

---

**Next**: [Day 4: Staging Testing](PHASE_5_DAY4_STAGING_TESTING.md)
