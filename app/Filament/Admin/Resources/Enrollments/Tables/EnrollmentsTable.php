<?php

namespace App\Filament\Admin\Resources\Enrollments\Tables;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Admin\CourseAccessService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentsTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return [
            EnrollmentStatus::Pending->value => __('admin.options.pending'),
            EnrollmentStatus::Active->value => __('admin.options.active'),
            EnrollmentStatus::Cancelled->value => __('admin.options.cancelled'),
            EnrollmentStatus::PendingCertificate->value => __('admin.options.pending_certificate'),
        ];
    }

    /**
     * Mirrors the single-row "deactivate" action: cancelling clears activated_at, activating stamps it.
     */
    private static function applyStatus(Enrollment $enrollment, EnrollmentStatus $status): void
    {
        $attributes = ['status' => $status];

        if ($status === EnrollmentStatus::Active && $enrollment->activated_at === null) {
            $attributes['activated_at'] = now();
        }

        if ($status === EnrollmentStatus::Cancelled) {
            $attributes['activated_at'] = null;
        }

        $enrollment->update($attributes);
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('user.name')->label(__('admin.labels.trainee'))->searchable(),
            TextColumn::make('user.email')->label(__('admin.labels.email'))->searchable()->toggleable(),
            TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course_title'))
                ->state(fn (Enrollment $record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('courseGroup.title')
                ->label(__('admin.labels.group'))
                ->formatStateUsing(fn ($state, Enrollment $record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-'),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof EnrollmentStatus ? $state->value : (string) $state;

                    return match ($value) {
                        EnrollmentStatus::Active->value => 'success',
                        EnrollmentStatus::Pending->value => 'warning',
                        EnrollmentStatus::Cancelled->value => 'danger',
                        EnrollmentStatus::PendingCertificate->value => 'info',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('activated_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('order_item_id')->label(__('admin.labels.order_item'))->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('user_id')
                ->relationship('user', 'email')
                ->getOptionLabelFromRecordUsing(fn (User $record): string => trim($record->name.' - '.$record->email, ' -'))
                ->label(__('admin.labels.trainee'))
                ->searchable(['name', 'email']),
            Filter::make('course_group')
                ->schema([
                    Select::make('course_id')
                        ->label(__('admin.labels.course'))
                        ->options(fn (): array => app(CourseAccessService::class)->scopeCourses(Course::query(), Filament::auth()->user(), 'courses.viewAny')
                            ->get()
                            ->mapWithKeys(fn (Course $course): array => [$course->id => $course->getTranslation('title', app()->getLocale())])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(function (Get $get): array {
                            $courseId = $get('course_id');

                            if (! $courseId) {
                                return [];
                            }

                            $query = CourseGroup::query()->where('course_id', $courseId);
                            $query = app(CourseAccessService::class)->scopeCourseRelation($query, Filament::auth()->user(), 'course', 'course_groups.viewAny');

                            return $query->get()
                                ->mapWithKeys(fn (CourseGroup $group): array => [$group->id => $group->getTranslation('title', app()->getLocale())])
                                ->all();
                        })
                        ->searchable()
                        ->preload(),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['course_id'] ?? null, fn (Builder $q, $courseId) => $q->where('course_id', $courseId))
                        ->when($data['course_group_id'] ?? null, fn (Builder $q, $groupId) => $q->where('course_group_id', $groupId));
                }),
            SelectFilter::make('status')->options(self::statusOptions())->searchable(),
            TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->actions([
            Action::make('deactivate')
                ->label(__('admin.actions.deactivate'))
                ->color('danger')
                ->visible(fn (Enrollment $record): bool => $record->status === EnrollmentStatus::Active)
                ->requiresConfirmation()
                ->action(function (Enrollment $record): void {
                    $record->update([
                        'status' => EnrollmentStatus::Cancelled,
                        'activated_at' => null,
                    ]);
                }),
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            DeleteAction::make()->label(__('admin.actions.delete')),
            RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            BulkAction::make('change_status')
                ->label(__('admin.actions.update_status'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn (): bool => auth('admin')->user()?->can('enrollments.update') ?? false)
                ->schema([
                    Select::make('status')
                        ->label(__('admin.labels.status'))
                        ->options(self::statusOptions())
                        ->required(),
                ])
                ->action(function (Collection $records, array $data): void {
                    $status = EnrollmentStatus::from($data['status']);
                    $admin = auth('admin')->user();

                    $updated = $records
                        ->filter(fn (Enrollment $record): bool => ! $record->trashed() && $admin->can('update', $record))
                        ->each(fn (Enrollment $record) => self::applyStatus($record, $status))
                        ->count();

                    Notification::make()
                        ->success()
                        ->title(__('admin.notifications.enrollments_status_updated', ['count' => $updated]))
                        ->send();
                })
                ->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
            RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed())->authorizeIndividualRecords('forceDelete'),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.enrollments.heading'))
            ->emptyStateDescription(__('admin.empty_states.enrollments.description'));
    }
}
