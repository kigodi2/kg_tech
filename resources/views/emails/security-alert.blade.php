@component('mail::message')
# 🔒 IRMS Security Alert

Dear {{ $admin->name }},

Suspicious activity has been detected in your IRMS system. Please review the alerts below immediately.

**Timestamp**: {{ $timestamp->format('Y-m-d H:i:s') }}

@foreach($alerts as $alert)
@component('mail::panel')
### {{ strtoupper($alert['type']) }} - Severity: {{ $alert['severity'] }}

**{{ $alert['description'] }}**

{{ $alert['details'] }}
@endcomponent
@endforeach

## Recommended Actions

@if(in_array('BRUTE_FORCE_ATTEMPT', array_column($alerts, 'type')))
- ✓ Check failed login attempts in Audit Logs
- ✓ Consider temporarily locking affected account(s)
- ✓ Review login policies
@endif

@if(in_array('MULTIPLE_FAILED_LOGINS', array_column($alerts, 'type')))
- ✓ Review authentication logs in detail
- ✓ Identify affected email addresses
- ✓ Consider implementing IP-based rate limiting
@endif

@if(in_array('UNAUTHORIZED_SCOPE_ATTEMPTS', array_column($alerts, 'type')))
- ✓ Check which users are attempting unauthorized access
- ✓ Review user scope assignments
- ✓ Consider retraining users on proper scope usage
@endif

@if(in_array('HIGH_IMPORT_FAILURE_RATE', array_column($alerts, 'type')))
- ✓ Check import error logs for details
- ✓ Verify CSV file formats are correct
- ✓ Contact affected users for troubleshooting
@endif

@if(in_array('MULTIPLE_SUSPENSIONS', array_column($alerts, 'type')))
- ✓ Review suspension reasons
- ✓ Verify if suspensions were authorized
- ✓ Check for suspicious patterns
@endif

## Access Your Dashboard

@component('mail::button', ['url' => route('filament.admin.pages.dashboard')])
View Full Audit Logs
@endcomponent

---

This is an automated alert from your IRMS Security Monitoring System. Please do not reply to this email.

@endcomponent
