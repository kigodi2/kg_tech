# Districts Import Guide

## How to Import Districts

### Step 1: Download Template
1. Go to **Registration → Districts**
2. Click **Tools** → **CSV Template**
3. This downloads the correct CSV format

### Step 2: Prepare Your Data
The CSV file must have exactly 3 columns in this order:

| Column 1 | Column 2 | Column 3 |
|----------|----------|----------|
| CODE | NAME | REGION |

**Example:**
```csv
CODE,NAME,REGION
IR0701,IRINGA MC,IRINGA
IR0702,IRINGA DC,IRINGA
IR0703,KILOLO DC,IRINGA
MT0801,MTWARA URBAN,MTWARA
MT0802,MTWARA RURAL,MTWARA
```

### Column Details

**CODE** (Required)
- Unique district code (e.g., IR0701, MT0801)
- No duplicates allowed
- Maximum 20 characters

**NAME** (Required)
- Full district name (e.g., IRINGA MC, MTWARA URBAN)
- Maximum 255 characters

**REGION** (Required)
- Must match exactly with an existing region name in the system
- Region must exist before importing districts
- Examples: IRINGA, MTWARA, MOROGORO, DODOMA
- **Case-sensitive**: Must match exactly

### Step 3: Upload File
1. Go to **Registration → Districts**
2. Click **Tools** → **Import CSV**
3. Select your prepared CSV file
4. Wait for confirmation message

### Success Message
After import, you'll see a message like:
- ✅ `5 district(s) imported successfully`
- ⚠️ `3 district(s) imported successfully, 2 failed`

### Common Issues

#### Issue: "0 districts imported"
**Cause:** CSV format incorrect or regions not found

**Solution:**
1. Verify the CSV has exactly 3 columns
2. Check that all region names match existing regions
3. Ensure no empty rows in the CSV
4. Make sure CODE and NAME columns are not empty

#### Issue: "Region 'X' not found"
**Cause:** The region name in your CSV doesn't match a region in the system

**Solution:**
1. First import all Regions (if needed)
2. Check exact spelling of region names in system
3. Go to **Registration → Regions** to see valid region names
4. Update your CSV to match exactly (including spaces and case)

#### Issue: "District 'X' already exists"
**Cause:** A district with the same code already exists

**Solution:**
- The system will update the existing district instead of creating a duplicate
- Check if the district in the system needs updating
- Or use a different code for the new district

### Validation Rules

✅ **Valid CSV:**
```csv
CODE,NAME,REGION
IR0701,IRINGA MC,IRINGA
IR0702,IRINGA DC,IRINGA
```

❌ **Invalid - Region not found:**
```csv
CODE,NAME,REGION
IR0701,IRINGA MC,UNKNOWN_REGION
```

❌ **Invalid - Missing columns:**
```csv
CODE,NAME
IR0701,IRINGA MC
```

❌ **Invalid - Empty rows:**
```csv
CODE,NAME,REGION
IR0701,IRINGA MC,IRINGA

IR0702,IRINGA DC,IRINGA
```

### After Import

1. **Verify:** Check Districts table to see imported districts
2. **Filter by Region:** Select region filter to verify districts are linked correctly
3. **Check Count:** Look for school and candidate counts (may take a moment to calculate)

### Bulk Operations

After importing, you can:
- ✅ Add schools to districts
- ✅ Register candidates under district schools
- ✅ Edit district details
- ✅ Delete districts (with bulk delete option)

### Tips

- 📥 **Update existing:** If you import a district with an existing code, it will update that district
- 🗂️ **Organize data:** Sort your CSV by region for easier verification
- 📋 **Template:** Always start with the downloaded template to ensure correct format
- 💾 **Backup:** Keep a copy of your imported data in case you need to re-import

### Example Workflow

1. **Step 1:** Import Regions
   ```
   Region Management → Tools → Import CSV
   ```

2. **Step 2:** Import Districts (one region at a time)
   ```
   District Management → Tools → Import CSV
   ```

3. **Step 3:** Import Schools
   ```
   School Management → Tools → Import CSV
   ```

4. **Step 4:** Register Candidates
   ```
   Candidates → Register Candidate (or bulk import)
   ```

## API Information

**Endpoint:** `POST /api/districts/import`

**Request:**
- Form data with 'file' parameter containing CSV

**Response:**
```json
{
  "message": "Imported 5 district(s)",
  "count": 5,
  "failed": 0,
  "errors": []
}
```

**Error Response:**
```json
{
  "message": "Import error: No file provided",
  "status": 400
}
```

## Troubleshooting

### Check Browser Console
1. Open Browser DevTools (F12)
2. Go to **Console** tab
3. Look for any error messages
4. Copy the error and report if needed

### Check System Logs
```bash
tail -f /path/to/laravel.log
```

### Verify Regions Exist
1. Go to **Registration → Regions**
2. Check that your region names match exactly
3. Add missing regions if needed before importing districts

## Support

If import still fails:
1. Download fresh template
2. Ensure regions are created first
3. Check CSV formatting with a text editor
4. Verify column order: CODE, NAME, REGION
5. Ensure no special characters in CSV

## Status
✅ Districts import endpoint implemented
✅ Error handling and validation added
✅ Clear user feedback messages
✅ Supports both create and update
