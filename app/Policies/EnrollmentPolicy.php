<?php

namespace App\Policies;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enrollments.viewAny');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->can('enrollments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('enrollments.create');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->can('enrollments.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('enrollments.delete');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        if (! $user->can('enrollments.delete')) {
            return false;
        }

        return $enrollment->status !== EnrollmentStatus::Active && $enrollment->activated_at === null;
    }

    public function forceDelete(User $user, Enrollment $enrollment): bool
    {
        if (! $user->hasRole(['admin', 'super-admin']) || ! $user->can('enrollments.delete')) {
            return false;
        }

        return $enrollment->status !== EnrollmentStatus::Active && $enrollment->activated_at === null;
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('enrollments.delete');
    }

    public function restore(User $user, Enrollment $enrollment): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }
}
