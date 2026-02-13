# Candidates Management - Quick Start Guide

## 📱 Access the Module

Visit: `http://127.0.0.1:8000/registration/candidates`

---

## 🚀 Quick Actions

### Add a Candidate
1. Click **"Register Candidate"** button (top right)
2. Fill in form fields (auto-focused on First Name)
   - First Name (required)
   - Last Name (required)
   - Email (required)
   - School (required, select from dropdown)
   - Exam Type (required)
3. Click **"Register Candidate"** button
4. Success notification appears, data reloads

### View Candidate
1. Click the **eye icon** (👁) in the Actions column
2. Read-only modal opens showing all details
3. Click **"Edit"** to make changes
4. Click **"Close"** to close modal

### Edit Candidate
1. Click the **edit icon** (✏️) in the Actions column
2. Form opens with pre-filled data
3. Modify any fields
4. Click **"Update Candidate"** button
5. Success notification appears

### Delete Candidate
1. Click the **trash icon** (🗑) in the Actions column
2. Confirmation dialog appears
3. Click **"OK"** to confirm or **"Cancel"** to abort
4. Success notification appears, data reloads

---

## 🔍 Search & Filter

### Search
- Type in the **search box** (top left)
- Searches: name, email, candidate ID
- Real-time filtering
- Supports partial matches

### Filter by School
- Select a school from the **dropdown** (top left)
- Shows only candidates from that school
- Combine with search for narrow results

---

## 📊 Bulk Operations

### Select Multiple Candidates
1. Click **checkboxes** on table rows
2. Selection count displays in blue bar
3. Click **header checkbox** to select all visible

### Bulk Delete
1. Select multiple candidates
2. Click **"Delete Selected"** button (red)
3. Confirmation dialog shows count
4. Click **"OK"** to confirm
5. All selected candidates deleted

---

## 📁 CSV Operations

### Download Template
1. Click **"Tools"** button (top right)
2. Click **"CSV Template"**
3. CSV file downloads with column headers
4. Use as template for bulk import

### Export CSV
1. Click **"Tools"** button
2. Click **"Export CSV"**
3. Current filtered data exports to CSV
4. Includes: ID, Name, Email, School, Exam Type

### Import CSV
1. Click **"Tools"** button
2. Click **"Import CSV"**
3. Select CSV file from your computer
4. File processes and data imports
5. Success notification shows count

**Expected CSV Format:**
```
Candidate ID,First Name,Last Name,Email,School ID,Exam Type
CAND-001,John,Doe,john@example.com,1,KCSE
CAND-002,Jane,Smith,jane@example.com,2,CSEE
```

---

## ✨ Smart Features

### Auto-Focus
- When adding candidate, cursor auto-focuses on First Name field
- No need to click field before typing

### Auto-ID Generation
- Candidate ID auto-generated if not provided
- Format: `CAND-XXXXXX`

### Form Validation
- Required fields marked with *
- Email format validated
- School must exist
- Duplicate checking

### Real-Time Feedback
- Green notification = success
- Red notification = error
- Auto-dismisses after 4 seconds

---

## 📋 Table Columns

| Column | Description |
|--------|-------------|
| Checkbox | Select for bulk operations |
| ID | Auto-generated candidate ID |
| Full Name | First and Last Name |
| Email | Email address |
| School | School name (from relationship) |
| Exam Type | KCSE, CSEE, ACSEE, etc. |
| Status | registered / pending |
| Actions | View, Edit, Delete buttons |

---

## 🛠️ Tools Dropdown

Located in top-right toolbar:

| Tool | Function |
|------|----------|
| CSV Template | Download headers for import |
| Import CSV | Upload CSV file with candidates |
| Export CSV | Download filtered candidates |

---

## 🔒 Security Features

- **CSRF Protection**: All requests include CSRF token
- **Confirmation Dialogs**: Required for destructive actions
- **Validation**: Server-side validation on all inputs
- **Error Handling**: Safe error responses with messages

---

## ⚡ Keyboard Shortcuts

- **Esc** (in modal): Close modal
- **Enter** (in form): Submit form
- **Tab**: Navigate between fields

---

## 📱 Responsive Design

- **Desktop**: Full-width table with all features
- **Tablet**: Responsive table with scrolling
- **Mobile**: Stacked layout (optimized for touch)

---

## 🔧 Troubleshooting

### "Modal doesn't close after save"
- Verify internet connection
- Check browser console for errors
- Refresh page and try again

### "Search results empty"
- Verify search term is correct
- Check if candidates exist in selected school
- Clear filters and try again

### "CSV import shows 0 records"
- Verify CSV headers match expected format
- Check candidate IDs aren't duplicates
- Ensure all required fields are filled

### "Bulk delete only deletes one candidate"
- Ensure using the bulk-delete button
- Not individual delete icons
- Select multiple candidates first

---

## 📊 Performance Tips

1. **Large datasets**: Use filters to reduce results
2. **Bulk operations**: Use bulk delete instead of individual deletes
3. **CSV import**: Keep files under 10MB
4. **Search**: Be specific for faster results

---

## 🔄 Data Flow

```
┌─────────────────┐
│  Candidates List│  (View all with pagination)
├─────────────────┤
│  Filter/Search  │  (Server-side filtering)
├─────────────────┤
│  Select Items   │  (Multi-select with checkboxes)
├─────────────────┤
│  CRUD/Bulk Ops  │  (Create, Read, Update, Delete)
├─────────────────┤
│  CSV Operations │  (Import, Export, Template)
└─────────────────┘
```

---

## 🎯 Common Workflows

### Workflow 1: Add Multiple Candidates
1. Click "Download Template"
2. Fill in CSV file with candidate data
3. Click "Import CSV"
4. Select your file
5. Done! All candidates added

### Workflow 2: Update School Filter
1. Select school from dropdown
2. All candidates from that school display
3. Edit as needed
4. Changes apply in real-time

### Workflow 3: Cleanup Duplicate Data
1. Search for potential duplicates
2. Select duplicates with checkboxes
3. Click "Delete Selected"
4. Confirm deletion
5. Duplicates removed

### Workflow 4: Backup Data
1. Click "Export CSV"
2. Choose optional filters
3. CSV downloads to computer
4. Store safely as backup

---

## 📞 Support

### Documentation
- `CANDIDATES_API_IMPLEMENTATION.md` - API reference
- `CANDIDATES_IMPLEMENTATION_STUDY.md` - Architecture details
- `PATTERN_REFERENCE.md` - Code examples

### If Something Breaks
1. Check browser console (F12 → Console tab)
2. Look for error messages
3. Try refreshing the page
4. Check internet connection
5. Report error with screenshot

---

## ✅ Verification Checklist

After first use, verify:

- [x] Can add new candidate
- [x] Can view candidate details
- [x] Can edit candidate
- [x] Can delete candidate
- [x] Can bulk delete
- [x] Can search
- [x] Can filter by school
- [x] Can export CSV
- [x] Can import CSV
- [x] Notifications display
- [x] Errors show messages

---

## 🎓 Best Practices

1. **Always confirm** before deleting
2. **Use filters** to reduce data volume
3. **Backup data** before bulk operations
4. **Verify imports** before bulk deleting old data
5. **Use templates** for consistent data format
6. **Search first** before adding to avoid duplicates

---

## 🚀 Ready to Use!

The Candidates Management module is fully functional and ready for:
- ✅ Daily operations
- ✅ Bulk imports
- ✅ Data management
- ✅ Reporting (via export)

**Happy managing! 🎉**

