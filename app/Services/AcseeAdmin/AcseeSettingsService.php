<?php

namespace App\Services\AcseeAdmin;

use App\Models\AcseeSettingsHistory;
use App\Models\SystemEventLog;
use App\Models\SystemSetting;

class AcseeSettingsService
{
    public static function defaults(): array
    {
        return [
            'acsee.require_submit_before_lock' => [
                'value' => '1', 'type' => 'boolean', 'group' => 'workflow',
                'description' => 'Require marks to be submitted before they can be locked',
            ],
            'acsee.reports_locked_only' => [
                'value' => '1', 'type' => 'boolean', 'group' => 'workflow',
                'description' => 'Reports only include data from LOCKED batches',
            ],
            'acsee.allow_draft_preview_pdfs' => [
                'value' => '0', 'type' => 'boolean', 'group' => 'workflow',
                'description' => 'Allow PDF preview generation for draft/unsubmitted batches',
            ],
            'acsee.auto_create_batches_on_import' => [
                'value' => '1', 'type' => 'boolean', 'group' => 'workflow',
                'description' => 'Automatically create batch records when marks are imported',
            ],
            'acsee.mark_range_min' => [
                'value' => '0', 'type' => 'integer', 'group' => 'validation',
                'description' => 'Minimum valid mark value',
            ],
            'acsee.mark_range_max' => [
                'value' => '100', 'type' => 'integer', 'group' => 'validation',
                'description' => 'Maximum valid mark value',
            ],
            'acsee.strict_header_matching' => [
                'value' => '1', 'type' => 'boolean', 'group' => 'validation',
                'description' => 'Require exact CSV header matching (no fuzzy matching)',
            ],
            'acsee.duplicate_import_policy' => [
                'value' => 'warn', 'type' => 'string', 'group' => 'validation',
                'description' => 'How to handle duplicate imports: block, warn, or allow-with-admin',
            ],
            'acsee.csv_template_version' => [
                'value' => '1.0', 'type' => 'string', 'group' => 'import',
                'description' => 'Current CSV template version string',
            ],
            'acsee.zip_max_size_mb' => [
                'value' => '50', 'type' => 'integer', 'group' => 'import',
                'description' => 'Maximum ZIP file upload size in megabytes',
            ],
        ];
    }

    public function getGroupedSettings(): array
    {
        $defaults = self::defaults();
        $stored = SystemSetting::where('key', 'like', 'acsee.%')->get()->keyBy('key');

        $groups = [];
        foreach ($defaults as $key => $def) {
            $setting = $stored->get($key);
            $group = $def['group'] ?? 'general';

            $groups[$group][] = [
                'key' => $key,
                'value' => $setting ? $setting->value : $def['value'],
                'type' => $def['type'],
                'group' => $group,
                'description' => $def['description'],
                'updated_by' => $setting?->updated_by,
                'updated_at' => $setting?->updated_at?->toIso8601String(),
            ];
        }

        return $groups;
    }

    public function updateSetting(string $key, string $newValue, int $userId): array
    {
        $defaults = self::defaults();
        if (!isset($defaults[$key])) {
            return ['ok' => false, 'message' => "Unknown setting key: {$key}"];
        }

        $def = $defaults[$key];
        $existing = SystemSetting::where('key', $key)->first();
        $oldValue = $existing?->value;

        if ($def['type'] === 'integer' && !is_numeric($newValue)) {
            return ['ok' => false, 'message' => 'Value must be a number'];
        }
        if ($def['type'] === 'boolean' && !in_array($newValue, ['0', '1', 'true', 'false'])) {
            return ['ok' => false, 'message' => 'Value must be boolean (0/1)'];
        }

        if ($def['type'] === 'boolean') {
            $newValue = in_array($newValue, ['1', 'true']) ? '1' : '0';
        }

        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $newValue,
                'type' => $def['type'],
                'description' => $def['description'],
                'updated_by' => $userId,
            ]
        );

        AcseeSettingsHistory::create([
            'setting_id' => $setting->id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $userId,
            'created_at' => now(),
        ]);

        SystemEventLog::record(
            SystemEventLog::CAT_ADMIN,
            'setting_updated',
            SystemEventLog::STATUS_SUCCESS,
            "Setting '{$key}' changed from '{$oldValue}' to '{$newValue}'",
            ['key' => $key, 'old_value' => $oldValue, 'new_value' => $newValue]
        );

        return ['ok' => true, 'setting' => $setting];
    }

    public function getHistory(string $key): array
    {
        $setting = SystemSetting::where('key', $key)->first();
        if (!$setting) {
            return [];
        }

        return AcseeSettingsHistory::where('setting_id', $setting->id)
            ->with('changedByUser:id,name,first_name,last_name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function restoreFromHistory(int $historyId, int $userId): array
    {
        $history = AcseeSettingsHistory::find($historyId);
        if (!$history) {
            return ['ok' => false, 'message' => 'History record not found'];
        }

        $setting = $history->setting;
        if (!$setting) {
            return ['ok' => false, 'message' => 'Setting no longer exists'];
        }

        return $this->updateSetting($setting->key, $history->old_value, $userId);
    }

    public function ensureDefaults(): void
    {
        foreach (self::defaults() as $key => $def) {
            SystemSetting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $def['value'],
                    'type' => $def['type'],
                    'description' => $def['description'],
                ]
            );
        }
    }
}
