# Filament Admin Panel Implementation Guide

## Overview

A professional, read-mostly Filament v3 admin panel has been integrated into the IRMS. This panel provides supervisory governance capabilities without interfering with operational workflows.

### Key Principles

- **Supervisory**: View and manage system governance, not operational data entry
- **Role-Based**: Access controlled via Laravel Policies and user roles
- **Audit-Safe**: Never mutates candidate marks or locked data
- **Policy-Driven**: All authorization through Laravel Policies, no inline checks
- **NECTA/NACTVET Aligned**: Respects exam year locking and data integrity

---

## Architecture

```
app/Filament/Admin/
├── Resources/
│   ├── ExamYearResource/
│   │   └── Pages/
│   │       ├── ListExamYears.php
│   │       ├── CreateExamYear.php
│   │       ├── ViewExamYear.php
│   │       └── EditExamYear.php
│   ├── RegionResource/
│   ├── DistrictResource/
│   ├── SchoolResource/
│   └── BulkImportResource/ (read-only)
│
├── Pages/
│   ├── Dashboard.php (custom overview)
│   ├── AuditLogs.php (immutable logs)
│   └── SystemSettings.php (limited config)
│
└── Widgets/
    ├── StatsOverview.php
    ├── ExamYearOverview.php
    └── BulkImportStats.php
```

---

## Access & Authentication

### Roles

Only these roles can access the admin panel:

- **Administrator** (`ROLE_ADMINISTRATOR`): Full access to all resources
- **Council IT** (`ROLE_COUNCIL_IT`): Limited read-only access (future extension)

### School Officers and Data Entry users

❌ **Cannot access** `/admin` route  
✅ Remain on operational dashboards at `/registration`, `/mark-entry`, etc.

### Authorization

All resource authorization is delegated to Laravel Policies:

```php
// app/Policies/ExamYearPolicy.php
public function create(User $user): bool {
    return $user->isAdmin();
}

public function update(User $user, ExamYear $examYear): bool {
    if ($examYear->isLocked()) return false;
    return $user->isAdmin();
}
```

---

## Navigation Structure

The admin panel navigation is automatically role-aware:

```
Dashboard
├── Exam Management
│   └── Exam Years (create, lock, activate)
├── Geographic
│   ├── Regions
│   ├── Districts
│   └── Schools
└── Operations
    ├── Bulk Imports (read-only)
    ├── Audit Logs (immutable)
    └── System Settings (limited)
```

---

## Modules

### 1. Dashboard (Read-Only)

**Location**: `/admin`

Displays supervisory overview:
- Active exam year (per exam type)
- Total schools registered
- Bulk import status (pending, completed, failed)
- Locked vs unlocked exam years

**Widgets**:
- `StatsOverview`: Key metrics
- `ExamYearOverview`: Recent years and status
- `BulkImportStats`: Import pipeline status

### 2. Exam Years (CRITICAL)

**Location**: `/admin/exam-years`

**Fields**:
- `year_label` (e.g., "2024")
- `is_active` (only one allowed at a time)
- `is_locked` (irreversible after publish)
- `published_at` (timestamp of publication/lock)

**Actions**:
- ✅ Create new draft year
- ✅ Activate an inactive year (with confirmation)
- ✅ Publish & lock a year (with confirmation, irreversible)
- ❌ Cannot edit locked years
- ❌ Cannot delete published/locked years

**Business Rules**:
```php
// Only one active year
if ($record->is_active) {
    ExamYear::where('id', '!=', $record->id)->update(['is_active' => false]);
}

// Publish triggers lock
public function publish(): bool {
    return $this->update([
        'published_at' => now(),
        'is_locked' => true,
        'locked_at' => now(),
    ]);
}
```

### 3. Regions / Districts / Schools

**Locations**: 
- `/admin/regions`
- `/admin/districts`
- `/admin/schools`

**Permissions**:
- ✅ Admins can create, edit
- ✅ Admins can view all
- ❌ Cannot delete if children exist:
  - Region: cannot delete if has schools
  - District: cannot delete if has schools
  - School: cannot delete if has candidates

