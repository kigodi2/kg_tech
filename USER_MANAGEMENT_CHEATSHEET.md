# User Management Cheatsheet

## Quick Commands

### Generate Test Password
```bash
php artisan tinker
>>> App\Services\PasswordGenerationService::generate()
```

### Create Test User via CLI
```bash
php artisan tinker
>>> $user = App\Models\User::create(['name' => 'John', 'email' => 'john@example.com', 'password' => Hash::make('temppass123!'), 'role_id' => 1, 'password_reset_required' => true, 'status' => 'active']);
>>> App\Models\UserScope::create(['user_id' => $user->id, 'scope_type' => 'district', 'scope_id' => 1]);
```

### Check User Roles
```bash
php artisan tinker
>>> App\Models\Role::all()
>>> App\Models\Role::pluck('code')->toArray()
```

### View User Scopes
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->scope;
>>> $user->getScopeType();
>>> $user->getScopeId();
```

### Check Audit Log
```bash
php artisan tinker
>>> App\Models\GovernanceAuditLog::orderBy('created_at', 'desc')->first();
>>> App\Models\GovernanceAuditLog::byUser(1)->get();
>>> App\Models\GovernanceAuditLog::byAction('user_created')->get();
```

### Reset User Password (Admin)
```bash
php artisan tinker
>>> $user = App\Models\User::find(2);
>>> $newPwd = App\Services\PasswordGenerationService::generate();
>>> $user->update(['password' => Hash::make($newPwd), 'password_reset_required' => true]);
>>> echo $newPwd;
```

### Suspend User
```bash
php artisan tinker
>>> $user = App\Models\User::find(2);
>>> $user->update(['status' => 'suspended']);
>>> DB::table('sessions')->where('user_id', $user->id)->delete();
```

### Activate User
```bash
php artisan tinker
>>> $user = App\Models\User::find(2);
>>> $user->update(['status' => 'active']);
```

---

## Role Codes (for FK queries)

| Role | Code | ID |
|------|------|---|
| Admin | `admin` | See DB |
| Regional Officer | `regional_officer` | See DB |
| District Data Entry | `district_data_entry_officer` | See DB |
| District Supervisor | `district_supervisor` | See DB |
| School Registrar | `school_registrar` | See DB |

```bash
# Get role ID by code
php artisan tinker
>>> App\Models\Role::where('code', 'admin')->first()->id
```

---

## Scope Types

```
region     → scope_id = regions.id
district   → scope_id = districts.id
school     → scope_id = schools.id
```

---

## User Model Helpers

```php
$user = App\Models\User::find(1);

// Status checks
$user->isActive()              // bool
$user->isSuspended()           // bool

// Role checks
$user->isAdmin()               // bool
$user->isRegionalOfficer()     // bool
$user->isDistrictDataEntryOfficer()  // bool
$user->isDistrictSupervisor()  // bool
$user->isSchoolRegistrar()     // bool
$user->hasRole('admin')        // bool

// Scope access
$user->getScopeType()          // 'region'|'district'|'school'|null
$user->getScopeId()            // int|null
$user->getDistrictId()         // int|null (if scope is district)
$user->getRegionId()           // int|null (if scope is region)
$user->getSchoolId()           // int|null (if scope is school)

