<?php

namespace App\Filament\Widgets;

use App\Filament\Admin\Resources\Quizzes\QuizResource;
use App\Models\Quiz;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LmsQuizPerformanceWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = '60s';

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.lms_quiz_performance_heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Quiz::query()
                    ->withCount('attempts')
                    ->with('course')
                    ->whereHas('attempts')
                    ->orderByDesc('attempts_count')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.labels.quiz'))
                    ->limit(40),
                TextColumn::make('course.title')
                    ->label(__('admin.labels.course'))
                    ->limit(35)
                    ->placeholder('—'),
                TextColumn::make('passing_score')
                    ->label(__('admin.dashboard_widgets.lms_stats.passing_score'))
                    ->formatStateUsing(fn (int $state): string => $state.'%'),
                TextColumn::make('attempts_count')
                    ->label(__('admin.dashboard_widgets.lms_stats.total_attempts'))
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Quiz $record): string => QuizResource::getUrl('edit', ['record' => $record])),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.lms_quiz_performance_empty'));
    }
}
