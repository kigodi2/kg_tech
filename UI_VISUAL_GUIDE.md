# Candidate Management UI - Visual Guide

**Date**: 2026-02-15  
**Type**: User Interface Documentation  

---

## 1. CANDIDATES TABLE VIEW

```
╔════════════════════════════════════════════════════════════════════════════════════════════════════════╗
║ 📇 Index #      │ 👤 Full Name        │ ♂/♀ 📘 Type   │ 📚 Combination │ 🏫 School  │ 🎓 Exam │ 📅 Yr │ ℹ️ Status ║
╠════════════════════════════════════════════════════════════════════════════════════════════════════════╣
║ ☐ S0445-0001  │ John Doe            │ ♂ M │ 🔵 SCHOOL  │ PCM   │ School A  │ ACSEE  │ 2026  │ ✓ Registered ║
║ ☐ S0445-0002  │ Jane Smith          │ ♀ F │ 🔵 SCHOOL  │ CBE   │ School A  │ ACSEE  │ 2026  │ ✓ Registered ║
║ ☐ P0652-0001  │ Peter Brown         │ ♂ M │ 🟣 PRIVATE │ -     │ -         │ ACSEE  │ 2026  │ ✓ Registered ║
║ ☐ S0108-0045  │ Mary Johnson        │ ♀ F │ 🔵 SCHOOL  │ HGE   │ School B  │ ACSEE  │ 2026  │ ⏳ Pending    ║
╚════════════════════════════════════════════════════════════════════════════════════════════════════════╝

Key Features:
✓ Index number in blue box (monospace, bold)
✓ Candidate Type shown as badge:
  - Blue: SCHOOL
  - Purple: PRIVATE
✓ Icons for quick visual scanning
✓ Gender displayed with symbols
✓ Status color-coded badges
✓ Headers sticky (remain visible on scroll)
```

---

## 2. ADD CANDIDATE MODAL (Form)

### A. Empty State

```
╔════════════════════════════════════════════════════╗
║ Register New Candidate                             ║ X
║ Index format: CCCC-SSSS (e.g., S0445-0001)        ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number * (NECTA format required)            ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ e.g., S0445-0001                               │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ Format: CCCC-SSSS                                 ║
║ • First char: S (School) or P (Private)          ║
║ • Digits: 4-digit centre code and 4-digit serial ║
║                                                    ║
║ Full Name *                                        ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ e.g., John Doe                                 │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ ... (more fields below)                           ║
║                                                    ║
║                    [ Cancel ]  [ Register ]        ║
╚════════════════════════════════════════════════════╝
```

### B. Valid Index Number

```
╔════════════════════════════════════════════════════╗
║ Register New Candidate                             ║
║ Index format: CCCC-SSSS (e.g., S0445-0001)        ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number * (NECTA format required)            ║
║ ┌────────────────────────────────────────────────┐║
║ │ S0445-0001                                  ✓  │║ ← Green border
║ └────────────────────────────────────────────────┘║
║                                                    ║
║ Format: CCCC-SSSS                                 ║
║ • First char: S (School) or P (Private)          ║
║ • Digits: 4-digit centre code and 4-digit serial ║
║                                                    ║
║ ┌──────────────────────────────────────────────┐  ║
║ │ ✓ Valid index number                         │  ║ ← Green box
║ │ • Type: SCHOOL                               │  ║
║ │ • Centre found                               │  ║
║ └──────────────────────────────────────────────┘  ║
║                                                    ║
║ Full Name *                                        ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ [cursor here]                                  │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ ... (fields auto-populated)                       ║
║                                                    ║
║                    [ Cancel ]  [ Register ]        ║
╚════════════════════════════════════════════════════╝

Auto-populated:
✓ Candidate Type = SCHOOL (from S prefix)
✓ School = School A (from S0445 centre code)
✓ Input border = Green
✓ Check circle icon displayed
```

### C. Invalid Index Number - Format Error

```
╔════════════════════════════════════════════════════╗
║ Register New Candidate                             ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number * (NECTA format required)            ║
║ ┌────────────────────────────────────────────────┐║
║ │ S04450001                                      │║ ← Red border
║ └────────────────────────────────────────────────┘║
║                                                    ║
║ Format: CCCC-SSSS                                 ║
║ • First char: S (School) or P (Private)          ║
║ • Digits: 4-digit centre code and 4-digit serial ║
║                                                    ║
║ ┌──────────────────────────────────────────────┐  ║
║ │ ✗ Invalid format. Use CCCC-SSSS             │  ║ ← Red box
║ │   (e.g., S0445-0001)                         │  ║
║ └──────────────────────────────────────────────┘  ║
║                                                    ║
║ ... (rest of form)                                ║
║                                                    ║
║                    [ Cancel ]  [ Register ]        ║
╚════════════════════════════════════════════════════╝

Problem: Missing hyphen (-)
Error: Specific message shown
Action: User corrects format
```

