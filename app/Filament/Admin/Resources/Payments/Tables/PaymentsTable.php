<?php

namespace App\Filament\Admin\Resources\Payments\Tables;

use App\Models\Payment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('order.order_no')->label(__('admin.labels.order'))->searchable()->sortable(),
            TextColumn::make('order.user.email')->label(__('admin.labels.customer'))->searchable()->toggleable(),
            TextColumn::make('provider')->searchable(),
            TextColumn::make('provider_reference')->label(__('admin.labels.reference'))->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })->badge()->sortable(),
            TextColumn::make('amount')
                ->formatStateUsing(fn ($state) => number_format((float) $state, 2))
                ->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
            TextColumn::make('deletedBy.name')->label(__('admin.labels.deleted_by'))->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
        ])->filters([
            SelectFilter::make('provider')
                ->label(__('admin.labels.provider'))
                ->options(fn (): array => Payment::query()->distinct()->pluck('provider', 'provider')->all())->searchable(),
            SelectFilter::make('status')
                ->options(fn (): array => Payment::query()
                    ->distinct()
                    ->get(['status'])
                    ->mapWithKeys(fn (Payment $payment): array => [
                        $payment->status->value => $payment->status->value,
                    ])
                    ->all())->searchable(),
            TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
            DeleteAction::make()->label(__('admin.actions.delete')),
            RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
            RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.payments.heading'))
            ->emptyStateDescription(__('admin.empty_states.payments.description'));
    }
}
