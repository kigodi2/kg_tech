<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BulkImport;
use App\Models\District;

/**
 * BulkImportPolicy
 *
 * Authorization rules for bulk imports (school and district level)
 */
class BulkImportPolicy
{
    /**
     * Determine if user can view bulk import
     */
    public function view(User $user, BulkImport $bulkImport): bool
    {
        // Admins can always view
        if ($user->isAdmin()) {
            return true;
        }

        // For now, only admins can view bulk imports
        return false;
    }

    /**
     * Determine if user can create school-level bulk import
     */
    public function uploadSchoolCsv(User $user): bool
    {
        // Admins can always upload
        if ($user->isAdmin()) {
            return true;
        }

        // District coordinators can upload
        if ($user->hasRole('district_coordinator')) {
            return true;
        }

        // School staff can upload
        if ($user->school_id) {
            return true;
        }

        // Authenticated users can upload (for testing/development)
        return true;
    }

    /**
     * Determine if user can create district-level bulk import
     */
    public function uploadDistrictCsv(User $user, int $districtId): bool
    {
        // Admins can always upload
        if ($user->isAdmin()) {
            return true;
        }

        // District coordinators can upload for their district
        if ($user->hasRole('district_coordinator') && $user->district_id === $districtId) {
            return true;
        }

        // Authenticated users can upload (for testing/development)
        return auth()->check();
    }

    /**
     * Determine if user can retry failed import
     */
    public function retry(User $user, BulkImport $bulkImport): bool
    {
        // Can only retry if viewing is allowed
        return $this->view($user, $bulkImport);
    }

    /**
     * Determine if user can cancel import
     */
    public function cancel(User $user, BulkImport $bulkImport): bool
    {
        // Can only cancel if viewing is allowed and import is not completed
        if (!$this->view($user, $bulkImport)) {
            return false;
        }

        return !in_array($bulkImport->status, ['completed', 'failed']);
    }

    /**
     * Determine if user can delete import record
     */
    public function delete(User $user, BulkImport $bulkImport): bool
    {
        // Only admins can delete completed imports
        return $user->isAdmin();
    }
}
