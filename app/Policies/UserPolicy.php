<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.viewAny');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('users.delete');
    }

    /**
     * Deleting a user cascades at the database level into orders, enrollments, certificates, and
     * progress (bypassing their soft deletes), so users with that history must be deactivated instead.
     */
    public function delete(User $user, User $model): Response
    {
        if (! $user->can('users.delete')) {
            return Response::deny();
        }

        $inUse = $model->orders()->withTrashed()->exists()
            || $model->enrollments()->withTrashed()->exists()
            || $model->certificates()->exists();

        return $inUse
            ? Response::deny(__('admin.validation.user_in_use'))
            : Response::allow();
    }
}
