<?php

namespace App\Policies;

use App\Models\Tindakan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TindakanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tindakan $tindakan): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'radiologist' && $tindakan->dokter_pembaca_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'radiologist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tindakan $tindakan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tindakan $tindakan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tindakan $tindakan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tindakan $tindakan): bool
    {
        return false;
    }
}
