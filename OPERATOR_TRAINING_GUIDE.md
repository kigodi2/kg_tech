# Hardened Restore System - Operator Training Guide

**Duration**: 30 minutes training + 30 minutes hands-on practice  
**Audience**: Database operators, system administrators  
**Prerequisite**: Admin access to IRMS system  
**Date**: 2026-02-02

---

## 📚 Module 1: Understanding the Restore System (10 minutes)

### What is Database Restore?

**Definition**: Restore is an operation that replaces your current database with a previous backup. This means:
- ✓ Old data is REPLACED with backup data
- ✓ New data entered AFTER the backup will be LOST
- ✓ This action CANNOT be undone
- ✓ The entire database is affected

### When Do We Restore?

Restore is used in emergencies only:
- 🔴 Database corruption detected
- 🔴 Accidental mass deletion of data
- 🔴 System compromise / security incident
- 🔴 Data integrity issues requiring recovery
- 🔴 Test data accidentally committed to production

### When Do We NOT Restore?

**Do NOT restore for**:
- ✗ Single record errors (use manual corrections)
- ✗ User mistakes (use undo functionality)
- ✗ Scheduled maintenance (use backup alone)
- ✗ Testing purposes (use test environment)

---

## 📋 Module 2: The 4-Step Restore Process (10 minutes)

### Overview

The restore system has 4 steps with legal protections built in at every stage.

```
Step 1️⃣: SELECT BACKUP
    ↓
Step 2️⃣: LEGAL ACKNOWLEDGMENT
    ↓
Step 3️⃣: FINAL CONFIRMATION
    ↓
Step 4️⃣: RESULT (Success/Error)
```

### Step 1: Select Backup

**What you do**:
1. Log into IRMS admin panel
2. Click "Restore Database" in sidebar
3. Enter the backup file path

**Example path**:
```
storage/backups/irms-backup-full-system-2026-02-02_102000.zip
```

**What happens**:
- System validates the backup file
- Checks: file exists, file is valid ZIP, contains database
- Shows: "✓ Backup validation passed" OR errors

**If you see errors**:
- ✗ "Backup file does not exist" → Check the path is correct
- ✗ "Not a valid ZIP archive" → Backup is corrupted, use different backup
- ✗ "Missing database.sqlite" → Backup incomplete, use different backup

### Step 2: Legal Acknowledgment

**What you see**:
A RED BOX with legal text:
```
⚠️ EXAMINATION DATA GOVERNANCE NOTICE

This operation will REPLACE the ENTIRE examination database.
All current results, registrations, and marks will be LOST.
This action is irreversible and must be authorized
according to examination data governance regulations.
```

**What you do** (3 required inputs):

**Input 1: Acknowledgment Checkbox**
- Text: "I understand and accept full responsibility"
- Action: Check the box
- Meaning: You accept that you're responsible for this action

**Input 2: Confirmation Text**
- Instruction: "Type "RESTORE" to confirm"
- What to type: `RESTORE` (case-sensitive, exact match)
- Purpose: Prevents accidental restores

**Input 3: Restore Reason**
- Instruction: "Why are you restoring? (minimum 10 characters)"
- Examples: 
  - "Emergency recovery due to data corruption"
  - "Database integrity issue detected - immediate recovery needed"
  - "Test data accidentally committed - must restore clean backup"
- Purpose: Creates audit record explaining why

**After all 3 inputs**:
- Click "Proceed to Confirmation"
- System validates all inputs
- Moves to Step 3

### Step 3: Final Confirmation

**What you see**:
- Yellow warning: "🚨 Point of No Return"
- Gray box showing:
  - Your name
  - Your role
  - Backup filename
  - Your restore reason
- Checkbox: "I confirm I understand this will replace the entire database..."

**What you do**:
1. Review the summary (especially the reason)
2. Check the final confirmation box
3. Click "Execute Restore (IRREVERSIBLE)" - RED BUTTON

