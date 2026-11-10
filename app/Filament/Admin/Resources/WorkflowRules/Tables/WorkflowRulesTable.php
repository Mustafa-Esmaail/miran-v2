<?php

namespace App\Filament\Admin\Resources\WorkflowRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WorkflowRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.labels.name'))
                    ->searchable(),
                TextColumn::make('trigger_event')
                    ->label(__('admin.labels.trigger_event'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color('primary'),
                TextColumn::make('actions')
                    ->label(__('admin.labels.actions'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' '.(__('admin.labels.actions')) : '0'),
                TextColumn::make('conditions')
                    ->label(__('admin.labels.conditions'))
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' '.(__('admin.labels.conditions')) : __('admin.labels.always'))
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.labels.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('trigger_event')
                    ->label(__('admin.labels.trigger_event'))
                    ->options(collect(\App\Enums\WorkflowTrigger::cases())->mapWithKeys(
                        fn (\App\Enums\WorkflowTrigger $t) => [$t->value => __('admin.options.'.$t->value)]
                    ))->searchable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
