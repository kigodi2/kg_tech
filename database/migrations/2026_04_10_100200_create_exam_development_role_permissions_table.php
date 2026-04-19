<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_development_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('permission', 100);
            $table->boolean('granted')->default(true);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();
            $table->unique(['role_id', 'permission'], 'exam_dev_role_perm_unique');
        });

        $roles = [
            ['code' => 'exam_admin', 'name' => 'Exam Admin', 'description' => 'Coordinates exam development projects and governance'],
            ['code' => 'chief_examiner', 'name' => 'Chief Examiner', 'description' => 'Leads subject review and approval'],
            ['code' => 'subject_panelist', 'name' => 'Subject Panelist', 'description' => 'Maintains subject formats and blueprints'],
            ['code' => 'item_writer', 'name' => 'Item Writer', 'description' => 'Authors and revises questions'],
            ['code' => 'moderator', 'name' => 'Moderator', 'description' => 'Reviews papers, questions, and schemes'],
            ['code' => 'proofreader', 'name' => 'Proofreader', 'description' => 'Checks candidate-facing paper quality'],
            ['code' => 'practical_coordinator', 'name' => 'Practical Coordinator', 'description' => 'Manages practical setup and variants'],
            ['code' => 'export_officer', 'name' => 'Export Officer', 'description' => 'Generates controlled paper exports'],
            ['code' => 'auditor', 'name' => 'Auditor', 'description' => 'Views audit and approval history'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                ['name' => $role['name'], 'description' => $role['description'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $adminRoleIds = DB::table('roles')
            ->whereIn('code', ['admin', 'exam_admin', 'chief_examiner'])
            ->pluck('id');

        $permissions = [
            'exam-development.view',
            'exam-development.manage-formats',
            'exam-development.manage-blueprints',
            'exam-development.create-project',
            'exam-development.edit-project',
            'exam-development.manage-questions',
            'exam-development.review-questions',
            'exam-development.approve-questions',
            'exam-development.assign-questions',
            'exam-development.manage-practical',
            'exam-development.approve-paper',
            'exam-development.lock-paper',
            'exam-development.export-paper',
            'exam-development.view-audit',
        ];

        foreach ($adminRoleIds as $roleId) {
            foreach ($permissions as $permission) {
                DB::table('exam_development_role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission' => $permission],
                    ['granted' => true, 'granted_at' => now(), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_development_role_permissions');
    }
};
