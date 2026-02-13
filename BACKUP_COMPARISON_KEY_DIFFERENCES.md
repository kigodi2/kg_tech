# Backup IRMS vs Current IRMS - Key Differences

## Data Architecture

### Backup IRMS (Django) - Dashboard/Exam/ACSEE
```
Dashboard owns candidate CRUD
├── Create candidates directly in dashboard
├── Edit candidates in dashboard
├── Delete candidates in dashboard
└── Export candidates from dashboard
```

### Current IRMS (Laravel) - Separation of Concerns
```
Registration Module owns candidate CRUD
├── /registration/candidates - Register/Edit/Delete
└── /dashboard/exam/ACSEE - Read-only view
    └── Display data from registration/candidates
        └── Enrich with combination subjects
```

## Key Advantages of Current Architecture

| Advantage | Benefit |
|-----------|---------|
| **Single source of truth** | All candidate data managed in one place |
| **Role-based access** | Registration role handles CRUD, Dashboard role reads only |
| **Data integrity** | No risk of conflicting edits from different modules |
| **Scalability** | Easy to add other exam types without modifying dashboard |
| **Maintainability** | Clear separation reduces coupling |

---

## Implementation Differences

### 1. **Candidate Registration Workflow**

#### Backup (Django)
```
User → Dashboard → Add Candidate Modal → Direct DB Insert
                 → Edit Candidate Modal → Direct DB Update
                 → Delete Button → Direct DB Delete
```

#### Current (Laravel)
```
User → Registration/Candidates → Add Modal → DB Insert
                              → Edit Modal → DB Update
                              → Delete Button → DB Delete
User → Dashboard/ACSEE → Read-only Table (API fetch) → Display
```

**Why**: Centralized candidate management in registration module

---

### 2. **Data Retrieval**

#### Backup
```python
# dashboard/views.py - Direct query
candidates = Candidate.objects.filter(exam_code='ACSEE')
                              .select_related('school', 'district', 'region')
                              .prefetch_related('subjectselections__subject')
```

#### Current
```php
// DashboardController - API-based
$candidates = Candidate::where('exam_type', 'ACSEE')
                        ->with('school.district.region')
                        ->paginate(15);
                        
// Enrich with combination subjects
$data = $candidates->map(function ($c) {
    return [
        ...$c->toArray(),
        'allocated_subjects' => $this->getCombinationSubjects($c->combination)
    ];
});
```

**Why**: RESTful API pattern for future mobile/external integrations

---

### 3. **Subject Allocation**

#### Backup
```python
# Subject/Combination linkage in exam type
class Subject(Model):
    exam_types = ManyToMany(ExamType)
    
class Combination(Model):
    exam_type = ForeignKey(ExamType)
    subjects = ManyToMany(Subject)
    
# Displayed in dashboard directly
subjects = candidate.subjectselections.filter(exam_type='ACSEE')
```

#### Current
```php
// Subject/Combination linkage
class Combination {
    public function subjects() {
        return $this->belongsToMany(Subject::class);
    }
}

// Displayed from combination lookup
$combination = Combination::where('code', $candidate->combination)
                          ->with('subjects')
                          ->first();
$subjects = $combination->subjects ?? [];
```

**Why**: Dynamic lookup avoids storing subject selections per candidate in registration

---

### 4. **Filtering Hierarchy**

#### Backup
```javascript
// Cascading dropdown filters
Region (selected) → Fetch Districts for region
                 → Fetch Schools for district
                 → Fetch Candidates for school
```

#### Current
```javascript
// Same cascade, but client-side filtering
Load all:
- regions, districts, schools (once on init)
- candidates (with selected filters)

Then filter client-side based on selections
```

**Trade-off**: Current approach requires loading more data but reduces server requests

---

### 5. **Frontend Framework**

