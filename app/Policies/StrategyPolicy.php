<?php

namespace App\Policies;

use App\Models\Strategy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StrategyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_strategy');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Strategy $strategy): bool
    {
        return $user->can('view_strategy');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_strategy');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Strategy $strategy): bool
    {
        return $user->can('update_strategy');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Strategy $strategy): bool
    {
        return $user->can('delete_strategy');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_strategy');
    }

    /**
     * Las estrategias nunca se eliminan definitivamente (solo soft-delete).
     */
    public function forceDelete(User $user, Strategy $strategy): bool
    {
        return false;
    }

    /**
     * Las estrategias nunca se eliminan definitivamente (solo soft-delete).
     */
    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Strategy $strategy): bool
    {
        return $user->can('restore_strategy');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_strategy');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Strategy $strategy): bool
    {
        return $user->can('replicate_strategy');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_strategy');
    }
}
