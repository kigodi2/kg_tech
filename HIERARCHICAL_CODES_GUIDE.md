# Hierarchical Auto-Generated Codes Guide

## Overview

The Registration Management system now features intelligent hierarchical auto-generated codes:
- **Regions**: `XX##` format (2 letters + 2 digits)
- **Districts**: `XXXX##` format (4-char region code + 2-digit sequence)

This creates a natural parent-child relationship visible in the code structure itself.

---

## Code Structure

### Regions Code Format: `XX##`

**Format**: 2 capital letters + 2 digits
- **Letters**: First 2 characters of region name (uppercase)
- **Numbers**: Sequential numbering starting from 01

**Examples**:
- "Arusha" → `AR01`
- "Dodoma" → `DO01`
- "Morogoro" → `MO01`
- "Dar Es Salaam" → `DA01`
- "Tanzania" → `TA01`

---

### Districts Code Format: `XXXX##`

**Format**: 4-char region code + 2-digit sequence
- **First 4 characters**: Region code (inherited from parent region)
- **Last 2 digits**: Sequential number within that region

**Examples**:

With Region "Arusha" (Code: `AR01`):
- "Arusha City" → `AR0101`
- "Arusha Metro" → `AR0102`
- "Arusha North" → `AR0103`
- "Arusha Rural" → `AR0104`

With Region "Dodoma" (Code: `DO01`):
- "Dodoma City" → `DO0101`
- "Dodoma Urban" → `DO0102`
- "Dodoma Rural" → `DO0103`

With Region "Morogoro" (Code: `MO01`):
- "Morogoro City" → `MO0101`
- "Morogoro Metro" → `MO0102`
- "Morogoro West" → `MO0103`

---

## Key Features

### ✅ Hierarchical Relationship
The district code inherits the parent region's code as its prefix, making the hierarchy immediately visible:
- `AR0101` clearly indicates "under region AR01"
- `DO0102` clearly indicates "under region DO01"

### ✅ Sequential Numbering Per Region
Each region maintains its own sequence counter:
- First district in "Arusha" region: `AR0101`
- Second district in "Arusha" region: `AR0102`
- First district in "Dodoma" region: `DO0101` (resets counter)
- Second district in "Dodoma" region: `DO0102`

### ✅ Uniqueness Guaranteed
- Codes are unique across all districts
- Sequential numbering prevents conflicts
- Maximum 99 districts per region (01-99)

### ✅ Real-Time Generation
- Code updates instantly when region is selected
- Code updates when name changes
- Full code displayed for user confirmation
- No manual code entry required

### ✅ Read-Only Fields
- Code fields are read-only (prevent manual editing)
- Ensures consistency and prevents errors
- Display split into: Region Code + Sequence Number

---

## Usage Workflow

### Creating a Region

1. Go to `/registration/regions`
2. Click **"Add Region"** button
3. Enter region name (e.g., "Arusha")
4. Code auto-generates: `AR01`
5. Click **"Add Region"** to save

### Creating Districts Under a Region

1. Go to `/registration/districts`
2. Click **"Add District"** button
3. **Select Region** first (e.g., "Arusha")
   - Region Code field updates: `AR01`
   - Sequence field updates: `01`
   - Full Code shows: `AR0101`
4. Enter **District Name** (e.g., "Arusha City")
5. Click **"Add District"** to save
6. Verify code: `AR0101`

### Adding More Districts to Same Region

1. Click **"Add District"** again
2. Select same Region: "Arusha"
   - Region Code: `AR01` (same)
   - Sequence auto-increments: `02`
   - Full Code: `AR0102`
3. Enter District Name: "Arusha Metro"
4. Click **"Add District"** to save
5. Verify code: `AR0102`

### Adding Districts to Different Region

1. Click **"Add District"** again
2. Select different Region: "Dodoma"
   - Region Code: `DO01` (different)
   - Sequence resets: `01` (new counter)
   - Full Code: `DO0101`
3. Enter District Name: "Dodoma City"
4. Click **"Add District"** to save
5. Verify code: `DO0101`

