<?php

namespace App\Filament\Admin\Resources\Permissions\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('module')
                ->label(__('admin.labels.module'))
                ->state(fn (Permission $record) => Str::before($record->name, '.'))
                ->toggleable(),
            TextColumn::make('guard_name')->badge()->sortable(),
            TextColumn::make('roles_count')->label(__('admin.labels.roles'))->counts('roles')->sortable(),
            TextColumn::make('users_count')->label(__('admin.labels.users'))->counts('users')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->filters([
            SelectFilter::make('guard_name')->options([
                'web' => __('admin.options.web'),
                'admin' => __('admin.options.admin'),
            ])->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('name')
            ->emptyStateHeading(__('admin.empty_states.permissions.heading'))
            ->emptyStateDescription(__('admin.empty_states.permissions.description'));
    }
}
