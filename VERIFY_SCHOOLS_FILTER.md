# Verify Schools Filter Fix

## Quick Test

### Test via Browser Console

Open your Schools Management page and test in browser console:

```javascript
// Test 1: Get all schools (no filters)
fetch('/api/schools?page=1&page_size=100')
    .then(r => r.json())
    .then(d => console.log('All schools:', d.pagination.total_count))

// Test 2: Get schools in region 1 (MOROGORO)
fetch('/api/schools?page=1&page_size=100&region_id=1')
    .then(r => r.json())
    .then(d => console.log('Schools in region 1:', d.pagination.total_count))

// Test 3: Get schools in district 53 (GAIRO DC)
fetch('/api/schools?page=1&page_size=100&district_id=53')
    .then(r => r.json())
    .then(d => console.log('Schools in district 53:', d.pagination.total_count))

// Test 4: Get schools in region 1 AND district 53
fetch('/api/schools?page=1&page_size=100&region_id=1&district_id=53')
    .then(r => r.json())
    .then(d => console.log('Schools in region 1, district 53:', d.pagination.total_count, d.data))
```

### Expected Results

```
All schools: 8
Schools in region 1: 3
Schools in district 53: 3
Schools in region 1, district 53: 3
```

---

## Test via PHP

```bash
php artisan tinker << 'EOF'
// Get schools by district
$district53Schools = App\Models\School::where('district_id', 53)->count();
echo "Schools in district 53: " . $district53Schools . "\n";

// Get schools by region
$region1Districts = App\Models\District::where('region_id', 1)->pluck('id');
$region1Schools = App\Models\School::whereIn('district_id', $region1Districts)->count();
echo "Schools in region 1: " . $region1Schools . "\n";

// List all schools with their district info
App\Models\School::with('district')->get()->each(function($s) {
    echo $s->code . " → " . $s->name . " (District: " . ($s->district->name ?? 'N/A') . ")\n";
});
EOF
```

---

## Manual Testing Steps

### Step 1: Open Schools Management
```
URL: http://your-site/registration/schools
```

### Step 2: Test Region Filter
1. Click Region dropdown
2. Select "IRINGA"
3. Keep District as "All Districts"
4. Verify: Only 1-2 schools show (those in IRINGA region)

### Step 3: Test District Filter
1. Click Region dropdown again
2. Select "MOROGORO"
3. Click District dropdown
4. Select "GAIRO DC"
5. Verify: Only schools in GAIRO DC show (subset of MOROGORO)

### Step 4: Clear Filters
1. Click Region dropdown
2. Select "All Regions"
3. Click District dropdown
4. Select "All Districts"
5. Verify: All schools display again

### Step 5: Search with Filters
1. Select Region: "MOROGORO"
2. Select District: "GAIRO DC"
3. Type in Search: "Dar"
4. Verify: Only matching schools in that district show

---

## Check Database

Verify your schools are properly linked to districts:

```bash
php artisan tinker << 'EOF'
$schools = App\Models\School::all();
foreach ($schools as $s) {
    $district = App\Models\District::find($s->district_id);
    $region = App\Models\Region::find($district->region_id);
    echo $s->code . " → District: " . $district->code . " → Region: " . $region->code . "\n";
}
EOF
```

Should show each school linked to a district and region.

---

## If Filter Still Not Working

1. **Clear browser cache** - Ctrl+Shift+Delete (Chrome/Firefox)
2. **Hard refresh** - Ctrl+F5
3. **Check browser console** - F12 → Console tab for errors
4. **Verify route was updated** - Check `routes/web.php` line 289 has `$districtId = request('district_id', '');`
5. **Check PHP errors** - Check `storage/logs/laravel.log`

---

## Confirm Fix Applied

Run this to verify the code change is in place:

```bash
grep -A 5 "Filter by district" routes/web.php
```

Should show:
```
// Filter by district if specified (takes precedence over region)
if ($districtId) {
    $query->where('district_id', $districtId);
}
```

If you see this code, the fix is applied ✅

---

## Success Indicators

✅ Region filter alone works
✅ District filter works
✅ Region + District together work
✅ Clearing filters shows all schools
✅ Search works with filters
✅ Pagination works with filters
✅ No errors in browser console
✅ Correct school counts displayed

If all indicators are ✅, the fix is working perfectly!

