<?php

namespace App\Filament\Admin\Resources\MenuItems\Schemas;

use App\Filament\Forms\TranslatableField;
use App\Models\MenuItem;
use App\Models\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.menu_item'))
                ->columnSpanFull()
                ->schema([
                    Select::make('menu_id')
                        ->label(__('admin.labels.menu'))
                        ->relationship('menu', 'name')
                        ->default(fn () => request()->integer('menu_id') ?: null)
                        ->disabled(fn () => request()->integer('menu_id'))
                        ->required(),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('label', __('admin.fields.label'), true),
                    Select::make('type')
                        ->options([
                            'page' => __('admin.options.page'),
                            'custom' => __('admin.options.custom'),
                            'social' => __('admin.options.social'),
                        ])
                        ->required()
                        ->live(),
                    Select::make('page_id')
                        ->relationship('page', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Page $record): string => $record->getTranslation('title', app()->getLocale()))
                        ->searchable(['title->en', 'title->ar', 'slug'])
                        ->preload()
                        ->visible(fn (Get $get) => $get('type') === 'page')
                        ->required(fn (Get $get) => $get('type') === 'page'),
                    TextInput::make('url')
                        ->label(__('admin.fields.url'))
                        ->url()
                        ->placeholder(__('admin.placeholders.https'))
                        ->visible(fn (Get $get) => in_array($get('type'), ['custom', 'social']))
                        ->required(fn (Get $get) => in_array($get('type'), ['custom', 'social'])),
                    Select::make('icon')
                        ->label(__('admin.fields.icon'))
                        ->options([
                            'linkedin' => 'LinkedIn',
                            'x' => 'X (Twitter)',
                            'instagram' => 'Instagram',
                            'tiktok' => 'TikTok',
                            'facebook' => 'Facebook',
                            'youtube' => 'YouTube',
                            'whatsapp' => 'WhatsApp',
                            'snapchat' => 'Snapchat',
                        ])
                        ->visible(fn (Get $get) => $get('type') === 'social')
                        ->required(fn (Get $get) => $get('type') === 'social'),
                    Select::make('parent_id')
                        ->label(__('admin.labels.parent'))
                        ->options(function (Get $get) {
                            $menuId = $get('menu_id');
                            if (! $menuId) {
                                return [];
                            }

                            return MenuItem::query()
                                ->where('menu_id', $menuId)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(function (MenuItem $item) {
                                    $label = $item->getTranslation('label', app()->getLocale());

                                    return [$item->id => $label ?: 'Item #'.$item->id];
                                });
                        })
                        ->searchable()
                        ->nullable(),
                    Toggle::make('open_in_new_tab')->label(__('admin.labels.open_in_new_tab')),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_active')->label(__('admin.labels.active'))->default(true),
                ])
                ->columns(3),
        ]);
    }
}
