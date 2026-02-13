# Hardened Restore - Filament Admin Page Integration

## Files Created

✅ **app/Filament/Admin/Pages/HardenedRestore.php** (Filament page class)  
✅ **resources/views/filament/admin/pages/hardened-restore.blade.php** (UI view)

## How to Register the Page

The Filament page is automatically discoverable if placed in the correct namespace:
- `app/Filament/Admin/Pages/` with class extending `Filament\Pages\Page`

### Option 1: Automatic Discovery (Recommended)

Filament automatically discovers pages in the `Pages` directory. The `HardenedRestore` class will be automatically registered.

**Access via:**
```
http://localhost:8000/admin/hardened-restore
```

### Option 2: Manual Registration (If Auto-Discovery Doesn't Work)

Edit your Filament panel configuration (usually in `app/Providers/Filament*Provider.php` or `config/filament.php`):

```php
->pages([
    \App\Filament\Admin\Pages\HardenedRestore::class,
])
```

### Option 3: Navigation Menu Update

To add to the sidebar navigation, update `app/Filament/Admin/Pages/HardenedRestore.php`:

The page already has these properties set:
```php
protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
protected static ?string $navigationLabel = 'Restore Database';
protected static ?int $navigationSort = 3;
```

This will automatically add it to the navigation menu.

## Features Implemented

### ✅ Multi-Step Restoration Workflow

**Step 1: Select Backup**
- Input backup file path
- Validate backup integrity (calls API endpoint)
- Display validation errors or success

**Step 2: Legal Acknowledgment**
- Display NECTA-style legal notice
- Require checkbox acceptance
- Require "RESTORE" confirmation text
- Require minimum 10-character reason

**Step 3: Confirmation**
- Show restore summary (operator, role, backup, reason)
- Final confirmation checkbox
- Display point-of-no-return warning

**Step 4: Execute Restore**
- Call restore API endpoint
- Show loading state during restore
- Display success with audit log ID, timestamp, quarantine location
- OR display error with recovery instructions

### ✅ Audit Log Viewer

- Display recent restore operations in table
- Show operator name, status, backup, date, reason
- Color-coded status badges (green/red/yellow)
- Export audit logs as CSV

### ✅ Full Error Handling

- Validation errors display with details
- API errors caught and displayed
- Recovery instructions provided
- Loading states prevent duplicate submissions

### ✅ Responsive Design

- Mobile-friendly layout
- Progress indicator at top
- Clear visual hierarchy
- Color-coded warnings and confirmations

## API Integration

The page makes HTTP requests to 6 REST endpoints:

```
GET  /api/restore/legal-text          ← Get legal text
POST /api/restore/validate            ← Validate backup
POST /api/restore/confirm             ← Get confirmation data
POST /api/restore/execute             ← Execute restore (DESTRUCTIVE)
GET  /api/restore/audit-logs          ← Load audit logs
POST /api/restore/audit-export        ← Export audit logs as CSV
```

All requests include Bearer token authentication using user's Sanctum token.

## Testing the Page

### 1. Access the Page
```
http://localhost:8000/admin/hardened-restore
```

### 2. Test Backup Selection
```
Enter: storage/backups/irms-backup-full-system-2026-02-02_102000.zip
Click: Validate Backup
Expected: Shows "Backup validation passed" or displays validation errors
```

### 3. Test Legal Acknowledgment
```
Check: "I understand..." checkbox
Type: "RESTORE" in confirmation field
Enter: Restore reason (min 10 chars)
Click: Proceed to Confirmation
Expected: Moves to Step 3 with restore summary
```

### 4. Test Confirmation
```
Check: Final confirmation checkbox
Click: Execute Restore (IRREVERSIBLE)
Expected: Shows loading state, then success or error result
```

### 5. Test Audit Logs
```
View: Table of recent restores
Click: Export Audit Log (CSV)
Expected: Downloads CSV file with restore history
```

## Troubleshooting

### Page Not Found (404)

**Solution:** Clear Filament cache
```bash
php artisan filament:clear-cached-components
php artisan cache:clear
```

### API Endpoints Return 401 (Unauthorized)

**Cause:** Sanctum token generation failed  
**Solution:** Ensure user is authenticated and has admin role

### Legal Text Not Loading

**Cause:** API endpoint not working  
**Solution:** Check API routes are registered:
```bash
php artisan route:list | grep restore
```

### Restore Fails Silently

**Cause:** JavaScript error in browser console  
**Solution:** Check browser console for errors, check Laravel logs

## Customization

### Change Navigation Icon
Edit in `HardenedRestore.php`:
```php
protected static ?string $navigationIcon = 'heroicon-o-your-icon';
```

### Change Navigation Label
```php
protected static ?string $navigationLabel = 'Your Label';
```

### Change Navigation Sort Order
```php
protected static ?int $navigationSort = 3; // Lower numbers appear first
```

### Styling
All styles are inline in the Blade view using Tailwind CSS classes.

## Security

✅ Authorization: `authorizeAccess()` checks if user is admin  
✅ CSRF Protection: Automatically handled by Filament/Laravel  
✅ API Authentication: All requests include Bearer token  
✅ Legal Acknowledgment: Required before restore  
✅ Confirmation Text: Must type exact "RESTORE"  
✅ Reason Required: Minimum 10 characters  

## Performance

- Lazy loads audit logs on page load
- Shows loading indicators during API calls
- Prevents duplicate submissions with disabled buttons
- Efficient table rendering of audit logs
- Single API call to fetch legal text

## Browser Support

- Chrome/Edge: ✅
- Firefox: ✅
- Safari: ✅
- IE11: ❌ (Uses ES6+ syntax)

## Next Steps

1. ✅ Page created and registered
2. Visit `/admin/hardened-restore` to access the UI
3. Test with sample backup file
4. Train operators on workflow
5. Document in examination authority guidelines
