<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcseeRolePermission extends Model
{
    protected $table = 'acsee_role_permissions';

    protected $fillable = ['role_id', 'permission', 'granted', 'granted_by', 'granted_at'];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function grantedByUser()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public static function roleHas(int $roleId, string $permission): bool
    {
        return self::where('role_id', $roleId)
            ->where('permission', $permission)
            ->where('granted', true)
            ->exists();
    }

    public static function forRole(int $roleId): array
    {
        return self::where('role_id', $roleId)
            ->where('granted', true)
            ->pluck('permission')
            ->toArray();
    }

    public static function definedPermissions(): array
    {
        return [
            'acsee.upload_marks' => 'Upload marks CSV/ZIP files',
            'acsee.view_moderation' => 'View moderation dashboard',
            'acsee.approve_marks' => 'Approve mark batches',
            'acsee.reject_marks' => 'Reject mark batches',
            'acsee.submit_marks' => 'Submit marks for review',
            'acsee.lock_marks' => 'Lock approved batches',
            'acsee.export_reports' => 'Export reports and scoresheets',
            'acsee.view_analytics' => 'View analytics dashboard',
            'acsee.admin.configuration' => 'Manage system configuration',
            'acsee.admin.permissions' => 'Manage roles and permissions',
            'acsee.admin.batch_management' => 'Administer batch lifecycle',
            'acsee.admin.system_logs' => 'View system event logs',
            'acsee.admin.unlock' => 'Admin unlock locked batches',
        ];
    }
}
