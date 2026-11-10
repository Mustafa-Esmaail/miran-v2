<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone')->label(__('admin.fields.phone'))->toggleable()->searchable(),
            TextColumn::make('nationality')->label(__('admin.fields.nationality'))->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->label(__('admin.fields.status'))
                ->badge()
                ->color(fn (?string $state): string => $state === User::STATUS_ACTIVE ? 'success' : 'danger')
                ->sortable()
                ->toggleable(),
            TextColumn::make('roles.name')->label(__('admin.labels.roles'))->badge()->separator(', '),
            TextColumn::make('locale')->label(__('admin.labels.locale'))->badge()->toggleable(),
            TextColumn::make('orders_count')->label(__('admin.labels.orders'))->counts('orders')->sortable()->toggleable(),
            TextColumn::make('enrollments_count')->label(__('admin.labels.enrollments'))->counts('enrollments')->sortable()->toggleable(),
            TextColumn::make('wishlist_items_count')->label(__('admin.labels.wishlist'))->counts('wishlistItems')->sortable()->toggleable(),
            TextColumn::make('birth_date')->label(__('admin.fields.birth_date'))->date()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->filters([
            SelectFilter::make('locale')->options([
                'en' => __('admin.options.english'),
                'ar' => __('admin.options.arabic'),
            ])->searchable(),
            SelectFilter::make('status')->options([
                User::STATUS_ACTIVE => __('admin.options.active'),
                User::STATUS_SUSPENDED => __('admin.options.suspended'),
                User::STATUS_DELETED => __('admin.options.deleted'),
            ])->searchable(),
            SelectFilter::make('roles')
                ->relationship('roles', 'name')
                ->label(__('admin.labels.role'))
                ->multiple()
                ->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.users.heading'))
            ->emptyStateDescription(__('admin.empty_states.users.description'));
    }
}
