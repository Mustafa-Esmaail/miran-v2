<?php

namespace App\Filament\Admin\Resources\BlogCategories;

use App\Filament\Admin\Resources\BlogCategories\Schemas\BlogCategoryForm;
use App\Filament\Admin\Resources\BlogCategories\Tables\BlogCategoriesTable;
use App\Models\BlogCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.blog_categories');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.blog_category');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.blog_categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return BlogCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogCategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('blogs');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogCategories::route('/'),
            'create' => Pages\CreateBlogCategory::route('/create'),
            'view' => Pages\ViewBlogCategory::route('/{record}'),
            'edit' => Pages\EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
