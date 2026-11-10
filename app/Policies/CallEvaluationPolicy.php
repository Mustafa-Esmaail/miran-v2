<?php

namespace App\Policies;

use App\Models\CallEvaluation;
use App\Models\User;
use App\Services\CallQualityService;

class CallEvaluationPolicy
{
    public function __construct(private readonly CallQualityService $quality) {}

    public function viewAny(User $user): bool
    {
        return $user->can('call_evaluations.viewAny');
    }

    public function view(User $user, CallEvaluation $evaluation): bool
    {
        return $user->can('call_evaluations.view') && $this->quality->canSeeEmployee($user, $evaluation->employee_id);
    }

    public function create(User $user): bool
    {
        return $this->quality->canEvaluate($user);
    }

    public function update(User $user, CallEvaluation $evaluation): bool
    {
        return $this->quality->canEdit($user, $evaluation);
    }

    public function delete(User $user, CallEvaluation $evaluation): bool
    {
        return $this->quality->canDelete($user, $evaluation);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('call_evaluations.delete');
    }
}
