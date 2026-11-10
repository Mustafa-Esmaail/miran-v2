<?php

namespace App\Filament\Admin\Resources\Teams;

use App\Filament\Admin\Resources\Teams\Pages\CreateTeam;
use App\Filament\Admin\Resources\Teams\Pages\EditTeam;
use App\Filament\Admin\Resources\Teams\Pages\ListTeams;
use App\Filament\Admin\Resources\Teams\Pages\ViewTeam;
use App\Filament\Admin\Resources\Teams\Schemas\TeamForm;
use App\Filament\Admin\Resources\Teams\Tables\TeamsTable;
use App\Models\Team;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.teams');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.team');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.teams');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_team_group');
    }

    public static function form(Schema $schema): Schema
    {
        return TeamForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'create' => CreateTeam::route('/create'),
            'view' => ViewTeam::route('/{record}'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('department');
    }
}
