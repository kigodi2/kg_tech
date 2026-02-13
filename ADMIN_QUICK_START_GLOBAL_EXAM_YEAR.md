# Admin Quick Start - Global Exam Year System

**What You Need to Know:** Set the exam year ONCE in admin, and it automatically appears everywhere in the system.

---

## Quick Setup (2 minutes)

### Option 1: Using Terminal (Fastest)
```bash
php artisan tinker
ExamYear::find(3)->activate()  # Use correct ID for your year
exit
```

### Option 2: Using Admin Panel
```
Admin → System Settings → Exam Years
  → Find the year (e.g., 2026)
  → Click "Make Active"
  → Done!
```

---

## What Happens After You Set It

All users immediately see:
- ✅ Registration form: Exam Year pre-filled with "2026"
- ✅ Bulk import modal: Year pre-filled with "2026"
- ✅ Mark entry page: Year pre-filled with "2026"

Users can still change the year if they need to work with a different one.

---

## Testing

### Test 1: Check API
```bash
curl http://127.0.0.1:8000/api/exam-years/active
```

Should return:
```json
{
  "active_year": {
    "year_label": "2026",
    "is_locked": false,
    "status": "✓ Active"
  }
}
```

### Test 2: Check Registration
1. Open http://127.0.0.1:8000/registration
2. Look for "Exam Year" field
3. Should show "2026" (pre-filled)

### Test 3: Check Bulk Import
1. Open http://127.0.0.1:8000/registration
2. Click "Import CSV" in Tools menu
3. Modal should show "Exam Year = 2026" (pre-filled)

### Test 4: Check Mark Entry
1. Open http://127.0.0.1:8000/mark-entry/acsee
2. Year field should show "2026" (pre-filled)

---

## Changing the Year (Later)

When you need to switch to a new year (e.g., from 2026 to 2027):

### Step 1: Set New Active Year
```bash
php artisan tinker
ExamYear::find(4)->activate()  # ID for 2027
exit
```

### Step 2: Done!
All users immediately see:
- Registration: Exam Year = "2027"
- Bulk import: Exam Year = "2027"
- Mark entry: Year = "2027"

No manual notifications or updates needed!

---

## Troubleshooting

### If year isn't pre-filling
1. Verify API returns active year: `curl http://127.0.0.1:8000/api/exam-years/active`
2. Check browser console for errors (F12)
3. Refresh page (Ctrl+F5)
4. Verify an active year is set (only one year should have `is_active = true`)

### If multiple years are marked as active
```bash
php artisan tinker
ExamYear::where('is_active', true)->get()  # Should show only 1
# If multiple, fix:
ExamYear::update(['is_active' => false])  # Reset all
ExamYear::find(3)->activate()  # Set the correct one
exit
```

### If page still shows blank year
1. Clear browser cache (Ctrl+Shift+Delete)
2. Hard refresh (Ctrl+F5)
3. Check if active year is actually set

---

## Key Points

✅ **One-time setup**: Set active year once, affects entire system  
✅ **System-wide effect**: All modules use the same year automatically  
✅ **Override capability**: Users can still change year if needed  
✅ **No user communication needed**: Year changes apply immediately  
✅ **Data integrity**: All registrations/marks tagged with correct year  

---

## Commands Reference

**Set 2026 as active:**
```bash
php artisan tinker
ExamYear::find(3)->activate()
exit
```

**Check current active year:**
```bash
php artisan tinker
ExamYear::where('is_active', true)->first()
exit
```

**Reset all years to inactive (emergency):**
```bash
php artisan tinker
ExamYear::update(['is_active' => false])
exit
```

---

That's it! The global exam year system handles the rest automatically.
