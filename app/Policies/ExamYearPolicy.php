<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExamYear;

/**
 * ExamYearPolicy
 *
 * Authorizes actions on exam years.
 * Prevents writes to locked years at the policy layer.
 *
 * Note: This policy can be extended to support role-based access
 * (e.g., only certain roles can publish/lock years).
 */
class ExamYearPolicy
{
    /**
     * Determine if the user can view the exam year.
     *
     * All authenticated users can view exam years.
     */
    public function view(User $user, ExamYear $examYear): bool
    {
        return true;
    }

    /**
     * Determine if the user can create exam years.
     *
     * Only admins can create exam years.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can update exam years.
     *
     * Only admins can update exam years, and only if not locked.
     */
    public function update(User $user, ExamYear $examYear): bool
    {
        // Cannot update locked years
        if ($examYear->isLocked()) {
            return false;
        }

        return $user->isAdmin();
    }

    /**
     * Determine if the user can delete exam years.
     *
     * Only admins can delete, and only if not locked or published.
     */
    public function delete(User $user, ExamYear $examYear): bool
    {
        // Cannot delete locked or published years
        if ($examYear->isLocked() || $examYear->isPublished()) {
            return false;
        }

        return $user->isAdmin();
    }

    /**
     * Determine if the user can publish results for a year.
     *
     * Only admins can publish.
     */
    public function publish(User $user, ExamYear $examYear): bool
    {
        // Cannot publish already published year
        if ($examYear->isPublished()) {
            return false;
        }

        return $user->isAdmin();
    }

    /**
     * Determine if the user can activate an exam year.
     *
     * Only admins can activate years.
     */
    public function activate(User $user, ExamYear $examYear): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine if the user can upload CSV for a locked year.
     *
     * Uploads are blocked for locked years.
     * Admin panel never uploads, so this is false.
     */
    public function uploadCsv(User $user, ExamYear $examYear): bool
    {
        // Cannot upload to locked years
        if ($examYear->isLocked()) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can edit marks in a year.
     *
     * Marks cannot be edited in locked years.
     * Admin panel never edits marks, so this is false.
     */
    public function editMarks(User $user, ExamYear $examYear): bool
    {
        // Cannot edit marks in locked years
        if ($examYear->isLocked()) {
            return false;
        }

        return false;
    }

    /**
     * Determine if the user can delete records from a year.
     *
     * Deletions are blocked for locked years.
     * Admin panel never deletes records from years.
     */
    public function deleteRecords(User $user, ExamYear $examYear): bool
    {
        // Cannot delete from locked years
        if ($examYear->isLocked()) {
            return false;
        }

        return false;
    }
}