### D. Invalid Index Number - Centre Not Found

```
╔════════════════════════════════════════════════════╗
║ Register New Candidate                             ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number * (NECTA format required)            ║
║ ┌────────────────────────────────────────────────┐║
║ │ S9999-0001                                     │║ ← Red border
║ └────────────────────────────────────────────────┘║
║                                                    ║
║ Format: CCCC-SSSS                                 ║
║ • First char: S (School) or P (Private)          ║
║ • Digits: 4-digit centre code and 4-digit serial ║
║                                                    ║
║ ┌──────────────────────────────────────────────┐  ║
║ │ ✗ Centre not found in system                │  ║ ← Red box
║ └──────────────────────────────────────────────┘  ║
║                                                    ║
║ ... (rest of form, School field empty)            ║
║                                                    ║
║                    [ Cancel ]  [ Register ]        ║
╚════════════════════════════════════════════════════╝

Problem: Centre S9999 doesn't exist
Error: CENTRE_NOT_FOUND
Action: User must select valid centre code or school
```

### E. Invalid Index Number - Bad Prefix

```
╔════════════════════════════════════════════════════╗
║ Register New Candidate                             ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number * (NECTA format required)            ║
║ ┌────────────────────────────────────────────────┐║
║ │ X0445-0001                                     │║ ← Red border
║ └────────────────────────────────────────────────┘║
║                                                    ║
║ Format: CCCC-SSSS                                 ║
║ • First char: S (School) or P (Private)          ║
║ • Digits: 4-digit centre code and 4-digit serial ║
║                                                    ║
║ ┌──────────────────────────────────────────────┐  ║
║ │ ✗ Unknown centre prefix. Must be S           │  ║ ← Red box
║ │   (School) or P (Private)                    │  ║
║ └──────────────────────────────────────────────┘  ║
║                                                    ║
║ ... (rest of form)                                ║
║                                                    ║
║                    [ Cancel ]  [ Register ]        ║
╚════════════════════════════════════════════════════╝

Problem: Prefix 'X' not allowed
Error: CENTRE_PREFIX_UNKNOWN
Action: User corrects to S or P
```

---

## 3. PRIVATE CANDIDATE

```
╔════════════════════════════════════════════════════╗
║ Register New Candidate                             ║
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number * (NECTA format required)            ║
║ ┌────────────────────────────────────────────────┐║
║ │ P0652-0502                                  ✓  │║ ← Green
║ └────────────────────────────────────────────────┘║
║                                                    ║
║ Format: CCCC-SSSS                                 ║
║ • First char: S (School) or P (Private)          ║
║ • Digits: 4-digit centre code and 4-digit serial ║
║                                                    ║
║ ┌──────────────────────────────────────────────┐  ║
║ │ ✓ Valid index number                         │  ║ ← Green
║ │ • Type: PRIVATE                              │  ║
║ └──────────────────────────────────────────────┘  ║
║                                                    ║
║ ... (form fields)                                 ║
║                                                    ║
║ Candidate Type (ACSEE only)                       ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ PRIVATE (auto-selected)                      ▼ │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ Private candidates can allocate subjects         ║
║ individually without a school affiliation.       ║
║                                                    ║
║                    [ Cancel ]  [ Register ]        ║
╚════════════════════════════════════════════════════╝

Auto-set:
✓ Candidate Type = PRIVATE (from P prefix)
✓ Informative message about private candidates
✓ School field may be empty (or used differently)
```

---

## 4. VIEW CANDIDATE MODAL

```
╔════════════════════════════════════════════════════╗
║ Candidate Details                                  ║ X
╠════════════════════════════════════════════════════╣
║                                                    ║
║ Index Number                                       ║
║ ┌───────────────────────┬──────────────────────┐  ║
║ │ S0445-0001            │ 🔵 SCHOOL            │  ║ ← Badge
║ └───────────────────────┴──────────────────────┘  ║
║                                                    ║
║ Full Name                                          ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ John Doe                                        │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ Sex                       │ Candidate Type         ║
║ ┌──────────────┬──────────┴──────────────────────┐ ║
║ │ Male (M)     │ SCHOOL                           │ ║
║ └──────────────┴──────────────────────────────────┘ ║
║                                                    ║
║ Combination                                        ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ PCM                                             │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ School                                             ║
║ ┌────────────────────────────────────────────────┐ ║
║ │ School A                                        │ ║
║ └────────────────────────────────────────────────┘ ║
║                                                    ║
║ Exam Type                 │ Exam Year              ║
║ ┌──────────────┬──────────┴──────────────────────┐ ║
║ │ ACSEE        │ 2026                             │ ║
║ └──────────────┴──────────────────────────────────┘ ║
║                                                    ║
║                    [ Close ]  [ Edit ]             ║
╚════════════════════════════════════════════════════╝

Enhancements:
✓ Candidate Type badge shown next to index
✓ 2-column layout for related fields
✓ All information organized clearly
✓ Edit button for quick access to editing
```

