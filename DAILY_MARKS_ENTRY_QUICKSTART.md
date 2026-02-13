# Daily Marks Entry Report - Quick Start Guide

## 🚀 Quick Overview

The Daily Marks Entry Report tracks how many exam answer scripts have been marked each day for each subject at the regional level. It shows:
- **S/N**: Serial number
- **SUBJECT**: Subject name  
- **EXPECTED SCRIPTS**: Total scripts that should be marked
- **MARKED DAY 1-5**: Count and % marked for each weekday
- **REMAINDER**: Scripts marked outside normal workdays
- **REMARKS**: Status (On Track, In Progress, etc.)

## 📍 How to Access

1. Go to **Evaluations** page (left navigation)
2. Expand **ENTRY REPORT** section
3. Expand **REGIONAL LEVEL** subsection
4. Click **SUBJECTS**

**Direct URL**: `http://127.0.0.1:8000/evaluations/acsee`

Then navigate through the sidebar menu.

## 🎛️ How to Filter

The report updates automatically when you change any filter:

| Filter | Options | Effect |
|--------|---------|--------|
| **Exam Year** | Dropdown | Shows data for selected exam year |
| **Region** | Dropdown | Shows only data from selected region |
| **Subject** | Dropdown | Shows only data for selected subject |
| **Entry Date** | Date picker | Shows only data entered on that date |

**Leave blank to see all data for that dimension**

### Filter Examples

| Scenario | Settings | Result |
|----------|----------|--------|
| All data | Leave all empty | All subjects, regions, years |
| One region | Region: "Dar" | All subjects in Dar, all years |
| One year | Exam Year: "2025" | All subjects, all regions in 2025 |
| One subject | Subject: "Math" | Math marks only, all regions/years |
| Specific day | Entry Date: "2025-02-12" | Only marks entered on Feb 12 |
| Multi-filter | Year: "2025", Region: "Dar" | Only Dar data for 2025 |

## 📊 Understanding the Data

### Expected Scripts
- Number of candidates registered to take that subject in the region
- Used as denominator for percentage calculations
- Set once, should match total candidates

### Marked Day Columns
- **Count**: How many scripts marked on that day (Mon-Fri)
- **%**: Percentage of expected scripts (Count ÷ Expected × 100)

### Remainder Column
- Scripts marked on weekends/holidays (Saturday, Sunday)
- Should ideally be 0 if marking only during workweek

### Remarks
- Auto-generated status based on completion %:
  - **Marking Complete**: 100%+ done
  - **On Track**: 75-100% done
  - **In Progress**: 50-75% done
  - **Slow Progress**: 1-50% done
  - **Not Started**: 0% done

## 💾 Export & Print

### Export to CSV
1. Set your filters (optional)
2. Click **[Export CSV]** button
3. File downloads: `daily-marks-entry-report-2025-02-12.csv`
4. Open in Excel, Google Sheets, or text editor

### Print Report
1. Set your filters (optional)
2. Click **[Print]** button
3. Print preview opens in new window
4. System print dialog appears
5. Select printer and print

**Tip**: Use landscape orientation for better table fit on paper

## ✅ What's Working

- ✓ Real-time filtering across 4 dimensions
- ✓ Automatic percentage calculations
- ✓ Status remarks generation
- ✓ CSV export with proper formatting
- ✓ Print-friendly layout
- ✓ Mobile responsive design
- ✓ Admin-level security
- ✓ No data/empty state handling

## 🔍 Troubleshooting

### "No data available" message appears
**Possible causes**:
- No marks have been entered for selected filters
- Wrong exam year selected
- Wrong region selected
- Date has no entries

**Solution**: Change filters to see if data exists for other selections

### Table doesn't update when I change filters
**Possible causes**:
- Network issue preventing API call
- JavaScript error in console

**Solution**: 
1. Refresh page (F5)
2. Check browser console for errors (F12)
3. Ensure you're logged in as admin

### Export CSV looks wrong in Excel
**Possible causes**:
- Excel interpreting dates as numbers
- Column width too narrow

**Solution**:
1. Right-click column → Format Cells → Text
2. Double-click column border to auto-fit width

### Print preview is blank
**Possible causes**:
- Page still loading
- Pop-up blocked by browser

**Solution**:
1. Wait 2-3 seconds for preview to load
2. Allow pop-ups from this site
3. Use Print button again

## 📈 Common Use Cases

### 1. Check daily progress
```
→ Leave all filters empty
→ View complete overview
→ Check remarks column for status
```

### 2. Monitor one subject
```
→ Select Subject: "Mathematics"
→ Leave others empty
→ See daily breakdown for Math only
```

### 3. Regional comparison
```
→ Select Region: "Dar es Salaam"
→ See all subjects in that region
→ Compare progress across subjects
```

### 4. End-of-day summary
```
→ Set Entry Date: [Today's date]
→ See only today's entries
→ Export for manager
```

### 5. Exam year analysis
```
→ Select Exam Year: "2025"
→ View all 2025 marking progress
→ Plan remaining marking activities
```

## 🔐 Security Notes

- ✓ Requires login (authentication)
- ✓ Requires admin role
- ✓ Data filtered by user permissions
- ✓ API endpoint protected with middleware
- ✓ No sensitive data in export

## 📱 Mobile & Browser Support

**Desktop**: Full functionality
**Tablet**: Mostly works, table may require horizontal scrolling
**Mobile**: Functional with reduced table visibility

**Browsers**:
- ✓ Chrome/Edge 88+
- ✓ Firefox 87+
- ✓ Safari 14+
- ? IE 11 (not tested)

## 🎯 Best Practices

1. **Filter before export** - Reduces file size
2. **Check remarks** - Quickly assess progress
3. **Print mid-day** - Share status with team
4. **Review Day 5** - Watch for delayed marking
5. **Monitor remainder** - Investigate off-schedule marking

## 💡 Tips & Tricks

### Tip 1: Quick full view
Leave all filters empty to see complete picture

### Tip 2: Single subject tracking
Select one subject to drill down on marking progress

### Tip 3: Date-specific analysis
Use Entry Date filter to see patterns by day of week

### Tip 4: Export for meetings
Export CSV before management meetings to have latest data

### Tip 5: Print for record keeping
Print daily snapshots for audit trail

## 📞 Support

**If something doesn't work**:
1. Refresh the page (Ctrl+F5 to clear cache)
2. Check you're logged in as admin
3. Clear browser cookies if persistent issues
4. Check browser console (F12) for errors

**Common errors**:
- `404 Not Found` → API endpoint issue (deployment problem)
- `401 Unauthorized` → Not logged in or not admin
- `500 Server Error` → Database/backend issue

## 🚀 Next Steps

1. **Test the feature** following above instructions
2. **Export a sample** to verify format
3. **Print a report** to check layout
4. **Share with users** who need daily tracking
5. **Gather feedback** for improvements

---

**Version**: 1.0  
**Last Updated**: Feb 12, 2025  
**Status**: Production Ready
