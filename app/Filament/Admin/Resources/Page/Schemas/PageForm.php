<?php

namespace App\Filament\Admin\Resources\Page\Schemas;

use App\Filament\Forms\TranslatableField;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Page;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.page_details'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true, 'slug'),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText(__('admin.helpers.used_in_urls_and_navigation'))
                        ->placeholder(__('admin.placeholders.about_us')),
                    Select::make('template')
                        ->options([
                            'default' => __('admin.options.default'),
                            'landing-page' => __('admin.options.landing_page'),
                            'landing-v2' => __('admin.options.landing_v2'),
                            'landing-excel' => __('admin.options.landing_excel'),
                            'landing-kpi' => __('admin.options.landing_kpi'),
                            'landing-powerbi' => __('admin.options.landing_powerbi'),
                            'about' => __('admin.options.about'),
                            'course-profile' => __('admin.options.course_profile'),
                        ])
                        ->placeholder(__('admin.placeholders.default_template'))
                        ->nullable()
                        ->live(),
                    Select::make('landable_type')
                        ->label(__('admin.labels.landing_page_for'))
                        ->options([
                            Course::class => __('admin.options.course'),
                            Bundle::class => __('admin.options.bundle'),
                        ])
                        ->live()
                        ->nullable()
                        ->visible(fn (Get $get) => in_array($get('template'), Page::LANDABLE_TEMPLATES, true)),
                    Select::make('landable_id')
                        ->label(__('admin.labels.select_course_or_bundle'))
                        ->options(fn (Get $get) => match ($get('landable_type')) {
                            Course::class => Course::query()
                                ->published()
                                ->get()
                                ->mapWithKeys(fn (Course $c) => [$c->id => $c->getTranslation('title', app()->getLocale())]),
                            Bundle::class => Bundle::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn (Bundle $b) => [$b->id => $b->getTranslation('title', app()->getLocale())]),
                            default => [],
                        })
                        ->searchable()
                        ->nullable()
                        ->visible(fn (Get $get) => in_array($get('template'), Page::LANDABLE_TEMPLATES, true) && filled($get('landable_type'))),
                    TranslatableField::text('cta_label', __('admin.labels.cta_button_label'))
                        ->visible(fn (Get $get) => $get('template') === 'course-profile'),
                ])
                ->columns(2),
            Section::make(__('admin.sections.seo'))
                ->description(__('admin.helpers.seo_fallback_hint'))
                ->columnSpanFull()
                ->collapsed()
                ->schema([
                    TranslatableField::text('meta_title', __('admin.fields.meta_title')),
                    TranslatableField::textarea('meta_description', __('admin.fields.meta_description')),
                    FileUpload::make('meta_image')
                        ->label(__('admin.labels.meta_image'))
                        ->disk('s3')
                        ->directory('pages/seo')
                        ->visibility('private')
                        ->image(),
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
