<?php

namespace App\Filament\Admin\Resources\Customers\Tables;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.labels.id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('admin.fields.phone'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('national_id')
                    ->label(__('admin.fields.national_id'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('orders_count')
                    ->label(__('admin.labels.orders'))
                    ->counts('orders')
                    ->sortable(),
                TextColumn::make('enrollments_count')
                    ->label(__('admin.labels.enrollments'))
                    ->counts('enrollments')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    User::STATUS_ACTIVE => __('admin.options.active'),
                    User::STATUS_DELETED => __('admin.options.deleted'),
                ])->searchable(),
                Filter::make('phone')
                    ->form([TextInput::make('phone')->label(__('admin.fields.phone'))])
                    ->query(fn (Builder $q, array $data) => $q->when($data['phone'] ?? null, fn ($q, $v) => $q->where('phone', 'like', "%{$v}%"))),
                Filter::make('national_id')
                    ->label(__('admin.fields.national_id'))
                    ->form([TextInput::make('national_id')->label(__('admin.fields.national_id'))])
                    ->query(fn (Builder $q, array $data) => $q->when($data['national_id'] ?? null, fn ($q, $v) => $q->where('national_id', 'like', "%{$v}%"))),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
                \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
                DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.customers.heading'))
            ->emptyStateDescription(__('admin.empty_states.customers.description'));
    }
}
