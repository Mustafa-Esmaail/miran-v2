<?php

namespace App\Filament\Admin\Resources\Students\Tables;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Services\Admin\StudentImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone')->label(__('admin.fields.phone'))->toggleable()->searchable(),
            TextColumn::make('enrollments_count')->label(__('admin.labels.enrollments'))->counts('enrollments')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    User::STATUS_ACTIVE => __('admin.options.active'),
                    User::STATUS_DELETED => __('admin.options.deleted'),
                ])->searchable(),
                Filter::make('course_group')
                    ->label(__('admin.labels.group'))
                    ->schema([
                        Select::make('course_id')
                            ->label(__('admin.labels.course'))
                            ->options(fn (): array => Course::query()
                                ->get()
                                ->mapWithKeys(fn (Course $course): array => [
                                    $course->id => $course->getTranslation('title', app()->getLocale()),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                        Select::make('course_group_id')
                            ->label(__('admin.labels.group'))
                            ->options(fn (Get $get): array => CourseGroup::query()
                                ->when($get('course_id'), fn (Builder $q, int|string $courseId) => $q->where('course_id', $courseId))
                                ->get()
                                ->mapWithKeys(fn (CourseGroup $group): array => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()),
                                ])
                                ->all())
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['course_id'] ?? null,
                                fn (Builder $q, int|string $courseId) => $q->whereHas(
                                    'enrollments',
                                    fn (Builder $eq) => $eq->where('course_id', $courseId)
                                )
                            )
                            ->when(
                                $data['course_group_id'] ?? null,
                                fn (Builder $q, int|string $groupId) => $q->whereHas(
                                    'enrollments',
                                    fn (Builder $eq) => $eq->where('course_group_id', $groupId)
                                )
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['course_id'] ?? null) {
                            $course = Course::find($data['course_id']);

                            if ($course) {
                                $indicators[] = __('admin.labels.course').': '.$course->getTranslation('title', app()->getLocale());
                            }
                        }

                        if ($data['course_group_id'] ?? null) {
                            $group = CourseGroup::find($data['course_group_id']);

                            if ($group) {
                                $indicators[] = __('admin.labels.group').': '.$group->getTranslation('title', app()->getLocale());
                            }
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
                \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
                Action::make('impersonate')
                    ->label(__('admin.actions.login_as_student'))
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('gray')
                    ->visible(fn (User $record): bool => (auth('admin')->user()?->can('students.impersonate') ?? false) && $record->canBeImpersonated())
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.modals.impersonate_student.description'))
                    ->url(fn (User $record): string => route('impersonate', ['id' => $record->id, 'guardName' => 'web']))
                    ->openUrlInNewTab(false),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
                Action::make('import_students')
                    ->label(__('admin.actions.import_students'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (): bool => auth('admin')->user()?->can('users.create') ?? false)
                    ->modalHeading(__('admin.modals.student_import.heading'))
                    ->modalDescription(__('admin.modals.student_import.description'))
                    ->schema([
                        Placeholder::make('template')
                            ->label(__('admin.fields.reference_file'))
                            ->content(new HtmlString(
                                '<a href="'.route('admin.students.import-template').'" class="inline-flex items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white">'.
                                e(__('admin.actions.download_template')).
                                '</a><p style="margin-top:12px;color:#6b7280;font-size:13px;">'.
                                e(__('admin.helpers.student_import_template_help')).
                                '</p>'
                            ))
                            ->columnSpanFull(),
                        FileUpload::make('file')
                            ->label(__('admin.fields.import_file'))
                            ->disk('local')
                            ->directory('imports/students')
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
                        $summary = app(StudentImportService::class)->import($disk->path($file));
                        $disk->delete($file);

                        $body = implode(PHP_EOL, array_filter([
                            __('admin.notifications.students_import_summary', ['processed' => $summary['processed']]),
                            __('admin.notifications.students_import_created', ['count' => $summary['created']]),
                            __('admin.notifications.students_import_updated', ['count' => $summary['updated']]),
                            __('admin.notifications.students_import_enrolled', ['count' => $summary['enrolled']]),
                            __('admin.notifications.students_import_skipped', ['count' => $summary['skipped']]),
                            __('admin.notifications.students_import_mail_sent', ['count' => $summary['mail_sent']]),
                            __('admin.notifications.students_import_mail_failed', ['count' => $summary['mail_failed']]),
                            ! empty($summary['mail_errors']) ? implode(PHP_EOL, array_slice($summary['mail_errors'], 0, 3)) : null,
                            ! empty($summary['errors']) ? implode(PHP_EOL, array_slice($summary['errors'], 0, 3)) : null,
                        ]));

                        Notification::make()
                            ->title(__('admin.notifications.students_import_complete'))
                            ->body($body)
                            ->status(empty($summary['errors']) ? 'success' : 'warning')
                            ->send();
                    }),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.students.heading'))
            ->emptyStateDescription(__('admin.empty_states.students.description'));
    }
}
