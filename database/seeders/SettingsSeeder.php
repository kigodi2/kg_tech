<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Migrate cache_ttl to cache_ttl_seconds if it exists and has not been migrated yet
        $oldCacheTtl = DB::table('system_settings')->where('key', 'cache_ttl')->first();
        if ($oldCacheTtl) {
            $existingNew = DB::table('system_settings')->where('key', 'cache_ttl_seconds')->exists();
            if (!$existingNew) {
                Setting::updateOrCreate(
                    ['key' => 'cache_ttl_seconds'],
                    [
                        'value' => $oldCacheTtl->value,
                        'type' => 'integer',
                        'group' => 'maintenance',
                        'description' => 'How long to cache queries (in seconds)',
                        'updated_by' => $oldCacheTtl->updated_by ?? null,
                    ]
                );
            }
            // Delete old cache_ttl to avoid confusion
            DB::table('system_settings')->where('key', 'cache_ttl')->delete();
        }

        // 2. Define all settings with their defaults
        $settings = [
            // General
            [
                'key' => 'system_name',
                'value' => 'IRMS Assessment System',
                'type' => 'string',
                'group' => 'general',
                'description' => 'System Name / Brand',
            ],
            [
                'key' => 'system_acronym',
                'value' => 'IRMS',
                'type' => 'string',
                'group' => 'general',
                'description' => 'System Acronym',
            ],
            [
                'key' => 'institution_name',
                'value' => 'National Examinations Council of Tanzania',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Institution Name',
            ],
            [
                'key' => 'region_name',
                'value' => 'Dar es Salaam',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Region Name',
            ],
            [
                'key' => 'council_name',
                'value' => 'Ilala',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Council Name',
            ],
            [
                'key' => 'active_academic_year',
                'value' => '2026',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Active Academic Year',
            ],
            [
                'key' => 'default_exam_year',
                'value' => '2026',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default Exam Year',
            ],
            [
                'key' => 'timezone',
                'value' => 'Africa/Dar_es_Salaam',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default Timezone',
            ],
            [
                'key' => 'default_language',
                'value' => 'English',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Default Language',
            ],
            [
                'key' => 'support_email',
                'value' => 'support@necta.go.tz',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Support Email Address',
            ],
            [
                'key' => 'support_phone',
                'value' => '+255 22 270 0493',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Support Phone Number',
            ],
            [
                'key' => 'system_logo',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'description' => 'Path to System Logo',
            ],

            // Examination
            [
                'key' => 'enabled_exam_levels',
                'value' => json_encode(['Class II', 'Standard IV', 'Standard VII', 'Form II', 'Form IV', 'Form VI']),
                'type' => 'json',
                'group' => 'examination',
                'description' => 'Enabled Exam Levels',
            ],
            [
                'key' => 'default_exam_type',
                'value' => 'PSLE',
                'type' => 'string',
                'group' => 'examination',
                'description' => 'Default Exam Type Code',
            ],
            [
                'key' => 'maximum_subject_score',
                'value' => '50',
                'type' => 'integer',
                'group' => 'examination',
                'description' => 'Maximum Mark per Subject',
            ],
            [
                'key' => 'minimum_subject_score',
                'value' => '0',
                'type' => 'integer',
                'group' => 'examination',
                'description' => 'Minimum Mark per Subject',
            ],
            [
                'key' => 'marks_decimal_places',
                'value' => '0',
                'type' => 'integer',
                'group' => 'examination',
                'description' => 'Decimal Places for Marks',
            ],
            [
                'key' => 'allow_decimal_marks',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'examination',
                'description' => 'Allow Decimal Marks entry',
            ],
            [
                'key' => 'absent_code',
                'value' => 'ABS',
                'type' => 'string',
                'group' => 'examination',
                'description' => 'Candidate Absent Code',
            ],
            [
                'key' => 'incomplete_code',
                'value' => 'INC',
                'type' => 'string',
                'group' => 'examination',
                'description' => 'Incomplete/Missing Mark Code',
            ],
            [
                'key' => 'withheld_code',
                'value' => 'WTH',
                'type' => 'string',
                'group' => 'examination',
                'description' => 'Withheld Status Code',
            ],
            [
                'key' => 'disqualified_code',
                'value' => 'FLD',
                'type' => 'string',
                'group' => 'examination',
                'description' => 'Disqualified/Cheated Code',
            ],

            // Results Processing
            [
                'key' => 'auto_calculate_totals',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Automatically Calculate Totals',
            ],
            [
                'key' => 'auto_calculate_average',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Automatically Calculate Average Marks',
            ],
            [
                'key' => 'auto_calculate_grade',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Automatically Assign Grades',
            ],
            [
                'key' => 'auto_calculate_division',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Automatically Assign Divisions',
            ],
            [
                'key' => 'ranking_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Enable Candidate/School Rankings',
            ],
            [
                'key' => 'ranking_scope',
                'value' => 'national',
                'type' => 'string',
                'group' => 'results_processing',
                'description' => 'Ranking Scope',
            ],
            [
                'key' => 'show_position_on_reports',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Show Position Rank on Reports',
            ],
            [
                'key' => 'result_approval_required',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Require Admin approval for results publication',
            ],
            [
                'key' => 'allow_reprocess_after_approval',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Allow reprocessing results after they are approved',
            ],
            [
                'key' => 'lock_results_after_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'results_processing',
                'description' => 'Lock results tables after approval is granted',
            ],

            // Grade & Division
            [
                'key' => 'grading_scale_id',
                'value' => 'NECTA_STANDARD',
                'type' => 'string',
                'group' => 'grade_division',
                'description' => 'Default Grading Scale ID',
            ],
            [
                'key' => 'division_rule_id',
                'value' => 'NECTA_DEFAULT',
                'type' => 'string',
                'group' => 'grade_division',
                'description' => 'Default Division Rule ID',
            ],
            [
                'key' => 'pass_mark',
                'value' => '20',
                'type' => 'integer',
                'group' => 'grade_division',
                'description' => 'Minimum Pass Mark per Subject',
            ],
            [
                'key' => 'use_division_points',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'grade_division',
                'description' => 'Use Division Points Calculation System',
            ],
            [
                'key' => 'best_subjects_count',
                'value' => '7',
                'type' => 'integer',
                'group' => 'grade_division',
                'description' => 'Number of best subjects counted for division points',
            ],
            [
                'key' => 'compulsory_subject_rules_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'grade_division',
                'description' => 'Apply compulsory subject rules (e.g. Mathematics)',
            ],

            // Registration
            [
                'key' => 'candidate_number_format',
                'value' => 'PS{YEAR}/{SCHOOL}/{NUMBER}',
                'type' => 'string',
                'group' => 'registration',
                'description' => 'Candidate Number Formatting Rule',
            ],
            [
                'key' => 'allow_duplicate_candidate_names',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Allow Duplicate Candidate Names in same school',
            ],
            [
                'key' => 'allow_candidate_transfer',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Allow Candidate transfer between centres',
            ],
            [
                'key' => 'require_candidate_photo',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Require Candidate Photo Upload',
            ],
            [
                'key' => 'require_gender',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Require Gender to be filled',
            ],
            [
                'key' => 'require_date_of_birth',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Require Date of Birth',
            ],
            [
                'key' => 'candidate_import_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'registration',
                'description' => 'Enable Candidate bulk CSV/Excel import',
            ],

            // Mark Entry
            [
                'key' => 'mark_entry_open',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mark_entry',
                'description' => 'Is Mark Entry Portal Active',
            ],
            [
                'key' => 'mark_entry_start_date',
                'value' => '2026-05-01',
                'type' => 'date',
                'group' => 'mark_entry',
                'description' => 'Mark Entry Start Date',
            ],
            [
                'key' => 'mark_entry_end_date',
                'value' => '2026-10-31',
                'type' => 'date',
                'group' => 'mark_entry',
                'description' => 'Mark Entry End Date',
            ],
            [
                'key' => 'allow_school_mark_entry',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mark_entry',
                'description' => 'Allow schools to input marks directly',
            ],
            [
                'key' => 'allow_bulk_mark_import',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mark_entry',
                'description' => 'Allow offline bulk import via Excel',
            ],
            [
                'key' => 'restrict_marks_between_0_and_100',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mark_entry',
                'description' => 'Restrict marks validation between 0 and 100',
            ],
            [
                'key' => 'require_mark_entry_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mark_entry',
                'description' => 'Require panel validation and approval',
            ],
            [
                'key' => 'lock_marks_after_submission',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'mark_entry',
                'description' => 'Lock marks entry forms after submission',
            ],

            // Reports
            [
                'key' => 'show_school_logo',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Render School Logo on report cards',
            ],
            [
                'key' => 'show_region_logo',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Render Regional Logo on summaries',
            ],
            [
                'key' => 'show_signatures',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Include official signatures in PDF printouts',
            ],
            [
                'key' => 'report_footer_text',
                'value' => 'This is an official document generated by IRMS.',
                'type' => 'text',
                'group' => 'reports',
                'description' => 'Official Report Footer Custom text',
            ],
            [
                'key' => 'report_watermark',
                'value' => 'CONFIDENTIAL',
                'type' => 'string',
                'group' => 'reports',
                'description' => 'Report PDF Watermark Text',
            ],
            [
                'key' => 'print_candidate_result_slips',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Allow printing candidate result slips',
            ],
            [
                'key' => 'print_school_summary',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Allow printing school summaries',
            ],
            [
                'key' => 'print_council_summary',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Allow printing council-level summaries',
            ],
            [
                'key' => 'print_regional_summary',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'reports',
                'description' => 'Allow printing regional summaries',
            ],

            // Notifications
            [
                'key' => 'email_notifications_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Enable Email Notifications system',
            ],
            [
                'key' => 'sms_notifications_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Enable SMS Alerts system via Twilio/etc',
            ],
            [
                'key' => 'notify_on_result_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Notify admin users when results are approved',
            ],
            [
                'key' => 'notify_on_mark_submission',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Notify panel leaders on school mark submission',
            ],
            [
                'key' => 'notify_on_import_failure',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notifications',
                'description' => 'Notify operator on bulk import background failure',
            ],

            // Security
            [
                'key' => 'session_timeout_minutes',
                'value' => '30',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Session Inactivity Timeout (Minutes)',
            ],
            [
                'key' => 'password_expiry_days',
                'value' => '90',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Mandatory Password Expiry cycle (Days)',
            ],
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Maximum failed login attempts before lockout',
            ],
            [
                'key' => 'two_factor_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Require Two-Factor Authenticated logins',
            ],
            [
                'key' => 'audit_log_retention_days',
                'value' => '365',
                'type' => 'integer',
                'group' => 'security',
                'description' => 'Retain governance logs database records (Days)',
            ],
            [
                'key' => 'restrict_admin_by_ip',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'security',
                'description' => 'Restrict admin panel login to whitelist',
            ],
            [
                'key' => 'allowed_admin_ips',
                'value' => '[]',
                'type' => 'json',
                'group' => 'security',
                'description' => 'Whitelisted admin IP addresses',
            ],

            // Maintenance
            [
                'key' => 'cache_ttl_seconds',
                'value' => '3600',
                'type' => 'integer',
                'group' => 'maintenance',
                'description' => 'How long to cache queries (in seconds)',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'maintenance',
                'description' => 'Put system in maintenance lockout mode',
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'The system is undergoing routine maintenance. Please try again later.',
                'type' => 'text',
                'group' => 'maintenance',
                'description' => 'Maintenance Mode public warning message',
            ],
            [
                'key' => 'system_notes',
                'value' => 'System initialized with custom settings architecture.',
                'type' => 'text',
                'group' => 'maintenance',
                'description' => 'Internal administrator notes and logs space',
            ],
        ];

        foreach ($settings as $settingData) {
            Setting::firstOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $settingData['value'],
                    'type' => $settingData['type'],
                    'group' => $settingData['group'],
                    'description' => $settingData['description'],
                ]
            );
        }
    }
}
