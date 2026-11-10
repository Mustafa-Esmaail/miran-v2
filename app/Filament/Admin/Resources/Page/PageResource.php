<?php

namespace App\Filament\Admin\Resources\Page;

use App\Filament\Admin\Resources\Page\RelationManagers\SectionsRelationManager;
use App\Filament\Admin\Resources\Page\Schemas\PageForm;
use App\Filament\Admin\Resources\Page\Tables\PagesTable;
use App\Models\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.pages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.page');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.pages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('sections');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record}'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
        ];
    }
}
