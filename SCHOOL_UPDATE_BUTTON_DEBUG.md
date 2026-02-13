# School Update Button - Debug Guide

## How to Check What's Happening

### Step 1: Open Browser Console
1. Press **F12** (or Ctrl+Shift+I on Windows, Cmd+Option+I on Mac)
2. Go to **Console** tab
3. Try to update the school
4. Look for error messages

### Step 2: Common Errors & Solutions

**Error 1: "Please fill in all required fields"**
- Make sure all fields are filled:
  - School Number ✓
  - School Name ✓
  - Ownership ✓
  - Region ✓
  - District ✓

**Error 2: "Validation error"**
- Check if School Number already exists (must be unique)
- Verify all field formats are correct

**Error 3: "Error saving school: ..."**
- Check console for the exact error message
- Usually a server-side validation issue

### Step 3: Check Network Tab
1. Open **F12 → Network** tab
2. Try to update school
3. Look for the request to `/api/schools/{id}`
4. Check the response for error details

---

## Possible Reasons Button Not Working

### 1. Region/District Not Selected
The form requires both region AND district to be selected properly:
```
Region: IRINGA ✓
District: IRINGA DC ✓
```

### 2. School Number Not Unique
If editing and keeping same number, that's OK. But if changing to an existing number, it will fail.

### 3. Browser Cache Issue
Try: **Ctrl+Shift+Delete** to clear cache, then refresh

### 4. JavaScript Error
Check console (F12) for any JavaScript errors

### 5. Validation Constraint
The code field in the database is UNIQUE. If you're changing it to match another school's code, it will fail.

---

## What the Update Button Does

When you click "Update School":

```
1. Collects form data:
   - code
   - name
   - ownership
   - region_id
   - district_id

2. Sends PUT request to: /api/schools/{id}

3. Backend validates:
   - Code is unique (except for self)
   - All required fields present
   - Ownership is GOVERNMENT or NON-GOVERNMENT
   - Region exists
   - District exists

4. If valid: Updates school in database
5. If error: Shows error message
```

---

## Check These

### Is the school code already taken?
```bash
php artisan tinker
App\Models\School::where('code', 'S0108')->get();
```

### Can you view the school details?
Open the school in edit mode, check all values load correctly.

### Is the District dropdown working?
When you change Region, does District list change?

---

## Try This Fix

### Clear and Reload
1. Close the modal
2. Press **Ctrl+F5** (hard refresh)
3. Open Schools Management again
4. Try editing the school again

### Manual SQL Update (If Button Still Fails)
```bash
php artisan tinker
$school = App\Models\School::find(10);
$school->update([
    'code' => 'S0108',
    'name' => 'IFUNDA TECHNICAL SECONDARY SCHOOL',
    'ownership' => 'GOVERNMENT',
    'region_id' => 4,  // IRINGA
    'district_id' => 15  // IRINGA DC
]);
```

---

## What Exactly Are You Seeing?

Tell me:
1. Does the button show an error message? If yes, what is it?
2. Does the button appear to be disabled (grayed out)?
3. Does anything happen when you click it (loading spinner)?
4. Check console (F12) - what errors appear?

---

## Most Likely Issue

The problem is probably the **School Number (S0108)** field. Since there are TWO schools with similar codes:
- S108 (IFUNDA TECHNICAL - IRINGA)
- S0108 (Test school - MOROGORO)

The system is probably treating the update as a duplicate code when trying to save.

---

## Solution

**Delete S0108 (the test school) first**, then you can work with S108 freely:

```bash
php artisan tinker
App\Models\School::where('code', 'S0108')->where('name', 'School S0108')->delete();
```

Then reload the page and try editing S108.

