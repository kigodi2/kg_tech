<?php

namespace App\Policies;

use App\Models\MarkImportBatch;
use App\Models\User;

class MarkImportBatchPolicy
{
    public function view(User $user, MarkImportBatch $batch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->isInScope($user, $batch);
    }

    public function submit(User $user, MarkImportBatch $batch): bool
    {
        if (!$batch->canBeSubmitted()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $allowed = ['teacher', 'school_registrar', 'school_hod', 'district_supervisor'];
        if (!in_array($user->roleCode(), $allowed)) {
            return false;
        }

        return $this->isInScope($user, $batch);
    }

    public function approve(User $user, MarkImportBatch $batch): bool
    {
        if (!$batch->canBeApproved()) {
            return false;
        }

        return $this->moderate($user, $batch);
    }

    public function reject(User $user, MarkImportBatch $batch): bool
    {
        if (!$batch->canBeRejected()) {
            return false;
        }

        return $this->moderate($user, $batch);
    }

    public function moderate(User $user, MarkImportBatch $batch): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role && $user->role->code === 'school_hod') {
            return $batch->school_id === $user->school_id;
        }

        if ($user->role && $user->role->code === 'district_supervisor') {
            return $batch->district_id === $user->district_id;
        }

        return false;
    }

    public function lock(User $user, MarkImportBatch $batch): bool
    {
        if (!$batch->canBeLocked()) {
            return false;
        }

        return $this->moderate($user, $batch);
    }

    public function unlock(User $user, MarkImportBatch $batch): bool
    {
        if (!$batch->canBeUnlocked()) {
            return false;
        }

        return $user->isAdmin();
    }

    private function isInScope(User $user, MarkImportBatch $batch): bool
    {
        if ($user->district_id && $batch->district_id) {
            return $batch->district_id === $user->district_id;
        }

        if ($user->school_id && $batch->school_id) {
            return $batch->school_id === $user->school_id;
        }

        return false;
    }
}
