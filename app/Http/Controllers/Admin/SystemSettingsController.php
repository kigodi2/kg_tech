<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Models\GovernanceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SystemSettingsController extends Controller
{
    protected $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Display the settings index page
     */
    public function index()
    {
        $groupedSettings = $this->settingsService->getGroupedSettings();
        
        // Load some extra data if needed, like existing grading scale and division rule options
        $gradingScales = [
            'NECTA_STANDARD' => 'NECTA Standard Grading Scale',
            'NECTA_BASIC' => 'NECTA Basic Certificate Scale',
            'CUSTOM_PERCENTAGE' => 'Custom Percentage-Based Scale',
        ];

        $divisionRules = [
            'NECTA_DEFAULT' => 'NECTA Default Division Rules',
            'NECTA_POINTS_7' => 'NECTA Best 7 Subjects Points Rules',
            'CUSTOM_AVERAGE' => 'Custom GPA/Average Rules',
        ];

        return view('admin.system-settings.index', compact('groupedSettings', 'gradingScales', 'divisionRules'));
    }

    /**
     * Update system settings
     */
    public function update(Request $request)
    {
        $rules = [
            // General
            'system_name' => 'required|string|max:255',
            'system_acronym' => 'required|string|max:50',
            'institution_name' => 'required|string|max:255',
            'region_name' => 'required|string|max:100',
            'council_name' => 'required|string|max:100',
            'active_academic_year' => 'required|string|max:10',
            'default_exam_year' => 'required|string|max:10',
            'timezone' => 'required|string|max:100',
            'default_language' => 'required|string|max:50',
            'support_email' => 'nullable|email|max:255',
            'support_phone' => 'nullable|string|max:50',
            'system_logo' => 'nullable|image|max:2048', // max 2MB

            // Examination
            'enabled_exam_levels' => 'nullable|array',
            'default_exam_type' => 'required|string|max:20',
            'maximum_subject_score' => 'required|numeric|min:0|max:100',
            'minimum_subject_score' => 'required|numeric|min:0|max:100',
            'marks_decimal_places' => 'required|integer|min:0|max:2',
            'allow_decimal_marks' => 'required|boolean',
            'absent_code' => 'required|string|max:10',
            'incomplete_code' => 'required|string|max:10',
            'withheld_code' => 'required|string|max:10',
            'disqualified_code' => 'required|string|max:10',

            // Results Processing
            'auto_calculate_totals' => 'required|boolean',
            'auto_calculate_average' => 'required|boolean',
            'auto_calculate_grade' => 'required|boolean',
            'auto_calculate_division' => 'required|boolean',
            'ranking_enabled' => 'required|boolean',
            'ranking_scope' => 'required|string|in:national,region,council,school',
            'show_position_on_reports' => 'required|boolean',
            'result_approval_required' => 'required|boolean',
            'allow_reprocess_after_approval' => 'required|boolean',
            'lock_results_after_approval' => 'required|boolean',

            // Grade & Division
            'grading_scale_id' => 'required|string|max:50',
            'division_rule_id' => 'required|string|max:50',
            'pass_mark' => 'required|numeric|min:0|max:100',
            'use_division_points' => 'required|boolean',
            'best_subjects_count' => 'required|integer|min:1',
            'compulsory_subject_rules_enabled' => 'required|boolean',

            // Registration
            'candidate_number_format' => 'required|string|max:255',
            'allow_duplicate_candidate_names' => 'required|boolean',
            'allow_candidate_transfer' => 'required|boolean',
            'require_candidate_photo' => 'required|boolean',
            'require_gender' => 'required|boolean',
            'require_date_of_birth' => 'required|boolean',
            'candidate_import_enabled' => 'required|boolean',

            // Mark Entry
            'mark_entry_open' => 'required|boolean',
            'mark_entry_start_date' => 'nullable|date',
            'mark_entry_end_date' => 'nullable|date|after_or_equal:mark_entry_start_date',
            'allow_school_mark_entry' => 'required|boolean',
            'allow_bulk_mark_import' => 'required|boolean',
            'restrict_marks_between_0_and_100' => 'required|boolean',
            'require_mark_entry_approval' => 'required|boolean',
            'lock_marks_after_submission' => 'required|boolean',

            // Reports
            'show_school_logo' => 'required|boolean',
            'show_region_logo' => 'required|boolean',
            'show_signatures' => 'required|boolean',
            'report_footer_text' => 'nullable|string',
            'report_watermark' => 'nullable|string|max:100',
            'print_candidate_result_slips' => 'required|boolean',
            'print_school_summary' => 'required|boolean',
            'print_council_summary' => 'required|boolean',
            'print_regional_summary' => 'required|boolean',

            // Notifications
            'email_notifications_enabled' => 'required|boolean',
            'sms_notifications_enabled' => 'required|boolean',
            'notify_on_result_approval' => 'required|boolean',
            'notify_on_mark_submission' => 'required|boolean',
            'notify_on_import_failure' => 'required|boolean',

            // Security
            'session_timeout_minutes' => 'required|integer|min:5',
            'password_expiry_days' => 'nullable|integer|min:0',
            'max_login_attempts' => 'required|integer|min:1',
            'two_factor_enabled' => 'required|boolean',
            'audit_log_retention_days' => 'required|integer|min:30',
            'restrict_admin_by_ip' => 'required|boolean',
            'allowed_admin_ips' => 'nullable|string',

            // Maintenance
            'cache_ttl_seconds' => 'required|integer|min:60',
            'maintenance_mode' => 'required|boolean',
            'maintenance_message' => 'required|string',
            'system_notes' => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        // Handle IP whitelist conversion from text block to array
        $allowedIpsInput = $request->input('allowed_admin_ips');
        $ipsArray = [];
        if (!empty($allowedIpsInput)) {
            $ipsArray = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $allowedIpsInput))));
            foreach ($ipsArray as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    return redirect()->back()->withErrors(['allowed_admin_ips' => "Invalid IP address format: '{$ip}'."])->withInput();
                }
            }
        }
        $validated['allowed_admin_ips'] = json_encode($ipsArray);

        // Handle Enabled Exam Levels array to JSON
        $enabledExamLevels = $request->input('enabled_exam_levels', []);
        $validated['enabled_exam_levels'] = json_encode($enabledExamLevels);

        // Track updated keys for audit logging
        $changedKeys = [];

        // Save normal setting parameters
        foreach ($validated as $key => $value) {
            if ($key === 'system_logo') {
                continue;
            }

            $oldValue = $this->settingsService->get($key);
            
            // For JSON data, let's decode old/new value to do a proper comparison
            if ($key === 'allowed_admin_ips' || $key === 'enabled_exam_levels') {
                $oldValDecoded = is_string($oldValue) ? json_decode($oldValue, true) : $oldValue;
                $newValDecoded = json_decode($value, true);
                if (serialize($oldValDecoded) !== serialize($newValDecoded)) {
                    $this->settingsService->set($key, $value, auth()->id());
                    $changedKeys[] = $key;
                }
                continue;
            }

            // Perform direct comparison
            if (is_bool($oldValue)) {
                $newBool = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                if ($oldValue !== $newBool) {
                    $this->settingsService->set($key, $newBool, auth()->id());
                    $changedKeys[] = $key;
                }
            } else {
                if ((string)$oldValue !== (string)$value) {
                    $this->settingsService->set($key, $value, auth()->id());
                    $changedKeys[] = $key;
                }
            }
        }

        // Handle System Logo file upload
        if ($request->hasFile('system_logo')) {
            $path = $request->file('system_logo')->store('system-branding', 'public');
            $this->settingsService->set('system_logo', $path, auth()->id());
            $changedKeys[] = 'system_logo';
        }

        // Log to GovernanceAuditLog if any changes were made
        if (!empty($changedKeys)) {
            GovernanceAuditLog::log(
                'system_settings_updated',
                userId: auth()->id(),
                adminId: auth()->id(),
                data: [
                    'updated_keys' => $changedKeys,
                    'source' => 'App\Http\Controllers\Admin\SystemSettingsController',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        }

        $this->settingsService->clearCache();

        return redirect()->route('admin.system-settings')->with('success', 'System settings saved successfully.');
    }

    /**
     * Clear system caches
     */
    public function clearCache()
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            
            $this->settingsService->clearCache();

            // Log Danger Zone action
            GovernanceAuditLog::log(
                'danger_clear_cache',
                userId: auth()->id(),
                adminId: auth()->id(),
                data: [
                    'action' => 'clear_cache',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            );

            return redirect()->route('admin.system-settings')->with('success', 'System caches, compiled views, and config maps cleared successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.system-settings')->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }
}
