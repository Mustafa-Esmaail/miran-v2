<?php

namespace App\Policies;

use App\Models\CourseQuestion;
use App\Models\User;

class CourseQuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('course_questions.viewAny');
    }

    public function view(User $user, CourseQuestion $courseQuestion): bool
    {
        return $user->can('course_questions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('course_questions.create');
    }

    public function update(User $user, CourseQuestion $courseQuestion): bool
    {
        return $user->can('course_questions.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('course_questions.delete');
    }

    public function delete(User $user, CourseQuestion $courseQuestion): bool
    {
        return $user->can('course_questions.delete');
    }
}
