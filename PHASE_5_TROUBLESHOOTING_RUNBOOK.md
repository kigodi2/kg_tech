# Phase 5: Troubleshooting Runbook

**Status**: 🔧 SUPPORT OPERATIONS  
**Document**: Troubleshooting & Issue Resolution  
**Audience**: Support Team, Level 1/2 Engineers  
**Last Updated**: 2026-02-13

---

## Quick Issue Resolution Guide

| Issue | Symptoms | Resolution Time | Severity |
|-------|----------|-----------------|----------|
| Cannot Login | "Invalid credentials" | < 5 min | MEDIUM |
| CSV Upload Fails | "Upload failed" error | < 10 min | HIGH |
| Validation Errors | Repeated errors | < 15 min | MEDIUM |
| System Slow | Page load > 5s | < 10 min | MEDIUM |
| Batch Rejected | "Rejected" status | < 15 min | LOW |
| Database Down | Connection refused | < 30 min | CRITICAL |
| Disk Full | Storage error | < 20 min | CRITICAL |
| PDF Generation Fails | "PDF error" | < 15 min | MEDIUM |
| CSV Export Slow | Takes > 2 min | < 10 min | LOW |

---

## Issue 1: User Cannot Login

### Quick Diagnosis

```bash
# Is user account active?
psql -U irms_app -d irms_production -c "
SELECT email, status FROM users WHERE email = 'user@example.com';" 

# Expected: Shows status='active'
```

### Resolution (Choose one)

**Solution A: Reset Password** (75% of login issues)
```bash
php artisan tinker
>>> $user = User::where('email', 'user@example.com')->first()
>>> $user->password = Hash::make('TempPass2026!')
>>> $user->save()
>>> exit
# Tell user: "Password reset to: TempPass2026! Please change on first login"
```

**Solution B: Activate Account**
```bash
psql -U irms_app -d irms_production -c "
UPDATE users SET status='active' WHERE email = 'user@example.com';"
```

**Solution C: Database Issue** (If both above fail)
```bash
# Test database
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production -c "SELECT 1;"
# If fails → See "Database Down" issue
```

**Time**: 5 minutes | **Escalate if**: Multiple users affected or database down

---

## Issue 2: CSV Upload Fails

### Quick Diagnosis

Ask user 3 questions:
1. "Is your file smaller than 5 MB?" 
2. "Is it saved as CSV format (not XLSX)?"
3. "Is encoding UTF-8?"

### Resolution (In order)

**Step 1: Check File Size**
- File > 5 MB? → Split into multiple files
- File < 1 MB? → Try uploading test file first

**Step 2: Verify File Format**
```
Tell user:
1. Open file in Excel
2. File → Save As
3. Format: CSV (Comma-delimited)
4. Encoding: UTF-8
5. Try uploading again
```

**Step 3: Check Disk Space**
```bash
df -h /
# If < 10% free, see "Disk Full" issue
```

**Step 4: Check PHP Limits**
```bash
php -i | grep upload_max
# Should show: 10M or higher
# If not, increase in /etc/php/8.1/fpm/conf.d/99-irms.ini
```

**Time**: 10 minutes | **Escalate if**: Small test file also fails

---

## Issue 3: Validation Errors Persist

### Quick Diagnosis

```bash
# Check error report
# Most common: Invalid mark (> 100), missing index, duplicates

# Ask user: "Did you download the error report?"
# It shows EXACTLY which rows need fixing
```

### Resolution

**Show user the fix process**:
1. Download error report from system
2. Open report (shows row numbers with errors)
3. Open CSV in Excel
4. Fix each error (use table below)
5. Save as CSV UTF-8
6. Upload again

**Common Error Fixes**:

| Error | Fix |
|-------|-----|
| "Mark 150" | Change to 100 (max value) |
| "Missing index" | Add index like S1378-0001 |
| "Duplicate S1378-0001" | Delete duplicate row |
| Wrong encoding | Save as CSV UTF-8 in Excel |
| Extra columns | Delete columns after Paper3 |

