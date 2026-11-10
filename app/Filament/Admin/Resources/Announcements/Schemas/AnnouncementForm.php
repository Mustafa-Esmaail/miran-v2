<?php

namespace App\Filament\Admin\Resources\Announcements\Schemas;

use App\Filament\Forms\TranslatableField;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.announcement'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TranslatableField::rich('body', __('admin.fields.content'), true)->columnSpanFull(),
                    Select::make('status')
                        ->options([
                            'draft' => __('admin.options.draft'),
                            'published' => __('admin.options.published'),
                        ])
                        ->required()
                        ->default('draft'),
                    DateTimePicker::make('starts_at')
                        ->seconds(false)
                        ->label(__('admin.labels.starts_at')),
                    DateTimePicker::make('ends_at')
                        ->seconds(false)
                        ->label(__('admin.labels.ends_at')),
                ])
                ->columns(2),
        ]);
    }
}
