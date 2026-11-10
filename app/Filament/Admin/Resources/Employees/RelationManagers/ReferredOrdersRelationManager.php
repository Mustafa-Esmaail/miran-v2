<?php

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferredOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'referredOrders';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.referred_orders');
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
                TextColumn::make('order_no')->label(__('admin.labels.order_no'))->copyable()->searchable(),
                TextColumn::make('user.name')->label(__('admin.fields.name'))->searchable()->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof OrderStatus ? $state->value : (string) $state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->value : (string) $state),
                TextColumn::make('total_amount')->label(__('admin.labels.total'))->sortable(),
                TextColumn::make('currency')->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(
                    collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $s) => [$s->value => ucfirst($s->value)])
                        ->all()
                )->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.orders.heading'))
            ->emptyStateDescription(__('admin.empty_states.orders.description'));
    }
}