// Relationships
$user->role                    // Role model
$user->scope                   // UserScope model
$user->governanceAuditLogs     // GovernanceAuditLog collection
```

---

## Filament Admin Panel

**URL**: http://localhost/admin/users

**Actions**:
- Create User
- Edit User
- Reset Password
- Suspend User
- Activate User

**Filters**:
- By Role
- By Status (active/suspended)

---

## Audit Log Actions

```php
GovernanceAuditLog::ACTION_USER_CREATED
GovernanceAuditLog::ACTION_USER_ROLE_ASSIGNED
GovernanceAuditLog::ACTION_USER_SCOPE_ASSIGNED
GovernanceAuditLog::ACTION_USER_SUSPENDED
GovernanceAuditLog::ACTION_USER_ACTIVATED
GovernanceAuditLog::ACTION_PASSWORD_RESET
GovernanceAuditLog::ACTION_PASSWORD_CHANGED
GovernanceAuditLog::ACTION_LOGIN_SUCCESSFUL
GovernanceAuditLog::ACTION_LOGIN_FAILED
GovernanceAuditLog::ACTION_IMPORT_INITIATED
GovernanceAuditLog::ACTION_IMPORT_COMPLETED
GovernanceAuditLog::ACTION_IMPORT_FAILED
```

---

## Log an Action

```php
GovernanceAuditLog::log(
    GovernanceAuditLog::ACTION_USER_CREATED,
    userId: 5,
    adminId: 1,
    data: [
        'email' => 'user@example.com',
        'role' => 'district_data_entry_officer'
    ]
);
```

---

## SQL Queries

### List all users with roles
```sql
SELECT u.id, u.name, u.email, u.status, r.code as role_code, r.name as role_name
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
ORDER BY u.created_at DESC;
```

### List user scopes
```sql
SELECT u.name, us.scope_type, us.scope_id, 
       CASE WHEN us.scope_type = 'district' THEN (SELECT name FROM districts WHERE id = us.scope_id)
            WHEN us.scope_type = 'region' THEN (SELECT name FROM regions WHERE id = us.scope_id)
            WHEN us.scope_type = 'school' THEN (SELECT name FROM schools WHERE id = us.scope_id)
       END as scope_name
FROM users u
LEFT JOIN user_scopes us ON u.id = us.user_id;
```

### Audit log for user
```sql
SELECT * FROM governance_audit_logs 
WHERE user_id = 5 OR admin_id = 5
ORDER BY created_at DESC;
```

### Find suspended users
```sql
SELECT id, name, email, status, created_at FROM users WHERE status = 'suspended';
```

### Users requiring password change
```sql
SELECT id, name, email FROM users WHERE password_reset_required = true;
```

---

## Routes

```
GET  /admin/users                              # List users
GET  /admin/users/create                       # Create form
POST /admin/users                              # Store user
GET  /admin/users/{id}/edit                    # Edit form
PUT  /admin/users/{id}                         # Update user

GET  /password/change-required                 # Forced change form
POST /password/update-required                 # Update password

GET  /login                                    # Login form
POST /login                                    # Login
POST /logout                                   # Logout
```

---

## Middleware & Policies

### Enforce Password Change
```php
// Automatically added to protected routes
middleware('auth')
middleware('enforce.password.change')
```

### Check Authorization
```php
// In controller
$this->authorize('create', new \App\Models\BulkImport);

// In policy
public function create(User $user): bool {
    return $user->isDistrictDataEntryOfficer() && $user->isActive();
}
```

---

## Troubleshooting

**User can't log in?**
```bash
php artisan tinker
>>> $user = App\Models\User::where('email', 'user@example.com')->first();
>>> $user->status;  # Should be 'active'
>>> $user->role_id; # Should not be null
>>> auth()->attempt(['email' => 'user@example.com', 'password' => 'pwd']);
```

**Password reset not working?**
```bash
php artisan tinker
>>> $user = App\Models\User::find(5);
>>> $user->password_reset_required;  # Should be true
>>> $user->status;                   # Should be 'active'
```

**Scope not showing?**
```bash
php artisan tinker
>>> $user = App\Models\User::with('scope')->find(5);
>>> $user->scope;  # Should have scope_type and scope_id
```

**Audit log not recording?**
```bash
php artisan tinker
>>> App\Models\GovernanceAuditLog::latest()->first();
>>> App\Models\GovernanceAuditLog::where('user_id', 5)->count();
```

---

**Document**: USER_MANAGEMENT_CHEATSHEET.md  
**Last Updated**: February 2, 2026
