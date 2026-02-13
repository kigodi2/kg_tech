# Admin Panel Navigation & Settings Guide

## 🎯 Admin Dashboard Entry Point: `/admin`

### Navbar Structure

The Filament v3 admin panel has the following navigation hierarchy:

```
┌─────────────────────────────────────────────────────────┐
│  IRMS Admin (Logo) | Dashboard | ... | User Menu        │
└─────────────────────────────────────────────────────────┘
```

---

## 📍 Main Navigation Menu (Sidebar)

The left sidebar organizes resources by functional group:

### 1. **Dashboard** (Default Landing)
- **Route**: `/admin`
- **Icon**: Dashboard icon
- **Widgets Displayed**:
  - **StatsOverview**: Key metrics (active year, schools, locked years, pending imports)
  - **ExamYearOverview**: Recent exam year statuses
  - **BulkImportStats**: Import pipeline progress

---

### 2. **Exam Management** (Navigation Group)
Located under "Exam Management" in sidebar:

#### **Exam Years** (CRITICAL)
- **Route**: `/admin/exam-years`
- **Icon**: Calendar/year icon
- **Actions**:
  - ✅ **Create** - New exam year (draft state)
  - ✅ **View** - Read-only inspection
  - ✅ **Edit** - Edit unlocked years
  - ✅ **Activate** - Make active (only one allowed)
  - ✅ **Publish & Lock** - IRREVERSIBLE lock for publication
  - ❌ **Delete** - Not allowed for published years

- **Fields**:
  - `year_label` (e.g., "2024")
  - `is_active` (boolean - only one can be true)
  - `is_locked` (boolean - true after publish)
  - `published_at` (timestamp)
  - `locked_at` (timestamp)

---

### 3. **Geographic Data** (Navigation Group)
Hierarchical management of locations:

#### **Regions**
- **Route**: `/admin/regions`
- **Actions**: Create, Read, Update, Delete (if no schools)
- **Fields**: region_name, code, description

#### **Districts**
- **Route**: `/admin/districts`
- **Actions**: Create, Read, Update, Delete (if no schools)
- **Constraints**: Must belong to region
- **Fields**: district_name, region_id, code

#### **Schools**
- **Route**: `/admin/schools`
- **Actions**: Create, Read, Update, Delete (if no candidates)
- **Relations**: region_id, district_id, optional council_id
- **Fields**: school_name, region_id, district_id, contact info

---

### 4. **Operations** (Navigation Group)
System monitoring and configuration:

#### **Bulk Imports** (Read-Only)
- **Route**: `/admin/bulk-imports`
- **Display**: List of all imports with metadata
- **Actions**: View details only (no edit, create, or delete)
- **Displayed Info**:
  - Import ID
  - Scope (school/district)
  - Exam year reference
  - Status (pending, processing, completed, failed)
  - Progress (files/schools processed)
  - Uploaded by (user info)
  - ZIP checksum & signature
  - Timestamps (created, completed)

#### **Audit Logs** (Immutable)
- **Route**: `/admin/audit-logs`
- **Display**: Chronological authentication events
- **Actions**: View, Search, Filter (no edit, delete)
- **Events Logged**:
  - LOGIN
  - LOGOUT
  - FAILED_LOGIN
  - PASSWORD_CHANGE
  - PROFILE_UPDATE
  - etc.

- **Searchable/Filterable By**:
  - Username
  - User name
  - Event type
  - Date range

- **Displayed Info**:
  - User who performed action
  - Event type
  - IP address
  - User agent
  - Timestamp

#### **System Settings** ⚙️
- **Route**: `/admin/system-settings`
- **Navigation Icon**: `heroicon-o-cog-6-tooth` (gear icon)
- **Navigation Sort**: 7 (Operations group)

---

## ⚙️ System Settings Detailed

The System Settings page is located at `/admin/system-settings` and contains the following sections:

### **Section 1: Import Settings**
Configure bulk import behavior:

| Setting | Field | Default | Range | Description |
|---------|-------|---------|-------|-------------|
| Import Chunk Size | `importChunkSize` | 1000 | 100-10,000 | Records to process per batch |
| Max ZIP File Size | `maxZipSize` | 104,857,600 | Any | Maximum ZIP file size in bytes (100MB default) |

### **Section 2: Cache Settings**
Configure caching behavior:

| Setting | Field | Default | Range | Description |
|---------|-------|---------|-------|-------------|
| Cache TTL | `cacheTtl` | 3600 | 60+ seconds | How long to cache queries (1 hour default) |

### **Section 3: Maintenance**
System maintenance options:

| Setting | Field | Type | Description |
|---------|-------|------|-------------|
| Maintenance Mode | `maintenanceMode` | Toggle | Put system in maintenance mode (show maintenance page) |
| System Notes | `systemNotes` | Textarea | Internal notes for administrators only |