**Time**: 15 minutes | **Escalate if**: Error still shows after fixes

---

## Issue 4: System Very Slow

### Quick Diagnosis

```bash
# Check load
top -n 1
# CPU > 80%? → Database issue
# Memory > 85%? → Cache or leak

# Check logs
tail -30 storage/logs/laravel.log | grep -i slow
```

### Resolution (Try in order)

**Step 1: Clear Cache** (Works 60% of time)
```bash
php artisan cache:clear
redis-cli -a PASSWORD FLUSHDB
sudo systemctl restart php-fpm
# Wait 30 seconds and test
```

**Step 2: Optimize Database** (Takes 5 minutes)
```bash
psql -U irms_app -d irms_production -c "ANALYZE;"
psql -U irms_app -d irms_production -c "VACUUM ANALYZE;"
# Test system
```

**Step 3: Restart Services**
```bash
sudo systemctl stop php-fpm
sleep 5
sudo systemctl start php-fpm
sleep 5
# Test
```

**Step 4: Check for Stuck Queries**
```bash
psql -U irms_app -d irms_production -c "
SELECT query FROM pg_stat_activity WHERE duration > interval '5 minutes';"
# If stuck queries exist, escalate to engineering
```

**Time**: 10 minutes | **Escalate if**: Slowness persists after all steps

---

## Issue 5: Batch Marked as Rejected

### Quick Diagnosis

```bash
# View rejection reason
# Should appear in system next to batch
# If not showing, refresh page
```

### Resolution

**Show user the rejection feedback**:
1. Find rejection reason in batch details
2. Read what HOD requested
3. Common reasons:
   - "Data validation errors" → Download error report from initial upload
   - "Verify these marks" → Check specific student marks
   - "Missing Paper 2" → Add missing paper marks
   - "Unusual clustering" → Explain mark distribution

**Next steps**:
1. Fix the data
2. Create new CSV
3. Re-upload
4. Will go through validation → moderation again

**Time**: 15 minutes | **Escalate if**: Rejection reason unclear

---

## Issue 6: Database Connection Failing

### CRITICAL ISSUE - Escalate Immediately

### Quick Diagnosis

```bash
# Check if PostgreSQL running
sudo systemctl status postgresql
# Should show: active (running)

# Test direct connection
psql -U irms_app -d irms_production -c "SELECT 1;"
# Should return: 1

# Test via PgBouncer
psql -h 127.0.0.1 -p 6432 -U irms_app -d irms_production -c "SELECT 1;"
# Should return: 1
```

### Resolution

**Step 1: Restart PostgreSQL**
```bash
sudo systemctl restart postgresql
sleep 10
# Test connection again
```

**Step 2: Restart PgBouncer**
```bash
sudo systemctl restart pgbouncer
sleep 5
# Test connection again
```

**Step 3: Check Connection Pool**
```bash
psql -h 127.0.0.1 -p 6432 -U postgres -d pgbouncer -c "SHOW POOLS;"
# Look for: active connections < max_connections
```

**Step 4: Escalate**
- If still failing after restarts
- Contact Database Administrator immediately
- This is a CRITICAL issue blocking all users

**Time**: 30 minutes | **Always Escalate if**: Persists after restarts

---

## Issue 7: Disk Space Full

### CRITICAL ISSUE - Act Immediately

### Quick Diagnosis

```bash
# Check disk usage
df -h /
# If < 5% free: CRITICAL
# If 5-10% free: URGENT
# If 10-20% free: WARNING

# Find large files
du -sh /* | sort -h | tail -10
# Look for large log files, old backups, temp files
```

### Resolution

**Step 1: Delete Old Logs** (Usually frees 50-200 MB)
```bash
sudo find /var/log -name "*.log" -mtime +30 -delete
sudo find /var/log -name "*.gz" -mtime +30 -delete
# Restart services
sudo systemctl restart nginx
sudo systemctl restart php-fpm
```