**STOP before clicking**:
- ✓ Have you contacted affected stakeholders?
- ✓ Have you verified the backup is correct?
- ✓ Do you understand the data loss implications?
- ✓ Is this truly an emergency situation?

**After clicking**:
- System shows loading indicator
- Application enters maintenance mode (users see "System is restoring...")
- Database is replaced with backup
- System comes back online

### Step 4: Result

**Success Page** (Green box):
```
✓ Restore Completed Successfully

Audit Log ID:      #42
Restored At:       2026-02-02T10:32:15Z
Quarantine Loc:    storage/backups/quarantine/2026-02-02_10-30-00_xxx/

Note: Original database safely backed up in quarantine location.
You can manually recover it if needed.
```

**What to do**:
1. Note the **Audit Log ID** (for records)
2. System is back online - verify it looks correct
3. Check that data matches the backup point
4. Notify stakeholders that restore completed

**Error Page** (Red box):
```
✗ Restore Operation Failed

Next Steps:
- Check storage/logs/laravel.log for error details
- Verify backup file is valid and accessible
- Check available disk space
- Contact system administrator
```

**What to do**:
1. **Don't panic** - System likely rolled back automatically
2. Note the error message
3. Check if the issue was automatic rollback (your DB is restored)
4. Contact system administrator with:
   - Backup filename
   - Error message
   - Time of restore attempt

---

## 🎯 Module 3: Practical Hands-On Exercise (20 minutes)

### Exercise: Perform a Test Restore

**Objective**: Complete the 4-step restore workflow with a test backup

**Prerequisites**:
- [ ] Test backup file exists
- [ ] You have admin access
- [ ] You've notified supervisor this is a test
- [ ] IRMS is running normally

**Step 1: Access the System**
```
1. Open browser
2. Go to: http://localhost:8000/admin
3. Log in with admin credentials
4. Look for "Restore Database" in sidebar
5. Click to open restore page
```

**Step 2: Select Test Backup**
```
1. You should see Step 1 form
2. Enter backup path: storage/backups/irms-backup-test-YYYY-MM-DD_HHMMSS.zip
3. Click "Validate Backup"
4. Wait for result (should say "✓ Backup validation passed")
5. If error, try different backup file
```

**Step 3: Complete Legal Acknowledgment**
```
1. Red box appears with legal text
2. Read it carefully (this is not a joke!)
3. Check the acknowledgment box
4. Type: RESTORE (exactly, case-sensitive)
5. Enter reason: "Test restore exercise - verifying system functionality"
6. Click "Proceed to Confirmation"
```

**Step 4: Final Confirmation**
```
1. Review summary (your name, role, backup, reason)
2. Check final checkbox
3. Say out loud: "I understand this cannot be undone"
4. Click "Execute Restore (IRREVERSIBLE)"
5. Watch loading indicator
6. Wait for result (should be success page)
```

**Step 5: Verify Result**
```
1. Note the Audit Log ID shown
2. Scroll down to "Recent Restore Operations" table
3. Find your restore in the table
4. Verify all details are correct
5. Click "Export Audit Log (CSV)" to download record
```

**Debrief Questions**:
- How many confirmations were required?
- Why is the confirmation text important?
- What would happen if you restored the wrong backup?
- How long did the restore take?
- What happens to your old database?

---

## ⚠️ Module 4: Error Handling & Recovery (5 minutes)

### Common Errors & Solutions

#### Error: "Backup file does not exist"
**Cause**: Wrong path entered  
**Solution**: 
1. List backups: `ls storage/backups/`
2. Copy exact filename
3. Enter correct path
4. Try again

#### Error: "Backup is not a valid ZIP archive"
**Cause**: Backup file is corrupted  
**Solution**:
1. Try older backup
2. Verify backup file isn't truncated
3. Check disk space when backup was created
4. Contact system administrator

#### Error: "Missing database.sqlite"
**Cause**: Backup is incomplete  
**Solution**:
1. Use different backup
2. Check backup creation logs
3. Verify backup process completed successfully
4. Contact system administrator

