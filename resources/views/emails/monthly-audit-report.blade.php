@component('mail::message')
# 📊 IRMS Monthly Audit Report

Dear {{ $admin->name }},

Your monthly audit report for **{{ $report['period'] }}** is ready.

## Summary

| Metric | Value |
|--------|-------|
| Total Events | {{ $report['summary']['total_events'] }} |
| Unique Users | {{ $report['summary']['unique_users'] }} |
| Unique Admins | {{ $report['summary']['unique_admins'] }} |
| Action Types | {{ $report['summary']['action_types'] }} |

## Key Statistics

### Authentication
- **Successful Logins**: {{ $report['statistics']['logins_successful'] }}
- **Failed Logins**: {{ $report['statistics']['logins_failed'] }}
- **Success Rate**: {{ round($report['logins']['success_rate'] ?? 0) }}%

### Imports
- **Imports Initiated**: {{ $report['statistics']['imports_initiated'] }}
- **Imports Completed**: {{ $report['statistics']['imports_completed'] }}
- **Imports Failed**: {{ $report['statistics']['imports_failed'] }}
- **Total Records Imported**: {{ $report['imports']['total_records_imported'] }}

### User Management
- **Users Created**: {{ $report['statistics']['users_created'] }}
- **Users Suspended**: {{ $report['statistics']['users_suspended'] }}
- **Password Resets**: {{ $report['statistics']['password_resets'] }}

## Security Events

@if(!empty($report['security_events']))
@foreach($report['security_events'] as $event)
### {{ $event['type'] }}: {{ $event['count'] }} event(s)
@endforeach
@else
✓ No security incidents detected
@endif

## Top Active Users

@if(!empty($report['user_activity']))
| User | Email | Events | Logins | Imports |
|------|-------|--------|--------|---------|
@foreach($report['user_activity'] as $activity)
| {{ $activity['user_name'] }} | {{ $activity['email'] }} | {{ $activity['events'] }} | {{ $activity['logins'] }} | {{ $activity['imports'] }} |
@endforeach
@endif

---

**Report Period**: {{ $report['start_date'] }} to {{ $report['end_date'] }}

**Generated**: {{ now()->format('Y-m-d H:i:s') }}

@component('mail::button', ['url' => route('filament.admin.pages.dashboard')])
View Complete Audit Logs
@endcomponent

---

This is an automated report from your IRMS Audit System.

@endcomponent
