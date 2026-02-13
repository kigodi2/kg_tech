# User Management Quick Start

## Test It Now

### 1. Create a Test User
```bash
php artisan tinker

# Generate a test password
$pwd = App\Services\PasswordGenerationService::generate();
echo $pwd;  # Copy this

# Create user with password
$user = App\Models\User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make($pwd),
    'password_reset_required' => true,
    'status' => 'active',
]);

# Assign a role (must be admin)
$adminRole = App\Models\Role::where('code', 'admin')->first();
$user->update(['role_id' => $adminRole->id]);

# Admin doesn't need a scope, but let's create a district officer
$districtRole = App\Models\Role::where('code', 'district_data_entry_officer')->first();
$officer = App\Models\User::create([
    'name' => 'Jane Officer',
    'email' => 'jane@example.com',
    'password' => Hash::make(App\Services\PasswordGenerationService::generate()),
    'password_reset_required' => true,
    'status' => 'active',
    'role_id' => $districtRole->id,
]);

# Give officer a district scope
App\Models\UserScope::create([
    'user_id' => $officer->id,
    'scope_type' => 'district',
    'scope_id' => 1,  # Assuming district ID 1 exists
]);
```

### 2. Test Password Reset
```bash
# Simulate admin resetting officer's password
$pwd = App\Services\PasswordGenerationService::generate();
$officer->update([
    'password' => Hash::make($pwd),
    'password_reset_required' => true,
]);

echo "New password: $pwd";

# Log the action
App\Models\GovernanceAuditLog::log(
    App\Models\GovernanceAuditLog::ACTION_PASSWORD_RESET,
    userId: $officer->id,
    adminId: 1,  # Admin user ID
    data: ['reason' => 'password recovery']
);
```

### 3. Test Account Suspension
```bash
# Suspend the user
$officer->update(['status' => 'suspended']);

# Kill all active sessions
DB::table('sessions')->where('user_id', $officer->id)->delete();

# Log it
App\Models\GovernanceAuditLog::log(
    App\Models\GovernanceAuditLog::ACTION_USER_SUSPENDED,
    userId: $officer->id,
    adminId: 1,
    data: ['reason' => 'security incident']
);
```

### 4. Test Authorization Helper
```bash
# Check if user is active
$officer->isActive();  # false (suspended)

# Check role
$officer->isDistrictDataEntryOfficer();  # true

# Get scope info
$officer->getScopeType();   # 'district'
$officer->getScopeId();     # 1
$officer->getDistrictId();  # 1
```

---

## Use Admin Panel

### 1. Access `/admin/users`
```
http://localhost/admin/users
```

### 2. Create User
- Click "Create User" button
- Fill: Name, Email, Phone
- Select Role (e.g., District Data Entry Officer)
- Select Scope Type (e.g., District)
- Select Scope (e.g., District Name)
- Click Save
- **Important**: Copy the generated password displayed in the notification

### 3. Reset Password
- Find user in list
- Click "Reset Password" button
- Notification shows new password
- User must change it on next login

### 4. Suspend User
- Find user in list
- Click "Suspend" button
- Confirm
- User is immediately locked out, all sessions invalidated

### 5. Activate User
- Find suspended user
- Click "Activate" button
- User can log in again

---

## Authentication Flow

### First Login After User Creation
```
1. User logs in with: email + generated password
2. Login succeeds
3. Middleware checks: user.password_reset_required == true
4. Redirect to: /password/change-required
5. Show form: Current Password + New Password + Confirm
6. User submits new password
7. password_reset_required set to false
8. Redirect to dashboard
9. User can now access system
```

### Subsequent Logins
```
1. User logs in normally
2. password_reset_required == false
3. No forced password change
4. User can access all authorized routes
```

---

## Database Queries

### View All Users with Roles
```sql
SELECT u.id, u.name, u.email, u.status, r.name as role_name
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
ORDER BY u.created_at DESC;
```

### View User Scopes
```sql
SELECT u.name, us.scope_type, 
       CASE 
           WHEN us.scope_type = 'district' THEN d.name
           WHEN us.scope_type = 'region' THEN rg.name
           WHEN us.scope_type = 'school' THEN s.name
       END as scope_name
FROM user_scopes us
JOIN users u ON us.user_id = u.id
LEFT JOIN districts d ON us.scope_type = 'district' AND us.scope_id = d.id
LEFT JOIN regions rg ON us.scope_type = 'region' AND us.scope_id = rg.id
LEFT JOIN schools s ON us.scope_type = 'school' AND us.scope_id = s.id;
```

