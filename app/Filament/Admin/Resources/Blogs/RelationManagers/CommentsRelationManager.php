<?php

namespace App\Filament\Admin\Resources\Blogs\RelationManagers;

use App\Enums\BlogCommentStatus;
use App\Models\BlogComment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.blog_comments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('content')
                ->label(__('admin.labels.comment'))
                ->state(fn (BlogComment $record) => $record->getTranslation('content', app()->getLocale()))
                ->limit(60)
                ->wrap(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof BlogCommentStatus ? $state->value : (string) $state;

                    return match ($value) {
                        BlogCommentStatus::Approved->value => 'success',
                        BlogCommentStatus::Pending->value => 'warning',
                        BlogCommentStatus::Rejected->value => 'danger',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])
            ->emptyStateHeading(__('admin.empty_states.blog_comments.heading'))
            ->emptyStateDescription(__('admin.empty_states.blog_comments.description'));
    }
}
