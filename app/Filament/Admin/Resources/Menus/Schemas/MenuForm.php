<?php

namespace App\Filament\Admin\Resources\Menus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.menu_details'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->required()->placeholder(__('admin.placeholders.main_navigation')),
                    Select::make('location')
                        ->options([
                            'header' => __('admin.options.header'),
                            'footer' => __('admin.options.footer'),
                            'user_dashboard' => __('admin.options.user_dashboard'),
                            'footer_browse' => __('admin.options.footer_browse'),
                            'footer_category' => __('admin.options.footer_category'),
                            'footer_policies' => __('admin.options.footer_policies'),
                            'footer_social' => __('admin.options.footer_social'),
                        ])
                        ->required(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.status'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')->label(__('admin.labels.active'))->default(true),
                ]),
        ]);
    }
}
