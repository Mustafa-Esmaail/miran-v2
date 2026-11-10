<?php

namespace App\Filament\Admin\Resources\QuestionAnswers\Schemas;

use App\Enums\QuestionAnswerStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionAnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.answer'))
                ->columnSpanFull()
                ->schema([
                    Select::make('question_id')
                        ->relationship('question', 'title')
                        ->label(__('admin.labels.question'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label(__('admin.labels.user'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->options([
                            QuestionAnswerStatus::Pending->value => __('admin.options.pending'),
                            QuestionAnswerStatus::Approved->value => __('admin.options.approved'),
                            QuestionAnswerStatus::Rejected->value => __('admin.options.rejected'),
                        ])
                        ->required(),
                    Textarea::make('content')
                        ->required()
                        ->rows(6)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
