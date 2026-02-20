<?php

namespace App\Policies;

use App\Models\GradingProfile;
use App\Models\User;

class GradingProfilePolicy
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
  public function view(User $user, GradingProfile $profile): bool
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
   * Cannot update if locked.
   */
  public function update(User $user, GradingProfile $profile): bool
  {
    return $user->isAdmin() && !$profile->is_locked;
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(User $user, GradingProfile $profile): bool
  {
    return $user->isAdmin() && !$profile->is_locked;
  }

  /**
   * Determine whether the user can restore the model.
   */
  public function restore(User $user, GradingProfile $profile): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can permanently delete the model.
   */
  public function forceDelete(User $user, GradingProfile $profile): bool
  {
    return $user->isAdmin();
  }
}
