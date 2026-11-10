<?php

namespace App\Filament\Admin\Resources\Menus\RelationManagers;

use App\Filament\Admin\Resources\MenuItems\MenuItemResource;
use App\Filament\Forms\TranslatableField;
use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MenuItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'menuItems';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.menu_items');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('admin')->user()?->can('menus.viewAny') ?? false;
    }

    public function canCreate(): bool
    {
        return auth('admin')->user()?->can('menus.create') ?? false;
    }

    public function canEdit(Model $record): bool
    {
        return auth('admin')->user()?->can('menus.update') ?? false;
    }

    public function canDelete(Model $record): bool
    {
        return auth('admin')->user()?->can('menus.delete') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.menu_item'))
                ->schema([
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
                        ->options(function () {
                            return $this->getOwnerRecord()
                                ->items()
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    $label = $item->getTranslation('label', app()->getLocale());

                                    return [$item->id => $label ?: 'Item #'.$item->id];
                                });
                        })
                        ->searchable()
                        ->nullable(),
                    Toggle::make('open_in_new_tab'),
                    TextInput::make('sort_order')->numeric()->default(0),
                    Toggle::make('is_active')->label(__('admin.labels.active'))->default(true),
                ])
                ->columns(3),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('label')
                    ->label(__('admin.fields.label'))
                    ->formatStateUsing(fn ($state, $record) => $record->getTranslation('label', app()->getLocale())),
                TextColumn::make('parent.label')
                    ->label(__('admin.labels.parent'))
                    ->formatStateUsing(fn ($state, $record) => $record->parent?->getTranslation('label', app()->getLocale())),
                TextColumn::make('sort_order')
                    ->label(__('admin.labels.sort_order'))
                    ->formatStateUsing(fn ($state, $record) => $record->sort_order),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'page' => 'primary',
                        'custom' => 'info',
                        default => 'secondary',
                    }),
                IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create'))
                    ->modal(false)
                    ->url(fn () => MenuItemResource::getUrl('create', ['menu_id' => $this->getOwnerRecord()->id])),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => MenuItemResource::getUrl('edit', ['record' => $record])),
                \Filament\Actions\DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->emptyStateHeading(__('admin.empty_states.menu_items.heading'))
            ->emptyStateDescription(__('admin.empty_states.menu_items.description'));
    }
}
