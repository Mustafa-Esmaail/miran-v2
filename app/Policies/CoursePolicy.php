<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courses.viewAny');
    }

    public function view(User $user, Course $course): bool
    {
        return $user->can('courses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('courses.create');
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can('courses.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('courses.delete');
    }

    public function delete(User $user, Course $course): Response
    {
        if (! $user->can('courses.delete')) {
            return Response::deny();
        }

        return $this->denyWhenInUse($course);
    }

    public function restore(User $user, Course $course): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function restoreAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Course $course): Response
    {
        if (! $user->hasRole(['admin', 'super-admin']) || ! $user->can('courses.delete')) {
            return Response::deny();
        }

        return $this->denyWhenInUse($course);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) && $user->can('courses.delete');
    }

    /**
     * Student-facing pages read $enrollment->course without null checks, and hard deletes cascade
     * into enrollments and certificates, so a course is only removable once nothing hangs off it.
     */
    private function denyWhenInUse(Course $course): Response
    {
        $inUse = $course->groups()->exists()
            || $course->enrollments()->withTrashed()->exists()
            || $course->certificates()->exists();

        return $inUse
            ? Response::deny(__('admin.validation.course_in_use'))
            : Response::allow();
    }
}
