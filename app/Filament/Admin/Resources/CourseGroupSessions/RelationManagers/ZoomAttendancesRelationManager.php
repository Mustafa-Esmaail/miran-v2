<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZoomAttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'zoomAttendances';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.zoom_attendances');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('participant_name')
                    ->label(__('admin.labels.participant_name'))
                    ->searchable(),
                TextColumn::make('participant_email')
                    ->label(__('admin.labels.participant_email'))
                    ->searchable(),
                TextColumn::make('joined_at')
                    ->label(__('admin.labels.joined_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('left_at')
                    ->label(__('admin.labels.left_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label(__('admin.labels.duration'))
                    ->formatStateUsing(fn (int $state): string => ($state / 60).' min')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('admin.labels.student'))
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('joined_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.zoom_attendances.heading'))
            ->emptyStateDescription(__('admin.empty_states.zoom_attendances.description'));
    }
}
