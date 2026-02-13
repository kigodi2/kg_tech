# ACSEE Candidates Page - Quick Reference

## Access
```
URL: http://localhost:8001/exam-types/acsee
Click: CANDIDATES (left sidebar)
```

## What's Displayed
| Column | Data Source |
|--------|-------------|
| Index Number | candidate_id from candidates |
| Full Name | full_name from candidates |
| Sex | gender from candidates |
| Combination | combination code from candidates |
| **Allocated Subjects** ✨ | Subject codes from combination→subjects |
| School | From school relationship |
| District | From district relationship |
| Region | From region relationship |
| Status | From candidates |
| Actions | View, Edit, Delete |

## Features
- 🔍 Search by Index Number or Name
- 🌍 Filter by Region
- 📊 Export to CSV
- 📥 Import CSV
- ➕ Add Candidate
- ✏️ Edit Candidate
- 🗑️ Delete / Bulk Delete
- 📄 Download Template

## Technical Details
- **View**: `resources/views/exam-types/show.blade.php` (lines 367-510)
- **Controller**: `ExamTypeController::getAcseeCandicates()` (lines 345-412)
- **API**: `GET /api/exam-types/acsee/candidates`
- **Data Source**: registration/candidates where exam_type = 'ACSEE'
- **Subjects Source**: combination → combination_subject → subjects

## Example Output
```
S6754-0675 | AGREY JOHN KIGODI | M | PCM | PHY, CHE, MAT | School Name | District | Region | registered
```

## Recent Changes
✨ Added "Allocated Subjects" column showing subject codes from combinations

---

Done! Just view the page and you'll see it working.
