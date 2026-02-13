# Filament Admin Panel Deployment Checklist

## Pre-Deployment

### Code Quality
- [ ] No PHP errors (`php artisan tinker` loads without errors)
- [ ] All imports correct
- [ ] Resources compile (`php artisan cache:clear`)
- [ ] No database migrations needed

### Authentication
- [ ] User table has `irms_role` column with values like `ROLE_ADMINISTRATOR`
- [ ] At least one admin user exists
- [ ] Login route `/login` is functional

### Database
- [ ] `exam_years` table exists with columns:
  - `id`, `year_label`, `is_active`, `is_locked`, `published_at`, `locked_at`, `created_at`, `updated_at`
- [ ] `regions` table exists
- [ ] `districts` table exists with `region_id` foreign key
- [ ] `schools` table exists with `district_id` and `region_id` foreign keys
- [ ] `bulk_imports` table exists with proper relationships
- [ ] `authentication_audit_logs` table exists

### Policies
- [ ] All policy classes in `app/Policies/` are syntactically correct
- [ ] User model has `isAdmin()` method
- [ ] User model has `irms_role` attribute

---

## Deployment Steps

### 1. Install & Publish

```bash
composer require filament/filament:"^3"
php artisan filament:install
php artisan make:filament-panel admin
```

✅ Filament is already installed. Skip if deploying to new environment.

### 2. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Verify Resources

```bash
php artisan tinker
# Type: exit
```

If no error, installation is successful.

### 4. Test Access

**Local**:
```bash
php artisan serve
# Visit http://localhost:8000/admin
```

**Production**:
- Deploy code
- Run: `php artisan config:cache` (after all env vars set)
- Visit: `https://yourdomain.com/admin`

### 5. Create Test Admin (if needed)

```bash
php artisan tinker
> $user = User::first();
> $user->irms_role = 'ROLE_ADMINISTRATOR';
> $user->save();
```

Or via migration/seeder in your data flow.

---

## Post-Deployment

### Verification

- [ ] Admin panel loads at `/admin`
- [ ] Can login with admin user
- [ ] Dashboard shows stats
- [ ] Exam Years resource loads
- [ ] Can view exam years list
- [ ] Regions/Districts/Schools resources load
- [ ] Bulk Imports resource loads (read-only)
- [ ] Audit Logs page displays data
- [ ] System Settings page accessible

### Navigation Check

- [ ] Sidebar shows all resource groups:
  - Exam Management
  - Geographic
  - Operations
- [ ] Non-admin users cannot access `/admin`
- [ ] School officers see 403 error on `/admin`

### Functional Tests

#### Exam Years

- [ ] Create new exam year
- [ ] Cannot edit locked year
- [ ] Can activate inactive year
- [ ] Activation deactivates others
- [ ] Can publish & lock year (one-way only)
- [ ] Cannot delete published year

#### Geographic Data

- [ ] Can view regions
- [ ] Can create region
- [ ] Can edit region
- [ ] Cannot delete region with schools
- [ ] Same for districts and schools

#### Bulk Imports

- [ ] Can view import list
- [ ] Pagination works
- [ ] Can filter by scope/status
- [ ] Can view import details
- [ ] No create/edit/delete buttons present

#### Audit Logs

- [ ] Can view logs
- [ ] Can search by username
- [ ] Can filter by event type
- [ ] Can filter by date range
- [ ] No delete option

---

## Monitoring

### Daily

- [ ] Check for unusual failed logins in Audit Logs
- [ ] Monitor active exam year status
- [ ] Check bulk import pending count

### Weekly

- [ ] Review all audit log entries
- [ ] Verify no unauthorized access attempts
- [ ] Check system performance (load times)

### Monthly

- [ ] Archive old audit logs (optional)
- [ ] Verify all geographic data is current
- [ ] Review system settings

---

## Rollback Plan

If issues occur:

1. **Disable Admin Panel** (comment out in `AdminPanelProvider.php`)
2. **Keep Operational Routes** (all other routes remain functional)
3. **Database**: No schema changes needed, can revert safely

```bash
# Disable admin panel
# Edit: app/Providers/Filament/AdminPanelProvider.php
# Remove it from bootstrap/providers.php

php artisan cache:clear
```

---

## Performance Baseline

Expected response times on reasonable hardware:

- Dashboard load: < 1 second
- Exam Years list (100 records): < 500ms
- Bulk Imports view: < 500ms
- Audit Logs (date range search): < 1 second

If slower:
- Check database indexes on `exam_years`, `bulk_imports`, `authentication_audit_logs`
- Verify no N+1 queries (use Debugbar)
- Check server resources (CPU, RAM, disk)

---

## Support Contacts

- **Filament Docs**: https://filamentphp.com/docs
- **Laravel Docs**: https://laravel.com/docs
- **IRMS Team**: [Internal contact]

---

## Sign-Off

- [ ] Lead Developer Reviewed
- [ ] QA Tested
- [ ] DBA Approved Database Schema
- [ ] Deployment Date: _______________
- [ ] Deployed By: _______________

---

## Notes

```
[Space for deployment notes and issues encountered]
```
