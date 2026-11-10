<?php

namespace App\Filament\Admin\Resources\Countries;

use App\Filament\Admin\Resources\Countries\Schemas\CountryForm;
use App\Filament\Admin\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.countries');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.country');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.countries');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCountries::route('/'),
            'create' => Pages\CreateCountry::route('/create'),
            'view' => Pages\ViewCountry::route('/{record}'),
            'edit' => Pages\EditCountry::route('/{record}/edit'),
        ];
    }
}