**Policies**:
```php
public function delete(User $user, Region $region): bool {
    if ($user->irms_role !== User::ROLE_ADMINISTRATOR) {
        return false;
    }
    if ($region->schools()->exists()) {
        return false;
    }
    return true;
}
```

### 4. Bulk Imports (READ-ONLY)

**Location**: `/admin/bulk-imports`

**Display**:
- Import ID and scope (school/district)
- Exam year reference
- Status (pending, processing, completed, failed)
- Progress (files/schools processed)
- Uploaded by (user info)
- ZIP checksum & signature
- Creation and completion timestamps

**Actions**:
- ✅ View details (full inspection of import metadata)
- ❌ Cannot re-upload
- ❌ Cannot edit
- ❌ Cannot delete

**View Includes**:
- Full import progress
- Error summary (if any)
- Metadata (checksums, signatures)

### 5. Audit Logs (IMMUTABLE)

**Location**: `/admin/audit-logs`

**Display**:
- User who performed action
- Event type (LOGIN, LOGOUT, FAILED_LOGIN, PASSWORD_CHANGE, etc.)
- IP address
- User agent
- Timestamp

**Features**:
- ✅ Searchable by user, username
- ✅ Filterable by event type and date range
- ✅ Sortable by timestamp
- ❌ Never delete logs
- ❌ Never edit logs
- ❌ Never export without permission (future)

**Data Source**: `AuthenticationAuditLog` model (append-only)

### 6. System Settings (LIMITED)

**Location**: `/admin/system-settings`

**Configurable Settings**:
- Import chunk size (100-10,000 records per batch)
- Max ZIP file size (bytes)
- Cache TTL (seconds)
- Maintenance mode toggle
- System notes (internal only)

⚠️ **Important**: These are demonstration fields. In production, use a proper configuration management system (e.g., database config table or environment variables).

---

## Policies Reference

All authorization is handled through Laravel Policies located in `app/Policies/`:

### AdminAccessPolicy
Controls access to admin panel itself:
```php
public function accessAdmin(User $user): bool
public function isFullAdmin(User $user): bool
public function isCouncilIT(User $user): bool
```

### ExamYearPolicy
```php
public function create(User $user): bool // Admin only
public function update(User $user, ExamYear $year): bool // Admin, not locked
public function delete(User $user, ExamYear $year): bool // Admin, not published
public function publish(User $user, ExamYear $year): bool // Admin only
public function activate(User $user, ExamYear $year): bool // Admin only
```

### RegionPolicy / DistrictPolicy / SchoolPolicy
```php
public function viewAny(User $user): bool // Admin + Council IT
public function view(User $user, Model $model): bool // Same
public function create(User $user): bool // Admin only
public function update(User $user, Model $model): bool // Admin only
public function delete(User $user, Model $model): bool // Admin, if no children
```

---

## Performance & Safety

### Database Queries

All list pages are paginated:
- Default: 25 items per page
- Options: 25, 50, 100

Eager loading is applied:
```php
->columns([
    TextColumn::make('examYear.year_label'), // Relation loaded
    TextColumn::make('createdBy.name'), // Relation loaded
])
```

### No Computed Columns

All displayed data comes directly from database columns or simple relations.

### No Background Jobs

Admin actions don't trigger background jobs. All operations complete synchronously.

---

## Usage Guide

### Accessing the Admin Panel

1. **Login** at `/login` with an admin account
2. **Navigate** to `/admin`
3. **Sidebar** shows all accessible resources

### Creating an Exam Year

1. Go to **Exam Years**
2. Click **Create Exam Year**
3. Fill in:
   - Year Label (e.g., "2024")
   - Leave Active and Locked unchecked initially
4. Click **Save**

### Activating an Exam Year

1. Go to **Exam Years**
2. Find the year in the table
3. Click **Activate** (green button)
4. Confirm the action
5. Only one year can be active at a time

### Publishing (Locking) an Exam Year

