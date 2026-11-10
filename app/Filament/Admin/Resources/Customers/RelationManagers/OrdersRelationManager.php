<?php

namespace App\Filament\Admin\Resources\Customers\RelationManagers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.user_orders');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('order_no')->label(__('admin.labels.order_no'))->searchable()->copyable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;
                        $key = 'admin.options.'.$value;

                        return __($key) !== $key ? __($key) : (string) $value;
                    })
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state->value : (string) $state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'refunded' => 'gray',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('admin.labels.total'))
                    ->formatStateUsing(fn ($state, Order $record): string => number_format((float) $state, 2).' '.($record->currency ?? 'SAR'))
                    ->sortable(),
                TextColumn::make('coupon.code')->label(__('admin.labels.coupon'))->toggleable()->placeholder('—'),
                TextColumn::make('items_count')
                    ->label(__('admin.labels.items'))
                    ->counts('items')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => __('admin.options.pending'),
                    'paid' => __('admin.options.paid'),
                    'rejected' => __('admin.options.rejected'),
                    'refunded' => __('admin.options.refunded'),
                    'cancelled' => __('admin.options.cancelled'),
                ])->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.orders.heading'))
            ->emptyStateDescription(__('admin.empty_states.orders.description'));
    }
}
