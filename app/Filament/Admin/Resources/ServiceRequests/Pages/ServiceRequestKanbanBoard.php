<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Pages;

use App\Enums\ServiceRequestStatusOutcome;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

abstract class ServiceRequestKanbanBoard extends KanbanBoard
{
    protected static string $model = ServiceRequest::class;

    protected static string $recordView = 'filament.resources.service-requests.kanban.record';

    protected static string $statusView = 'filament.resources.service-requests.kanban.status';

    protected string $view = 'filament.resources.service-requests.kanban.board';

    public bool $disableEditModal = true;

    public string $search = '';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public string $assignedTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->subWeek()->toDateString();
    }

    private const UNASSIGNED_ID = 'unassigned';

    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::canViewAny();
    }

    public function getBreadcrumb(): ?string
    {
        return __('admin.actions.kanban_view');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table_view')
                ->label(__('admin.actions.table_view'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => static::getResource()::getUrl('index')),
        ];
    }

    /**
     * Slugs of the service_request_types whose stages this board shows as
     * columns (e.g. ['tlbat'] for B2C/B2B, ['shakawi'] for Complaint).
     * Return an empty array to show every type's stages (the base "all
     * requests" board). Declared explicitly per board rather than inferred
     * from data, since a type's stages must still show even before any
     * record has actually been moved onto one of its statuses.
     *
     * @return array<int, string>
     */
    abstract protected function relevantTypeSlugs(): array;

    protected function relevantTypeIds(): Collection
    {
        $slugs = $this->relevantTypeSlugs();

        return ServiceRequestType::query()
            ->when($slugs !== [], fn (Builder $q) => $q->whereIn('slug', $slugs))
            ->pluck('id');
    }

    protected function statuses(): Collection
    {
        $typeIds = $this->relevantTypeIds();

        $columns = $typeIds->isEmpty()
            ? collect()
            : ServiceRequestStage::query()
                ->active()
                ->whereIn('type_id', $typeIds)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (ServiceRequestStage $stage): array => [
                    'id' => (string) $stage->id,
                    'title' => $stage->getTranslatedName(),
                    'color' => $stage->color,
                    'limit' => null,
                ]);

        return $columns->push([
            'id' => self::UNASSIGNED_ID,
            'title' => __('admin.options.unassigned'),
            'color' => '#6b7280',
            'limit' => null,
        ]);
    }

    protected function getEloquentQuery(): Builder
    {
        $query = static::getResource()::getEloquentQuery()
            ->with(['statusModel.stage', 'assignedTo']);

        if ($this->search !== '') {
            $search = '%'.$this->search.'%';
            $query->where(fn (Builder $q) => $q->where('phone', 'like', $search)->orWhere('name', 'like', $search));
        }

        $query
            ->when($this->dateFrom, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
            ->when($this->dateTo, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))
            ->when($this->assignedTo !== '', fn (Builder $q) => $q->where('assigned_to', (int) $this->assignedTo));

        return $query;
    }

    /**
     * @return array<int, ServiceRequest>
     */
    protected function filterRecordsByStatus(Collection $records, array $status): array
    {
        return $records->filter(function (ServiceRequest $record) use ($status): bool {
            $stageId = $record->statusModel?->stage_id;

            return $status['id'] === self::UNASSIGNED_ID
                ? $stageId === null
                : (string) $stageId === $status['id'];
        })->values()->all();
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $stageId = $status === self::UNASSIGNED_ID ? null : (int) $status;

        $matchingStatuses = ServiceRequestStatus::query()
            ->active()
            ->when($stageId, fn (Builder $q) => $q->where('stage_id', $stageId), fn (Builder $q) => $q->whereNull('stage_id'))
            ->orderBy('sort_order')
            ->get();

        if ($matchingStatuses->count() === 1) {
            $targetStatus = $matchingStatuses->first();

            if ($targetStatus->requires_date) {
                $this->mountAction('setStatusDate', [
                    'recordId' => $recordId,
                    'status' => $targetStatus->slug,
                ]);

                return;
            }

            $this->applyStatus((int) $recordId, $targetStatus->slug);

            return;
        }

        $this->mountAction('pickStatus', [
            'recordId' => $recordId,
            'stageId' => $stageId,
        ]);
    }

    public function pickStatusAction(): Action
    {
        return Action::make('pickStatus')
            ->label(__('admin.actions.choose_status'))
            ->modalHeading(__('admin.actions.choose_status'))
            ->schema(fn (array $arguments): array => [
                Select::make('status')
                    ->label(__('admin.labels.status'))
                    ->options(fn (): array => $this->statusOptions($arguments['stageId'] ?? null))
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        if ($this->statusRequiresDate($state) && blank($get('status_date'))) {
                            $set('status_date', $this->defaultStatusDate()->toDateTimeString());
                        }
                    })
                    ->required(),
                $this->statusDateField()
                    ->visible(fn (Get $get): bool => $this->statusRequiresDate($get('status')))
                    ->required(fn (Get $get): bool => $this->statusRequiresDate($get('status'))),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->applyStatus((int) $arguments['recordId'], $data['status'], $data['status_date'] ?? null);
            });
    }

    public function setStatusDateAction(): Action
    {
        return Action::make('setStatusDate')
            ->label(__('admin.kanban.set_status_date'))
            ->modalHeading(fn (array $arguments): string => __('admin.kanban.set_status_date')
                .' — '
                .(ServiceRequestStatus::query()->where('slug', $arguments['status'] ?? '')->first()?->getTranslatedName() ?? ''))
            ->schema([
                $this->statusDateField()
                    ->default(fn (): string => $this->defaultStatusDate()->toDateTimeString())
                    ->required(),
            ])
            ->action(function (array $data, array $arguments): void {
                $this->applyStatus((int) $arguments['recordId'], (string) $arguments['status'], $data['status_date'] ?? null);
            });
    }

    public function isFollowUpOverdue(ServiceRequest $record): bool
    {
        if ($record->status_date === null || ! $record->status_date->lt(today()->startOfDay())) {
            return false;
        }

        $outcome = $record->statusModel?->outcome;

        return $outcome === null || $outcome === ServiceRequestStatusOutcome::Open;
    }

    private function statusDateField(): DateTimePicker
    {
        return DateTimePicker::make('status_date')
            ->label(__('admin.labels.status_date'))
            ->seconds(false);
    }

    private function defaultStatusDate(): Carbon
    {
        return now()->addDay()->setTime(10, 0);
    }

    private function statusRequiresDate(?string $statusSlug): bool
    {
        if (blank($statusSlug)) {
            return false;
        }

        return (bool) ServiceRequestStatus::query()->where('slug', $statusSlug)->value('requires_date');
    }

    /**
     * @return array<string, mixed>
     */
    private function statusOptions(?int $stageId): array
    {
        $query = ServiceRequestStatus::query()->active()->orderBy('sort_order');

        if ($stageId) {
            return $query->where('stage_id', $stageId)->get()
                ->mapWithKeys(fn (ServiceRequestStatus $requestStatus): array => [$requestStatus->slug => $requestStatus->getTranslatedName()])
                ->all();
        }

        $typeIds = $this->relevantTypeIds();

        return $query->with('stage')
            ->where(function (Builder $q) use ($typeIds): void {
                $q->whereNull('stage_id');

                if ($typeIds->isNotEmpty()) {
                    $q->orWhereHas('stage', fn (Builder $sq) => $sq->whereIn('type_id', $typeIds));
                }
            })
            ->get()
            ->groupBy(fn (ServiceRequestStatus $requestStatus): string => $requestStatus->stage?->getTranslatedName() ?? __('admin.options.unassigned'))
            ->map(fn (Collection $group): array => $group
                ->mapWithKeys(fn (ServiceRequestStatus $requestStatus): array => [$requestStatus->slug => $requestStatus->getTranslatedName()])
                ->all())
            ->all();
    }

    /**
     * A status that requires a date is only saved together with that date;
     * without one the record is left untouched.
     */
    private function applyStatus(int $recordId, string $statusSlug, mixed $statusDate = null): void
    {
        $record = $this->getEloquentQuery()->find($recordId);

        if (! $record) {
            return;
        }

        if (! $this->statusRequiresDate($statusSlug)) {
            $record->update(['status' => $statusSlug]);

            return;
        }

        if (blank($statusDate)) {
            return;
        }

        $record->update([
            'status' => $statusSlug,
            'status_date' => Carbon::parse($statusDate),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            ...parent::getViewData(),
            'editUrlResolver' => fn (ServiceRequest $record): string => static::getResource()::getUrl('edit', ['record' => $record]),
            'assigneeOptions' => static::getResource()::assignedToOptions(null),
        ];
    }
}
