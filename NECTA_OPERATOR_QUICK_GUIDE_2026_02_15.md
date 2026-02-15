# NECTA-Aligned ACSEE Operator Quick Guide
**Date**: 2026-02-15  
**Audience**: IRMS Operators & Administrators  

---

## What's New?

A new feature for NECTA-aligned ACSEE registration and subject allocation is now live. It supports two types of candidates:
- **SCHOOL candidates**: Use existing combination templates
- **PRIVATE candidates**: Manually select individual subjects

---

## Quick Reference

### SCHOOL Candidate Workflow
1. Click **Add Candidate**
2. Select **SCHOOL** as Candidate Type
3. Choose a **Combination** (e.g., "Science", "Commerce")
4. Fill other required fields
5. Click **Save**
6. System automatically allocates subjects based on combination

### PRIVATE Candidate Workflow
1. Click **Add Candidate**
2. Select **PRIVATE** as Candidate Type
3. Leave **Combination field blank** (optional)
4. Fill other required fields
5. Click **Save**
6. Click **Allocate Subjects** (or use Allocation modal)
7. Manually select subjects:
   - General Studies (111) is required
   - Select at least 3 principal subjects
   - Optional: Add elective subjects
8. Click **Apply**

---

## Important Rules

✅ **Required**:
- General Studies (code 111) must be allocated to every candidate
- At least 3 principal subjects must be selected

❌ **Not Allowed**:
- Duplicate subject allocations (system prevents this)
- Missing General Studies
- Fewer than 3 principal subjects

---

## Troubleshooting

### "Invalid allocation" error
**Cause**: Missing General Studies or too few principal subjects  
**Fix**: 
- Ensure General Studies (111) is selected
- Ensure at least 3 principal subjects are selected
- Try again

### "Combination field should not be empty for SCHOOL candidates"
**Cause**: Selected SCHOOL type but didn't choose a combination  
**Fix**: Select a combination from the dropdown, or switch to PRIVATE type

### Subjects not showing in modal
**Cause**: Database connection issue or form validation  
**Fix**:
- Refresh the page
- Check browser console for errors
- Contact IT support if persists

### Old candidates missing subjects
**Cause**: Pre-deployment data (expected behavior)  
**Fix**: No action needed. Old candidates retain their original allocations.

---

## When to Use Each Type

| Aspect | SCHOOL | PRIVATE |
|--------|--------|---------|
| **Registration** | Via school system | Self/external registration |
| **Combination** | Required | Optional |
| **Subject Selection** | Automatic (template) | Manual |
| **Flexibility** | Low (fixed by template) | High (any subjects) |
| **Use Case** | Regular school candidates | Independent/external students |

---

## Common Tasks

### Task: Register a NECTA-aligned school candidate
1. Navigate to **Candidates** > **Add Candidate**
2. Fill: Name, Registration #, District, School
3. **Candidate Type**: Select **SCHOOL**
4. **Combination**: Select (e.g., "Science")
5. **Exam Year**: Select (e.g., 2026)
6. Save
7. Done! Subjects auto-allocated.

### Task: Register a NECTA-aligned private candidate
1. Navigate to **Candidates** > **Add Candidate**
2. Fill: Name, Registration #, District
3. **Candidate Type**: Select **PRIVATE**
4. **Combination**: Leave blank
5. **Exam Year**: Select (e.g., 2026)
6. Save
7. Click candidate row > **Allocate Subjects**
8. Select subjects manually (GS required, 3+ principals)
9. Click **Apply**
10. Done!

### Task: Re-allocate subjects for a candidate (Replace mode)
1. Open candidate
2. Click **Allocate Subjects**
3. Select subjects
4. Click **Replace Allocations** (not "Add missing only")
5. Confirm in dialog
6. Done! Old allocation removed, new subjects applied.

---

## Reference: Subject Codes

| Code | Name | Type | NECTA Required |
|------|------|------|---|
| 111 | General Studies | Mandatory | ✓ Always |
| 102 | Biology | Principal | Optional |
| 103 | Chemistry | Principal | Optional |
| 104 | Physics | Principal | Optional |
| 105 | Mathematics | Principal | Optional |
| 106 | English | Principal | Optional |
| ... | [Other subjects] | Various | See combination |

**Note**: General Studies (111) is the only truly required subject for all ACSEE candidates.

---

## FAQ

**Q: Can I change a SCHOOL candidate to PRIVATE after creation?**  
A: No. Candidate type is set at registration. Create a new candidate if needed.

**Q: What happens to a SCHOOL candidate's subjects if I change the combination?**  
A: Only new allocations use the new combination. Existing subjects remain (use "Replace" mode to change all).

**Q: Can a PRIVATE candidate have a combination?**  
A: Yes, but it's optional. If set, they still need manual subject selection.

**Q: What if I accidentally delete subjects?**  
A: The allocation modal will ask for confirmation. If deleted, manually re-allocate via the modal.

**Q: Are old candidates (pre-deployment) affected?**  
A: No. They keep their original allocations and type is set to SCHOOL automatically.

---

## Support

**Issue?** Contact IT support with:
1. Candidate registration number
2. Error message (if any)
3. Steps taken before issue occurred

**Questions?** Refer to the full deployment documentation or ask your supervisor.

---

**Deployment Date**: 2026-02-15  
**Last Updated**: 2026-02-15
