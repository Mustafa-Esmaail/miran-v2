<?php

namespace App\Services;

use App\Models\CallEvaluation;
use App\Models\CallExclusion;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Traits\CrmRoleScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Rules of the call-quality module, ported from the reference system:
 * who may evaluate / edit / discuss / approve, which employees a user sees,
 * and the audit queue of first-contact leads not yet evaluated.
 */
class CallQualityService
{
    use CrmRoleScope;

    public const QUEUE_LIMIT = 1500;

    /**
     * Employee ids the user may see, or null for everyone. Users with
     * call_evaluations.view_all (quality, management) see everyone; others follow
     * the CRM role scope (agent → self, team lead → team, manager → department),
     * and — as everywhere in the CRM — a user with no employee row / CRM role sees all.
     *
     * @return Collection<int, int>|null
     */
    public function visibleEmployeeIds(?User $user): ?Collection
    {
        if ($user === null) {
            return collect();
        }

        if ($user->can('call_evaluations.view_all')) {
            return null;
        }

        $employee = Employee::query()->where('user_id', $user->getKey())->first();

        if ($employee?->role === null) {
            return null;
        }

        return $this->scopeEmployees(Employee::query(), $employee)->pluck('id');
    }

    public function scopeEvaluations(Builder $query, ?User $user): Builder
    {
        $ids = $this->visibleEmployeeIds($user);

        return $ids === null ? $query : $query->whereIn('employee_id', $ids);
    }

    public function canSeeEmployee(?User $user, ?int $employeeId): bool
    {
        $ids = $this->visibleEmployeeIds($user);

        return $ids === null || ($employeeId !== null && $ids->contains($employeeId));
    }

    public function canEvaluate(?User $user): bool
    {
        return $user?->can('call_evaluations.create') ?? false;
    }

    public function canEdit(?User $user, CallEvaluation $evaluation): bool
    {
        return $user !== null
            && $user->can('call_evaluations.update')
            && $this->canSeeEmployee($user, $evaluation->employee_id)
            && ($user->can('call_evaluations.view_all') || $evaluation->evaluator_id === $user->getKey());
    }

    public function canDelete(?User $user, CallEvaluation $evaluation): bool
    {
        return $user !== null
            && $user->can('call_evaluations.delete')
            && $this->canSeeEmployee($user, $evaluation->employee_id)
            && ($user->can('call_evaluations.view_all') || $evaluation->evaluator_id === $user->getKey());
    }

    /**
     * Marking "discussed with the employee": the evaluator, or anyone with the
     * discuss permission who can see the employee.
     */
    public function canDiscuss(?User $user, CallEvaluation $evaluation): bool
    {
        if ($user === null) {
            return false;
        }

        return $evaluation->evaluator_id === $user->getKey()
            || ($user->can('call_evaluations.discuss') && $this->canSeeEmployee($user, $evaluation->employee_id));
    }

    /**
     * Management approval — no order is enforced against "discussed", as in the reference.
     */
    public function canApprove(?User $user): bool
    {
        return $user?->can('call_evaluations.approve') ?? false;
    }

    public function setDiscussed(CallEvaluation $evaluation, bool $discussed, User $by): void
    {
        $evaluation->forceFill([
            'discussed_at' => $discussed ? now() : null,
            'discussed_by' => $discussed ? $by->getKey() : null,
        ])->save();
    }

    public function setApproved(CallEvaluation $evaluation, bool $approved, User $by): void
    {
        $evaluation->forceFill([
            'approved_at' => $approved ? now() : null,
            'approved_by' => $approved ? $by->getKey() : null,
        ])->save();
    }

