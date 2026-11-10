<?php

namespace App\Filament\Admin\Resources\BlogComments;

use App\Filament\Admin\Resources\BlogComments\Schemas\BlogCommentForm;
use App\Filament\Admin\Resources\BlogComments\Tables\BlogCommentsTable;
use App\Models\BlogComment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlogCommentResource extends Resource
{
    protected static ?string $model = BlogComment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.blog_comments');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.blog_comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.blog_comments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return BlogCommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogCommentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['blog', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogComments::route('/'),
            'view' => Pages\ViewBlogComment::route('/{record}'),
        ];
    }
}
