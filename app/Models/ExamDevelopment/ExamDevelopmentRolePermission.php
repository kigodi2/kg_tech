<?php

namespace App\Models\ExamDevelopment;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExamDevelopmentRolePermission extends Model
{
    protected $table = 'exam_development_role_permissions';

    protected $fillable = [
        'role_id',
        'permission',
        'granted',
        'granted_by',
        'granted_at',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public static function roleHas(?int $roleId, string $permission): bool
    {
        if (!$roleId) {
            return false;
        }

        return static::query()
            ->where('role_id', $roleId)
            ->where('permission', $permission)
            ->where('granted', true)
            ->exists();
    }

    public static function definedPermissions(): array
    {
        return [
            'exam-development.view' => 'Access the Exam Development module',
            'exam-development.manage-formats' => 'Create and maintain official subject formats',
            'exam-development.manage-blueprints' => 'Maintain format blueprints and topic coverage',
            'exam-development.create-project' => 'Create exam development projects',
            'exam-development.edit-project' => 'Edit exam development projects',
            'exam-development.manage-questions' => 'Create and edit question bank items',
            'exam-development.review-questions' => 'Review questions and papers',
            'exam-development.approve-questions' => 'Approve questions and marking schemes',
            'exam-development.assign-questions' => 'Assign approved questions to paper slots',
            'exam-development.manage-practical' => 'Manage practical setup, apparatus, and confidential instructions',
            'exam-development.approve-paper' => 'Approve developed papers',
            'exam-development.lock-paper' => 'Lock final papers after approval',
            'exam-development.export-paper' => 'Export candidate, moderator, and archive packs',
            'exam-development.view-audit' => 'View audit and approval history',
        ];
    }
}
