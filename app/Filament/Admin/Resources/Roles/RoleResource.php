<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Roles;

use App\Filament\Admin\Resources\Roles\Schemas\RoleForm;
use App\Filament\Admin\Resources\Roles\Tables\RolesTable;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    /**
     * Role names that cannot be renamed or deleted through the admin UI.
     *
     * @var array<int, string>
     */
    public const PROTECTED_ROLES = ['super-admin'];

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.roles');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.roles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.users_access');
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('guard_name', 'admin')
            ->withCount(['users', 'permissions']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'view' => Pages\ViewRole::route('/{record}'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