---

## 5. EDIT CANDIDATE MODAL

Same as Add modal, but:
- Title: "Edit Candidate" (instead of "Register New Candidate")
- Fields pre-populated with existing data
- Index number validation still works
- Button shows "Update Candidate" (instead of "Register Candidate")
- Can change index number (will be validated)

---

## Color Reference

### Badges
- 🔵 **SCHOOL**: Blue background (`bg-blue-100`), blue text (`text-blue-800`)
- 🟣 **PRIVATE**: Purple background (`bg-purple-100`), purple text (`text-purple-800`)

### Status Badges
- ✅ **Registered**: Green background (`bg-green-100`), green text (`text-green-800`)
- ⏳ **Pending**: Yellow background (`bg-yellow-100`), yellow text (`text-yellow-800`)

### Input Validation
- 🟢 **Valid**: Green border (`border-green-300`), green focus ring (`focus:ring-green-500`)
  - Checkmark icon: ✓ (text-green-600)
- 🔴 **Invalid**: Red border (`border-red-300`), red focus ring (`focus:ring-red-500`)
  - Error message box: Red background (`bg-red-50`), red text (`text-red-700`)
- 🔵 **Default**: Blue border (`border-gray-300`), blue focus ring (`focus:ring-blue-500`)

### Icons
- 📇 Barcode (Index #)
- 👤 User (Full Name)
- ♂/♀ Gender symbols
- 📘 Book (Candidate Type)
- 📚 List (Combination)
- 🏫 School
- 🎓 Graduation cap (Exam Type)
- 📅 Calendar (Year)
- ℹ️ Info (Status)
- ✓ Check (Valid)
- ✗ Error (Invalid)

---

## User Interactions

### Adding a Candidate

1. **Click "Register" button** → Add modal opens, index field focused
2. **Type index number** → Real-time validation starts
   - Valid format → Green input, check icon, success message
   - Invalid format → Red input, specific error message
   - Centre found → School auto-selected, type auto-set
   - Centre not found → Error shown
3. **Fill remaining fields** → Pre-populated fields can be edited
4. **Click "Register Candidate"** → Submit and create
   - Success → Modal closes, table refreshes, candidate appears
   - Error → Error message shown in modal

### Viewing a Candidate

1. **Click eye icon** → View modal opens
   - All fields read-only
   - Candidate Type shown as badge
   - Information clearly organized
2. **Click "Edit"** → Edit modal opens with data
3. **Click "Close"** → Modal closes

### Editing a Candidate

1. **Click pencil icon** → Edit modal opens with data
2. **Modify any field** → Changes reflected in real-time
3. **Change index number** → Validation runs, auto-updates
4. **Click "Update Candidate"** → Submit changes
   - Success → Modal closes, table updates
   - Error → Error message shown

---

## Mobile View

On mobile devices, the layout adapts:

```
┌─────────────────────────────┐
│ Index #      ✓ Registered   │
│ S0445-0001   🔵 SCHOOL      │
│                             │
│ John Doe                    │
│ ♂ M - ACSEE 2026           │
│                             │
│ 👁 ✏️ 🗑️     [View/Edit]     │
└─────────────────────────────┘

- Horizontal scroll for table
- Stacked fields in modal
- Large buttons for touch
- Icons remain visible
- Validation feedback works same
```

---

## Accessibility Features

✓ **Semantic HTML**: Proper labels, form structure
✓ **Color + Icon**: Don't rely on color alone
✓ **Focus Indicators**: Blue outline visible on inputs
✓ **ARIA labels**: Titles on icon buttons
✓ **Keyboard Navigation**: Tab through fields, Enter to submit
✓ **Screen Reader**: Semantic headings and labels
✓ **High Contrast**: Dark text on light backgrounds

---

## Summary

The updated UI provides:

1. **Table**: Better visual hierarchy, sticky headers, candidate type badges
2. **Add/Edit Modal**: Real-time validation, auto-population, color feedback
3. **View Modal**: Complete information, candidate type badge, organized layout
4. **Responsive**: Works on desktop and mobile
5. **Accessible**: Keyboard navigation, screen reader friendly

All enhanced with NECTA Index Number validation integration.

