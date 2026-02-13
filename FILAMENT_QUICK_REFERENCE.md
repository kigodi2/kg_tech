# Filament Admin Panel - Quick Reference

## URLs

| Path | Resource | Purpose |
|------|----------|---------|
| `/admin` | Dashboard | Overview & stats |
| `/admin/exam-years` | Exam Years | Manage academic years |
| `/admin/regions` | Regions | Geographic data |
| `/admin/districts` | Districts | Geographic data |
| `/admin/schools` | Schools | Geographic data |
| `/admin/bulk-imports` | Bulk Imports | View import history (read-only) |
| `/admin/audit-logs` | Audit Logs | View authentication logs (immutable) |
| `/admin/system-settings` | System Settings | Configuration |

## File Locations

```
app/
├── Filament/Admin/
│   ├── Resources/
│   │   ├── ExamYearResource.php
│   │   ├── RegionResource.php
│   │   ├── DistrictResource.php
│   │   ├── SchoolResource.php
│   │   └── BulkImportResource.php
│   ├── Pages/
│   │   ├── Dashboard.php
│   │   ├── AuditLogs.php
│   │   └── SystemSettings.php
│   └── Widgets/
│       ├── StatsOverview.php
│       ├── ExamYearOverview.php
│       └── BulkImportStats.php
├── Policies/
│   ├── AdminAccessPolicy.php
│   ├── ExamYearPolicy.php
│   ├── RegionPolicy.php
│   ├── DistrictPolicy.php
│   └── SchoolPolicy.php
└── Providers/Filament/
    └── AdminPanelProvider.php

resources/views/filament/admin/
├── widgets/
│   ├── exam-year-overview.blade.php
│   └── bulk-import-stats.blade.php
└── pages/
    ├── audit-logs.blade.php
    └── system-settings.blade.php
```

## Key Classes

### Resources
- `ExamYearResource` - Manage exam years, publish, activate, lock
- `RegionResource` - View/create/edit/delete regions
- `DistrictResource` - View/create/edit/delete districts
- `SchoolResource` - View/create/edit/delete schools
- `BulkImportResource` - Read-only view of bulk imports

### Pages
- `Dashboard` - Stats and overview (custom)
- `AuditLogs` - Authentication log viewer
- `SystemSettings` - Limited configuration

### Policies
- All in `app/Policies/`
- Control create, read, update, delete, publish, activate actions
- Respect locked years and hierarchical constraints

## Common Tasks

### Add a Field to Exam Year Form

```php
// app/Filament/Admin/Resources/ExamYearResource.php
public static function form(Form $form): Form {
    return $form->schema([
        Forms\Components\Section::make('Exam Year Details')->schema([
            // Add here:
            Forms\Components\TextInput::make('description')
                ->label('Description')
                ->columnSpanFull(),
        ]),
    ]);
}
```

### Add a Column to School Table

```php
// app/Filament/Admin/Resources/SchoolResource.php
public static function table(Table $table): Table {
    return $table->columns([
        // Add here:
        Tables\Columns\TextColumn::make('email')
            ->label('Email')
            ->searchable(),
    ]);
}
```

### Add a Filter

```php
->filters([
    Tables\Filters\SelectFilter::make('is_active')
        ->options([true => 'Active', false => 'Inactive']),
])
```

### Customize Dashboard

```php
// app/Filament/Admin/Pages/Dashboard.php
public function getWidgets(): array {
    return [
        \App\Filament\Admin\Widgets\StatsOverview::class,
        \App\Filament\Admin\Widgets\ExamYearOverview::class,
        \App\Filament\Admin\Widgets\CustomWidget::class, // Add here
    ];
}
```

## Authorization

### Check in Controller

```php
if (!auth()->user()->isAdmin()) {
    abort(403);
}
```

### Check in Policy

```php
// app/Policies/ExamYearPolicy.php
public function update(User $user, ExamYear $year): bool {
    if (!$user->isAdmin()) return false;
    if ($year->isLocked()) return false;
    return true;
}
```

### Enforce in Resource

Filament automatically enforces policies:
```php
Tables\Actions\EditAction::make()
    // Automatically hidden if policy denies update()
```

## Query Optimization

### Eager Loading

```php
// In Resource table():
Tables\Columns\TextColumn::make('examYear.year_label')
    // Loads relation automatically
```

### Add with() if needed

```php
// In Resource class
public static function getEloquentQuery(): Builder {
    return parent::getEloquentQuery()->with('examYear', 'createdBy');
}
```

### Pagination

Default pagination applied automatically. To change:

```php
->paginated([10, 25, 50, 100])
```

## Navigation

### Control Visibility

```php
class MyResource extends Resource {
    protected static ?string $navigationGroup = 'Exam Management';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true; // Hide if false
}
```

### Custom Navigation Label

```php
protected static ?string $navigationLabel = 'Custom Label';
```

## Testing

### View Generated SQL

```bash
php artisan tinker
> DB::listen(fn($q) => dump($q->sql));
> \App\Models\ExamYear::with('candidates')->get();
```

### Test a Policy

```bash
php artisan tinker
> $user = User::first();
> $year = ExamYear::first();
> auth()->setUser($user);
> $user->can('update', $year)
```

## Debugging

### Enable Query Logging

```php
// In AdminPanelProvider or AppServiceProvider
if (config('app.debug')) {
    DB::listen(fn($query) => \Log::info($query->sql, $query->bindings));
}
```

### Check Policies are Registered

```bash
php artisan tinker
> auth()->user()->can('create', \App\Models\ExamYear::class)
```

### Verify Resource Discovery

```bash
php artisan tinker
> collect(app()->getProviders())->pluck('class')->grep('Filament')
```

## Performance Tips

1. **Paginate** - Always paginate large tables (default: 25)
2. **Eager Load** - Use `with()` to avoid N+1 queries
3. **Index Columns** - Add DB indexes to:
   - `exam_years.is_active`
   - `exam_years.is_locked`
   - `bulk_imports.status`
   - `authentication_audit_logs.user_id`
   - `authentication_audit_logs.created_at`
4. **Count Cache** - Use `withCount()` instead of eager loading full relations
5. **Cache Query** - For slow aggregates:
   ```php
   $count = Cache::remember('total_schools', 3600, fn() => School::count());
   ```

## Common Errors

### "Not authorized to view this resource"
**Cause**: Policy denies access  
**Fix**: Check policy `viewAny()` method

### "Call to undefined method"
**Cause**: Typo in column/form field name  
**Fix**: Match database column name exactly

### "Too many queries" / Slow
**Cause**: N+1 problem  
**Fix**: Add `->with()` to load relations

### "Relation not found"
**Cause**: Model doesn't have the relation method  
**Fix**: Check model has `public function otherModel()`

## Useful Links

- Filament Docs: https://filamentphp.com/docs
- Forms: https://filamentphp.com/docs/3.x/forms
- Tables: https://filamentphp.com/docs/3.x/tables
- Resources: https://filamentphp.com/docs/3.x/resources
- Actions: https://filamentphp.com/docs/3.x/actions
- Laravel Policies: https://laravel.com/docs/authorization#creating-policies

## Command Cheat Sheet

```bash
# Make a new resource
php artisan make:filament-resource Admin/MyModel

# Make pages for resource
php artisan make:filament-resource-pages

# Make a widget
php artisan make:filament-widget MyWidget

# Clear caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Test an artisan command
php artisan tinker
```

## Version Info

- **Filament**: 3.x
- **Laravel**: 12.x
- **PHP**: 8.2+
- **Last Updated**: 2026-02-01
