<?php

namespace App\Policies;

use App\Models\ExamSubmission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExamSubmissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own submissions
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExamSubmission $examSubmission): bool
    {
        return $user->isAdmin() || $user->id === $examSubmission->user_id;
    }

    /**
     * Determine whether the user can review submissions.
     */
    public function review(User $user, ExamSubmission $examSubmission): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create submissions
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExamSubmission $examSubmission): bool
    {
        return $user->id === $examSubmission->user_id && $examSubmission->status === 'rejected';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExamSubmission $examSubmission): bool
    {
        return false; // Submissions cannot be deleted once created
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExamSubmission $examSubmission): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExamSubmission $examSubmission): bool
    {
        return false;
    }
}
