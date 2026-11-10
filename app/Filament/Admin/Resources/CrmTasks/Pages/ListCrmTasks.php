<?php

namespace App\Filament\Admin\Resources\CrmTasks\Pages;

use App\Enums\CrmTaskStatus;
use App\Exports\CrmTasksExport;
use App\Filament\Admin\Resources\CrmTasks\CrmTaskResource;
use App\Models\CrmTask;
use App\Models\Employee;
use App\Services\Admin\CrmTaskImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListCrmTasks extends KanbanBoard
{
    protected static string $resource = CrmTaskResource::class;

    protected static string $model = CrmTask::class;

    protected static string $statusEnum = CrmTaskStatus::class;

    protected static string $recordView = 'filament.resources.crm-tasks.kanban.record';

    protected static string $statusView = 'filament.resources.crm-tasks.kanban.status';

    protected string $view = 'filament.resources.crm-tasks.kanban.board';

    public bool $disableEditModal = true;

    public string $search = '';

    public string $employeeId = '';

    public string $entityType = '';

    public string $statusFilter = 'all';

    public string $dueScope = 'all';

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_xlsx')
                ->label(__('admin.actions.export_xlsx'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn (): BinaryFileResponse => Excel::download(new CrmTasksExport($this->getEloquentQuery()), 'crm-tasks-'.now()->format('Y-m-d').'.xlsx')),

            Action::make('import_tasks')
                ->label(__('admin.actions.import_tasks'))
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading(__('admin.modals.crm_task_import.heading'))
                ->modalDescription(__('admin.modals.crm_task_import.description'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.crm-tasks.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                            e(__('admin.actions.download_template')).
                            '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                            e(__('admin.helpers.crm_task_import_template_help')).
                            '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label(__('admin.fields.import_file'))
                        ->disk('local')
                        ->directory('imports/crm-tasks')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = (string) ($data['file'] ?? '');
                    $disk = Storage::disk('local');
                    $summary = app(CrmTaskImportService::class)->import($disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        __('admin.notifications.crm_tasks_import_summary', ['processed' => $summary['processed'], 'created' => $summary['created'], 'skipped' => $summary['skipped']]),
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 5)) : null,
                    ]));

                    Notification::make()
                        ->title(__('admin.notifications.crm_tasks_import_complete'))
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    protected function statuses(): Collection
    {
        return collect(CrmTaskStatus::cases())
            ->map(fn (CrmTaskStatus $status): array => [
                'id' => $status->value,
                'title' => __('admin.options.'.$status->value),
                'limit' => $this->getColumnLimit($status),
            ]);
    }

    protected function getEloquentQuery(): Builder
    {
        $query = CrmTaskResource::getEloquentQuery();

        if ($this->search !== '') {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if ($this->employeeId !== '') {
            $query->where('employee_id', (int) $this->employeeId);
        }

        if ($this->entityType !== '') {
            $query->where('entity_type', $this->entityType);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dueScope === 'overdue') {
            $query->whereNotNull('due_at')->where('due_at', '<', now());
        }

        if ($this->dueScope === 'today') {
            $query->whereDate('due_at', now()->toDateString());
        }

        if ($this->dueScope === 'this_week') {
            $query->whereBetween('due_at', [now(), now()->endOfWeek()]);
        }

        if ($this->dueScope === 'no_due') {
            $query->whereNull('due_at');
        }

        return $query;
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        $targetStatus = CrmTaskStatus::from($status);
        $limit = $this->getColumnLimit($targetStatus);

        if ($limit !== null && count($toOrderedIds) > $limit) {
            Notification::make()
                ->title(__('admin.notifications.limit_reached'))
                ->body(__('admin.notifications.kanban_column_limit_reached', ['limit' => $limit]))
                ->warning()
                ->send();

            return;
        }

        $task = $this->getEloquentQuery()->find($recordId);

        if (! $task) {
            return;
        }

        $task->update([
            static::$recordStatusAttribute => $status,
            'is_done' => $targetStatus === CrmTaskStatus::Done,
            'completed_at' => $targetStatus === CrmTaskStatus::Done ? now() : null,
        ]);
    }

    private function getColumnLimit(CrmTaskStatus $status): ?int
    {
        return match ($status) {
            CrmTaskStatus::Todo => 30,
            CrmTaskStatus::InProgress => 20,
            CrmTaskStatus::Done => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            ...parent::getViewData(),
            'employees' => Employee::query()->orderBy('name')->pluck('name', 'id')->all(),
            'statusOptions' => [
                'all' => __('admin.labels.all_courses'),
                CrmTaskStatus::Todo->value => __('admin.options.'.CrmTaskStatus::Todo->value),
                CrmTaskStatus::InProgress->value => __('admin.options.'.CrmTaskStatus::InProgress->value),
                CrmTaskStatus::Done->value => __('admin.options.'.CrmTaskStatus::Done->value),
            ],
            'dueOptions' => [
                'all' => __('admin.labels.all_courses'),
                'overdue' => __('admin.dashboard_widgets.stats.tasks_overdue'),
                'today' => __('admin.options.today'),
                'this_week' => __('admin.dashboard_widgets.stats.tasks_due_week'),
                'no_due' => __('admin.labels.no_due_date'),
            ],
            'entityOptions' => [
                '' => __('admin.labels.all_courses'),
                \App\Models\ServiceRequest::class => __('admin.models.service_request'),
                \App\Models\Deal::class => __('admin.models.deal'),
            ],
        ];
    }
}
