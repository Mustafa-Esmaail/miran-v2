<?php

namespace App\Filament\Admin\Resources\Menus;

use App\Filament\Admin\Resources\Menus\RelationManagers\MenuItemsRelationManager;
use App\Filament\Admin\Resources\Menus\Schemas\MenuForm;
use App\Filament\Admin\Resources\Menus\Tables\MenusTable;
use App\Models\Menu;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.menus');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.menu');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.menus');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('menus.viewAny') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth('admin')->user()?->can('menus.create') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount([
            'items',
            'items as active_items_count' => fn (Builder $query) => $query->where('is_active', true),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            MenuItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'view' => Pages\ViewMenu::route('/{record}'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
