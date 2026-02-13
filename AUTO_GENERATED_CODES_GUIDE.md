# Auto-Generated Codes Feature Guide

## Overview

The Registration Management system now includes automatic code generation for Regions and Districts. Codes are intelligently generated based on the entity name and are guaranteed to be unique.

---

## Code Format

**Format**: `XX##`
- **XX** = First 2 letters of the name (uppercase)
- **##** = Sequential 2-digit number (01, 02, 03, etc.)

### Examples

**Regions:**
- "Arusha" → AR01, AR02, AR03, ...
- "Dodoma" → DO01, DO02, DO03, ...
- "Morogoro" → MO01, MO02, MO03, ...
- "Dar Es Salaam" → DA01, DA02, DA03, ...

**Districts:**
- "Arusha City" → AC01
- "Arusha Metro" → AM01
- "Dodoma Urban" → DU01
- "Morogoro Rural" → MR01

---

## How It Works

### Regions Page (`/registration/regions`)

1. **Click "Add Region"** button
2. **Enter Region Name** (e.g., "Arusha")
3. **Code auto-generates** in the Code field:
   - Extracts first 2 letters: "AR"
   - Checks for existing "AR" codes
   - Assigns next available number
   - Shows: "AR01" (or "AR02" if AR01 exists)
4. **Code updates in real-time** as you type
5. **Click "Add Region"** to save

**Code Field Properties:**
- ✓ Read-only (cannot be manually edited)
- ✓ Auto-updates while typing name
- ✓ Shows format hint: "2 letters + 2 digits"
- ✓ Blank if name is empty

### Districts Page (`/registration/districts`)

1. **Click "Add District"** button
2. **Enter District Name** (e.g., "Arusha City")
3. **Code auto-generates** in the Code field
4. **Select Parent Region**
5. **Code updates in real-time**
6. **Click "Add District"** to save

**Code Field Properties:**
- ✓ Read-only (cannot be manually edited)
- ✓ Auto-updates while typing name
- ✓ Shows format hint: "2 letters + 2 digits"
- ✓ Blank if name is empty

---

## Algorithm Details

### Step 1: Extract Letters
```
Input: "Arusha"
Output: "AR" (first 2 characters, uppercase)
```

### Step 2: Find Existing Codes
```
Existing regions: [AR01, AR02, DO01]
Prefix: "AR"
Matching codes: [AR01, AR02]
```

### Step 3: Calculate Next Number
```
Existing numbers: [1, 2]
Max number: 2
Next number: 3
Formatted: "03" (padded with zero)
```

### Step 4: Generate Code
```
Letters: "AR"
Number: "03"
Final Code: "AR03"
```

---

## Features

### ✅ Real-Time Generation
Code updates automatically as you type the name in real-time—no button click needed.

### ✅ Automatic Sequencing
The system automatically finds the next available number for the given prefix.

### ✅ Uniqueness Guaranteed
Each code is unique across all regions/districts. Sequential numbering prevents duplicates.

### ✅ No Manual Entry
Code field is read-only to prevent manual editing and ensure consistency.

### ✅ Smart Handling
- Blank name → Blank code
- Name with 1 letter → Blank code
- Name with 2+ letters → Code generated
- Special characters ignored (only first 2 letters used)

### ✅ Edit Mode Support
When editing an existing region/district:
- Code and name pre-populate
- Changing the name recalculates the code
- Original code is preserved if name doesn't change

---

## Edge Cases Handled

### Case 1: Name Too Short
```
Input: "A" (only 1 letter)
Code: (blank - waiting for more characters)
```

### Case 2: Name with Special Characters
```
Input: "Dar Es Salaam" (multiple words)
First 2 letters: "DA"
Code: "DA01" (only letters extracted)
```

### Case 3: Numbers in Name
```
Input: "Region 123"
First 2 letters: "RE"
Code: "RE01" (numbers in name ignored)
```

### Case 4: Duplicate Prefix
```
Existing: AR01, AR02
New name: "Arusha Extended"
New code: AR03 (automatically incremented)
```

---

## Technical Implementation

### Frontend (Alpine.js)

**Function: `generateRegionCode()`**

