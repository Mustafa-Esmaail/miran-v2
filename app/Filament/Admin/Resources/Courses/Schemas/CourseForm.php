<?php

namespace App\Filament\Admin\Resources\Courses\Schemas;

use App\Enums\CourseLevel;
use App\Enums\CourseTemplate;
use App\Enums\DeliveryType;
use App\Filament\Forms\TranslatableField;
use App\Models\Category;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.content'))
                ->columnSpanFull()
                ->extraAttributes(['class' => 'h-full'])
                ->schema([
                    Select::make('category_id')
                        ->relationship('category', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getTranslation('name', app()->getLocale()))
                        ->label(__('admin.labels.category'))
                        ->required()
                        ->preload()
                        ->searchable(['name->en', 'name->ar', 'slug'])
                        ->helperText(__('admin.helpers.organizes_the_course_in_navigation')),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true, 'slug'),
                    TranslatableField::rich('summary', __('admin.fields.summary'))->columnSpanFull(),
                    TranslatableField::rich('description', __('admin.fields.description'))->columnSpanFull(),
                    Repeater::make('timeline_items')
                        ->label(__('admin.fields.timeline_items'))
                        ->schema([
                            TextInput::make('title_ar')
                                ->label(__('admin.labels.title_ar'))
                                ->required(),
                            TextInput::make('title_en')
                                ->label(__('admin.labels.title_en')),
                            RichEditor::make('description_ar')
                                ->label(__('admin.labels.description_ar')),
                            RichEditor::make('description_en')
                                ->label(__('admin.labels.description_en')),
                            FileUpload::make('image')
                                ->label(__('admin.fields.timeline_image'))
                                ->disk('s3')
                                ->directory('course-timeline')
                                ->visibility('private')
                                ->image(),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsed()
                        ->columnSpanFull(),
                    Repeater::make('timeline_highlights')
                        ->label(__('admin.fields.timeline_highlights'))
                        ->schema([
                            Placeholder::make('icon_guide')
                                ->label('Icons')
                                ->content(new HtmlString(
                                    '<div class="flex items-center gap-4">'.
                                        '<div class="flex items-center gap-2"><img src="'.asset('img/icons/image-1.png').'" style="width:28px;height:28px;object-fit:contain;" alt=""> <span>Item 1</span></div>'.
                                        '<div class="flex items-center gap-2"><img src="'.asset('img/icons/image-2.png').'" style="width:28px;height:28px;object-fit:contain;" alt=""> <span>Item 2</span></div>'.
                                        '<div class="flex items-center gap-2"><img src="'.asset('img/icons/image-3.png').'" style="width:28px;height:28px;object-fit:contain;" alt=""> <span>Item 3</span></div>'.
                                        '</div>'
                                ))
                                ->columnSpanFull(),
                            RichEditor::make('text_ar')
                                ->label(__('admin.fields.timeline_highlight_text_ar'))
                                ->required(),
                            RichEditor::make('text_en')
                                ->label(__('admin.fields.timeline_highlight_text_en')),
                            FileUpload::make('icon')
                                ->label(__('admin.labels.icon'))
                                ->disk('s3')
                                ->directory('course-timeline/highlights')
                                ->visibility('private')
                                ->image(),
                        ])
                        ->columns(2)
                        ->reorderable(false)
                        ->columnSpanFull()
                        ->helperText(__('admin.helpers.timeline_highlights_limit')),
                ])
                ->columns(2),
            CourseBootcampContentForm::section(),
            Section::make(__('admin.sections.publishing'))
                ->extraAttributes(['class' => 'h-full'])
                ->schema([
                    TextInput::make('slug')
                        ->required()
                        ->helperText(__('admin.helpers.used_in_urls_and_internal_links'))
                        ->placeholder(__('admin.placeholders.intro_to_ai')),
                    Select::make('status')
                        ->options([
                            'draft' => __('admin.options.draft'),
                            'published' => __('admin.options.published'),
                            'archived' => __('admin.options.archived'),
                        ])
                        ->required()
                        ->default('draft'),
                    Select::make('template')
                        ->label(__('admin.bootcamp.fields.template'))
                        ->helperText(__('admin.bootcamp.helpers.template'))
                        ->options(CourseTemplate::options())
                        ->default(CourseTemplate::Course->value)
                        ->required()
                        ->live(),
                    Select::make('delivery_type')
                        ->options([
                            DeliveryType::Online->value => __('admin.options.online'),
                            DeliveryType::Offline->value => __('admin.options.offline'),
                            DeliveryType::Hybrid->value => __('admin.options.hybrid'),
                            DeliveryType::RemoteLive->value => __('admin.options.remote_live'),
                        ])
                        ->required(),
                    Select::make('course_level')
                        ->label(__('admin.labels.course_level'))
                        ->options([
                            CourseLevel::Beginner->value => __('web.courses.level_beginner'),
                            CourseLevel::Intermediate->value => __('web.courses.level_intermediate'),
                            CourseLevel::Advanced->value => __('web.courses.level_advanced'),
                            CourseLevel::Expert->value => __('web.courses.level_expert'),
                        ])
                        ->default(CourseLevel::Beginner->value)
                        ->native(),
                    DatePicker::make('start_date')->label(__('admin.labels.start_date')),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(100)
                        ->label(__('admin.labels.sort_order'))
                        ->helperText(__('admin.helpers.lower_numbers_appear_first')),
                    Toggle::make('is_featured')
                        ->label(__('admin.labels.is_featured'))
                        ->helperText(__('admin.helpers.featured_course'))
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.schedule_logistics'))
                ->extraAttributes(['class' => 'h-full'])
                ->schema([
                    TextInput::make('duration_hours')
                        ->numeric()
                        ->label(__('admin.labels.duration_hours'))
                        ->placeholder(__('admin.placeholders.24')),
                    TextInput::make('duration_group')
                        ->label(__('admin.labels.duration_group'))
                        ->placeholder(__('admin.placeholders.4_weeks')),
                    TextInput::make('days_count')
                        ->numeric()
                        ->label(__('admin.labels.days_count'))
                        ->placeholder(__('admin.placeholders.5')),
                    TextInput::make('time_from')->label(__('admin.labels.time_from'))->placeholder(__('admin.placeholders.09_00')),
                    TextInput::make('time_to')->label(__('admin.labels.time_to'))->placeholder(__('admin.placeholders.12_00')),
                    TextInput::make('source_url')->url()->label(__('admin.labels.source_url'))->placeholder(__('admin.placeholders.https')),
                    TextInput::make('sku')->label(__('admin.labels.sku'))->helperText(__('admin.helpers.optional_internal_reference')),
                    TextInput::make('accreditation_number')
                        ->label(__('admin.labels.accreditation_number'))
                        ->placeholder(__('admin.placeholders.accreditation_number')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.pricing'))
                ->extraAttributes(['class' => 'h-full'])
                ->schema([
                    Toggle::make('is_free')
                        ->label(__('admin.labels.is_free'))
                        ->helperText(__('admin.helpers.free_course_checkout'))
                        ->live(),
                    TextInput::make('price')
                        ->numeric()
                        ->label(__('admin.labels.price'))
                        ->prefix('SAR')
                        ->helperText(__('admin.helpers.base_price_before_discounts'))
                        ->disabled(fn ($get) => (bool) $get('is_free'))
                        ->dehydrated(fn ($get) => ! $get('is_free')),
                    Select::make('discounts')
                        ->multiple()
                        ->relationship('discounts', 'type')
                        ->getOptionLabelFromRecordUsing(fn ($record) => sprintf(
                            '%s (%s)',
                            ucfirst($record->type),
                            $record->amount
                        ))
                        ->preload()
                        ->searchable()
                        ->label(__('admin.labels.discounts'))
                        ->placeholder(__('admin.placeholders.select_discounts'))
                        ->disabled(fn ($get) => (bool) $get('is_free')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.media'))
                ->extraAttributes(['class' => 'h-full'])
                ->schema([
                    FileUpload::make('image_path')
                        ->disk('s3')
                        ->directory('course-thumbnails')
                        ->visibility('private')
                        ->image()
                        ->helperText(__('admin.helpers.recommended_size_1200x630')),
                    FileUpload::make('intro_video_path')
                        ->disk('s3')
                        ->directory('course-intros')
                        ->visibility('private')
                        ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg'])
                        ->maxSize(51200)
                        ->label(__('admin.labels.intro_video'))
                        ->helperText(__('admin.helpers.intro_video')),
                    FileUpload::make('sneak_peek_images')
                        ->disk('s3')
                        ->directory('course-sneak-peek')
                        ->visibility('private')
                        ->image()
                        ->multiple()
                        ->reorderable()
                        ->label(__('admin.labels.sneak_peek_images'))
                        ->helperText(__('admin.helpers.sneak_peek_images')),
                ])
                ->columns(2),

        ])->columns(2);
    }
}
