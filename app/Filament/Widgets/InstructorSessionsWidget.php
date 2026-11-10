<?php

namespace App\Filament\Widgets;

use App\Filament\Admin\Resources\CourseGroupSessions\CourseGroupSessionResource;
use App\Models\CourseGroupSession;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class InstructorSessionsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 12,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.instructor_sessions.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('sessions.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = auth('admin')->user();

        return $table
            ->query($user ? static::getScopedQueryForUser($user) : CourseGroupSession::query()->whereRaw('1 = 0'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.labels.title'))
                    ->searchable()
                    ->limit(30),
                TextColumn::make('course_title')
                    ->label(__('admin.labels.course'))
                    ->state(fn (CourseGroupSession $record): string => $record->courseGroup?->course?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::applyCourseTitleSearch($query, $search)),
                TextColumn::make('instructors')
                    ->label(__('admin.labels.trainer'))
                    ->state(function (CourseGroupSession $record): string {
                        $names = $record->courseGroup?->instructors?->pluck('name')
                            ->filter()
                            ->implode(', ');

                        return filled($names) ? $names : '-';
                    })
                    ->searchable(query: fn (Builder $query, string $search): Builder => static::applyInstructorSearch($query, $search))
                    ->wrap(),
                TextColumn::make('start_at')
                    ->label(__('admin.labels.start_date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('join_url')
                    ->label(__('admin.labels.join_url'))
                    ->formatStateUsing(fn (CourseGroupSession $record): string => filled($record->join_url) ? __('admin.actions.join') : '-')
                    ->badge()
                    ->color(fn (CourseGroupSession $record): string => filled($record->join_url) ? 'success' : 'gray')
                    ->icon(fn (CourseGroupSession $record): ?string => filled($record->join_url) ? 'heroicon-o-arrow-top-right-on-square' : null)
                    ->placeholder('-')
                    ->url(fn (CourseGroupSession $record): ?string => filled($record->join_url) ? $record->join_url : null)
                    ->openUrlInNewTab(),
                TextColumn::make('zoom_start_url')
                    ->label(__('admin.labels.zoom_start_url'))
                    ->formatStateUsing(fn (CourseGroupSession $record): string => filled($record->zoom_start_url) ? __('admin.actions.start_meeting') : '-')
                    ->badge()
                    ->color(fn (CourseGroupSession $record): string => filled($record->zoom_start_url) ? 'warning' : 'gray')
                    ->icon(fn (CourseGroupSession $record): ?string => filled($record->zoom_start_url) ? 'heroicon-o-video-camera' : null)
                    ->placeholder('-')
                    ->url(fn (CourseGroupSession $record): ?string => filled($record->zoom_start_url) ? $record->zoom_start_url : null)
                    ->openUrlInNewTab(),
                TextColumn::make('recording_url')
                    ->label(__('admin.labels.recording_url'))
                    ->formatStateUsing(fn (CourseGroupSession $record): string => filled($record->recording_url) ? __('admin.actions.watch_recording') : '-')
                    ->badge()
                    ->color(fn (CourseGroupSession $record): string => filled($record->recording_url) ? 'info' : 'gray')
                    ->icon(fn (CourseGroupSession $record): ?string => filled($record->recording_url) ? 'heroicon-o-play-circle' : null)
                    ->placeholder('-')
                    ->url(fn (CourseGroupSession $record): ?string => filled($record->recording_url) ? $record->recording_url : null)
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (CourseGroupSession $record): string => CourseGroupSessionResource::getUrl('edit', ['record' => $record])),
            ])
            ->headerActions([
                Action::make('view_all')
                    ->label(__('admin.actions.view_all'))
                    ->icon('heroicon-o-list-bullet')
                    ->url(CourseGroupSessionResource::getUrl('index')),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.instructor_sessions.empty'));
    }

    public static function getScopedQueryForUser(User $user): Builder
    {
        $query = CourseGroupSession::query()
            ->with(['courseGroup.course', 'courseGroup.instructors', 'curriculumSection'])
            ->where('start_at', '>=', now()->subDay());

        if ($user->hasRole('instructor')) {
            $query->whereHas('courseGroup.instructors', function (Builder $builder) use ($user): void {
                $builder->whereKey($user->id);
            });
        }

        return $query
            ->latest('start_at')
            ->limit(5);
    }

    public static function applyCourseTitleSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('courseGroup.course', function (Builder $builder) use ($search): void {
            $builder
                ->where('title->en', 'like', "%{$search}%")
                ->orWhere('title->ar', 'like', "%{$search}%");
        });
    }

    public static function applyInstructorSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('courseGroup.instructors', function (Builder $builder) use ($search): void {
            $builder->where('name', 'like', "%{$search}%");
        });
    }
}
