# Backup & Restore Quick Start Guide

## Access Backups

**From Dashboard:**
1. Click `SETTINGS` dropdown (top-right)
2. Click `Backups & Restore`
3. You're now on the backups page

**Direct URL:** `http://127.0.0.1:8000/admin/backups`

## Create a Backup

### Step 1: Click "Create Backup"
![Create Button]
- Button is at top-right of backups list page

### Step 2: Configure Backup Type
```
Choose one of:
• Full System (entire database)
• Exam Year (default - specific exam only)
• Metadata Only (users, roles, settings)
```

### Step 3: Select Exam Year (if Exam Year type)
- Dropdown shows all exam years
- Select the year to backup

### Step 4: Add Notes (Optional)
- Example: "Pre-publication backup for ACSEE 2025"
- Helps you remember why you created this backup

### Step 5: Confirm Pre-Backup Checklist
Check all 3 boxes:
- ✅ I understand this backup will capture current state
- ✅ I confirm sufficient storage space is available  
- ✅ I understand the purpose and scope of this backup

### Step 6: Submit
- Click "Create Backup"
- System creates backup and shows you:
  - Filename
  - Size
  - Checksum (first 16 chars)

## View Backup Details

1. On backups list, click any backup row
2. You'll see:
   - Full filename
   - Backup type
   - Exam year
   - File size
   - Verification status
   - Full checksum
   - Manifest (JSON)
   - Created by/when

## Download Backup

1. On backups list, click download icon (⬇)
2. Or on backup details, click "Download" button
3. System downloads ZIP file to your computer

**Keep backups safe!** Store copies off-site.

## Restore from Backup

### ⚠️ WARNING: THIS CANNOT BE UNDONE

**Before you restore:**
1. Make sure this is really what you want
2. Understand ALL CURRENT DATA will be replaced
3. A snapshot will be auto-created (your recovery option)
4. After restore, you cannot undo except by restoring another backup

### Step 1: Click "Restore" on Backup
- On backups list, click restore icon (⬆️)
- Or on backup details, click "Restore" button

### Step 2: Review Information
- Check filename
- Check backup type
- Check exam year
- Check file size
- Verify checksum

### Step 3: Read Warnings Carefully
Page shows:
- ⚠️ ALL CURRENT DATA WILL BE REPLACED
- When pre-restore snapshot will be created
- What data will be lost
- Audit trail info
- Confirmation requirements

### Step 4: Type "RESTORE"
- In "Confirmation Code" field
- Type exactly: `RESTORE`
- (This prevents accidental restores)

### Step 5: Check Confirmation Boxes
- ✅ Pre-restore backup will be created
- ✅ This action is irreversible

### Step 6: Click "Restore Backup"
- System validates backup integrity
- Creates automatic snapshot
- Performs restore in transaction
- Shows success/error message

### Step 7: Wait for Completion
- Takes 30-90 seconds depending on size
- Don't close page or browser
- Screen shows "Restoring... Please wait"

### Step 8: Verify Success
- You'll see success notification
- Check data looks correct
- Audit log will show restore event

## Common Questions

### Q: Where are backups stored?
**A:** `storage/app/backups/` on the server

### Q: How big are backups?
- Full system: ~100-500MB
- Exam year: ~10-50MB
- Metadata only: ~1-5MB

### Q: Can I delete old backups?
**A:** Soft-delete via Filament (they're not removed from disk).
To hard-delete: manually remove files from `storage/app/backups/`

### Q: What if restore fails?
**A:** 
1. Database transaction rolls back (no partial data)
2. Pre-restore snapshot is still available
3. Restore error is logged in audit trail
4. You can restore from snapshot or try different backup

### Q: How do I recover if restore went wrong?
**A:**
1. Go back to Backups page
2. Look for snapshot labeled "Pre-restore snapshot"
3. Click Restore on the snapshot
4. This brings you back to your previous state

### Q: Can I restore a locked exam year?
**A:** No, unless you:
1. Check the "Override locked exam year" box
2. Confirm you really want to do this

### Q: Who can create/restore backups?
**A:** Admin users only. Non-admins cannot access backup page.

### Q: Are backups automatically created?
**A:** Not yet. You must manually create them.
(Scheduled backups coming in future release)

### Q: What's the checksum for?
**A:** Ensures backup file hasn't been corrupted or tampered with.
If corrupted, restore is blocked.

### Q: What's the signature for?
**A:** Proves backup is authentic and wasn't modified.
If signature invalid, restore is blocked.

## Audit Trail

Every backup action is logged to governance audit logs:

**Backup Created:**
```
- Admin: [name]
- Action: Backup Created
- Type: exam_year
- Exam Year: ACSEE 2025
- File: irms-backup-acsee-2025-2025-02-02_120000.zip
- Checksum: abcd1234...
```

**Restore Completed:**
```
- Admin: [name]
- Action: Restore Completed
- Backup ID: [id]
- Checksum: abcd1234...
```

## Troubleshooting

### Backup Creation Says "Failed"
- Check disk space: `df -h`
- Check temp directory exists: `storage/app/temp/`
- Check database connection
- Check server logs

### Validation Failed Before Restore
- Backup file may be corrupted
- Try re-downloading backup from another source
- Check ZIP integrity: `unzip -t file.zip`
- Check file permissions: `ls -l file.zip`

### Restore Says "Cannot Proceed"
- Backup checksum doesn't match
- Backup signature invalid
- ZIP file is corrupted
- Try different backup if available

### Snapshot Not Created
- Disk space might be full
- Check permissions on backup directory
- Try creating smaller backup (metadata only)

## Need Help?

- Check BACKUP_RESTORE_SYSTEM.md for technical details
- Review audit logs for error messages
- Contact system administrator
- Check server logs: `storage/logs/laravel.log`

---

**System:** IRMS v1.0
**Last Updated:** February 2, 2025
