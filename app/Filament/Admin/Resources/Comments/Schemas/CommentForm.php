<?php

namespace App\Filament\Admin\Resources\Comments\Schemas;

use App\Models\Comment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.comment'))
                ->columnSpanFull()
                ->schema([
                    Select::make('commentable_type')
                        ->options([
                            \App\Models\Course::class => __('admin.options.course'),
                            \App\Models\Lesson::class => __('admin.options.lesson'),
                            \App\Models\Page::class => __('admin.options.page'),
                        ])
                        ->required(),
                    Select::make('commentable_id')
                        ->label(__('admin.labels.record'))
                        ->required()
                        ->options(fn () => Comment::query()
                            ->select('commentable_id')
                            ->distinct()
                            ->pluck('commentable_id', 'commentable_id')
                            ->all()),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label(__('admin.labels.user'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Textarea::make('content')
                        ->required()
                        ->rows(6)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