**Step 2: Delete Old Backups** (Usually frees 500+ MB)
```bash
sudo rm -rf /backups/irms/full-2026-01-*
sudo rm -rf /backups/irms/full-2026-02-0[1-9]
# Keep current week's backups
```

**Step 3: Clear Cache**
```bash
php artisan cache:clear
redis-cli -a PASSWORD FLUSHDB
# Usually frees 100-200 MB
```

**Step 4: Verify Space**
```bash
df -h /
# Should show > 15% free now
```

**Time**: 20 minutes | **Alert Engineering**: System needs more storage planned

---

## Issue 8: PDF Generation Fails or Takes Too Long

### Quick Diagnosis

```bash
# Check if PDF generation started
# Look for file in storage/app/pdf/

# Check logs
tail -30 storage/logs/laravel.log | grep -i pdf

# Check system resources
top -n 1 | head -10
# High memory = PDF rendering is heavy
```

### Resolution

**Step 1: Check System Resources**
```bash
# If CPU > 80% or Memory > 85%
# See "System Very Slow" issue above
# Clear cache and optimize
```

**Step 2: Try Again**
- PDF generation needs resources
- If system busy, PDF will be slow
- Ask user to try again in 5 minutes

**Step 3: Check File Size**
```bash
# If PDF file exists but incomplete
# Delete and try again
rm storage/app/pdf/scoresheet-*.pdf

# User tries again
```

**Step 4: Escalate**
- If generation still fails after 2 attempts
- If error message appears in logs
- Contact engineering

**Time**: 15 minutes | **Escalate if**: Still fails after retry

---

## Issue 9: CSV Export Takes Too Long

### Quick Diagnosis

```bash
# Check if export started
# Monitor browser download

# Check logs
tail -30 storage/logs/laravel.log | grep -i export

# Check database load
psql -U irms_app -d irms_production -c "
SELECT COUNT(*) FROM pg_stat_activity WHERE state = 'active';"
```

### Resolution

**Step 1: Check CSV Size**
- Exporting 50,000+ records is slow
- Target time: < 2 minutes
- If > 2 min, user's file might be very large

**Step 2: Try Smaller Export**
- Ask user: "How many records are you exporting?"
- If > 10,000: Normal for CSV export to take 1-2 min
- If < 10,000: Should be < 30 seconds

**Step 3: Clear Cache & Retry**
```bash
php artisan cache:clear
# User tries export again
```

**Step 4: Monitor System**
- If export slow, check if other users uploading
- Multiple concurrent operations = slower
- Not an error, just performance

**Time**: 10 minutes | **Escalate if**: Error message appears

---

## Escalation Decision Matrix

```
Issue Level         Response Time    Who to Contact
=================================================
CRITICAL
- Database down     < 5 minutes      Database Admin + Tech Lead
- Disk full         < 10 minutes     Operations + Tech Lead
- All users down    < 5 minutes      CTO + Operations

HIGH
- Login broken      < 30 minutes     Support Team Lead
- Upload broken     < 30 minutes     Engineering
- Data corruption   < 1 hour         Engineering + Database Admin

MEDIUM
- Slow performance  < 1 hour         Operations
- Validation errors < 2 hours        Support Team
- PDF generation    < 2 hours        Engineering
- Single user issue < 2 hours        Support Team

LOW
- CSV export slow   < 4 hours        Operations (monitor)
- Batch rejected    < 4 hours        Support Team (guidance)
```

---

## Support Ticket Template

**For all issues reported**:

```
SUPPORT TICKET
==============

Issue: [One sentence description]
Severity: [CRITICAL/HIGH/MEDIUM/LOW]
Reported by: [User name/email]
Reported at: [Date/Time]
Affected users: [1 / Multiple]

Symptoms:
- [What user sees/experiences]
- [Error messages if any]
- [When it started]

Steps to Reproduce:
1. [Step 1]
2. [Step 2]
3. [Step 3]

Investigation:
- [What checks were run]
- [What was found]

Resolution Attempted:
- [Solution A: Result]
- [Solution B: Result]

Status: 
- [RESOLVED / ESCALATED / WAITING]

Escalated to: [If applicable]
Escalation time: [If applicable]

Notes:
[Any additional information]
```