### View Audit Log
```sql
SELECT 
    pal.id,
    pal.action,
    u.name as affected_user,
    admin.name as admin_user,
    pal.data,
    pal.created_at
FROM governance_audit_logs pal
LEFT JOIN users u ON pal.user_id = u.id
LEFT JOIN users admin ON pal.admin_id = admin.id
ORDER BY pal.created_at DESC
LIMIT 50;
```

### Find Recently Created Users
```sql
SELECT u.id, u.name, u.email, u.status, r.name as role, u.created_at
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY u.created_at DESC;
```

### Find Suspended Users
```sql
SELECT u.id, u.name, u.email, r.name as role, u.updated_at
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE u.status = 'suspended'
ORDER BY u.updated_at DESC;
```

---

## Roles Reference

| Code | Name | Scope Required | Description |
|------|------|---|---|
| `admin` | Administrator | No | Full system access, user management |
| `regional_officer` | Regional Officer | Yes (Region) | Oversees region, quality assurance |
| `district_data_entry_officer` | District Data Entry Officer | Yes (District) | Imports marks for district |
| `district_supervisor` | District Supervisor | Yes (District) | Supervises data entry at district |
| `school_registrar` | School Registrar | Yes (School) | Registers candidates at school |

---

## Scope Rules

### Admin
- **Scope Required**: NO
- **Can See**: Everything (all districts, schools)
- **Can Do**: Create/reset/suspend any user

### Regional Officer
- **Scope Required**: YES (Region)
- **Can See**: Only their region's data
- **Can Do**: Approve imports, monitor quality

### District Data Entry Officer
- **Scope Required**: YES (District)
- **Can See**: Only their district's schools
- **Can Do**: Import marks for their district schools only

### District Supervisor
- **Scope Required**: YES (District)
- **Can See**: Only their district
- **Can Do**: Supervise data entry, view logs

### School Registrar
- **Scope Required**: YES (School)
- **Can See**: Only their school
- **Can Do**: Register candidates, view own records

---

## Common Tasks

### Task: Create a District Data Entry Officer
```
1. Go to /admin/users
2. Click "Create User"
3. Name: Jane Smith
4. Email: jane.smith@district.tz
5. Phone: +255 xxx xxx xxx
6. Role: District Data Entry Officer
7. Scope Type: District
8. Scope: Select the district
9. Save → Copy password
10. Send password securely to Jane
11. Jane logs in → Changes password → Ready to import
```

### Task: Create a Regional Officer
```
1. Go to /admin/users
2. Click "Create User"
3. Name: Mr. Officer
4. Email: officer@region.tz
5. Role: Regional Officer
6. Scope Type: Region
7. Scope: Select the region
8. Save → Copy password
9. Officer logs in, changes password → Can view all district data in that region
```

### Task: Recover a User's Account (Reset Password)
```
1. Go to /admin/users
2. Find user in list
3. Click "Reset Password"
4. Notification shows new password
5. Tell user the new password
6. User logs in with new password
7. Forced to change password on login
8. User can now work again
```

### Task: Prevent a User from Accessing System
```
1. Go to /admin/users
2. Find user
3. Click "Suspend"
4. Confirm suspension
5. User's all sessions killed
6. User cannot log in (status = suspended)
7. When ready, click "Activate" to restore access
```

---

## Troubleshooting

### Q: User says "Password reset required" message keeps appearing
A: The `password_reset_required` flag is still true. User must:
   1. Go to `/password/change-required`
   2. Enter current password
   3. Set new password (min 12 chars)
   4. Submit
   
   Check database:
   ```sql
   SELECT password_reset_required FROM users WHERE email = 'user@example.com';
   ```

### Q: User logs in but gets redirected to password change page
A: This is correct behavior. New users MUST change password on first login.

### Q: Can't see user in admin panel
A: 
   - User's `status` might be 'suspended' (still visible, but can't log in)
   - User might not have `role_id` set (create with role first)
   - Filament UI needs refresh

### Q: Password doesn't work even though I copied it
A: 
   - Only displayed ONCE after user creation
   - If lost, use "Reset Password" action to generate new one
   - Make sure you copied it correctly (no spaces)

### Q: Scope selection disappears after selecting role
A: Some roles don't require scope (like admin). If you select "Admin", scope fields hide (correct behavior).

---

**Document**: USER_MANAGEMENT_QUICK_START.md  
**Last Updated**: February 2, 2026  
**Status**: Ready for testing
