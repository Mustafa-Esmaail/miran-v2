<?php

namespace App\Filament\Admin\Resources\ServiceRequests\Schemas;

use App\Enums\EmployeeRole;
use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\HasStatusDateField;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\ScopesServiceRequestsToEmployee;
use App\Http\Middleware\CaptureUtm;
use App\Models\Course;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Services\ServiceRequestService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ServiceRequestForm
{
    use HasStatusDateField;
    use ScopesServiceRequestsToEmployee;

    /**
     * @param  array<int, ServiceRequestSource>|null  $lockedSources  When set, the "source"
     *                                                                field is restricted to these values and pre-filled with the first one on create.
     * @param  bool  $showAttachments  When true, shows the multi-file attachment upload field (Complaint requests only).
     * @param  bool  $showCompanyFields  When true, shows the company name/city fields (B2B requests only).
     */
    public static function configure(Schema $schema, ?array $lockedSources = null, bool $showAttachments = false, bool $showCompanyFields = false): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.request_details'))
                    ->columnSpanFull()
                    ->schema([
                        Select::make('source')
                            ->label(__('admin.labels.source'))
                            ->options(self::options($lockedSources ?? ServiceRequestSource::cases()))
                            ->default($lockedSources[0]->value ?? null)
                            ->live()
                            ->hidden(fn () => self::isSalesAgent())
                            ->required(fn () => ! self::isSalesAgent()),
                        Select::make('type_id')
                            ->label(__('admin.labels.type'))
                            ->options(fn () => ServiceRequestType::query()
                                ->active()
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (ServiceRequestType $type) => [
                                    $type->id => $type->getTranslatedName(),
                                ])
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('status', null))
                            ->hidden(fn () => self::isSalesAgent())
                            ->nullable(),
                        Select::make('status')
                            ->label(__('admin.labels.status'))
                            ->options(fn (Get $get) => ServiceRequestStatus::query()
                                ->active()
                                ->forType($get('type_id') !== null ? (int) $get('type_id') : null)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (ServiceRequestStatus $status) => [
                                    $status->slug => $status->getTranslatedName(),
                                ])
                            )
                            ->live()
                            ->required(),
                        DateTimePicker::make('status_date')
                            ->label(__('admin.labels.status_date'))
                            ->seconds(false)
                            ->visible(fn (Get $get): bool => self::statusRequiresDate($get('status')))
                            ->required(fn (Get $get): bool => self::statusRequiresDate($get('status'))),
                        Select::make('priority')
                            ->label(__('admin.labels.priority'))
                            ->options(self::options(ServiceRequestPriority::cases()))
                            ->required(),
                        Select::make('team_id')
                            ->label(__('admin.labels.owner_team'))
                            ->relationship('team', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('assigned_to', null))
                            ->required(),
                        Select::make('user_id')
                            ->label(__('admin.labels.customer'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->label(__('admin.labels.name'))
                            ->maxLength(190)
                            ->disabled(fn (?ServiceRequest $record): bool => $record !== null),
                        TextInput::make('company_name')
                            ->label(__('admin.labels.company_name'))
                            ->maxLength(190)
                            ->visible($showCompanyFields),
                        TextInput::make('city')
                            ->label(__('admin.labels.city'))
                            ->maxLength(190)
                            ->visible($showCompanyFields),
                        TextInput::make('phone')
                            ->label(__('admin.labels.phone'))
                            ->required()
                            ->maxLength(60)
                            ->live(onBlur: true)
                            ->helperText(fn (?string $state, ?ServiceRequest $record): ?HtmlString => $record === null
                                ? self::possibleDuplicatesHint((string) $state)
                                : null)
                            ->disabled(fn (?ServiceRequest $record): bool => $record !== null),
                        TextInput::make('email')
                            ->label(__('admin.labels.email'))
                            ->email()
                            ->maxLength(190)
                            ->disabled(fn (?ServiceRequest $record): bool => $record !== null),
                        Select::make('course_id')
                            ->label(__('admin.labels.course'))
                            ->options(function () {
                                $locale = app()->getLocale();

                                return Course::query()
                                    ->published()
                                    ->orderBy("title->{$locale}")
                                    ->get()
                                    ->mapWithKeys(fn (Course $course) => [
                                        $course->id => $course->getTranslation('title', $locale),
                                    ]);
                            })
                            ->searchable()
                            ->preload(),
                        Textarea::make('message')
                            ->label(__('admin.labels.message'))
                            ->rows(4)
                            ->disabled(fn (?ServiceRequest $record): bool => $record !== null)
                            ->columnSpanFull(),
                        FileUpload::make('attachments')
                            ->label(__('admin.labels.attachments'))
                            ->disk('s3')
                            ->directory('service-requests/attachments')
                            ->visibility('private')
                            ->multiple()
                            ->reorderable()
                            ->openable()
                            ->deletable(fn (?ServiceRequest $record): bool => $record === null)
                            ->visible($showAttachments)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.assignment'))
                    ->hidden(fn () => self::isSalesAgent())
                    ->schema([
                        Select::make('assigned_to')
                            ->label(__('admin.labels.assigned_to'))
                            ->options(fn (Get $get) => self::assignedToOptions($get('team_id')))
                            ->live()
                            ->searchable()
                            ->preload(),
                        TextInput::make('last_activity_at')
                            ->label(__('admin.labels.last_activity_at'))
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.utm_attribution'))
                    ->schema(collect(CaptureUtm::KEYS)
                        ->map(fn (string $key) => TextInput::make($key)
                            ->label(__("admin.labels.{$key}"))
                            ->disabled()
                            ->dehydrated(false))
                        ->all())
                    ->visible(fn (?ServiceRequest $record) => $record !== null && collect(CaptureUtm::KEYS)->contains(fn (string $key) => filled($record->{$key})))
                    ->collapsible()
                    ->columns(2),
                Section::make(__('admin.sections.security'))
                    ->hidden(fn () => self::isSalesAgent())
                    ->schema([
                        TextInput::make('ip_address')
                            ->label(__('admin.labels.ip_address'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('user_agent')
                            ->label(__('admin.labels.user_agent'))
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(2);
    }

    private static function isSalesAgent(): bool
    {
        $employee = Employee::query()->where('user_id', auth()->id())->first();

        return $employee?->role === EmployeeRole::SalesPerson;
    }

    /**
     * @param  array<int, BackedEnum>  $cases
     * @return array<string, string>
     */
    private static function options(array $cases): array
    {
        $options = [];
        foreach ($cases as $case) {
            $key = 'admin.options.'.$case->value;
            $options[$case->value] = __($key) !== $key ? __($key) : $case->value;
        }

        return $options;
    }

    /**
     * A non-blocking warning under the phone field listing existing leads with the same number.
     */
    private static function possibleDuplicatesHint(string $phone): ?HtmlString
    {
        if (trim($phone) === '') {
            return null;
        }

        $duplicates = ServiceRequestService::findPossibleDuplicates($phone);

        if ($duplicates->isEmpty()) {
            return null;
        }

        $items = $duplicates
            ->map(fn (ServiceRequest $lead): string => '<li>#'.$lead->id.' — '.e($lead->name ?: '—')
                .' · '.e($lead->statusModel?->getTranslatedName() ?? $lead->status ?? '—')
                .' · '.e($lead->assignedTo?->name ?? __('admin.options.unassigned'))
                .' · '.e($lead->created_at?->format('Y-m-d') ?? '').'</li>')
            ->implode('');

        return new HtmlString(
            '<span class="font-medium text-warning-600 dark:text-warning-400">'.e(__('admin.labels.possible_duplicates')).'</span>'
            .'<ul class="mt-1 list-disc ps-5 text-gray-600 dark:text-gray-300">'.$items.'</ul>'
        );
    }
}
