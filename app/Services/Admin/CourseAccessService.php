<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CourseAccessService
{
    public function scopeCourses(Builder $query, User $user, string $permission): Builder
    {
        if ($user->can($permission)) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeCourseRelation(Builder $query, User $user, string $relation, string $permission): Builder
    {
        if ($user->can($permission)) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    public function scopeForInstructor(Builder $query, User $user, string $instructorRelationPath): Builder
    {
        if ($user->hasRole('instructor')) {
            return $query->whereHas($instructorRelationPath, fn (Builder $b) => $b->whereKey($user->id));
        }

        return $query;
    }
}