#### Backup
```html
<!-- jQuery + Custom JavaScript -->
<script>
  $(document).on('change', '#regionFilter', function() {
    var regionId = $(this).val();
    $.ajax({
      url: `/dashboard/exam/acsee/districts/${regionId}/`,
      success: function(data) {
        $('#districtFilter').html(data);
      }
    });
  });
</script>
```

#### Current
```javascript
// Alpine.js + Reactive properties
function dashboardAcseeManager() {
    return {
        selectedRegion: '',
        filteredDistricts: computed(() => 
            this.districts.filter(d => d.region_id == this.selectedRegion)
        ),
        onRegionChange() {
            // Auto-updates filteredDistricts
            this.loadCandidates();
        }
    };
}
```

**Advantage**: Alpine.js is lightweight, reactive, and modern

---

### 6. **Export Functionality**

#### Backup
```python
# Server-side generation
def export_acsee_candidates(request):
    response = HttpResponse(content_type='text/csv')
    writer = csv.writer(response)
    # Write data
    return response
```

#### Current
```javascript
// Client-side generation
exportToExcel() {
    const csv = [headers, ...rows].map(row => 
        row.map(v => `"${v}"`).join(',')
    ).join('\n');
    const blob = new Blob([csv]);
    // Trigger download
}
```

**Advantage**: No server load, instant client-side export

---

## Architectural Comparison Diagram

```
┌─────────────────────────────────────────────┐
│           BACKUP (Django)                   │
├─────────────────────────────────────────────┤
│                                             │
│   Dashboard Module (monolithic)            │
│   ├─ Exam Summary View                     │
│   ├─ Candidates CRUD (Create/Edit/Del)     │
│   ├─ Subject Management                    │
│   ├─ Combination Management                │
│   └─ Export Functions                      │
│                                             │
│   One module = One responsibility           │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│        CURRENT (Laravel)                    │
├─────────────────────────────────────────────┤
│                                             │
│  Registration Module      Dashboard Module  │
│  ├─ Candidates CRUD    ├─ Read-only Views  │
│  ├─ Import/Export      ├─ Analytics        │
│  └─ Management         ├─ Filtering        │
│                        └─ Reporting        │
│                                             │
│  Exam Types Module     Education Levels    │
│  ├─ ACSEE Management   ├─ Form Levels      │
│  ├─ CSEE Management    └─ Classes          │
│  └─ Combinations                           │
│                                             │
│  Multiple modules = Multiple responsibilities
│  Each module can be maintained/scaled independently
└─────────────────────────────────────────────┘
```

---

## Why Current Architecture is Better

### 1. **Scalability**
- Add CSEE dashboard without modifying registration module
- Add PSLE dashboard without touching exam-types
- Each module can scale independently

### 2. **Maintainability**
- Bug fixes in registration don't affect dashboard
- Dashboard improvements don't affect registration
- Clear code ownership

### 3. **Testing**
- Unit test registration module independently
- Unit test dashboard independently
- Integration tests for API contracts

### 4. **Security**
- Role-based access: `registration.write`, `dashboard.read`
- Audit trail only in registration module
- Dashboard never directly modifies data

### 5. **Performance**
- Can cache dashboard data separately
- Can optimize registration queries independently
- API-based approach allows for future GraphQL migration

---

## Migration Path (If Needed)

If you ever need to add direct editing in dashboard, you can:

1. Create `api/candidates/{id}` update endpoint
2. Add edit modal to dashboard
3. Optionally, add audit logging to track changes
4. Keep registration as primary edit interface

This is non-breaking because dashboard is currently read-only.

---

## Summary

**Backup IRMS**: Monolithic dashboard module with full CRUD + read features.

**Current IRMS**: Distributed architecture where:
- **Registration module** handles all candidate CRUD operations
- **Dashboard module** provides read-only views and analytics
- **Exam-types module** manages subjects and combinations
- **API layer** connects everything

This pattern scales better, maintains data integrity, and follows SOLID principles.
