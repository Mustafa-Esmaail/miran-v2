<?php

namespace App\Filament\Admin\Resources\Comments;

use App\Filament\Admin\Resources\Comments\RelationManagers\RepliesRelationManager;
use App\Filament\Admin\Resources\Comments\Schemas\CommentForm;
use App\Filament\Admin\Resources\Comments\Tables\CommentsTable;
use App\Models\Comment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.comments');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.comments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_engagement_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return Comment::applyAdminContextEagerLoading(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListComments::route('/'),
            'view' => Pages\ViewComment::route('/{record}'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RepliesRelationManager::class,
        ];
    }
}
