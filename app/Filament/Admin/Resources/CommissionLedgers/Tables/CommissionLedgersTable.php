<?php

namespace App\Filament\Admin\Resources\CommissionLedgers\Tables;

use App\Enums\CommissionLedgerStatus;
use App\Models\CommissionLedger;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommissionLedgersTable
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
                TextColumn::make('period')
                    ->label(__('admin.labels.period'))
                    ->sortable(),
                TextColumn::make('gross_amount')
                    ->label(__('admin.labels.gross_amount'))
                    ->formatStateUsing(fn (int $state): string => 'SAR '.number_format($state))
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label(__('admin.labels.commission_rate'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->label(__('admin.labels.commission_amount'))
                    ->formatStateUsing(fn (int $state): string => 'SAR '.number_format($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'paid' => 'success',
                        'approved' => 'info',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('orderAttribution.order.order_no')
                    ->label(__('admin.labels.order'))
                    ->toggleable(),
                TextColumn::make('approved_at')->label(__('admin.labels.approved_at'))->dateTime('Y-m-d')->toggleable()->sortable(),
                TextColumn::make('paid_at')->label(__('admin.labels.paid_at'))->dateTime('Y-m-d')->toggleable()->sortable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.labels.status'))
                    ->options([
                        'pending' => __('admin.options.pending'),
                        'approved' => __('admin.options.approved'),
                        'paid' => __('admin.options.paid'),
                    ])->searchable(),
                SelectFilter::make('period')
                    ->label(__('admin.labels.period'))
                    ->options(fn () => CommissionLedger::query()
                        ->distinct()
                        ->orderByDesc('period')
                        ->pluck('period', 'period'))->searchable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                Action::make('approve')
                    ->label(__('admin.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn (CommissionLedger $record): bool => $record->status === CommissionLedgerStatus::Pending
                        && (bool) auth('admin')->user()?->can('commission_ledgers.approve')
                    )
                    ->action(fn (CommissionLedger $record) => $record->update([
                        'status' => CommissionLedgerStatus::Approved,
                        'approved_at' => now(),
                    ])),
                Action::make('mark_paid')
                    ->label(__('admin.actions.mark_paid'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (CommissionLedger $record): bool => $record->status === CommissionLedgerStatus::Approved
                        && (bool) auth('admin')->user()?->can('commission_ledgers.mark_paid')
                    )
                    ->action(fn (CommissionLedger $record) => $record->update([
                        'status' => CommissionLedgerStatus::Paid,
                        'paid_at' => now(),
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