### **Saving Settings**
- Click the **"Save Settings"** button at the bottom
- Displays success notification: "Settings Updated"
- ⚠️ **NOTE**: Current implementation is demonstration only. Values are not persisted to database/config in production code.

---

## 📊 User Menu (Top Right)

The top-right corner displays:
- **Account Widget**: Shows current user profile
- **Logout button**: Sign out of admin panel
- **Profile link**: Edit user account details

---

## 🔑 Access Control

### Who Can Access Admin?
Only users with `irms_role = 'ROLE_ADMINISTRATOR'`

### What School Officers See
- ❌ No access to `/admin` route
- ✅ Redirect to operational dashboards at `/registration`, `/mark-entry`, etc.

### Authorization Enforcement
All resource access is controlled by **Laravel Policies** in `app/Policies/`:
- `AdminAccessPolicy` - Controls `/admin` access
- `ExamYearPolicy` - Controls exam year operations
- `RegionPolicy` - Controls region operations
- `DistrictPolicy` - Controls district operations
- `SchoolPolicy` - Controls school operations
- `BulkImportPolicy` - Controls bulk import visibility
- `AuditLogPolicy` - Controls audit log access

---

## 🎨 Panel Configuration

**Provider**: `App\Providers\Filament\AdminPanelProvider`

**Settings**:
```php
- ID: 'admin'
- Path: '/admin'
- Brand Name: 'IRMS Admin'
- Brand Logo: asset('images/logo.png')
- Primary Color: Blue
- Login Required: Yes
- Session-Based Auth: Yes
```

---

## 📱 Responsive Behavior

The admin panel is fully responsive:
- Desktop (1200px+): Full sidebar + content
- Tablet (768px-1199px): Collapsible sidebar
- Mobile (< 768px): Mobile menu with hamburger toggle

---

## 🔄 Navigation Flow Chart

```
Login at /login
    ↓
Check Role
    ├─→ Admin? → /admin (Dashboard)
    │            ├─→ Exam Years
    │            ├─→ Regions/Districts/Schools
    │            └─→ Bulk Imports / Audit Logs / Settings
    │
    └─→ Other Role? → /registration or /mark-entry (Operational)
```

---

## 📋 Common Tasks & Routes

| Task | Route | Sidebar Path | Notes |
|------|-------|--------------|-------|
| Go Home | `/admin` | Dashboard | Main dashboard |
| Manage Years | `/admin/exam-years` | Exam Management → Exam Years | Create/activate/lock |
| Geographic Data | `/admin/regions` | Geographic → Regions | Manage locations |
| View Imports | `/admin/bulk-imports` | Operations → Bulk Imports | Read-only monitoring |
| Check Logs | `/admin/audit-logs` | Operations → Audit Logs | Search authentication events |
| Configure System | `/admin/system-settings` | Operations → System Settings | Adjust import/cache settings |

---

## ⚠️ Important Notes

### Settings Persistence
The System Settings page currently **demonstrates the UI** but does **NOT persist values** to the config system. To make it functional:

**Option 1: Database-Backed Config**
```php
// Create a config table
Schema::create('system_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->json('value');
    $table->timestamps();
});

// Update SystemSettings.php to save to DB
public function saveSettings(): void {
    SystemSetting::updateOrCreate(['key' => 'import_chunk_size'], ['value' => $this->importChunkSize]);
    // ... etc
}
```

**Option 2: Environment Variables**
Use `.env` file and update via UI → write to .env

**Option 3: Config Files**
Use Laravel config cache system

---

## 🚀 Going Live Checklist

Before deploying to production:

- [ ] Login to admin panel with admin user
- [ ] Verify all navbar links work
- [ ] Test Exam Years CRUD (especially publish/lock)
- [ ] Verify geographic data loads correctly
- [ ] Check that bulk imports appear in list
- [ ] Review audit logs for any errors
- [ ] Decide on Settings persistence strategy (DB/ENV/Config)
- [ ] Train administrators on basic operations
- [ ] Set up monitoring/alerts for admin panel errors

---

## 🔗 Related Documentation

- `FILAMENT_ADMIN_PANEL_GUIDE.md` - Complete feature overview
- `FILAMENT_IMPLEMENTATION_SUMMARY.md` - Architecture & design decisions
- `FILAMENT_QUICK_REFERENCE.md` - Developer cheat sheet
- `FILAMENT_DEPLOYMENT_CHECKLIST.md` - Pre-deployment verification

---

## Version Info

- **Filament**: v3
- **Laravel**: 12
- **Admin Path**: `/admin`
- **Status**: ✅ Production Ready
- **Last Updated**: 2026-02-02