⚠️ **This action is IRREVERSIBLE**

1. Go to **Exam Years**
2. Find the active year
3. Click **Publish & Lock** (red button)
4. Confirm that you want to lock the year
5. The year is now immutable globally
6. No new records can be added to this year

### Viewing Bulk Imports

1. Go to **Bulk Imports**
2. View list of all imports (pending, processing, completed, failed)
3. Click on an import to see full details:
   - Progress statistics
   - Error summary (if any)
   - ZIP checksum and signature
   - Metadata

### Checking Audit Logs

1. Go to **Audit Logs**
2. Search by username or filter by event type
3. Date range filtering available
4. View IP address and user agent for each action

---

## Customization

### Adding a New Resource

To add a new admin resource (e.g., Candidates):

```php
php artisan make:filament-resource Admin/Candidate
```

Then:

1. Create the Resource in `app/Filament/Admin/Resources/`
2. Implement `form()` and `table()` methods
3. Create Pages in `Resources/*/Pages/`
4. Create Policy in `app/Policies/CandidatePolicy.php`
5. The resource appears in navigation automatically

### Extending the Dashboard

Add new widgets to `app/Filament/Admin/Widgets/` and register in:

```php
// app/Filament/Admin/Pages/Dashboard.php
public function getWidgets(): array {
    return [
        \App\Filament\Admin\Widgets\YourWidget::class,
    ];
}
```

### Branding

Customize in `app/Providers/Filament/AdminPanelProvider.php`:

```php
->brandName('IRMS Admin')
->brandLogo(asset('images/logo.png'))
->colors(['primary' => Color::Blue])
```

---

## Important Notes

### ✅ DO

- ✅ Use admin panel for governance and reporting
- ✅ Lock exam years when ready to publish
- ✅ Review audit logs regularly
- ✅ Manage school/district/region hierarchy
- ✅ Monitor bulk import progress

### ❌ DON'T

- ❌ Edit marks manually in admin panel (policy prevents this)
- ❌ Unlock locked exam years (not possible via UI)
- ❌ Delete audit logs (immutable by design)
- ❌ Edit bulk imports (read-only)
- ❌ Expect admin panel to handle data entry (use operational dashboards)

---

## Troubleshooting

### Admin Panel Not Accessible

**Check**:
1. User has `ROLE_ADMINISTRATOR`
2. Session is valid
3. Check auth middleware in `AdminPanelProvider.php`

### Resource Not Appearing in Navigation

**Check**:
1. Resource class exists in correct namespace
2. Policy exists and `viewAny()` returns true
3. Navigation sort order (use `protected static ?int $navigationSort = X;`)

### Performance Issues

1. Check pagination settings (default 25)
2. Add eager loading to relations
3. Use `withCount()` for aggregate counts
4. Profile with Laravel Debugbar

---

## API Routes

The admin panel does not expose new API routes. Operational APIs remain at:
- `/api/regions`
- `/api/districts`
- `/api/schools`
- `/api/bulk-import/*`
- etc.

---

## Security Checklist

- ✅ Policies enforce authorization
- ✅ Locked years are read-only
- ✅ Audit logs are immutable
- ✅ No marks can be edited
- ✅ Only admins can access
- ✅ All actions use POST (CSRF protected)
- ✅ No SQL injection vectors
- ✅ Sensitive data (checksums) is displayed but not editable

---

## Migration from Old Admin

If migrating from a previous admin system:

1. **Regions/Districts/Schools** still exist at `/api/*` for operational use
2. **Bulk Imports** are now viewable in admin panel
3. **Audit Logs** existing data is queryable via the Filament page
4. **Exam Years** logic unchanged, just with better UI

---

## Support

For issues or enhancements:

1. Check policies in `app/Policies/`
2. Review resource schema in `app/Filament/Admin/Resources/`
3. Check Filament v3 documentation: https://filamentphp.com/docs

---

## Version Info

- **Filament**: v3
- **Laravel**: 12
- **PHP**: 8.2+
- **Installation Date**: {{ date('Y-m-d') }}
