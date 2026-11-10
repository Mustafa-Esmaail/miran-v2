<?php

namespace App\Services;

use App\Models\AssignmentRule;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;

class AssignmentRuleService
{
    public function apply(ServiceRequest $sr): void
    {
        // Skip if already assigned
        if ($sr->assigned_to) {
            return;
        }

        $rules = AssignmentRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $this->matchesConditions($sr, $rule->conditions ?? [])) {
                continue;
            }

            $userId = $this->pickUserFromRule($rule);
            if ($userId) {
                $sr->update(['assigned_to' => $userId]);

                return;
            }
        }
    }

    private function matchesConditions(ServiceRequest $sr, array $conditions): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            $actual = match ($field) {
                'source' => $sr->source?->value,
                'owner_team' => mb_strtolower((string) $sr->team?->department?->name) ?: null,
                'status' => $sr->status,
                default => null,
            };

            $matched = match ($operator) {
                'equals' => $actual === $value,
                'not_equals' => $actual !== $value,
                default => true,
            };

            if (! $matched) {
                return false;
            }
        }

        return true;
    }

    private function pickUserFromRule(AssignmentRule $rule): ?int
    {
        $employeeIds = $rule->employee_ids ?? [];
        if (empty($employeeIds)) {
            return null;
        }

        $employees = Employee::whereIn('id', $employeeIds)
            ->whereNotNull('user_id')
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return null;
        }

        // Least-loaded: pick the employee with fewest open leads
        $assignedCounts = ServiceRequest::query()
            ->whereIn('assigned_to', $employees->pluck('user_id'))
            ->whereIn('status', ServiceRequestStatus::query()->where('outcome', 'open')->pluck('slug'))
            ->selectRaw('assigned_to, count(*) as cnt')
            ->groupBy('assigned_to')
            ->pluck('cnt', 'assigned_to');

        return $employees
            ->sortBy(fn (Employee $e) => $assignedCounts->get($e->user_id, 0))
            ->first()
            ?->user_id;
    }
}
