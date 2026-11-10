<?php

namespace App\Filament\Admin\Resources\Blogs;

use App\Filament\Admin\Resources\Blogs\RelationManagers\CommentsRelationManager;
use App\Filament\Admin\Resources\Blogs\Schemas\BlogForm;
use App\Filament\Admin\Resources\Blogs\Tables\BlogsTable;
use App\Models\Blog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.blogs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.blog_post');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.blog_posts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return BlogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['author', 'category']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogs::route('/'),
            'create' => Pages\CreateBlog::route('/create'),
            'view' => Pages\ViewBlog::route('/{record}'),
            'edit' => Pages\EditBlog::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }
}