### Editing a District

1. Click the **Edit** icon on a district row
2. Region and Name pre-populate with existing code
3. To change region:
   - Select new region
   - Code updates automatically
4. Make any changes needed
5. Click **"Update District"** to save

---

## Algorithm Details

### Step-by-Step Process

#### Step 1: User Selects Region
```
User Action: Select "Arusha" from dropdown
System Action: Find region code for "Arusha"
Result: region_code = "AR01"
```

#### Step 2: System Extracts Numeric Suffix
```
Action: Query all districts for selected region
Existing Districts: AR0101, AR0102, AR0103
Extract Suffixes: [01, 02, 03]
```

#### Step 3: Calculate Next Number
```
Existing Numbers: [1, 2, 3]
Max Number: 3
Next Number: 4
Formatted: "04" (padded with leading zero)
```

#### Step 4: Generate Full Code
```
Region Code: "AR01"
Sequence: "04"
Full Code: "AR01" + "04" = "AR0104"
```

#### Step 5: Display to User
```
Region Code Field: "AR01" (read-only)
Sequence Field: "04" (read-only)
Full Code Preview: "AR0104" (display only)
```

---

## Form Layout

The Districts Add/Edit modal has been redesigned for clarity:

```
┌────────────────────────────────────┐
│ ADD NEW DISTRICT                   │
├────────────────────────────────────┤
│                                    │
│ Region * (Select first)            │
│ ┌──────────────────────────────┐   │
│ │ [Dropdown v]  ← Select here  │   │
│ │ - Arusha                     │   │
│ │ - Dodoma                     │   │
│ │ - Morogoro                   │   │
│ └──────────────────────────────┘   │
│                                    │
│ District Name *                    │
│ ┌──────────────────────────────┐   │
│ │ e.g., Arusha City            │   │
│ └──────────────────────────────┘   │
│                                    │
│ District Code (Auto-generated)     │
│ ┌────────────┬─────────────────┐   │
│ │ AR01       │ 01              │   │
│ │ Region Code│ Sequence        │   │
│ └────────────┴─────────────────┘   │
│                                    │
│ Format: XXXX## (6 characters)      │
│ Full Code: AR0101                  │
│                                    │
│ [Cancel]  [Add District]           │
└────────────────────────────────────┘
```

---

## Data Storage

### District Record Structure

```javascript
{
  "id": 1,
  "code": "AR0101",           // Full 6-character code
  "name": "Arusha City",
  "region_id": 1,             // FK to regions table
  "region_code": "AR01",      // Reference to parent
  "region_name": "Arusha",    // For display
  "schools_count": 5,
  "status": "active",
  "created_at": "2026-01-26T10:30:00Z",
  "updated_at": "2026-01-26T10:30:00Z"
}
```

---

## Examples & Scenarios

### Scenario 1: Multi-Region Setup

**Regions Created**:
- Arusha → `AR01`
- Dodoma → `DO01`
- Morogoro → `MO01`
- Dar Es Salaam → `DA01`

**Districts Under Arusha** (Region Code: `AR01`):
- Arusha City → `AR0101`
- Arusha Metro → `AR0102`
- Arusha North → `AR0103`

**Districts Under Dodoma** (Region Code: `DO01`):
- Dodoma City → `DO0101`
- Dodoma Urban → `DO0102`

**Districts Under Morogoro** (Region Code: `MO01`):
- Morogoro City → `MO0101`

**Districts Under Dar Es Salaam** (Region Code: `DA01`):
- Dar City Center → `DA0101`
- Dar Suburban → `DA0102`
- Dar Coastal → `DA0103`

### Scenario 2: Code Hierarchy Visualization

```
Regions:
├── AR01 - Arusha
│   ├── AR0101 - Arusha City
│   ├── AR0102 - Arusha Metro
│   └── AR0103 - Arusha North
├── DO01 - Dodoma
│   ├── DO0101 - Dodoma City
│   └── DO0102 - Dodoma Urban
└── MO01 - Morogoro
    ├── MO0101 - Morogoro City
    └── MO0102 - Morogoro Rural
```

