<?php

namespace App\Filament\Admin\Resources\Orders\RelationManagers;

use App\Enums\OrderItemSeatStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderItemSeatsRelationManager extends RelationManager
{
    protected static string $relationship = 'seats';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.order_seats');
    }

    public function canCreate(): bool
    {
        return false;
    }

    public function canEdit(Model $record): bool
    {
        return false;
    }

    public function canDelete(Model $record): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('courseGroup.title')->label(__('admin.labels.course_group'))->toggleable(),
            TextColumn::make('purchaser.name')->label(__('admin.labels.purchaser'))->searchable(),
            TextColumn::make('invitedUser.name')->label(__('admin.labels.invited_user'))->placeholder('—')->toggleable(),
            TextColumn::make('invited_phone')->label(__('admin.labels.invited_phone'))->placeholder('—')->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(fn ($state): string => match ($state instanceof OrderItemSeatStatus ? $state->value : (string) $state) {
                    OrderItemSeatStatus::Enrolled->value => 'success',
                    OrderItemSeatStatus::Invited->value => 'warning',
                    OrderItemSeatStatus::Canceled->value => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading(__('admin.empty_states.order_seats.heading'))
            ->emptyStateDescription(__('admin.empty_states.order_seats.description'));
    }
}