```javascript
generateRegionCode() {
    // 1. Get name from form
    const name = this.formData.name;
    
    // 2. Extract first 2 letters
    const letters = name.substring(0, 2).toUpperCase();
    
    // 3. Find existing codes with same prefix
    const existingCodes = this.regions
        .filter(r => r.code.startsWith(letters))
        .map(r => {
            const match = r.code.match(/\d+$/);
            return match ? parseInt(match[0]) : 0;
        });
    
    // 4. Calculate next number
    let nextNumber = 1;
    if (existingCodes.length > 0) {
        nextNumber = Math.max(...existingCodes) + 1;
    }
    
    // 5. Format and assign code
    this.formData.code = letters + String(nextNumber).padStart(2, '0');
}
```

### Trigger Point
The function is called:
- When opening the Add modal
- On every keystroke in the name field
- When opening the Edit modal

### Validation
- Code uniqueness is enforced at the database level
- Read-only field prevents manual tampering
- Format validation on backend

---

## Usage Workflow

### Creating Your First Regions

```
Step 1: Go to /registration/regions
Step 2: Click "Add Region"
Step 3: Type "Tanzania"
        → Code auto-generates: TA01
Step 4: Click "Add Region"
Step 5: Click "Add Region" again
Step 6: Type "Tanzania Mainland"
        → Code shows: TA02 (same prefix, next number)
Step 7: Click "Add Region"
Step 8: Add another region: "Kenya"
        → Code auto-generates: KE01
```

### Creating Districts Under Regions

```
Step 1: Go to /registration/districts
Step 2: Click "Add District"
Step 3: Type "Dar Es Salaam"
        → Code auto-generates: DA01
Step 4: Select Region: "Tanzania"
Step 5: Click "Add District"
Step 6: Click "Add District" again
Step 7: Type "Dodoma City"
        → Code auto-generates: DO01
Step 8: Select Region: "Tanzania"
Step 9: Click "Add District"
Step 10: Add another: "Dodoma Rural"
         → Code auto-generates: DO02
```

---

## Benefits

### ✅ Data Consistency
All codes follow the same format, making data clean and predictable.

### ✅ No Duplicates
Sequential numbering eliminates code duplication.

### ✅ User Efficiency
No need to manually create or manage codes—automatic generation saves time.

### ✅ Error Prevention
Read-only field prevents typos and formatting errors.

### ✅ Scalability
Works seamlessly as you add hundreds or thousands of regions/districts.

---

## Limitations & Considerations

### Code Space
- Maximum codes per prefix: 99 (01-99)
- If you need more than 99 regions starting with same 2 letters, they won't be auto-generated
- **Practical note**: Unlikely to have 99+ regions with identical first 2 letters

### Name Changes
- Editing a region/district name may change its code
- For example, changing "Arusha" to "Dodoma" changes code from "AR" to "DO"
- **Best practice**: Keep original names unchanged after creation

### Special Characters
- Only first 2 letters are extracted
- Numbers, spaces, and special characters are ignored
- "Dar-Es-Salaam" → "DA01" (uses D and E)

---

## FAQ

**Q: Can I manually change the code?**  
A: No, the code field is read-only for data consistency.

**Q: What if I want a custom code?**  
A: Currently, all codes are auto-generated. Consider changing the region/district name instead.

**Q: What if two names start with the same 2 letters?**  
A: Both will have the same letter prefix. The system automatically assigns sequential numbers (AR01, AR02, etc.).

**Q: How many codes can I create per prefix?**  
A: Up to 99 (01-99). If you need more, consider using different naming conventions.

**Q: Is the code saved to the database?**  
A: Yes, the auto-generated code is saved along with the region/district data.

**Q: Can I import data with custom codes?**  
A: The import process accepts codes as-is, but new regions/districts created via the UI will follow auto-generation rules.

---

## Migration from Manual Codes

If you have existing regions/districts with manual codes:

1. **Option 1: Keep Existing Codes**
   - Only new regions/districts will auto-generate
   - Existing codes remain unchanged

2. **Option 2: Regenerate All Codes**
   - Delete all existing regions/districts
   - Re-create them using the UI (auto-generates codes)

---

## Support

For issues or questions:
- Verify that the name field has at least 2 characters
- Check that the Code field shows the expected format (XX##)
- Ensure you're using the latest version of the Registration system

---

**Feature Status**: ✅ Active and Ready  
**Date Implemented**: January 26, 2026  
**Version**: 1.0
