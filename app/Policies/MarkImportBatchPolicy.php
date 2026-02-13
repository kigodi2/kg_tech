<?php

namespace App\Policies;

use App\Models\MarkImportBatch;
use App\Models\User;

class MarkImportBatchPolicy {

    public function moderate(User $user, MarkImportBatch $batch): bool {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role && $user->role->code === 'school_hod') {
            return $batch->school_id === $user->school_id;
        }

        if ($user->role && $user->role->code === 'district_supervisor') {
            return $batch->school->district_id === $user->district_id;
        }

        return false;
    }

    public function lock(User $user, MarkImportBatch $batch): bool {
        return $this->moderate($user, $batch) && $batch->lifecycle_state === 'approved';
    }

    public function unlock(User $user): bool {
        return $user->isAdmin();
    }
}
