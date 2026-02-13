@php
    $warningText = <<<'TEXT'
⚠️  CRITICAL WARNING ⚠️

This operation will REPLACE the ENTIRE examination database.

WHAT WILL BE LOST:
✗ All current examination results
✗ All student registrations  
✗ All marks and grades
✗ All candidate information

WHAT WILL HAPPEN:
• The current database will be moved to quarantine
• A previous backup will be restored
• If restoration fails, the system will automatically recover from quarantine
• This operation is IRREVERSIBLE

AUTHORIZATION REQUIREMENT:
This action must only be performed:
1. By authorized examination personnel
2. According to examination data governance regulations
3. With documented justification
4. With full understanding of the consequences

AUDIT TRAIL:
This restore operation will be permanently recorded in the audit log with:
- Your name and role
- Exact timestamp and date
- IP address and device information
- Your justification for the restore
- Success or failure status

By proceeding, you accept FULL RESPONSIBILITY for this operation
and agree that it complies with examination authority regulations.
TEXT;
@endphp

<div class="rounded-lg border-2 border-red-300 bg-red-50 p-6">
    <div class="flex items-start gap-4">
        <svg class="mt-1 h-6 w-6 flex-shrink-0 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div class="flex-1">
            <h3 class="font-bold text-red-900">EXAMINATION DATABASE RESTORE OPERATION</h3>
            <p class="mt-2 whitespace-pre-wrap text-sm text-red-800">{{ $warningText }}</p>
        </div>
    </div>
</div>

<style>
    .warning-text {
        font-family: 'Courier New', monospace;
        white-space: pre-wrap;
    }
</style>
