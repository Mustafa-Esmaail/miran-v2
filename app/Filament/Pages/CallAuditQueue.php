<?php

namespace App\Filament\Pages;

use App\Enums\DateRangePreset;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Admin\Resources\ServiceRequests\ServiceRequestResource;
use App\Filament\Pages\Concerns\HasDateRangePresets;
use App\Models\CallEvaluation;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\CallQualityService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Call-quality audit queue: the first lead of each phone in the range whose
 * phone was never evaluated nor marked as a follow-up, ready to be evaluated.
 */
class CallAuditQueue extends Page implements HasTable
{
    use HasDateRangePresets;
    use HasFiltersForm, InteractsWithTable {
        HasFiltersForm::normalizeTableFilterValuesFromQueryString insteadof InteractsWithTable;
    }

    public const PER_PAGE = 50;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneArrowDownLeft;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'call-audit-queue';

    protected string $view = 'filament.pages.call-audit-queue';

    /**
     * @var Collection<int, array<string, mixed>>|null
     */
    private ?Collection $queueCache = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.call_quality.queue.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_quality_group');
    }

    public static function canAccess(): bool
    {
        return app(CallQualityService::class)->canEvaluate(self::currentUser());
    }

    public function getTitle(): string
    {
        return __('admin.call_quality.queue.title');
    }

    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::Last7Days;
    }

    /**
     * @return array<int, DateRangePreset>
     */
    protected function availableDateRangePresets(): array
    {
        return [
            DateRangePreset::Today,
            DateRangePreset::Yesterday,
            DateRangePreset::Last7Days,
            DateRangePreset::Last30Days,
            DateRangePreset::ThisMonth,
            DateRangePreset::LastMonth,
            DateRangePreset::Custom,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(['md' => 2, 'xl' => 5])
            ->components([
                ...$this->dateRangeFields(),
                Select::make('employee_id')
                    ->label(__('admin.call_quality.queue.employee'))
                    ->placeholder(__('admin.call_quality.queue.all_employees'))
                    ->options(fn (): array => $this->employeeOptions())
                    ->searchable()
                    ->live()
                    ->hidden(fn (): bool => $this->seesOnlyThemselves()),
                Toggle::make('new_only')
                    ->label(__('admin.call_quality.queue.new_only'))
                    ->helperText(__('admin.call_quality.queue.new_only_hint'))
                    ->default(true)
                    ->live()
                    ->inline(false),
            ]);
    }

    public function updatedFilters(): void
    {
        if ($this->persistsFiltersInSession()) {
            session()->put($this->getFiltersSessionKey(), $this->filters);
        }

        $this->queueCache = null;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (int $page, int $recordsPerPage): LengthAwarePaginator => $this->paginatedRows($page, $recordsPerPage))
            ->paginated([self::PER_PAGE])
            ->defaultPaginationPageOption(self::PER_PAGE)
            ->emptyStateHeading(__('admin.call_quality.queue.empty'))
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge)
            ->columns([
                TextColumn::make('date')
                    ->label(__('admin.call_quality.queue.columns.date')),
                TextColumn::make('time')
                    ->label(__('admin.call_quality.queue.columns.time')),
                TextColumn::make('phone')
                    ->label(__('admin.call_quality.queue.columns.phone'))
                    ->url(fn (array $record): string => 'tel:'.$record['phone'])
                    ->extraAttributes(['dir' => 'ltr'])
                    ->copyable(),
                TextColumn::make('name')
                    ->label(__('admin.call_quality.queue.columns.name'))
                    ->weight('medium')
                    ->color('primary')
                    ->url(fn (array $record): string => ServiceRequestResource::getUrl('view', ['record' => $record['id']])),
                TextColumn::make('course')
                    ->label(__('admin.call_quality.queue.columns.course'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('employee')
                    ->label(__('admin.call_quality.queue.columns.employee'))
                    ->placeholder('—')
                    ->color(fn (array $record): ?string => $record['has_employee'] ? null : 'warning')
                    ->icon(fn (array $record): ?Heroicon => $record['has_employee'] ? null : Heroicon::OutlinedExclamationTriangle)
                    ->description(fn (array $record): ?string => $record['has_employee'] ? null : __('admin.call_quality.queue.no_employee')),
                TextColumn::make('status')
                    ->label(__('admin.call_quality.queue.columns.status'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('evaluate')
                    ->label(__('admin.call_quality.queue.evaluate'))
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->button()
                    ->size('sm')
                    ->url(fn (array $record): string => CallEvaluationResource::getUrl('create', ['service_request' => $record['id']])),
                Action::make('followUp')
                    ->label(__('admin.call_quality.queue.follow_up'))
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.call_quality.queue.follow_up_heading'))
                    ->modalDescription(__('admin.call_quality.queue.follow_up_description'))
                    ->modalSubmitActionLabel(__('admin.call_quality.queue.follow_up'))
                    ->visible(fn (): bool => self::currentUser()?->can('call_exclusions.create') ?? false)
                    ->action(function (array $record): void {
                        $this->markFollowUp((int) $record['id']);
                    }),
            ]);
    }

    /**
     * @return array{awaiting: int, employees: int, my_evaluations_today: int}
     */
    public function getQueueTotals(): array
    {
        $rows = $this->queueRows();

        return [
            'awaiting' => $rows->count(),
            'employees' => $rows->pluck('assigned_to')->filter()->unique()->count(),
            'my_evaluations_today' => CallEvaluation::query()
                ->where('evaluator_id', self::currentUser()?->getKey())
                ->whereDate('created_at', today())
                ->count(),
        ];
    }

    protected function markFollowUp(int $leadId): void
    {
        $user = self::currentUser();
        $lead = ServiceRequest::query()->find($leadId);

        if ($user === null || $lead === null || ! $user->can('call_exclusions.create')) {
            return;
        }

        app(CallQualityService::class)->exclude($lead, $user);

        $this->queueCache = null;
        $this->flushCachedTableRecords();

        Notification::make()
            ->success()
            ->title(__('admin.call_quality.queue.follow_up_done'))
            ->send();
    }

    protected function paginatedRows(int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $rows = $this->queueRows();

        return new LengthAwarePaginator(
            $rows->forPage($page, $recordsPerPage),
            total: $rows->count(),
            perPage: $recordsPerPage,
            currentPage: $page,
        );
    }

    /**
     * @return Collection<int, array{id: int, assigned_to: ?int, date: ?string, time: ?string, phone: string, name: ?string, course: ?string, employee: ?string, has_employee: bool, status: ?string}>
     */
    protected function queueRows(): Collection
    {
        return $this->queueCache ??= $this->buildQueueRows();
    }

    /**
     * @return Collection<int, array{id: int, assigned_to: ?int, date: ?string, time: ?string, phone: string, name: ?string, course: ?string, employee: ?string, has_employee: bool, status: ?string}>
     */
    protected function buildQueueRows(): Collection
    {
        [$from, $to] = $this->queueDateRange();
        $employeeId = filled($this->filters['employee_id'] ?? null) && ! $this->seesOnlyThemselves()
            ? (int) $this->filters['employee_id']
            : null;

        $leads = app(CallQualityService::class)->auditQueue(
            $from,
            $to,
            (bool) ($this->filters['new_only'] ?? true),
            self::currentUser(),
            $employeeId,
        );

        $linkedUserIds = Employee::query()
            ->whereIn('user_id', $leads->pluck('assigned_to')->filter()->unique()->values())
            ->pluck('user_id')
            ->flip();

        return $leads->mapWithKeys(fn (ServiceRequest $lead): array => [
            $lead->getKey() => [
                'id' => $lead->getKey(),
                'assigned_to' => $lead->assigned_to,
                'date' => $lead->created_at?->format('Y-m-d'),
                'time' => $lead->created_at?->format('H:i'),
                'phone' => (string) $lead->phone,
                'name' => $lead->name,
                'course' => $lead->course?->title,
                'employee' => $lead->assignedTo?->name,
                'has_employee' => $lead->assigned_to !== null && $linkedUserIds->has($lead->assigned_to),
                'status' => $lead->statusModel?->getTranslatedName() ?? $lead->status,
            ],
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function queueDateRange(): array
    {
        [$from, $to] = $this->reportDateRange();
        [$defaultFrom, $defaultTo] = $this->defaultDateRangePreset()->bounds();

        return [$from ?? $defaultFrom, $to ?? $defaultTo->endOfDay()];
    }

    /**
     * @return array<int, string>
     */
    protected function employeeOptions(): array
    {
        $visibleIds = app(CallQualityService::class)->visibleEmployeeIds(self::currentUser());

        return Employee::query()
            ->whereNotNull('user_id')
            ->when($visibleIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $visibleIds))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function seesOnlyThemselves(): bool
    {
        $visibleIds = app(CallQualityService::class)->visibleEmployeeIds(self::currentUser());

        return $visibleIds !== null && $visibleIds->count() <= 1;
    }

    protected static function currentUser(): ?User
    {
        $user = auth('admin')->user();

        return $user instanceof User ? $user : null;
    }
}
