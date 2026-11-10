<?php

namespace App\Policies;

use App\Models\CallQualityCriterion;
use App\Models\User;

class CallQualityCriterionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('call_quality_criteria.manage');
    }

    public function view(User $user, CallQualityCriterion $criterion): bool
    {
        return $user->can('call_quality_criteria.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('call_quality_criteria.manage');
    }

    public function update(User $user, CallQualityCriterion $criterion): bool
    {
        return $user->can('call_quality_criteria.manage');
    }

    public function delete(User $user, CallQualityCriterion $criterion): bool
    {
        return $user->can('call_quality_criteria.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('call_quality_criteria.manage');
    }

    public function reorder(User $user): bool
    {
        return $user->can('call_quality_criteria.manage');
    }
}
