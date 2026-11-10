<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Tables;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasStatusDateField;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\ScopesServiceRequestsToEmployee;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\Team;
use App\Services\ServiceRequestService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ServiceRequestsTable
{
    use HasStatusDateField;
    use ScopesServiceRequestsToEmployee;

    /**
     * Team + assignee fields shared by the single-row and bulk assign actions.
     *
     * @return array<int, Select>
     */
    private static function assignmentFields(): array
    {
        return [
            Select::make('team_id')
                ->label(__('admin.labels.owner_team'))
                ->options(fn () => Team::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->required(),
            Select::make('assigned_to')
                ->label(__('admin.labels.assigned_to'))
                ->options(fn (Get $get) => self::assignedToOptions($get('team_id') ? (int) $get('team_id') : null))
                ->searchable()
                ->required(),
        ];
    }

    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table, bool $showCompanyFields = false): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.labels.id'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('source')
                    ->label(__('admin.labels.source'))
                    ->formatStateUsing(fn ($state) => self::translateOption($state))
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('admin.labels.name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('company_name')
                    ->label(__('admin.labels.company_name'))
                    ->searchable()
                    ->visible($showCompanyFields)
                    ->toggleable(),

                TextColumn::make('city')
                    ->label(__('admin.labels.city'))
                    ->searchable()
                    ->visible($showCompanyFields)
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label(__('admin.labels.phone'))
                    ->searchable(),

                TextColumn::make('phone_copy')
                    ->label('')
                    ->state(fn (ServiceRequest $record): string => (string) $record->phone)
                    ->formatStateUsing(fn (): string => '')
                    ->icon('heroicon-o-clipboard-document')
                    ->iconColor('gray')
                    ->extraAttributes(['class' => 'fi-ta-icon-btn fi-ta-icon-btn-neutral'])
                    ->copyable()
                    ->copyableState(fn (ServiceRequest $record): string => (string) $record->phone)
                    ->copyMessage(__('admin.messages.copied')),

                TextColumn::make('phone_call')
                    ->label('')
                    ->state(fn (ServiceRequest $record): string => (string) $record->phone)
                    ->formatStateUsing(fn (): string => '')
                    ->icon('heroicon-o-phone')
                    ->iconColor('success')
                    ->extraAttributes(['class' => 'fi-ta-icon-btn fi-ta-icon-btn-success'])
                    ->url(fn (ServiceRequest $record): ?string => filled($record->phone)
                        ? 'tel:'.ServiceRequestService::normalizePhone($record->phone)
                        : null),

                TextColumn::make('email')
                    ->label(__('admin.labels.email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(fn ($state) => self::formatStatusWithStage($state))
                    ->badge()
                    ->color(fn ($state) => Color::hex(self::statusesBySlug()->get($state)?->color ?? '#6b7280'))
                    ->sortable(),

                TextColumn::make('status_date')
                    ->label(__('admin.labels.status_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('course_or_bundle')
                    ->label(__('admin.labels.course_or_bundle'))
                    ->state(fn (ServiceRequest $record): ?string => $record->course?->getTranslation('title', app()->getLocale())
                        ?? $record->bundle?->getTranslation('title', app()->getLocale()))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('assignedTo.name')
                    ->label(__('admin.labels.assigned_to'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('priority')
                    ->label(__('admin.labels.priority'))
                    ->formatStateUsing(fn ($state) => self::translateOption($state))
                    ->badge()
                    ->color(function ($state): string {
                        $value = $state instanceof ServiceRequestPriority ? $state->value : (string) $state;

                        return match ($value) {
                            ServiceRequestPriority::High->value => 'danger',
                            ServiceRequestPriority::Normal->value => 'info',
                            ServiceRequestPriority::Low->value => 'gray',
                            default => 'secondary',
                        };
                    })
                    ->sortable(),

                TextColumn::make('last_activity_at')
                    ->label(__('admin.labels.last_activity_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sheet_synced_at')
                    ->label(__('admin.labels.sheet_synced_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.synced') : __('admin.options.not_synced')),

                TextColumn::make('utm_source')
                    ->label(__('admin.labels.utm_source'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('utm_campaign')
                    ->label(__('admin.labels.utm_campaign'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('utm_content')
                    ->label(__('admin.labels.utm_content'))
                    ->limit(30)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ip_address')
                    ->label(__('admin.labels.ip_address'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                TextColumn::make('user_agent')
                    ->label(__('admin.labels.user_agent'))
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deletedBy.name')
                    ->label(__('admin.labels.deleted_by'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label(__('admin.labels.source'))
                    ->options(self::options(array_column(ServiceRequestSource::cases(), 'value')))
                    ->searchable()
                    ->native(false),

                SelectFilter::make('type_id')
                    ->label(__('admin.labels.type'))
                    ->options(fn () => ServiceRequestType::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (ServiceRequestType $type) => [$type->id => $type->getTranslatedName()])
                    )
                    ->searchable()
                    ->native(false),

                SelectFilter::make('status')
                    ->label(__('admin.labels.status'))
                    ->options(fn () => ServiceRequestStatus::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (ServiceRequestStatus $status) => [$status->slug => $status->getTranslatedName()])
                    )
                    ->searchable()
                    ->native(false),

                SelectFilter::make('team_id')
                    ->label(__('admin.labels.owner_team'))
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('priority')
                    ->label(__('admin.labels.priority'))
                    ->options(self::options([
                        'low',
                        'normal',
                        'high',
                    ]))
                    ->searchable()
                    ->native(false),

                SelectFilter::make('assigned_to')
                    ->label(__('admin.labels.assigned_to'))
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('follow_up')
                    ->label(__('admin.labels.follow_up'))
                    ->options([
                        'overdue' => __('admin.options.follow_up_overdue'),
                        'due_today' => __('admin.options.follow_up_due_today'),
                        'upcoming' => __('admin.options.follow_up_upcoming'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'overdue' => $query->followUpOverdue(),
                        'due_today' => $query->followUpDueToday(),
                        'upcoming' => $query->followUpUpcoming(),
                        default => $query,
                    })
                    ->native(false),

                TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                Action::make('reassign')
                    ->label(__('admin.actions.assign'))
                    ->icon('heroicon-o-user-plus')
                    ->color('gray')
                    ->visible(fn (): bool => auth('admin')->user()?->can('service_requests.assign') ?? false)
                    ->fillForm(fn (ServiceRequest $record): array => [
                        'team_id' => $record->team_id,
                        'assigned_to' => $record->assigned_to,
                    ])
                    ->schema(self::assignmentFields())
                    ->action(fn (ServiceRequest $record, array $data): bool => $record->update([
                        'team_id' => $data['team_id'],
                        'assigned_to' => $data['assigned_to'],
                    ])),
                Action::make('update_status')
                    ->label(__('admin.actions.update_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (): bool => auth('admin')->user()?->can('service_requests.update') ?? false)
                    ->schema([
                        Section::make(__('admin.sections.status'))
                            ->schema([
                                Select::make('status')
                                    ->label(__('admin.labels.status'))
                                    ->options(fn (ServiceRequest $record): array => ServiceRequestStatus::query()
                                        ->active()
                                        ->forType($record->type_id)
                                        ->orderBy('sort_order')
                                        ->get()
                                        ->mapWithKeys(fn (ServiceRequestStatus $status): array => [$status->slug => $status->getTranslatedName()])
                                        ->all())
                                    ->default(fn (ServiceRequest $record): string => $record->status)
                                    ->live()
                                    ->required(),
                                DateTimePicker::make('status_date')
                                    ->label(__('admin.labels.status_date'))
                                    ->seconds(false)
                                    ->default(fn (ServiceRequest $record): ?Carbon => $record->status_date)
                                    ->visible(fn (Get $get): bool => self::statusRequiresDate($get('status')))
                                    ->required(fn (Get $get): bool => self::statusRequiresDate($get('status'))),
                                TextInput::make('name')
                                    ->label(__('admin.labels.name'))
                                    ->default(fn (ServiceRequest $record): ?string => $record->name),
                            ]),
                        Section::make(__('admin.sections.activity'))
                            ->schema([
                                Select::make('type')
                                    ->label(__('admin.labels.type'))
                                    ->options(collect(CrmActivityType::cases())->mapWithKeys(
                                        fn (CrmActivityType $type): array => [$type->value => __('admin.options.'.$type->value)]
                                    ))
                                    ->required(),
                                Select::make('outcome')
                                    ->label(__('admin.labels.outcome'))
                                    ->options(collect(CrmActivityOutcome::cases())->mapWithKeys(
                                        fn (CrmActivityOutcome $outcome): array => [$outcome->value => __('admin.options.'.$outcome->value)]
                                    ))
                                    ->nullable(),
                                DateTimePicker::make('completed_at')
                                    ->label(__('admin.labels.date'))
                                    ->seconds(false)
                                    ->default(now()),
                                Textarea::make('body')
                                    ->label(__('admin.labels.body'))
                                    ->rows(3),
                            ])
                            ->columns(2),
                    ])
                    ->modalHeading(__('admin.modals.service_request_update.heading'))
                    ->modalDescription(__('admin.modals.service_request_update.description'))
                    ->action(function (array $data, ServiceRequest $record): void {
                        $updates = [
                            'status' => $data['status'],
                            'name' => $data['name'],
                        ];

                        if (self::statusRequiresDate($data['status'])) {
                            $updates['status_date'] = $data['status_date'] ?? null;
                        }

                        $record->update($updates);

                        $type = CrmActivityType::from($data['type']);

                        $record->crmActivities()->create([
                            'type' => $data['type'],
                            'outcome' => $data['outcome'] ?? null,
                            'subject' => __('admin.options.'.$type->value).' — '.__('admin.options.'.$data['status']),
                            'body' => $data['body'] ?? null,
                            'completed_at' => $data['completed_at'] ?? now(),
                            'employee_id' => Employee::query()->where('user_id', auth('admin')->id())->value('id'),
                        ]);
                    }),
                Action::make('preview_attachments')
                    ->label(__('admin.labels.preview'))
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    ->visible(fn (ServiceRequest $record): bool => filled($record->attachments))
                    ->modalContent(function (ServiceRequest $record): HtmlString {
                        $disk = Storage::disk('s3');

                        $links = collect((array) $record->attachments)
                            ->filter()
                            ->map(function (string $path) use ($disk): string {
                                $url = rescue(fn () => $disk->temporaryUrl($path, now()->addMinutes(30)), null, false);
                                $name = basename(str_replace('_', ' ', $path));

                                return $url
                                    ? '<p class="py-1"><a href="'.e($url).'" target="_blank" class="underline text-primary-600">'.e($name).'</a></p>'
                                    : '<p class="py-1 text-gray-400">'.e($name).'</p>';
                            })
                            ->implode('');

                        return new HtmlString('<div class="space-y-1">'.$links.'</div>');
                    })
                    ->modalHeading(__('admin.labels.attachments'))
                    ->modalSubmitAction(false),
                EditAction::make()
                    ->label(__('admin.actions.edit')),
                RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
                ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assign')
                        ->label(__('admin.actions.assign'))
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->visible(fn (): bool => auth('admin')->user()?->can('service_requests.assign') ?? false)
                        ->schema(self::assignmentFields())
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (ServiceRequest $record) => $record->update([
                                'team_id' => $data['team_id'],
                                'assigned_to' => $data['assigned_to'],
                            ]));
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label(__('admin.actions.delete_bulk')),
                    RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
                    ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.service_requests.heading'))
            ->emptyStateDescription(__('admin.empty_states.service_requests.description'));
    }

    private static function translateOption($state): string
    {
        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
        $key = 'admin.options.'.$value;

        return __($key) !== $key ? __($key) : $value;
    }

    private static function formatStatusWithStage($state): string
    {
        $status = self::statusesBySlug()->get($state);

        if (! $status) {
            return (string) $state;
        }

        $stageName = $status->stage?->getTranslatedName();

        return $stageName
            ? "{$stageName} ({$status->getTranslatedName()})"
            : $status->getTranslatedName();
    }

    /**
     * @return \Illuminate\Support\Collection<string, ServiceRequestStatus>
     */
    private static function statusesBySlug(): \Illuminate\Support\Collection
    {
        static $statuses = null;

        return $statuses ??= ServiceRequestStatus::query()->with('stage')->get()->keyBy('slug');
    }

    /**
     * @param  array<int, string>  $values
     * @return array<string, string>
     */
    private static function options(array $values): array
    {
        $options = [];

        foreach ($values as $value) {
            $key = 'admin.options.'.$value;
            $options[$value] = __($key) !== $key ? __($key) : $value;
        }

        return $options;
    }
}
