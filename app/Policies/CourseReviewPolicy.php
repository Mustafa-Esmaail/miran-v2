<?php

namespace App\Policies;

use App\Models\CourseReview;
use App\Models\User;

class CourseReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('course_reviews.viewAny');
    }

    public function view(User $user, CourseReview $review): bool
    {
        return $user->can('course_reviews.view');
    }

    public function create(User $user): bool
    {
        return $user->can('course_reviews.create');
    }

    public function update(User $user, CourseReview $review): bool
    {
        return $user->can('course_reviews.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('course_reviews.delete');
    }

    public function delete(User $user, CourseReview $review): bool
    {
        return $user->can('course_reviews.delete');
    }
}
