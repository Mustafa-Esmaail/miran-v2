<?php

namespace App\Filament\Admin\Resources\Deals\Tables;

use App\Enums\DealStage;
use App\Models\Deal;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('title')->label(__('admin.labels.title'))->searchable()->limit(50),
                TextColumn::make('company.name')
                    ->label(__('admin.labels.company'))
                    ->searchable()
                    ->toggleable()
                    ->headerFilter(
                        SelectFilter::make('company_id')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                    ),
                TextColumn::make('stage')
                    ->label(__('admin.labels.stage'))
                    ->badge()
                    ->headerFilter(
                        SelectFilter::make('stage')
                            ->options(collect(DealStage::cases())->mapWithKeys(
                                fn (DealStage $stage): array => [$stage->value => __('admin.options.'.$stage->value)]
                            ))
                            ->searchable()
                            ->native(false)
                    )
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '-')
                    ->color(fn ($state) => match ($state?->value) {
                        'closed_won' => 'success',
                        'closed_lost' => 'danger',
                        'negotiation' => 'warning',
                        'proposal' => 'info',
                        'qualification' => 'primary',
                        'prospecting' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('value')
                    ->label(__('admin.labels.deal_value'))
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2).' '.__('admin.labels.sar'))
                    ->sortable(),
                TextColumn::make('probability')
                    ->label(__('admin.labels.probability'))
                    ->formatStateUsing(fn ($state) => $state.'%')
                    ->toggleable(),
                TextColumn::make('expected_close_date')
                    ->label(__('admin.labels.expected_close_date'))
                    ->date('Y-m-d')
                    ->color(fn (Deal $record): string => ! in_array($record->stage?->value, ['closed_won', 'closed_lost']) && $record->expected_close_date?->isPast() ? 'danger' : 'gray')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('employee.name')
                    ->label(__('admin.labels.assigned_to'))
                    ->toggleable()
                    ->headerFilter(
                        SelectFilter::make('employee_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                    ),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d')->sortable()->toggleable(),
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
