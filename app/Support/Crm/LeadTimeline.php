<?php

namespace App\Support\Crm;

use App\Enums\CrmActivityType;
use App\Enums\CrmTaskStatus;
use App\Enums\ServiceRequestActivityType;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Models\CallEvaluation;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use App\Services\CallQualityService;
use App\Support\CallQuality\CallQualityLevels;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds one newest-first list from the lead's own activity log, its CRM activities,
 * its CRM tasks and its call evaluations, so the lead view can show everything that
 * happened in one place. Call evaluations are only included for viewers who may see them.
 *
 * @phpstan-type TimelineEntry array{
 *     key: string,
 *     source: string,
 *     icon: string,
 *     color: string,
 *     title: string,
 *     badges: array<int, string>,
 *     rating: array{label: string, color: string}|null,
 *     from: string|null,
 *     to: string|null,
 *     detail: string|null,
 *     body: string|null,
 *     actor: string|null,
 *     url: string|null,
 *     at: CarbonInterface|null
 * }
 */
class LeadTimeline
{
    public const SOURCE_LOG = 'log';

    public const SOURCE_CRM_ACTIVITY = 'crm_activity';

    public const SOURCE_CRM_TASK = 'crm_task';

    public const SOURCE_CALL_EVALUATION = 'call_evaluation';

