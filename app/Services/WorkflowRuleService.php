<?php

namespace App\Services;

use App\Enums\CrmTaskStatus;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowTrigger;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\WorkflowRule;
use Illuminate\Database\Eloquent\Model;

class WorkflowRuleService
{
    public function run(WorkflowTrigger $trigger, Model $entity, array $context = []): void
    {
        $rules = WorkflowRule::query()
            ->where('trigger_event', $trigger->value)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesConditions($entity, $rule->conditions ?? [], $context)) {
                $this->executeActions($rule->actions ?? [], $entity, $context);
            }
        }
    }

    private function matchesConditions(Model $entity, array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            $actual = $this->resolveField($entity, $field, $context);

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

    private function resolveField(Model $entity, ?string $field, array $context): mixed
    {
        return match ($field) {
            'status' => $entity instanceof ServiceRequest ? $entity->status : null,
            'source' => $entity instanceof ServiceRequest ? $entity->source?->value : null,
            'owner_team' => $entity instanceof ServiceRequest ? mb_strtolower((string) $entity->team?->department?->name) ?: null : null,
            'stage' => $entity instanceof Deal ? $entity->stage?->value : null,
            'new_status' => $context['new_status'] ?? null,
            'new_stage' => $context['new_stage'] ?? null,
            default => null,
        };
    }

    private function executeActions(array $actions, Model $entity, array $context): void
    {
        foreach ($actions as $action) {
            $type = WorkflowActionType::tryFrom($action['type'] ?? '');

            match ($type) {
                WorkflowActionType::CreateTask => $this->createTask($action, $entity),
                WorkflowActionType::UpdateStatus => $this->updateStatus($action, $entity),
                WorkflowActionType::AssignEmployee => $this->assignEmployee($action, $entity),
                null => null,
            };
        }
    }

    private function createTask(array $action, Model $entity): void
    {
        $employeeId = $action['employee_id'] ?? null;

        // Auto-resolve to the lead's assigned employee when not specified
        if (! $employeeId && $entity instanceof ServiceRequest && $entity->assigned_to) {
            $employeeId = Employee::query()->where('user_id', $entity->assigned_to)->value('id');
        }

        CrmTask::create([
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'title' => $action['title'] ?? 'Follow up',
            'priority' => $action['priority'] ?? 'normal',
            'status' => CrmTaskStatus::Todo->value,
            'due_at' => now()->addHours((int) ($action['due_in_hours'] ?? 24)),
            'employee_id' => $employeeId,
            'is_done' => false,
        ]);
    }

    private function updateStatus(array $action, Model $entity): void
    {
        if (! $entity instanceof ServiceRequest) {
            return;
        }

        $slug = ServiceRequestStatus::query()->active()->where('slug', $action['value'] ?? '')->value('slug');
        if ($slug) {
            $entity->updateQuietly(['status' => $slug]);
        }
    }

    private function assignEmployee(array $action, Model $entity): void
    {
        if (! $entity instanceof ServiceRequest) {
            return;
        }

        $employeeId = $action['employee_id'] ?? null;
        if (! $employeeId) {
            return;
        }

        $userId = Employee::query()->where('id', $employeeId)->value('user_id');
        if ($userId) {
            $entity->update(['assigned_to' => $userId]);
        }
    }
}
