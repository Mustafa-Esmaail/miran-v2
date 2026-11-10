<?php

namespace App\Filament\Widgets;

use App\Enums\CourseStatus;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Models\Course;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LmsTopCoursesWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.lms_top_courses_heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Course::query()
                    ->withCount(['enrollments', 'groups'])
                    ->with('category')
                    ->orderByDesc('enrollments_count')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.labels.course'))
                    ->limit(45)
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label(__('admin.labels.category'))
                    ->placeholder('—'),
                TextColumn::make('groups_count')
                    ->label(__('admin.labels.groups'))
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label(__('admin.dashboard_widgets.lms_stats.enrollments_label'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->badge()
                    ->color(fn (CourseStatus $state): string => match ($state) {
                        CourseStatus::Published => 'success',
                        CourseStatus::Draft => 'warning',
                        CourseStatus::Archived => 'gray',
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Course $record): string => CourseResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.lms_top_courses_empty'));
    }
}
