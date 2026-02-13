# Quick Import Command - Copy & Paste Ready

## Option 1: Fix CSV with PHP Script (Fastest)

```bash
cd /home/prosmart-technologies/SOL/irms

# Run the script
php fix_csv.php candidates.csv candidates_fixed.csv
```

**What happens:**
- Reads your `candidates.csv` 
- Replaces all `S0108` with `DSM001`
- Creates `candidates_fixed.csv`

**Result**: File is ready to import via web UI

---

## Option 2: Use PHP Artisan to Fix & Import (No Web UI)

```bash
cd /home/prosmart-technologies/SOL/irms

# Copy this entire command (paste all at once)
php artisan tinker << 'EOF'
// Get CSV file path
$csvPath = 'candidates.csv';

if (!file_exists($csvPath)) {
    echo "❌ File not found: $csvPath\n";
} else {
    $handle = fopen($csvPath, 'r');
    $header = fgetcsv($handle);
    
    $count = 0;
    $errors = [];
    
    echo "📂 Importing candidates...\n";
    
    while (($row = fgetcsv($handle)) !== false) {
        if (empty(array_filter($row))) continue;
        
        try {
            $candidateId = trim($row[0]) ?: null;
            $fullName = trim($row[1] ?? '');
            $gender = strtoupper(trim($row[2] ?? ''));
            $combination = trim($row[3] ?? '') ?: null;
            $schoolCode = trim($row[4] ?? '');
            $examType = strtoupper(trim($row[5] ?? ''));
            
            // Find school
            $school = \App\Models\School::where('code', $schoolCode)->first();
            if (!$school) {
                $errors[] = "School code '$schoolCode' not found - using DSM001";
                $school = \App\Models\School::where('code', 'DSM001')->first();
            }
            
            if (!$school) {
                $errors[] = "Neither '$schoolCode' nor DSM001 found";
                continue;
            }
            
            // Auto-generate ID if not provided
            if (empty($candidateId)) {
                $candidateCount = \App\Models\Candidate::count() + $count + 1;
                $candidateId = 'CAND-' . str_pad($candidateCount, 6, '0', STR_PAD_LEFT);
            }
            
            // Create or update candidate
            \App\Models\Candidate::updateOrCreate(
                ['candidate_id' => $candidateId],
                [
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'combination' => $combination,
                    'school_id' => $school->id,
                    'exam_type' => $examType,
                    'status' => 'registered'
                ]
            );
            $count++;
            
        } catch (\Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    echo "\n✅ Import Complete!\n";
    echo "   Imported: $count candidates\n";
    if (count($errors) > 0) {
        echo "   Warnings: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
    }
}
EOF
```

**What happens:**
- Reads your CSV file
- Auto-replaces missing schools with DSM001
- Creates all candidates
- Shows count of imported records

**Result**: Candidates imported directly to database

---

## Option 3: One-Line Fix (If Using Bash)

```bash
cd /home/prosmart-technologies/SOL/irms && \
sed 's/S0108/DSM001/g' candidates.csv > candidates_fixed.csv && \
echo "✅ Fixed CSV created: candidates_fixed.csv"
```

Then import `candidates_fixed.csv` via web UI.

---

## Which to Use?

| Option | Time | Difficulty | Result |
|--------|------|-----------|--------|
| **Option 1** | 1 min | Very easy | CSV file ready to import |
| **Option 2** | 2 min | Easy | Direct DB import, no web UI |
| **Option 3** | 30 sec | Linux users | CSV file ready to import |

---

## Step-by-Step for Option 1 (Easiest)

1. **Place your CSV file** in the project root:
   ```
   /home/prosmart-technologies/SOL/irms/candidates.csv
   ```

2. **Open terminal/command line**

3. **Navigate to project**:
   ```bash
   cd /home/prosmart-technologies/SOL/irms
   ```

4. **Run the fixer**:
   ```bash
   php fix_csv.php candidates.csv candidates_fixed.csv
   ```

5. **Wait for output** showing replacements made

6. **Go to IRMS web interface**
   - REGISTRATION > Candidates
   - Click Tools > Import CSV
   - Select `candidates_fixed.csv`
   - Click Import
   - ✅ Done!

---

## Step-by-Step for Option 2 (Direct Import)

1. **Place your CSV file** in project root

2. **Open terminal**

3. **Navigate to project**:
   ```bash
   cd /home/prosmart-technologies/SOL/irms
   ```

4. **Paste the entire tinker command** (above) and press Enter

5. **Wait for completion message**

6. **Check IRMS web interface** - candidates should appear

---

## Troubleshooting

### "File not found"
```bash
# Make sure file is in the right place
ls -la /home/prosmart-technologies/SOL/irms/candidates.csv

# If not found, provide full path to fix_csv.php
php fix_csv.php /path/to/your/candidates.csv output.csv
```

### "Permission denied"
```bash
# Make PHP script executable
chmod +x fix_csv.php

# Then run it
./fix_csv.php candidates.csv candidates_fixed.csv
```

### "Still getting errors after import"
Check the CSV format matches exactly:
```
✓ Exactly 6 columns
✓ Column 5 uses DSM001, DSM002, MGO001, MGO002, or MGO003
✓ No completely blank rows
✓ Name column (2) is not empty
```

---

## After Import Succeeds

Once candidates are imported:

1. **Verify in web interface**:
   - Go to REGISTRATION > Candidates
   - Should see candidates listed

2. **Next steps**:
   - Assign to exam registrations
   - Enter marks
   - Generate results

All set!

---

## Need Help?

If anything fails:
1. Share the exact error message
2. Confirm file location and format
3. Let me know which option you used

I can debug from there.
