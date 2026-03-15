<?php

namespace App\Policies;

use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payment::type');
    }

    public function view(User $user, PaymentType $paymentType): bool
    {
        return $user->can('view_payment::type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payment::type');
    }

    public function update(User $user, PaymentType $paymentType): bool
    {
        return $user->can('update_payment::type');
    }

    public function delete(User $user, PaymentType $paymentType): bool
    {
        return $user->can('delete_payment::type');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_payment::type');
    }

    public function forceDelete(User $user, PaymentType $paymentType): bool
    {
        return $user->can('force_delete_payment::type');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_payment::type');
    }

    public function restore(User $user, PaymentType $paymentType): bool
    {
        return $user->can('restore_payment::type');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_payment::type');
    }

    public function replicate(User $user, PaymentType $paymentType): bool
    {
        return $user->can('replicate_payment::type');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_payment::type');
    }
}
