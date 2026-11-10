<?php

namespace App\Filament\Admin\Resources\Roles\Tables;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('users_count')->label(__('admin.labels.users'))->counts('users')->sortable(),
            TextColumn::make('permissions_count')->label(__('admin.models.permissions'))->counts('permissions')->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
            EditAction::make()->label(__('admin.actions.edit')),
            DeleteAction::make()
                ->label(__('admin.actions.delete'))
                ->visible(fn (Role $record): bool => ! in_array($record->name, RoleResource::PROTECTED_ROLES, true)),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('name')
            ->emptyStateHeading(__('admin.empty_states.roles.heading'))
            ->emptyStateDescription(__('admin.empty_states.roles.description'));
    }
}
