<?php

namespace App\Filament\Admin\Resources\CourseGroups\Tables;

use App\Enums\CourseGroupStatus;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use App\Services\Admin\CourseGroupCloneService;
use App\Services\Admin\CourseGroupImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class CourseGroupsTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course_title'))
                ->state(fn (CourseGroup $record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.group'))
                ->state(fn (CourseGroup $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('group_price_amount')->label(__('admin.labels.price'))->money('SAR')->sortable(),
            TextColumn::make('start_at')->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('end_at')->dateTime('Y-m-d H:i')->toggleable(),
            TextColumn::make('capacity')->sortable()->toggleable(),
            TextColumn::make('instructors.name')
                ->label(__('admin.labels.instructors'))
                ->listWithLineBreaks()
                ->limitList(3)
                ->expandableLimitedList()
                ->toggleable(),
            TextColumn::make('enrollments_count')->label(__('admin.labels.enrollments'))->counts('enrollments')->sortable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof CourseGroupStatus ? $state->value : (string) $state;

                    return match ($value) {
                        CourseGroupStatus::Open->value => 'success',
                        CourseGroupStatus::Closed->value => 'warning',
                        CourseGroupStatus::Archived->value => 'danger',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('course_id')
                ->relationship('course', 'slug')
                ->getOptionLabelFromRecordUsing(fn (Course $record): string => $record->getTranslation('title', app()->getLocale()))
                ->label(__('admin.labels.course'))
                ->searchable(),
            SelectFilter::make('status')->options([
                'open' => __('admin.options.open'),
                'closed' => __('admin.options.closed'),
                'archived' => __('admin.options.archived'),
            ])->searchable(),
            TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            Action::make('copy')
                ->label(__('admin.actions.copy_group'))
                ->icon('heroicon-o-document-duplicate')
                ->modalHeading(__('admin.actions.copy_group'))
                ->modalSubmitActionLabel(__('admin.actions.copy_group'))
                ->fillForm(fn (CourseGroup $record): array => [
                    'course_id' => $record->course_id,
                    'title_ar' => $record->getTranslation('title', 'ar'),
                    'title_en' => $record->getTranslation('title', 'en'),
                    // 'group_price_amount' => $record->group_price_amount,
                    'capacity' => $record->capacity,
                    'start_at' => $record->start_at,
                    'end_at' => $record->end_at,
                    'enrollment_deadline_at' => $record->enrollment_deadline_at,
                    'status' => $record->status->value,
                    'copy_curriculum_sections' => true,
                    'copy_sessions' => true,
                    'copy_lessons' => false,
                    'copy_attachments' => false,
                ])
                ->schema([
                    Section::make(__('admin.sections.group_details'))
                        ->schema([
                            Select::make('course_id')
                                ->relationship(
                                    'course',
                                    'slug',
                                    modifyQueryUsing: fn (Builder $query) => app(CourseAccessService::class)->scopeCourses($query, Filament::auth()->user(), 'courses.viewAny')
                                )
                                ->getOptionLabelFromRecordUsing(fn (Course $record): string => $record->getTranslation('title', app()->getLocale()))
                                ->label(__('admin.labels.course'))
                                ->required()
                                ->preload()
                                ->searchable(['title->en', 'title->ar', 'slug']),
                            TextInput::make('title_ar')
                                ->label(__('admin.labels.title_ar'))
                                ->required(),
                            TextInput::make('title_en')
                                ->label(__('admin.labels.title_en')),
                            // TextInput::make('group_price_amount')
                            //     ->numeric()
                            //     ->label(__('admin.labels.group_price'))
                            //     ->prefix('SAR'),
                            TextInput::make('capacity')
                                ->numeric()
                                ->label(__('admin.labels.capacity')),
                            Select::make('status')
                                ->options([
                                    'open' => __('admin.options.open'),
                                    'closed' => __('admin.options.closed'),
                                    'archived' => __('admin.options.archived'),
                                ])
                                ->required()
                                ->label(__('admin.labels.status')),
                            DateTimePicker::make('start_at')
                                ->seconds(false),
                            DateTimePicker::make('end_at')
                                ->seconds(false),
                            DateTimePicker::make('enrollment_deadline_at')
                                ->seconds(false)
                                ->label(__('admin.labels.enrollment_deadline')),
                        ])
                        ->columns(2),
                    Section::make(__('admin.sections.content'))
                        ->schema([
                            Toggle::make('copy_curriculum_sections')
                                ->label(__('admin.actions.copy_curriculum_sections'))
                                ->default(true),
                            Toggle::make('copy_sessions')
                                ->label(__('admin.actions.copy_sessions'))
                                ->default(true),
                            Toggle::make('copy_lessons')
                                ->label(__('admin.actions.copy_lessons'))
                                ->default(false),
                            Toggle::make('copy_attachments')
                                ->label(__('admin.actions.copy_attachments'))
                                ->default(false),
                        ])
                        ->columns(2),
                ])
                ->action(function (CourseGroup $record, array $data): void {
                    $newGroup = app(CourseGroupCloneService::class)->clone($record, $data);

                    Notification::make()
                        ->success()
                        ->title(__('admin.notifications.group_copied'))
                        ->body(__('admin.notifications.group_copied_body', ['id' => $newGroup->id]))
                        ->send();
                }),
            RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
            Action::make('import_groups')
                ->label(__('admin.actions.import_groups'))
                ->icon('heroicon-o-arrow-up-tray')
                ->visible(fn (): bool => auth('admin')->user()?->can('course_groups.create') ?? false)
                ->modalHeading(__('admin.modals.import_groups'))
                ->modalDescription(__('admin.descriptions.import_groups'))
                ->schema([
                    Placeholder::make('template')
                        ->label(__('admin.fields.reference_file'))
                        ->content(new HtmlString(
                            '<a href="'.route('admin.course-groups.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                                e(__('admin.actions.download_template')).
                                '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                                e('Use this xlsx template and keep the header names unchanged.').
                                '</p>'
                        ))
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label('Import file')
                        ->disk('local')
                        ->directory('imports/course-groups')
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
                    $summary = app(CourseGroupImportService::class)->import($disk->path($file));
                    $disk->delete($file);

                    $body = implode(PHP_EOL, array_filter([
                        'Processed: '.$summary['processed'],
                        'Created: '.$summary['created'],
                        'Updated: '.$summary['updated'],
                        'Skipped: '.$summary['skipped'],
                        ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 5)) : null,
                    ]));

                    Notification::make()
                        ->title('Group import complete')
                        ->body($body)
                        ->status(empty($summary['errors']) ? 'success' : 'warning')
                        ->send();
                }),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
            RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed())->authorizeIndividualRecords('restore'),
            ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed())->authorizeIndividualRecords('forceDelete'),
        ])
            ->defaultSort('start_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.course_groups.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_groups.description'));
    }
}
