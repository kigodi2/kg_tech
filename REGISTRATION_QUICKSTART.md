# Registration Management - Quick Start Guide

## 🚀 Getting Started

### Access the Registration System

1. **Start the Laravel server** (if not running):
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. **Open in browser**:
   ```
   http://localhost:8000/registration/dashboard
   ```

3. **Authenticate** (if required):
   - Login with your user credentials
   - You'll be redirected to the dashboard

---

## 📍 Navigation

### Main Dashboard
**URL**: `/registration/dashboard`
- Central hub for all registration data
- View statistics and quick actions
- See overview of all entities

### Regions
**URL**: `/registration/regions`
- Manage geographical regions
- Required first step before creating districts

### Districts  
**URL**: `/registration/districts`
- Create districts within regions
- Organize schools by district
- Requires regions to be created first

### Schools
**URL**: `/registration/schools`
- Create schools within districts
- Assign candidates to schools
- Requires regions and districts to be created first

### Candidates
**URL**: `/registration/candidates`
- Register examination candidates
- Assign candidates to schools
- Select exam type (PSLE, CSEE, ACSEE)

---

## 🎯 Common Tasks

### Task 1: Create a Region
1. Go to **Regions** page
2. Click **"Add Region"** button
3. Enter:
   - Region Code (e.g., `R001`)
   - Region Name (e.g., `Arusha`)
4. Click **"Add Region"**
5. Success message appears

### Task 2: Create a District
1. Go to **Districts** page
2. Click **"Add District"** button
3. Enter:
   - District Code (e.g., `D001`)
   - District Name (e.g., `Arusha City`)
   - Select Region
4. Click **"Add District"**

### Task 3: Create a School
1. Go to **Schools** page
2. Click **"Add School"** button
3. Enter:
   - School Code (e.g., `S001`)
   - School Name (e.g., `Arusha High School`)
   - Select Region (filters districts automatically)
   - Select District
4. Click **"Add School"**

### Task 4: Register a Candidate
1. Go to **Candidates** page
2. Click **"Register Candidate"** button
3. Enter:
   - First Name
   - Last Name
   - Email
   - Select School
   - Select Exam Type (PSLE/CSEE/ACSEE)
4. Click **"Register Candidate"**
5. Candidate ID is auto-generated

### Task 5: Edit a Record
1. Go to the relevant management page
2. Find the record in the table
3. Click the **Edit icon** (pencil)
4. Modify the fields
5. Click the **Update** button
6. Success message appears

### Task 6: Delete a Record
1. Go to the relevant management page
2. Find the record in the table
3. Click the **Delete icon** (trash)
4. Confirm deletion in the dialog
5. Record is removed

### Task 7: Bulk Delete Records
1. Go to **Schools** or **Candidates** page
2. Check the **checkbox** next to multiple records
3. Bulk actions bar appears at top
4. Click **"Delete Selected"**
5. Confirm the bulk deletion
6. All selected records are removed

### Task 8: Search and Filter
1. Use the **Search box** to find by name, code, or email
2. Results update in real-time
3. Use **Dropdown filters** to filter by region/school
4. Combine search + filter for precise results

### Task 9: Export Data
1. Go to the management page
2. (Optional) Filter/search to select specific records
3. Click **"Export CSV"** button
4. File downloads to your computer
5. Open in Excel or Google Sheets

### Task 10: Import Data
1. Go to the management page
2. Click **"Download Template"** to get CSV format
3. Fill in the template with data
4. Click **"Import CSV"** button
5. Select your CSV file
6. Records are imported and table updates

---

## 🔍 Search & Filter Examples

### Search for a Region
- Go to **Regions** page
- Type region name in search box
- Results filter instantly

### Search for a Candidate
- Go to **Candidates** page
- Type name, email, or ID in search
- Filter by school from dropdown
- Results update in real-time

### Filter Schools by Region
- Go to **Schools** page
- Select a region from dropdown
- Only schools in that region show
- Can combine with search