#### Restore Takes Very Long
**Cause**: Large database or slow storage  
**Normal**: 1-3 minutes is typical  
**Solution**:
1. Be patient - don't interrupt
2. Check system load
3. Ensure disk space is available
4. Wait for result

#### Restore Failed & System Still in Maintenance
**Cause**: Automatic rollback didn't work (rare)  
**URGENT ACTIONS**:
1. **DO NOT** restart application
2. Contact system administrator immediately
3. Have ready:
   - Backup ID
   - Error message
   - Quarantine location from error
   - Log file location (storage/logs/laravel.log)

### Emergency Recovery (Manual)

**If system is stuck in maintenance mode**:

```bash
# 1. Access server command line

# 2. Find your database in quarantine
ls -la storage/backups/quarantine/

# 3. Find the most recent directory
# Example: 2026-02-02_10-30-00_a1b2c3d4e

# 4. Restore manually
cp storage/backups/quarantine/2026-02-02_10-30-00_a1b2c3d4e/database.sqlite \
   database/database.sqlite

# 5. Fix permissions
chmod 640 database/database.sqlite

# 6. Exit maintenance mode
rm storage/framework/down

# 7. Test access
curl http://localhost:8000
# Should respond with page content, not maintenance message
```

---

## 📖 Module 5: Reference & Resources

### Quick Reference Card
- **Print**: HARDENED_RESTORE_REFERENCE.md
- **Keep at desk** for quick lookups

### Complete System Guide
- **Read**: HARDENED_RESTORE_SYSTEM.md
- **For detailed technical information**

### Troubleshooting
- **See**: HARDENED_RESTORE_VERIFICATION.md
- **For testing and debugging**

### Contact Information
- **System Administrator**: [Name & Contact]
- **On-Call Support**: [Phone/Email]
- **Emergency**: [Protocol]

---

## ✅ Certification Checklist

After training, verify you can:

- [ ] Access the restore page
- [ ] Validate a backup file
- [ ] Understand the legal acknowledgment
- [ ] Complete all 3 required confirmations
- [ ] Explain what each step does
- [ ] Navigate to Step 3 confirmation
- [ ] Know what "Point of No Return" means
- [ ] Understand why restore is irreversible
- [ ] Export audit logs as CSV
- [ ] Identify at least 3 common errors
- [ ] Know when to NOT restore
- [ ] Understand your responsibility

**Sign-Off**:
- Name: ________________
- Date: ________________
- Trainer: ________________

---

## 🎓 Key Takeaways

### Remember:
1. **Restore is DESTRUCTIVE** - All new data since backup is lost
2. **Restore is IRREVERSIBLE** - Cannot undo once executed
3. **3 Confirmations Required** - Checkbox, "RESTORE" text, reason
4. **Audit Trail Automatic** - All restores are recorded
5. **Quarantine Protection** - Old database backed up for 30 days
6. **Emergency Only** - Use only for critical situations

### Never:
- ✗ Restore for non-emergency reasons
- ✗ Skip reading the legal acknowledgment
- ✗ Restore wrong backup without verification
- ✗ Restore without notifying stakeholders
- ✗ Delete quarantine backups immediately
- ✗ Proceed past Step 3 if you're unsure

### Always:
- ✓ Verify the backup is correct first
- ✓ Notify affected stakeholders
- ✓ Document the reason in the reason field
- ✓ Note the Audit Log ID
- ✓ Export audit CSV for records
- ✓ Verify the restore completed correctly
- ✓ Keep quarantine backups for 30+ days

---

## 📞 After Training

**Questions?** Contact your system administrator  
**Practice needed?** Ask for test environment access  
**Certification?** Bring completed checklist to supervisor  

**Stay safe. Restore responsibly. 🔐**

---

**Course Version**: 1.0  
**Last Updated**: 2026-02-02  
**Next Review**: 2026-04-02
