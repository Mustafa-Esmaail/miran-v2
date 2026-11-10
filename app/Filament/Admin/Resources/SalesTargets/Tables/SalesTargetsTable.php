<?php

namespace App\Filament\Admin\Resources\SalesTargets\Tables;

use App\Models\SalesTarget;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('employee.name')
                    ->label(__('admin.labels.sales_person'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.team.name')
                    ->label(__('admin.labels.team'))
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('period_type')
                    ->label(__('admin.labels.period_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => $state?->value === 'monthly' ? 'info' : 'warning')
                    ->sortable(),
                TextColumn::make('period')
                    ->label(__('admin.labels.period'))
                    ->sortable(),
                TextColumn::make('target_revenue')
                    ->label(__('admin.labels.target_revenue'))
                    ->formatStateUsing(fn (int $state): string => 'SAR '.number_format($state))
                    ->sortable(),
                TextColumn::make('actual_revenue')
                    ->label(__('admin.labels.actual_revenue'))
                    ->state(fn (SalesTarget $record): string => 'SAR '.number_format($record->getActualRevenue())),
                TextColumn::make('revenue_pct')
                    ->label(__('admin.labels.revenue_pct'))
                    ->state(fn (SalesTarget $record): string => $record->getRevenueHitPercent().'%')
                    ->badge()
                    ->color(fn (SalesTarget $record): string => match (true) {
                        $record->getRevenueHitPercent() >= 100 => 'success',
                        $record->getRevenueHitPercent() >= 70 => 'info',
                        $record->getRevenueHitPercent() >= 40 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('target_leads')
                    ->label(__('admin.labels.target_leads'))
                    ->sortable(),
                TextColumn::make('actual_leads')
                    ->label(__('admin.labels.actual_leads'))
                    ->state(fn (SalesTarget $record): int => $record->getActualLeads()),
                TextColumn::make('leads_pct')
                    ->label(__('admin.labels.leads_pct'))
                    ->state(fn (SalesTarget $record): string => $record->getLeadsHitPercent().'%')
                    ->badge()
                    ->color(fn (SalesTarget $record): string => match (true) {
                        $record->getLeadsHitPercent() >= 100 => 'success',
                        $record->getLeadsHitPercent() >= 70 => 'info',
                        $record->getLeadsHitPercent() >= 40 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('period_type')
                    ->label(__('admin.labels.period_type'))
                    ->options([
                        'monthly' => __('admin.options.monthly'),
                        'quarterly' => __('admin.options.quarterly'),
                    ])->searchable(),
                SelectFilter::make('period')
                    ->label(__('admin.labels.period'))
                    ->options(fn () => \App\Models\SalesTarget::query()
                        ->distinct()
                        ->orderByDesc('period')
                        ->pluck('period', 'period'))->searchable(),
                SelectFilter::make('employee_id')
                    ->label(__('admin.labels.sales_person'))
                    ->relationship('employee', 'name')
                    ->searchable(),
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
            ->defaultSort('period', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['employee.team']));
    }
}
