<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
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
  public function view(User $user, Role $role): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can create models.
   * Roles are system-defined and should not be created dynamically.
   */
  public function create(User $user): bool
  {
    return false;
  }

  /**
   * Determine whether the user can update the model.
   * Only admin can update role descriptions, code is locked.
   */
  public function update(User $user, Role $role): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can delete the model.
   * Roles should not be deleted.
   */
  public function delete(User $user, Role $role): bool
  {
    return false;
  }

  /**
   * Determine whether the user can restore the model.
   */
  public function restore(User $user, Role $role): bool
  {
    return $user->isAdmin();
  }

  /**
   * Determine whether the user can permanently delete the model.
   */
  public function forceDelete(User $user, Role $role): bool
  {
    return false;
  }
}
