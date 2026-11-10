<?php

namespace App\Filament\Admin\Resources\Bundles;

use App\Filament\Admin\Resources\Bundles\Schemas\BundleForm;
use App\Filament\Admin\Resources\Bundles\Tables\BundlesTable;
use App\Models\Bundle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.bundles');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.bundle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.bundles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_catalog_group');
    }

    public static function form(Schema $schema): Schema
    {
        return BundleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BundlesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'discounts'])->withCount('courses');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CoursesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBundles::route('/'),
            'create' => Pages\CreateBundle::route('/create'),
            'view' => Pages\ViewBundle::route('/{record}'),
            'edit' => Pages\EditBundle::route('/{record}/edit'),
        ];
    }
}
