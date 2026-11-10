<?php

namespace App\Filament\Admin\Resources\Trainers\Tables;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TrainersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone')->label(__('admin.fields.phone'))->toggleable()->searchable(),
            TextColumn::make('teaching_courses_count')->label(__('admin.labels.courses'))->counts('teachingCourses')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])
            ->filters([
                SelectFilter::make('status')->options([
                    User::STATUS_ACTIVE => __('admin.options.active'),
                    User::STATUS_DELETED => __('admin.options.deleted'),
                ])->searchable(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
                \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.trainers.heading'))
            ->emptyStateDescription(__('admin.empty_states.trainers.description'));
    }
}
