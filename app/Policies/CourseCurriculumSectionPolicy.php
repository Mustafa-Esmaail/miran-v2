<?php

namespace App\Policies;

use App\Models\CourseCurriculumSection;
use App\Models\User;

class CourseCurriculumSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('course_curriculum_sections.viewAny');
    }

    public function view(User $user, CourseCurriculumSection $courseCurriculumSection): bool
    {
        return $user->can('course_curriculum_sections.view');
    }

    public function create(User $user): bool
    {
        return $user->can('course_curriculum_sections.create');
    }

    public function update(User $user, CourseCurriculumSection $courseCurriculumSection): bool
    {
        return $user->can('course_curriculum_sections.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('course_curriculum_sections.delete');
    }

    public function delete(User $user, CourseCurriculumSection $courseCurriculumSection): bool
    {
        return $user->can('course_curriculum_sections.delete');
    }
}
