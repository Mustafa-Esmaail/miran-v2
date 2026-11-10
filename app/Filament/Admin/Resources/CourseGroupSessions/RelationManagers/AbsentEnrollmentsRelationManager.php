<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AbsentEnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'groupEnrollments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.absent_enrollments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $attendedIds = $this->getOwnerRecord()
                    ->zoomAttendances()
                    ->whereNotNull('enrollment_id')
                    ->pluck('enrollment_id');

                return $query->whereNotIn('id', $attendedIds);
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.labels.student'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('admin.labels.email'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge(),
                TextColumn::make('activated_at')
                    ->label(__('admin.labels.activated_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('activated_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.absent_enrollments.heading'))
            ->emptyStateDescription(__('admin.empty_states.absent_enrollments.description'));
    }
}