---

## Phone Support Script

**When user calls with issue**:

```
1. Greeting
   "IRMS Support, this is [Name]. How can I help?"

2. Collect Information
   "What's your name and email address?"
   "What are you trying to do?"
   "What error message do you see?"
   "When did this start?"

3. Diagnose
   "Let me check a few things. [Run checks from issue]"
   "I found the issue. Here's what we need to do:"

4. Guide Solution
   "Please [solution step 1]"
   "Now [solution step 2]"
   "Let me know if you see any changes"

5. Verify
   "Are you able to [action] now?"
   "Is the issue resolved?"

6. Close
   "Great! You're all set."
   "If this happens again, contact us immediately."
   "Thank you for using IRMS!"
```

---

## Batch Rejection Guidance

**When user says: "My batch was rejected, what now?"**

```
1. Find rejection reason
   "Let me help you find why it was rejected."
   "Log in → View Batch → Click 'View Rejection Reason'"

2. Understand reason
   "It says: [Read reason from system]"
   "This means: [Explain in simple terms]"

3. Get error report
   "Did you download the error report when you first uploaded?"
   "That shows exactly which rows need fixing"

4. Guide fix
   "You need to:
   1. Fix [problem] in rows [X, Y, Z]
   2. Save file as CSV
   3. Upload again
   4. It will go through validation again"

5. Prevent future rejection
   "For next time:
   - Download error report immediately
   - Fix ALL errors before submitting
   - Double-check data before upload"
```

---

## Common User Questions

**Q: "Why was my upload rejected?"**
A: "Your HOD reviewed it and found issues. Check the rejection reason for details."

**Q: "Can I just re-upload the same file?"**
A: "No, you need to fix the errors first. The rejection reason tells you what to fix."

**Q: "How long does moderation take?"**
A: "Usually 1-3 days. Your HOD will review and either approve or ask for changes."

**Q: "What if my marks are correct?"**
A: "Contact your HOD directly. They can explain the rejection reason."

**Q: "Can I modify the batch after submission?"**
A: "No, once submitted, it's locked. If rejected, you'll re-upload the fixed file."

**Q: "Is my data safe?"**
A: "Yes, all data is encrypted and backed up daily. Only authorized users can access."

---

## Knowledge Base Articles

Create these for users to self-service:

1. **How to Reset My Password** (Link in login page)
2. **CSV File Format Requirements** (Link in upload page)
3. **Understanding Validation Errors** (Link in upload results)
4. **What Does Rejected Mean?** (Link in batch details)
5. **System Slow - What to Do** (FAQ page)
6. **Contact Support** (Always available)

---

## Monitoring During Days 6-7

**Check these every 2 hours**:

```bash
# Error logs
tail -100 storage/logs/laravel.log | grep ERROR

# System health
top -n 1 | head -5

# Database
psql -U irms_app -d irms_production -c "SELECT COUNT(*) FROM mark_import_batches;"

# Support tickets
# Check email for new issues

# User feedback
# Monitor social channels/comments
```

**If anything unusual**:
1. Document in ticket
2. Inform on-call engineer
3. Escalate if needed

---

## Post-Issue Follow-Up

**After resolving any issue**:

1. Send thank you email to user
2. Document solution in knowledge base
3. Inform team about issue (daily briefing)
4. If recurring, escalate to engineering

---

**Troubleshooting Runbook Complete**

Use this guide to:
- Resolve 80% of user issues
- Escalate remaining 20% efficiently
- Provide consistent support
- Build knowledge base

**Status**: ✅ READY FOR SUPPORT TEAM
