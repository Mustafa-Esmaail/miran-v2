<?php

namespace App\Filament\Admin\Resources\Blogs\Schemas;

use App\Enums\BlogStatus;
use App\Filament\Forms\TranslatableField;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        $canSelectAuthor = auth('admin')->user()?->can('users.viewAny') ?? false;

        return $schema->schema([
            Section::make(__('admin.sections.content'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true, 'slug'),
                    TextInput::make('slug')
                        ->unique(ignoreRecord: true)
                        ->helperText(__('admin.helpers.used_in_urls_and_sharing_links'))
                        ->placeholder(__('admin.placeholders.intro_to_ai')),
                    Select::make('category_id')
                        ->relationship('category', 'title')
                        ->label(__('admin.labels.category'))
                        ->required()
                        ->preload()
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->getTranslation('title', app()->getLocale())),
                    Select::make('author_id')
                        ->relationship('author', 'name')
                        ->label(__('admin.labels.author'))
                        ->required()
                        ->preload()
                        ->searchable()
                        ->visible($canSelectAuthor),
                    Hidden::make('author_id')
                        ->default(fn () => auth('admin')->id())
                        ->visible(! $canSelectAuthor),
                    TranslatableField::text('short_description', 'Short description', true),
                    TranslatableField::rich('long_description', 'Long description', true)->columnSpanFull(),
                    FileUpload::make('image_path')
                        ->label(__('admin.labels.cover_image'))
                        ->disk('s3')
                        ->directory('blogs')
                        ->visibility('private')
                        ->image()
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.publishing'))
                ->columnSpanFull()
                ->schema([
                    Select::make('status')
                        ->options([
                            BlogStatus::Draft->value => __('admin.options.draft'),
                            BlogStatus::Published->value => __('admin.options.published'),
                            BlogStatus::Archived->value => __('admin.options.archived'),
                        ])
                        ->required()
                        ->default(BlogStatus::Draft->value),
                    DateTimePicker::make('published_at')
                        ->seconds(false)
                        ->label(__('admin.labels.publish_date'))
                        ->helperText(__('admin.helpers.leave_empty_to_keep_as_draft')),
                ]),
        ]);
    }
}