---

## 💡 Tips & Tricks

### Cascading Dropdowns
When creating a school, select the region first. The district dropdown will automatically update to show only districts in that region.

### Auto-Generated IDs
Candidate IDs are automatically generated as `CAND-XXXXX`. No need to enter manually.

### Exam Types
Choose from three exam types:
- **PSLE** - Primary Level (Grade 6)
- **CSEE** - Form 4 (Secondary level)
- **ACSEE** - Form 6 (Advanced level)

### Bulk Operations
Use checkboxes to select multiple items quickly. Perfect for bulk deletions or future batch operations.

### Empty States
If a page shows "No items found", it means no data exists. Click the suggestion link to add the first item.

### Notifications
- **Green** = Success message
- **Red** = Error message
- Messages auto-dismiss after 3 seconds

---

## ⚠️ Important Notes

### Deletion is Permanent
Deleted records cannot be recovered. Confirm carefully before deleting.

### Unique Constraints
- Region/District/School codes must be unique
- Candidate emails must be unique
- System will prevent duplicates

### Dependencies
You cannot delete:
- A region that has districts
- A district that has schools
- A school that has candidates

(This is to maintain data integrity)

### CSV Import Format
Headers must match exactly:
- Regions: `Code, Name`
- Districts: `Code, Name, Region ID`
- Schools: `Code, Name, Region ID, District ID`
- Candidates: `First Name, Last Name, Email, School ID, Exam Type`

---

## 📊 Dashboard Metrics

The dashboard shows:
- **Total Regions** - Number of regions created
- **Total Districts** - Number of districts created
- **Total Schools** - Number of schools created
- **Total Candidates** - Number of candidates registered

Plus:
- Recent regions list
- Recent districts list
- Candidates breakdown by exam type
- Average statistics

---

## 🔗 API Access

### For Developers

All operations are powered by REST APIs:

```
GET    /api/regions              - List all regions
POST   /api/regions              - Create region
PUT    /api/regions/{id}         - Update region
DELETE /api/regions/{id}         - Delete region

GET    /api/districts            - List all districts
POST   /api/districts            - Create district
PUT    /api/districts/{id}       - Update district
DELETE /api/districts/{id}       - Delete district

GET    /api/schools              - List all schools
POST   /api/schools              - Create school
PUT    /api/schools/{id}         - Update school
DELETE /api/schools/{id}         - Delete school

GET    /api/candidates           - List all candidates
POST   /api/candidates           - Register candidate
PUT    /api/candidates/{id}      - Update candidate
DELETE /api/candidates/{id}      - Delete candidate
```

**Headers Required**:
- `Content-Type: application/json`
- `X-CSRF-TOKEN: <csrf-token>`

---

## 🆘 Troubleshooting

### Changes not showing?
- Clear browser cache (Ctrl+F5)
- Clear Laravel cache: `php artisan cache:clear`
- Clear views: `php artisan view:clear`

### Form not submitting?
- Check browser console for errors
- Ensure all required fields are filled
- Verify CSRF token is present

### Import not working?
- Check CSV format matches template exactly
- Ensure proper headers
- Verify foreign keys exist (regions, schools, etc.)

### Page shows old data?
- Refresh the page
- Close and reopen browser
- Clear application cache

---

## 📞 Need Help?

Refer to:
- `REGISTRATION_CRUD_IMPLEMENTATION.md` - Complete feature documentation
- `REGISTRATION_FEATURES_CHECKLIST.md` - Full feature list
- Laravel logs: `storage/logs/laravel.log`
- Browser console: F12 → Console tab

---

## ✅ Initial Setup Checklist

When starting fresh:

- [ ] Create at least 1 region
- [ ] Create districts under your regions
- [ ] Create schools under your districts
- [ ] Start registering candidates
- [ ] Verify dashboard statistics update
- [ ] Test export functionality
- [ ] Test search and filtering
- [ ] Create a backup (export all data)

---

**Happy Data Management!** 🎉
