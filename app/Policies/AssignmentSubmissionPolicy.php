<?php

namespace App\Policies;

use App\Models\AssignmentSubmission;
use App\Models\User;

class AssignmentSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('assignment_submissions.viewAny');
    }

    public function view(User $user, AssignmentSubmission $assignmentSubmission): bool
    {
        return $user->can('assignment_submissions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('assignment_submissions.create');
    }

    public function update(User $user, AssignmentSubmission $assignmentSubmission): bool
    {
        return $user->can('assignment_submissions.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('assignment_submissions.delete');
    }

    public function delete(User $user, AssignmentSubmission $assignmentSubmission): bool
    {
        return $user->can('assignment_submissions.delete');
    }

    public function grade(User $user, AssignmentSubmission $assignmentSubmission): bool
    {
        return $user->can('assignment_submissions.grade');
    }
}