    /**
     * First lead per phone in the date range whose phone was never evaluated nor
     * excluded; with $newOnly, phones that already had a lead before $from are skipped.
     * Newest first, capped like the reference (1500).
     *
     * @return Collection<int, ServiceRequest>
     */
    public function auditQueue(CarbonInterface $from, CarbonInterface $to, bool $newOnly, ?User $user, ?int $employeeId = null): Collection
    {
        $userIds = $this->visibleUserIds($user, $employeeId);

        $leads = ServiceRequest::query()
            ->with(['assignedTo', 'course', 'statusModel'])
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($userIds !== null, fn (Builder $query): Builder => $query->whereIn('assigned_to', $userIds))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $firstPerPhone = $leads
            ->groupBy(fn (ServiceRequest $lead): string => ServiceRequestService::normalizePhone((string) $lead->phone))
            ->reject(fn (Collection $group, string $phone): bool => $phone === '')
            ->map(fn (Collection $group): ServiceRequest => $group->first());

        $phones = $firstPerPhone->keys()->all();

        if ($phones === []) {
            return collect();
        }

        $blocked = CallExclusion::query()->whereIn('phone', $phones)->pluck('phone')
            ->merge(CallEvaluation::query()->whereIn('call_phone', $phones)->pluck('call_phone'))
            ->unique()
            ->flip();

        $queue = $firstPerPhone->reject(fn (ServiceRequest $lead, string $phone): bool => $blocked->has($phone));

        if ($newOnly && $queue->isNotEmpty()) {
            $earlier = ServiceRequest::query()
                ->whereIn('phone', $queue->keys()->flatMap(fn (string $phone): array => ServiceRequestService::phoneVariants($phone))->unique()->values())
                ->where('created_at', '<', $from->copy()->startOfDay())
                ->pluck('phone')
                ->map(fn (string $phone): string => ServiceRequestService::normalizePhone($phone))
                ->flip();

            $queue = $queue->reject(fn (ServiceRequest $lead, string $phone): bool => $earlier->has($phone));
        }

        return $queue
            ->sortByDesc(fn (ServiceRequest $lead): string => $lead->created_at?->format('Y-m-d H:i:s').'-'.str_pad((string) $lead->id, 12, '0', STR_PAD_LEFT))
            ->take(self::QUEUE_LIMIT)
            ->values();
    }

    /**
     * "Follow-up": take the lead's phone out of the queue for good.
     */
    public function exclude(ServiceRequest $lead, User $by, string $reason = CallExclusion::REASON_FOLLOW_UP): CallExclusion
    {
        $phone = ServiceRequestService::normalizePhone((string) $lead->phone);

        return CallExclusion::query()->firstOrCreate(
            ['phone' => $phone],
            [
                'employee_id' => $this->employeeIdForUser($lead->assigned_to),
                'service_request_id' => $lead->getKey(),
                'reason' => $reason,
                'created_by' => $by->getKey(),
            ],
        );
    }

    /**
     * Prefill for an evaluation of a lead, optionally of one of its logged call activities.
     *
     * @return array{employee_id: ?int, service_request_id: int, crm_activity_id: ?int, call_phone: ?string, call_date: string}
     */
    public function prefillFor(ServiceRequest $lead, ?CrmActivity $activity = null): array
    {
        $activity ??= $this->latestUnratedCallActivity($lead);

        return [
            'employee_id' => $activity?->employee_id ?? $this->employeeIdForUser($lead->assigned_to),
            'service_request_id' => $lead->getKey(),
            'crm_activity_id' => $activity?->getKey(),
            'call_phone' => $lead->phone,
            'call_date' => ($activity?->completed_at ?? $activity?->created_at ?? $lead->created_at ?? now())->toDateString(),
        ];
    }

    /**
     * The lead's logged call activities that are not evaluated yet (newest first).
     *
     * @return Collection<int, CrmActivity>
     */
    public function unratedCallActivities(ServiceRequest $lead, ?int $exceptEvaluationId = null): Collection
    {
        $rated = CallEvaluation::query()
            ->whereNotNull('crm_activity_id')
            ->when($exceptEvaluationId, fn (Builder $query, int $id): Builder => $query->whereKeyNot($id))
            ->pluck('crm_activity_id');

        return CrmActivity::query()
            ->with('employee')
            ->where('entity_type', ServiceRequest::class)
            ->where('entity_id', $lead->getKey())
            ->where('type', 'call')
            ->whereNotIn('id', $rated)
            ->latest('created_at')
            ->get();
    }

    public function latestUnratedCallActivity(ServiceRequest $lead): ?CrmActivity
    {
        return $this->unratedCallActivities($lead)->first();
    }

    /**
     * Average score % of the evaluations in range that the user may see.
     */
    public function averageScore(CarbonInterface $from, CarbonInterface $to, ?User $user): ?float
    {
        $average = $this->scopeEvaluations(CallEvaluation::query(), $user)
            ->whereBetween('call_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('score_percent')
            ->avg('score_percent');

        return $average === null ? null : round((float) $average, 1);
    }

    private function employeeIdForUser(?int $userId): ?int
    {
        return $userId ? Employee::query()->where('user_id', $userId)->value('id') : null;
    }

    /**
     * User ids whose leads the user may audit (null = everyone), narrowed to one employee if asked.
     *
     * @return Collection<int, int>|null
     */
    private function visibleUserIds(?User $user, ?int $employeeId): ?Collection
    {
        $employeeIds = $this->visibleEmployeeIds($user);

        if ($employeeId !== null) {
            $employeeIds = ($employeeIds === null || $employeeIds->contains($employeeId)) ? collect([$employeeId]) : collect();
        }

        return $employeeIds === null
            ? null
            : Employee::query()->whereIn('id', $employeeIds)->whereNotNull('user_id')->pluck('user_id');
    }
}