    /**
     * @return Collection<int, TimelineEntry>
     */
    public static function for(ServiceRequest $lead, ?User $viewer = null): Collection
    {
        $viewer ??= auth('admin')->user();

        $logs = $lead->activities()->with('actor:id,name')->get();
        $crmActivities = $lead->crmActivities()->with('employee:id,name')->get();
        $crmTasks = $lead->crmTasks()->with('employee:id,name')->get();
        $evaluations = self::visibleEvaluations($lead, $viewer);
        $evaluationsByActivity = $evaluations->whereNotNull('crm_activity_id')->keyBy('crm_activity_id');

        $statusNames = self::statusNames($logs);
        $userNames = self::assigneeNames($logs);

        return collect()
            ->concat($logs->map(fn (ServiceRequestActivity $log): array => self::fromLog($log, $statusNames, $userNames)))
            ->concat($crmActivities->map(fn (CrmActivity $activity): array => self::fromCrmActivity($activity, $evaluationsByActivity->get($activity->getKey()), $viewer)))
            ->concat($crmTasks->map(fn (CrmTask $task): array => self::fromCrmTask($task)))
            ->concat($evaluations->map(fn (CallEvaluation $evaluation): array => self::fromCallEvaluation($evaluation, $viewer)))
            ->sortByDesc(fn (array $entry): int => $entry['at']?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * @param  Collection<string, string>  $statusNames
     * @param  Collection<int, string>  $userNames
     * @return TimelineEntry
     */
    private static function fromLog(ServiceRequestActivity $log, Collection $statusNames, Collection $userNames): array
    {
        $type = $log->type;
        $from = filled($log->from_value) ? (string) $log->from_value : null;
        $to = filled($log->to_value) ? (string) $log->to_value : null;

        if ($type === ServiceRequestActivityType::StatusChanged) {
            $from = $from !== null ? ($statusNames->get($from) ?? $from) : null;
            $to = $to !== null ? ($statusNames->get($to) ?? $to) : null;
        }

        if ($type === ServiceRequestActivityType::AssignedChanged) {
            $from = $from !== null ? ($userNames->get((int) $from) ?? '#'.$from) : null;
            $to = $to !== null ? ($userNames->get((int) $to) ?? '#'.$to) : null;
        }

        if ($type === ServiceRequestActivityType::Converted) {
            $from = null;
            $to = $to !== null ? '#'.$to : null;
        }

        [$icon, $color] = match ($type) {
            ServiceRequestActivityType::Created => ['heroicon-m-sparkles', 'gray'],
            ServiceRequestActivityType::StatusChanged => ['heroicon-m-arrow-path', 'primary'],
            ServiceRequestActivityType::AssignedChanged => ['heroicon-m-user-plus', 'info'],
            ServiceRequestActivityType::TeamTransferred => ['heroicon-m-user-group', 'info'],
            ServiceRequestActivityType::NoteAdded => ['heroicon-m-chat-bubble-bottom-center-text', 'warning'],
            ServiceRequestActivityType::Converted => ['heroicon-m-check-badge', 'success'],
            default => ['heroicon-m-information-circle', 'gray'],
        };

        return [
            'key' => self::SOURCE_LOG.'-'.$log->getKey(),
            'source' => self::SOURCE_LOG,
            'icon' => $icon,
            'color' => $color,
            'title' => self::optionLabel($type),
            'badges' => [],
            'rating' => null,
            'from' => $from,
            'to' => $to,
            'detail' => null,
            'body' => filled($log->note) ? (string) $log->note : null,
            'actor' => $log->actor?->name,
            'url' => null,
            'at' => $log->created_at,
        ];
    }

    /**
     * @return TimelineEntry
     */
    private static function fromCrmActivity(CrmActivity $activity, ?CallEvaluation $evaluation, ?User $viewer): array
    {
        $icon = match ($activity->type) {
            CrmActivityType::Call => 'heroicon-m-phone',
            CrmActivityType::Email => 'heroicon-m-envelope',
            CrmActivityType::WhatsApp => 'heroicon-m-chat-bubble-oval-left-ellipsis',
            CrmActivityType::Meeting => 'heroicon-m-users',
            CrmActivityType::Note => 'heroicon-m-pencil-square',
            CrmActivityType::Visit => 'heroicon-m-map-pin',
            default => 'heroicon-m-bolt',
        };

        return [
            'key' => self::SOURCE_CRM_ACTIVITY.'-'.$activity->getKey(),
            'source' => self::SOURCE_CRM_ACTIVITY,
            'icon' => $icon,
            'color' => 'info',
            'title' => filled($activity->subject) ? (string) $activity->subject : self::optionLabel($activity->type),
            'badges' => array_values(array_filter([
                $activity->type !== null ? self::optionLabel($activity->type) : null,
                $activity->outcome !== null ? self::optionLabel($activity->outcome) : null,
            ])),
            'rating' => $evaluation !== null ? [
                'label' => __('admin.call_quality.lead.rated_badge', [
                    'percent' => self::percent($evaluation->score_percent),
                    'level' => CallQualityLevels::label($evaluation->level),
                ]),
                'color' => CallQualityLevels::color($evaluation->level),
            ] : null,
            'from' => null,
            'to' => null,
            'detail' => null,
            'body' => filled($activity->body) ? (string) $activity->body : null,
            'actor' => $activity->employee?->name,
            'url' => $evaluation !== null ? self::evaluationUrl($evaluation, $viewer) : null,
            'at' => $activity->created_at ?? $activity->completed_at ?? $activity->scheduled_at,
        ];
    }

    /**
     * @return TimelineEntry
     */
    private static function fromCrmTask(CrmTask $task): array
    {
        $isDone = $task->status === CrmTaskStatus::Done;
        $isOverdue = ! $isDone && $task->due_at !== null && $task->due_at->isPast();

        $detail = $isDone && $task->completed_at !== null
            ? __('admin.lead_view.completed_on', ['date' => $task->completed_at->format('Y-m-d H:i')])
            : ($task->due_at !== null ? __('admin.lead_view.due_on', ['date' => $task->due_at->format('Y-m-d H:i')]) : null);

        return [
            'key' => self::SOURCE_CRM_TASK.'-'.$task->getKey(),
            'source' => self::SOURCE_CRM_TASK,
            'icon' => $isDone ? 'heroicon-m-check-circle' : 'heroicon-m-clipboard-document-list',
            'color' => $isDone ? 'success' : ($isOverdue ? 'danger' : 'warning'),
            'title' => (string) $task->title,
            'badges' => array_values(array_filter([
                $task->status !== null ? self::optionLabel($task->status) : null,
                $isOverdue ? __('admin.lead_view.overdue') : null,
            ])),
            'rating' => null,
            'from' => null,
            'to' => null,
            'detail' => $detail,
            'body' => filled($task->notes) ? (string) $task->notes : null,
            'actor' => $task->employee?->name,
            'url' => null,
            'at' => $task->created_at ?? $task->due_at,
        ];
    }

    /**
     * @return TimelineEntry
     */
    private static function fromCallEvaluation(CallEvaluation $evaluation, ?User $viewer): array
    {
        $color = CallQualityLevels::color($evaluation->level);

        $state = match (true) {
            $evaluation->isApproved() => __('admin.call_quality.lead.state_approved'),
            $evaluation->isDiscussed() => __('admin.call_quality.lead.state_discussed'),
            default => __('admin.call_quality.lead.state_pending'),
        };

        return [
            'key' => self::SOURCE_CALL_EVALUATION.'-'.$evaluation->getKey(),
            'source' => self::SOURCE_CALL_EVALUATION,
            'icon' => 'heroicon-m-star',
            'color' => $color,
            'title' => __('admin.call_quality.lead.timeline_title', ['percent' => self::percent($evaluation->score_percent)]),
            'badges' => [$state],
            'rating' => [
                'label' => CallQualityLevels::label($evaluation->level),
                'color' => $color,
            ],
            'from' => null,
            'to' => null,
            'detail' => $evaluation->call_date !== null
                ? __('admin.call_quality.lead.call_date', ['date' => $evaluation->call_date->format('Y-m-d')])
                : null,
            'body' => null,
            'actor' => $evaluation->evaluator?->name,
            'url' => self::evaluationUrl($evaluation, $viewer),
            'at' => $evaluation->created_at,
        ];
    }

    /**
     * The lead's call evaluations the viewer may see; none without call_evaluations.viewAny.
     *
     * @return Collection<int, CallEvaluation>
     */
    private static function visibleEvaluations(ServiceRequest $lead, ?User $viewer): Collection
    {
        if (! ($viewer?->can('call_evaluations.viewAny') ?? false)) {
            return collect();
        }

        return app(CallQualityService::class)
            ->scopeEvaluations(CallEvaluation::query(), $viewer)
            ->with('evaluator:id,name')
            ->where('service_request_id', $lead->getKey())
            ->get();
    }

    private static function evaluationUrl(CallEvaluation $evaluation, ?User $viewer): ?string
    {
        if (! ($viewer?->can('view', $evaluation) ?? false)) {
            return null;
        }

        return CallEvaluationResource::getUrl('view', ['record' => $evaluation]);
    }

    private static function percent(?float $percent): string
    {
        return $percent === null ? '—' : number_format($percent, 0).'%';
    }

    /**
     * @param  Collection<int, ServiceRequestActivity>  $logs
     * @return Collection<string, string>
     */
    private static function statusNames(Collection $logs): Collection
    {
        $slugs = $logs
            ->where('type', ServiceRequestActivityType::StatusChanged)
            ->flatMap(fn (ServiceRequestActivity $log): array => [$log->from_value, $log->to_value])
            ->filter()
            ->unique()
            ->values();

        if ($slugs->isEmpty()) {
            return collect();
        }

        return ServiceRequestStatus::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->mapWithKeys(fn (ServiceRequestStatus $status): array => [$status->slug => $status->getTranslatedName()]);
    }

    /**
     * @param  Collection<int, ServiceRequestActivity>  $logs
     * @return Collection<int, string>
     */
    private static function assigneeNames(Collection $logs): Collection
    {
        $ids = $logs
            ->where('type', ServiceRequestActivityType::AssignedChanged)
            ->flatMap(fn (ServiceRequestActivity $log): array => [$log->from_value, $log->to_value])
            ->filter(fn (?string $value): bool => is_numeric($value))
            ->map(fn (string $value): int => (int) $value)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->pluck('name', 'id');
    }

    private static function optionLabel(BackedEnum|string|null $value): string
    {
        $value = $value instanceof BackedEnum ? (string) $value->value : (string) $value;
        $key = 'admin.options.'.$value;

        return __($key) !== $key ? __($key) : $value;
    }
}
