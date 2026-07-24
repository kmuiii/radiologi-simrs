<?php

namespace App\Policies;

use App\Models\DetailTindakan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DetailTindakanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'radiologist']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DetailTindakan $detailTindakan): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        // Dokter hanya bisa akses yang ditugaskan ke dirinya.
        // Otomatis mencakup Detail Tindakan yang ia buat sendiri,
        // karena radiologist_id di-set ke dirinya saat pembuatan.
        return $user->role === 'radiologist'
            && $detailTindakan->radiologist_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin dan Dokter Radiologi sama-sama boleh membuat
        return in_array($user->role, ['admin', 'radiologist']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DetailTindakan $detailTindakan): bool
    {
        return $this->view($user, $detailTindakan);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DetailTindakan $detailTindakan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DetailTindakan $detailTindakan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DetailTindakan $detailTindakan): bool
    {
        return false;
    }
}
