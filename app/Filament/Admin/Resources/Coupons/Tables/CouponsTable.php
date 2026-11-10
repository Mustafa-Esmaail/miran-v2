<?php

namespace App\Filament\Admin\Resources\Coupons\Tables;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Coupon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CouponsTable
{
    private static function canManageTrashed(): bool
    {
        return auth('admin')->user()?->hasRole(['admin', 'super-admin']) ?? false;
    }

    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Coupon $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('code')->searchable(),
            TextColumn::make('time_status')
                ->label(__('admin.labels.time_status'))
                ->badge()
                ->state(fn (Coupon $record): string => $record->ends_at?->isPast() ? 'Expired' : 'Active')
                ->color(fn (string $state): string => $state === 'Expired' ? 'danger' : 'success')
                ->toggleable(),
            TextColumn::make('usage_status')
                ->label(__('admin.labels.usage_status'))
                ->badge()
                ->state(function (Coupon $record): string {
                    if (! $record->usage_limit) {
                        return 'Unlimited';
                    }

                    return $record->redemptions_count >= $record->usage_limit ? 'Exceeded' : 'Available';
                })
                ->color(function (string $state): string {
                    return match ($state) {
                        'Exceeded' => 'danger',
                        'Unlimited' => 'info',
                        default => 'success',
                    };
                })
                ->toggleable(),

            TextColumn::make('type')
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof CouponType ? $state->value : (string) $state;

                    return match ($value) {
                        CouponType::Percent->value => 'info',
                        CouponType::Fixed->value => 'success',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('applies_to')
                ->label(__('admin.labels.applies_to'))
                ->badge()
                ->formatStateUsing(fn ($state) => __('admin.options.'.($state instanceof CouponAppliesTo ? $state->value : (string) $state)))
                ->toggleable(),
            TextColumn::make('value')
                ->formatStateUsing(fn ($state, Coupon $record) => $record->type === 'percent' ? $state.'%' : 'SAR '.number_format((float) $state, 2))
                ->sortable(),
            TextColumn::make('min_order_amount')->label(__('admin.labels.min_order'))->sortable()->toggleable(),
            TextColumn::make('min_items_count')->label(__('admin.labels.minimum_items'))->sortable()->toggleable(),
            TextColumn::make('usage_limit')->toggleable(),
            TextColumn::make('per_user_limit')->label(__('admin.labels.per_user'))->toggleable(),
            TextColumn::make('employee.name')->label(__('admin.labels.sales_person'))->searchable()->toggleable(),
            TextColumn::make('redemptions_count')->label(__('admin.labels.redemptions'))->counts('redemptions')->sortable(),
            TextColumn::make('starts_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('ends_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('deletedBy.name')->label(__('admin.labels.deleted_by'))->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
        ])->filters([
            SelectFilter::make('type')->options([
                'percent' => __('admin.options.percent'),
                'fixed' => __('admin.options.fixed'),
            ])->searchable(),
            SelectFilter::make('applies_to')->options([
                CouponAppliesTo::Course->value => __('admin.options.course'),
                CouponAppliesTo::Bundle->value => __('admin.options.bundle'),
                CouponAppliesTo::Both->value => __('admin.options.both'),
            ])->searchable(),
            TrashedFilter::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            \Filament\Actions\RestoreAction::make()->visible(fn (): bool => self::canManageTrashed()),
            \Filament\Actions\ForceDeleteAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
            RestoreBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
            ForceDeleteBulkAction::make()->visible(fn (): bool => self::canManageTrashed()),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.coupons.heading'))
            ->emptyStateDescription(__('admin.empty_states.coupons.description'));
    }
}
