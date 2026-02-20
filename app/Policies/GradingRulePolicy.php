<?php

namespace App\Policies;

use App\Models\GradingRule;
use App\Models\User;

class GradingRulePolicy
{
  /**
   * Determine whether the user can view any models.
   */
  public function viewAny(User $user): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, GradingRule $rule): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can create models.
   */
  public function create(User $user): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can update the model.
   * Cannot update if parent profile is locked.
   */
  public function update(User $user, GradingRule $rule): bool
  {
    return $user->isAdmin() && !$rule->gradingProfile->is_locked;
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(User $user, GradingRule $rule): bool
  {
    return $user->isAdmin() && !$rule->gradingProfile->is_locked;
  }

  /**
   * Determine whether the user can restore the model.
   */
  public function restore(User $user, GradingRule $rule): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can permanently delete the model.
   */
  public function forceDelete(User $user, GradingRule $rule): bool
  {
    return $user->isAdmin();
  }
}
