<?php

namespace App\Filament\Admin\Resources\CourseQuestions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.question'))
                ->columnSpanFull()
                ->schema([
                    Select::make('course_id')
                        ->relationship('course', 'title')
                        ->label(__('admin.labels.course'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('title', app()->getLocale())),
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label(__('admin.labels.user'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('title')
                        ->required()
                        ->maxLength(120),
                    Textarea::make('content')
                        ->required()
                        ->rows(6)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
