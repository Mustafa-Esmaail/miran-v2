<?php

namespace App\Policies;

use App\Models\ChatRoom;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CourseGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('course_groups.viewAny');
    }

    public function view(User $user, CourseGroup $group): bool
    {
        return $user->can('course_groups.view');
    }

    public function create(User $user): bool
    {
        return $user->can('course_groups.create');
    }

    public function update(User $user, CourseGroup $group): bool
    {
        return $user->can('course_groups.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('course_groups.delete');
    }

    public function delete(User $user, CourseGroup $group): Response
    {
        if (! $user->can('course_groups.delete')) {
            return Response::deny();
        }

        return $this->denyWhenInUse($group);
    }

    public function restore(User $user, CourseGroup $group): Response
    {
        if (! $user->hasRole(['admin', 'super-admin'])) {
            return Response::deny();
        }

        return $group->course()->exists()
            ? Response::allow()
            : Response::deny(__('admin.validation.course_group_parent_deleted'));
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, CourseGroup $group): Response
    {
        if (! $user->hasRole(['admin', 'super-admin']) || ! $user->can('course_groups.delete')) {
            return Response::deny();
        }

        return $this->denyWhenInUse($group);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('course_groups.delete');
    }

    /**
     * Student pages, session forms, and chat read the group without null checks, and hard deletes
     * cascade into enrollments and certificates, so a group is only removable once nothing hangs off it.
     */
    private function denyWhenInUse(CourseGroup $group): Response
    {
        $inUse = $group->enrollments()->withTrashed()->exists()
            || $group->orderItems()->exists()
            || $group->certificates()->exists()
            || $group->sessions()->exists()
            || ChatRoom::query()->where('course_group_id', $group->id)->exists();

        return $inUse
            ? Response::deny(__('admin.validation.course_group_in_use'))
            : Response::allow();
    }
}
