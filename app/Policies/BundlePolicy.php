<?php

namespace App\Policies;

use App\Models\Bundle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BundlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bundles.viewAny');
    }

    public function view(User $user, Bundle $bundle): bool
    {
        return $user->can('bundles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('bundles.create');
    }

    public function update(User $user, Bundle $bundle): bool
    {
        return $user->can('bundles.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('bundles.delete');
    }

    /**
     * Order items reference the bundle polymorphically; deleting it would leave unpaid orders that
     * enroll nothing once paid and break bundle item edits, so bundles with orders must be deactivated.
     */
    public function delete(User $user, Bundle $bundle): Response
    {
        if (! $user->can('bundles.delete')) {
            return Response::deny();
        }

        return $bundle->orderItems()->exists()
            ? Response::deny(__('admin.validation.bundle_in_use'))
            : Response::allow();
    }
}