Each district code immediately shows which region it belongs to!

---

## Validation Rules

### ✅ Enforcement

1. **Region Selection Required**
   - Must select region before code generates
   - Submit button disabled if no region selected

2. **Unique Codes**
   - Each district code is unique
   - Sequential numbering prevents duplicates
   - Maximum 99 districts per region (01-99)

3. **Code Format**
   - Always 6 characters: `XXXX##`
   - First 4: uppercase letters and digits from region
   - Last 2: zero-padded sequential number

4. **Inheritance**
   - District code always starts with parent region's code
   - Code automatically updates if region is changed

---

## Benefits

### For System Administrators
- ✓ Clear data hierarchy in code structure
- ✓ No duplicate codes possible
- ✓ Easy to audit parent-child relationships
- ✓ Automatic code generation saves time

### For End Users
- ✓ No need to manage codes manually
- ✓ Immediate visibility of which region owns a district
- ✓ Predictable code format
- ✓ No confusion about code structure

### For Data Integrity
- ✓ Codes reflect actual hierarchy
- ✓ Sequential numbering per region prevents conflicts
- ✓ Read-only fields prevent tampering
- ✓ Automatic generation ensures consistency

---

## Limitations

### Maximum Districts Per Region
- **Limit**: 99 districts per region (01-99)
- **Practical**: Extremely unlikely to exceed
- **Workaround**: If needed, consider sub-districts or renaming strategy

### Code Changes on Region Change
- **Behavior**: Changing a district's region changes its code
- **Example**: Moving from "Arusha" to "Dodoma" changes `AR0101` → `DO0101`
- **Best Practice**: Keep original region assignments

### Special Characters in Names
- **Handled**: Only first 2 letters extracted for region codes
- **Example**: "Dar-Es-Salaam" becomes "DA" prefix
- **Works Well**: Naturally works with multi-word names

---

## FAQ

**Q: What if I have an existing district with a manual code?**
A: The system accepts existing codes on import/edit. New districts created via the UI follow auto-generation rules.

**Q: Can I manually edit the code?**
A: No, code fields are read-only. This ensures data consistency and prevents conflicts.

**Q: What happens if I exceed 99 districts in a region?**
A: The system won't auto-generate a 100th code. You'd need to use different naming conventions for the region or create additional regions.

**Q: How are codes stored in the database?**
A: The full 6-character code (e.g., `AR0101`) is stored. The region code is also referenced via `region_id` and `region_code` fields.

**Q: Can I change a district's region after creation?**
A: Yes, but the code will update to reflect the new region. For example, `AR0101` → `DO0101` if moved from Arusha to Dodoma.

**Q: What's the maximum number of regions?**
A: Theoretically unlimited, but practical maximum is 99 regions with same prefix (e.g., AA01-AA99). In practice, this is not a concern for regional hierarchies.

---

## Testing Checklist

- [ ] Create 3 regions with different names
- [ ] Verify each region gets unique code (e.g., AR01, DO01, MO01)
- [ ] Add 5 districts to first region
- [ ] Verify districts get codes: XX0101, XX0102, XX0103, XX0104, XX0105
- [ ] Add 3 districts to second region
- [ ] Verify districts get codes: YY0101, YY0102, YY0103 (counter resets)
- [ ] Edit a district and change its region
- [ ] Verify code updates automatically
- [ ] Try to manually edit code field (should be read-only)
- [ ] Export districts and verify codes in CSV
- [ ] Verify codes in database match displayed codes

---

## Support & Documentation

For more information, refer to:
- `AUTO_GENERATED_CODES_GUIDE.md` - Region codes guide
- `REGISTRATION_QUICKSTART.md` - Quick start guide
- `REGISTRATION_CRUD_IMPLEMENTATION.md` - Technical documentation

---

**Feature Status**: ✅ Active and Ready  
**Last Updated**: January 26, 2026  
**Version**: 2.0 (Hierarchical Districts)
