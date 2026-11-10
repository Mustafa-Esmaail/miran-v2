<?php

namespace App\Filament\Admin\Resources\Sections\Schemas;

use App\Models\Page;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            FormSection::make(__('admin.sections.section'))
                ->columnSpanFull()
                ->schema([
                    Select::make('page_id')
                        ->label(__('admin.labels.page'))
                        ->relationship('page', 'title')
                        ->default(fn () => request()->integer('page_id') ?: null)
                        ->disabled(fn () => request()->integer('page_id'))
                        ->getOptionLabelFromRecordUsing(
                            fn (Page $record) => $record->getTranslation('title', app()->getLocale())
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('type')
                        ->options([
                            'hero' => __('admin.options.hero'),
                            'about' => __('admin.options.about'),
                            'how_it_works' => __('admin.options.how_it_works'),
                            'trainer_quotes' => __('admin.options.trainer_quotes'),
                            'testimonials_strip' => __('admin.options.testimonials_strip'),
                            'cta_banner' => __('admin.options.cta_banner'),
                            'cta_counter' => __('admin.options.cta_counter'),
                            'categories' => __('admin.options.categories'),
                            'featured_courses' => __('admin.options.featured_courses'),
                            'partners' => __('admin.options.partners'),
                            'values' => __('admin.options.values'),
                            'story' => __('admin.options.story'),
                            'contact_hero' => __('admin.options.contact_hero'),
                            'why' => __('admin.options.why_choose_us'),
                            'corporate' => __('admin.options.corporate_cta'),
                            'testimonials' => __('admin.options.testimonials'),
                            'stats' => __('admin.options.stats'),
                            'lead_form' => __('admin.options.lead_form'),
                            'complaints' => __('admin.options.complaints'),
                            'features' => __('admin.options.features'),
                            'faq' => __('admin.options.faq'),
                            'cta' => __('admin.options.cta'),
                            'special_bundles' => __('admin.options.special_bundles'),
                            'action_cards' => __('admin.options.action_cards'),
                            'policy_text' => __('admin.options.policy_text'),
                            'video' => __('admin.options.video'),
                            'content' => __('admin.options.content'),
                            'image_text' => __('admin.options.image_text'),
                            'map' => __('admin.options.map'),
                            'iframe' => __('admin.options.iframe'),
                            'service_request_form' => __('admin.options.service_request_form'),
                            'licenses_profile' => __('admin.options.licenses_profile'),
                            'landing_hero' => __('admin.options.landing_hero'),
                            'landing_lead_form' => __('admin.options.landing_lead_form'),
                            'landing_v2_brands' => __('admin.options.landing_v2_brands'),
                            'landing_v2_hero' => __('admin.options.landing_v2_hero'),
                            'landing_v2_clients' => __('admin.options.landing_v2_clients'),
                            'landing_v2_benefits' => __('admin.options.landing_v2_benefits'),
                            'landing_v3_hero' => __('admin.options.landing_v3_hero'),
                            'landing_course_hero' => __('admin.options.landing_course_hero'),
                            'landing_course_benefits' => __('admin.options.landing_course_benefits'),
                            'course_profile_tab' => __('admin.options.course_profile_tab'),
                        ])
                        ->required()
                        ->live()
                        ->helperText(__('admin.helpers.controls_which_fields_appear_below')),
                    TextInput::make('title')->placeholder(__('admin.placeholders.section_title')),
                    TextInput::make('subtitle')->placeholder(__('admin.placeholders.optional_subtitle')),
                    self::typeFields(),
                ])
                ->columns(3),
            FormSection::make(__('admin.sections.settings'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')->label(__('admin.labels.active'))->default(true),
                    TextInput::make('sort_order')->numeric()->default(0)->helperText(__('admin.helpers.lower_numbers_appear_first')),
                ])
                ->columns(2),
        ]);
    }

    protected static function typeFields()
    {
        return Group::make()
            ->schema(function (Get $get) {
                return match ($get('type')) {
                    'hero' => self::heroFields(),
                    'values' => self::valuesFields(),
                    'story' => self::storyFields(),
                    'contact_hero' => self::contactHeroFields(),
                    'lead_form' => self::leadFormFields(),
                    'features' => self::featuresFields(),
                    'testimonials' => self::testimonialsFields(),
                    'faq' => self::faqFields(),
                    'cta' => self::ctaFields(),
                    'featured_courses' => self::featuredCoursesFields(),
                    'special_bundles' => self::specialBundlesFields(),
                    'about' => self::aboutFields(),
                    'how_it_works' => self::howItWorksFields(),
                    'trainer_quotes' => self::trainerQuotesFields(),
                    'testimonials_strip' => self::testimonialsStripFields(),
                    'cta_banner' => self::ctaBannerFields(),
                    'cta_counter' => self::ctaCounterFields(),
                    'categories' => self::categoriesFields(),
                    'partners' => self::partnersFields(),
                    'why' => self::whyFields(),
                    'corporate' => self::corporateFields(),
                    'stats' => self::statsFields(),
                    'complaints' => self::complaintsFields(),
                    'action_cards' => self::actionCardsFields(),
                    'policy_text' => self::policyTextFields(),
                    'video' => self::videoFields(),
                    'map' => self::mapFields(),
                    'iframe' => self::iframeFields(),
                    'service_request_form' => self::serviceRequestFormFields(),
                    'licenses_profile' => self::licensesProfileFields(),
                    'landing_hero' => self::landingHeroFields(),
                    'landing_lead_form' => self::landingLeadFormFields(),
                    'landing_v2_brands' => self::landingV2BrandsFields(),
                    'landing_v2_hero' => self::landingV2HeroFields(),
                    'landing_v2_clients' => self::landingV2ClientsFields(),
                    'landing_v2_benefits' => self::landingV2BenefitsFields(),
                    'landing_v3_hero' => self::landingV3HeroFields(),
                    'landing_course_hero' => self::landingCourseHeroFields(),
                    'landing_course_benefits' => self::landingCourseBenefitsFields(),
                    'course_profile_tab' => self::courseProfileTabFields(),
                    default => [],
                };
            })
            ->columnSpanFull();
    }

    protected static function mapFields(): array
    {
        return [
            Textarea::make('content.address_ar')->label(__('admin.labels.address').' (AR)'),
            Textarea::make('content.address_en')->label(__('admin.labels.address').' (EN)'),
            TextInput::make('content.map_url')->label(__('admin.labels.url'))->placeholder(__('admin.placeholders.https')),
        ];
    }

    protected static function iframeFields(): array
    {
        return [
            Textarea::make('content.iframe_code')
                ->label(__('admin.labels.iframe_code'))
                ->placeholder('<iframe src="..." width="100%" height="500" ...></iframe>')
                ->helperText(__('admin.helpers.iframe_code'))
                ->rows(6)
                ->columnSpanFull(),
            TextInput::make('content.height')
                ->label(__('admin.labels.iframe_height'))
                ->placeholder('500')
                ->helperText(__('admin.helpers.iframe_height_hint'))
                ->numeric(),
        ];
    }

    protected static function heroFields(): array
    {
        return [
            Select::make('content.layout')
                ->label(__('admin.labels.layout'))
                ->options([
                    'default' => __('admin.options.default'),
                    'about' => __('admin.options.about'),
                    'complaint' => __('admin.options.complaint'),
                ])
                ->default('default'),
            TextInput::make('content.tag_ar')->label(__('admin.labels.tag_ar')),
            TextInput::make('content.tag_en')->label(__('admin.labels.tag_en')),
            TextInput::make('content.heading_ar')->label(__('admin.labels.heading_ar')),
            TextInput::make('content.heading_en')->label(__('admin.labels.heading_en')),
            Textarea::make('content.subheading_ar')->label(__('admin.labels.subheading_ar')),
            Textarea::make('content.subheading_en')->label(__('admin.labels.subheading_en')),
            FileUpload::make('content.background_image')
                ->label(__('admin.labels.background_image'))
                ->disk('s3')
                ->directory('sections/hero')
                ->image(),
            // TextInput::make('content.search_placeholder_ar')->label(__('admin.labels.search_placeholder_ar')),
            // TextInput::make('content.search_placeholder_en')->label(__('admin.labels.search_placeholder_en')),
            // TextInput::make('content.category_placeholder_ar')->label(__('admin.labels.category_placeholder_ar')),
            // TextInput::make('content.category_placeholder_en')->label(__('admin.labels.category_placeholder_en')),
            // TextInput::make('content.search_cta_ar')->label(__('admin.labels.search_cta_ar')),
            // TextInput::make('content.search_cta_en')->label(__('admin.labels.search_cta_en')),
            Repeater::make('content.slides')
                ->label(__('admin.labels.slides'))
                ->schema([
                    TextInput::make('heading_ar')->label(__('admin.labels.heading_ar')),
                    TextInput::make('heading_en')->label(__('admin.labels.heading_en')),
                    Textarea::make('subheading_ar')->label(__('admin.labels.subheading_ar')),
                    Textarea::make('subheading_en')->label(__('admin.labels.subheading_en')),
                    TextInput::make('button_text_ar')->label(__('admin.labels.button_text_ar')),
                    TextInput::make('button_text_en')->label(__('admin.labels.button_text_en')),
                    TextInput::make('button_url')->label(__('admin.labels.button_url')),
                    FileUpload::make('background_image')
                        ->disk('s3')
                        ->directory('sections/hero')
                        ->image(),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
            FileUpload::make('content.foreground_image')
                ->label(__('admin.labels.foreground_image'))
                ->disk('s3')
                ->directory('sections/hero')
                ->image(),
            TextInput::make('content.primary_button_text_ar')->label(__('admin.labels.primary_button_ar')),
            TextInput::make('content.primary_button_text_en')->label(__('admin.labels.primary_button_en')),
            TextInput::make('content.primary_button_url')->label(__('admin.labels.primary_button_url')),
            TextInput::make('content.secondary_button_text_ar')->label(__('admin.labels.secondary_button_ar')),
            TextInput::make('content.secondary_button_text_en')->label(__('admin.labels.secondary_button_en')),
            TextInput::make('content.secondary_button_url')->label(__('admin.labels.secondary_button_url')),
            FileUpload::make('content.image')
                ->label(__('admin.labels.hero_image_about_layout'))
                ->disk('s3')
                ->directory('sections/about')
                ->image(),
            Repeater::make('content.stats')
                ->label(__('admin.labels.stats_about_layout'))
                ->schema([
                    TextInput::make('value')->required(),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar'))->required(),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function leadFormFields(): array
    {
        return [
            Select::make('content.layout')
                ->label(__('admin.labels.layout'))
                ->options([
                    'default' => __('admin.options.default'),
                    'complaint' => __('admin.options.complaint'),
                    'contact' => __('admin.options.contact'),
                ])
                ->default('default'),
            Select::make('content.source')
                ->label(__('admin.labels.source'))
                ->options(array_combine(
                    array_column(\App\Enums\ServiceRequestSource::cases(), 'value'),
                    array_map(fn ($c) => __('admin.options.'.$c->value), \App\Enums\ServiceRequestSource::cases())
                ))
                ->nullable(),
            Select::make('content.type_id')
                ->label(__('admin.labels.type'))
                ->options(fn () => \App\Models\ServiceRequestType::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslatedName()])
                )
                ->searchable()
                ->nullable(),
            TextInput::make('content.heading_ar')->label(__('admin.labels.heading_ar')),
            TextInput::make('content.heading_en')->label(__('admin.labels.heading_en')),
            Textarea::make('content.description_ar')->label(__('admin.labels.description_ar')),
            Textarea::make('content.description_en')->label(__('admin.labels.description_en')),
            FileUpload::make('content.image')
                ->label(__('admin.labels.form_image'))
                ->disk('s3')
                ->directory('sections/forms')
                ->image(),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            Toggle::make('content.show_course_select')
                ->label(__('admin.labels.show_course_select'))
                ->default(true),
            Repeater::make('content.fields')
                ->label(__('admin.labels.form_fields'))
                ->schema([
                    TextInput::make('name')->required(),
                    Select::make('type')->options([
                        'text' => __('admin.options.text'),
                        'tel' => __('admin.options.phone'),
                        'email' => __('admin.options.email'),
                        'select' => __('admin.options.select'),
                        'textarea' => __('admin.options.textarea'),
                    ])->required(),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar'))->required(),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                    Toggle::make('required')->default(true),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function contactHeroFields(): array
    {
        return [
            TextInput::make('content.heading_ar')->label(__('admin.labels.heading_ar')),
            TextInput::make('content.heading_en')->label(__('admin.labels.heading_en')),
            Textarea::make('content.subheading_ar')->label(__('admin.labels.subheading_ar')),
            Textarea::make('content.subheading_en')->label(__('admin.labels.subheading_en')),
            Repeater::make('content.cards')
                ->label(__('admin.labels.cards'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    RichEditor::make('body_ar')->label(__('admin.labels.body_ar'))->columnSpanFull(),
                    RichEditor::make('body_en')->label(__('admin.labels.body_en'))->columnSpanFull(),
                    TextInput::make('icon')->label(__('admin.labels.icon_name')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function featuresFields(): array
    {
        return [
            Repeater::make('content.items')
                ->label(__('admin.labels.items'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    Textarea::make('description_ar')->label(__('admin.labels.description_ar')),
                    Textarea::make('description_en')->label(__('admin.labels.description_en')),
                    FileUpload::make('image')
                        ->disk('s3')
                        ->directory('sections/features')
                        ->image(),
                    TextInput::make('icon')->label(__('admin.labels.icon_name')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function testimonialsFields(): array
    {
        return [
            Repeater::make('content.items')
                ->label(__('admin.labels.testimonials'))
                ->schema([
                    TextInput::make('text_ar')->label(__('admin.labels.text_ar'))->required(),
                    TextInput::make('text_en')->label(__('admin.labels.text_en')),
                    TextInput::make('name_ar')->label(__('admin.labels.name_ar')),
                    TextInput::make('name_en')->label(__('admin.labels.name_en')),
                    TextInput::make('role_ar')->label(__('admin.labels.role_ar')),
                    TextInput::make('role_en')->label(__('admin.labels.role_en')),
                    FileUpload::make('avatar')
                        ->disk('s3')
                        ->directory('sections/testimonials')
                        ->image(),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function faqFields(): array
    {
        return [
            Repeater::make('content.items')
                ->label(__('admin.labels.faq'))
                ->schema([
                    TextInput::make('question_ar')->label(__('admin.labels.question_ar'))->required(),
                    TextInput::make('question_en')->label(__('admin.labels.question_en')),
                    Textarea::make('answer_ar')->label(__('admin.labels.answer_ar')),
                    Textarea::make('answer_en')->label(__('admin.labels.answer_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function ctaFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            TextInput::make('content.text_ar')->label(__('admin.labels.text_ar')),
            TextInput::make('content.text_en')->label(__('admin.labels.text_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
            FileUpload::make('content.image')
                ->disk('s3')
                ->directory('sections/cta')
                ->image(),
            FileUpload::make('content.logos')
                ->label(__('admin.labels.logos'))
                ->disk('s3')
                ->directory('sections/cta')
                ->image()
                ->multiple()
                ->reorderable(),
        ];
    }

    protected static function featuredCoursesFields(): array
    {
        return [
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
            TextInput::make('content.discount_badge_ar')->label(__('admin.labels.discount_badge_ar')),
            TextInput::make('content.discount_badge_en')->label(__('admin.labels.discount_badge_en')),
        ];
    }

    protected static function specialBundlesFields(): array
    {
        return [
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
        ];
    }

    protected static function aboutFields(): array
    {
        return [
            RichEditor::make('content.body_ar')
                ->label(__('admin.labels.body_ar'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private'),
            RichEditor::make('content.body_en')
                ->label(__('admin.labels.body_en'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private'),
            TextInput::make('content.card_label_ar')->label(__('admin.labels.card_label_ar')),
            TextInput::make('content.card_label_en')->label(__('admin.labels.card_label_en')),
            TextInput::make('content.card_title_ar')->label(__('admin.labels.card_title_ar')),
            TextInput::make('content.card_title_en')->label(__('admin.labels.card_title_en')),
            RichEditor::make('content.card_body_ar')
                ->label(__('admin.labels.card_body_ar'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private'),
            RichEditor::make('content.card_body_en')
                ->label(__('admin.labels.card_body_en'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private'),
            Repeater::make('content.points')
                ->label(__('admin.labels.points'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    Textarea::make('body_ar')->label(__('admin.labels.body_ar')),
                    Textarea::make('body_en')->label(__('admin.labels.body_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function howItWorksFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            TextInput::make('content.feature_title_ar')->label(__('admin.labels.feature_title_ar')),
            TextInput::make('content.feature_title_en')->label(__('admin.labels.feature_title_en')),
            Textarea::make('content.feature_body_ar')->label(__('admin.labels.feature_body_ar')),
            Textarea::make('content.feature_body_en')->label(__('admin.labels.feature_body_en')),
            TextInput::make('content.cta_text_ar')->label(__('admin.labels.cta_text_ar')),
            TextInput::make('content.cta_text_en')->label(__('admin.labels.cta_text_en')),
            TextInput::make('content.cta_url')->label(__('admin.labels.cta_url')),
            FileUpload::make('content.image')
                ->label(__('admin.labels.main_image'))
                ->disk('s3')
                ->directory('sections/how-it-works')
                ->image(),
            TextInput::make('content.stat_value')->label(__('admin.labels.stat_value')),
            TextInput::make('content.stat_label_ar')->label(__('admin.labels.stat_label_ar')),
            TextInput::make('content.stat_label_en')->label(__('admin.labels.stat_label_en')),
            TextInput::make('content.skill_label_ar')->label(__('admin.labels.skill_label_ar')),
            TextInput::make('content.skill_label_en')->label(__('admin.labels.skill_label_en')),
            TextInput::make('content.skill_percent')->label(__('admin.labels.skill_percent')),
            Repeater::make('content.cards')
                ->label(__('admin.labels.cards'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    Textarea::make('body_ar')->label(__('admin.labels.body_ar')),
                    Textarea::make('body_en')->label(__('admin.labels.body_en')),
                    TextInput::make('icon')->label(__('admin.labels.icon_key_discover_access_share')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function trainerQuotesFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            Repeater::make('content.items')
                ->label(__('admin.labels.cards'))
                ->schema([
                    TextInput::make('headline_ar')->label(__('admin.labels.headline_ar'))->required(),
                    TextInput::make('headline_en')->label(__('admin.labels.headline_en')),
                    FileUpload::make('image')
                        ->disk('s3')
                        ->directory('sections/trainer-quotes')
                        ->image(),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function testimonialsStripFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            Repeater::make('content.items')
                ->label(__('admin.labels.testimonials'))
                ->schema([
                    TextInput::make('text_ar')->label(__('admin.labels.text_ar'))->required(),
                    TextInput::make('text_en')->label(__('admin.labels.text_en')),
                    TextInput::make('name_ar')->label(__('admin.labels.name_ar')),
                    TextInput::make('name_en')->label(__('admin.labels.name_en')),
                    FileUpload::make('avatar')
                        ->disk('s3')
                        ->directory('sections/testimonials-strip')
                        ->image(),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function ctaBannerFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
            FileUpload::make('content.background_image')
                ->label(__('admin.labels.background_image'))
                ->disk('s3')
                ->directory('sections/cta-banner')
                ->image(),
        ];
    }

    protected static function ctaCounterFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            DateTimePicker::make('content.target_date')
                ->label(__('admin.labels.target_date'))
                ->seconds(false)
                ->required(),
            FileUpload::make('content.background_image')
                ->label(__('admin.labels.background_image'))
                ->disk('s3')
                ->directory('sections/cta-counter')
                ->image(),
        ];
    }

    protected static function partnersFields(): array
    {
        return [
            Select::make('content.layout')
                ->label(__('admin.labels.layout'))
                ->options([
                    'marquee' => __('admin.options.marquee'),
                    'grid' => __('admin.options.grid'),
                ])
                ->default('marquee'),
            FileUpload::make('content.logos')
                ->label(__('admin.labels.logos'))
                ->disk('s3')
                ->directory('sections/partners')
                ->image()
                ->multiple()
                ->reorderable(),
        ];
    }

    protected static function categoriesFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
        ];
    }

    protected static function valuesFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            Repeater::make('content.cards')
                ->label(__('admin.labels.cards'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    Textarea::make('body_ar')->label(__('admin.labels.body_ar')),
                    Textarea::make('body_en')->label(__('admin.labels.body_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function storyFields(): array
    {
        return [
            TextInput::make('content.title_ar')->label(__('admin.labels.title_ar')),
            TextInput::make('content.title_en')->label(__('admin.labels.title_en')),
            Textarea::make('content.subtitle_ar')->label(__('admin.labels.subtitle_ar')),
            Textarea::make('content.subtitle_en')->label(__('admin.labels.subtitle_en')),
            Repeater::make('content.blocks')
                ->label(__('admin.labels.story_blocks'))
                ->schema([
                    Textarea::make('body_ar')->label(__('admin.labels.body_ar'))->required(),
                    Textarea::make('body_en')->label(__('admin.labels.body_en')),
                    FileUpload::make('image')
                        ->disk('s3')
                        ->directory('sections/story')
                        ->image(),
                    Select::make('alignment')
                        ->options([
                            'image-left' => __('admin.options.image_left'),
                            'image-right' => __('admin.options.image_right'),
                        ])
                        ->default('image-left'),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function whyFields(): array
    {
        return [
            Repeater::make('content.points')
                ->label(__('admin.labels.points'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    Textarea::make('body_ar')->label(__('admin.labels.body_ar')),
                    Textarea::make('body_en')->label(__('admin.labels.body_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function corporateFields(): array
    {
        return [
            TextInput::make('content.tag_ar')->label(__('admin.labels.tag_ar')),
            TextInput::make('content.tag_en')->label(__('admin.labels.tag_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
        ];
    }

    protected static function statsFields(): array
    {
        return [
            Repeater::make('content.items')
                ->label(__('admin.labels.stats'))
                ->schema([
                    TextInput::make('value')->required(),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar'))->required(),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function complaintsFields(): array
    {
        return [
            TextInput::make('content.tag_ar')->label(__('admin.labels.tag_ar')),
            TextInput::make('content.tag_en')->label(__('admin.labels.tag_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
        ];
    }

    protected static function actionCardsFields(): array
    {
        return [
            Repeater::make('content.items')
                ->label(__('admin.labels.items'))
                ->schema([
                    Textarea::make('icon')->label(__('admin.labels.icon')),
                    TextInput::make('text_ar')->label(__('admin.labels.text_ar'))->required(),
                    TextInput::make('text_en')->label(__('admin.labels.text_en')),
                    TextInput::make('url')->label(__('admin.labels.url')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed(),
        ];
    }

    protected static function policyTextFields(): array
    {
        return [
            RichEditor::make('content.body_ar')
                ->label(__('admin.labels.body_ar'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private'),
            RichEditor::make('content.body_en')
                ->label(__('admin.labels.body_en'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private'),
        ];
    }

    protected static function videoFields(): array
    {
        return [
            TextInput::make('content.video_url')->label(__('admin.labels.video_url'))->required(),
            FileUpload::make('content.thumbnail')
                ->label(__('admin.labels.video_thumbnail'))
                ->disk('s3')
                ->directory('sections/video')
                ->image(),
        ];
    }

    protected static function serviceRequestFormFields(): array
    {
        return [
            Select::make('content.type_id')
                ->label(__('admin.labels.type'))
                ->options(fn () => \App\Models\ServiceRequestType::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn ($type) => [$type->id => $type->getTranslatedName()])
                )
                ->searchable()
                ->nullable(),
            TextInput::make('content.heading_ar')->label(__('admin.labels.heading_ar')),
            TextInput::make('content.heading_en')->label(__('admin.labels.heading_en')),
            Textarea::make('content.description_ar')->label(__('admin.labels.description_ar')),
            Textarea::make('content.description_en')->label(__('admin.labels.description_en')),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            TextInput::make('content.success_message_ar')->label(__('admin.labels.success_message_ar')),
            TextInput::make('content.success_message_en')->label(__('admin.labels.success_message_en')),
            Toggle::make('content.show_email')->label(__('admin.labels.show_email'))->default(true),
            Toggle::make('content.show_message')->label(__('admin.labels.show_message'))->default(true),
            Toggle::make('content.show_course_select')->label(__('admin.labels.show_course_select'))->default(false),
        ];
    }

    protected static function licensesProfileFields(): array
    {
        return [
            Repeater::make('content.items')
                ->label(__('admin.labels.items'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    RichEditor::make('body_ar')->label(__('admin.labels.body_ar'))->columnSpanFull(),
                    RichEditor::make('body_en')->label(__('admin.labels.body_en'))->columnSpanFull(),
                    FileUpload::make('image')
                        ->label(__('admin.labels.image'))
                        ->disk('s3')
                        ->directory('sections/licenses')
                        ->image()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
        ];
    }

    protected static function landingHeroFields(): array
    {
        return [
            FileUpload::make('content.override_image')
                ->label(__('admin.labels.override_image'))
                ->helperText(__('admin.helpers.landing_hero_image_hint'))
                ->disk('s3')
                ->directory('sections/landing')
                ->image()
                ->columnSpanFull(),
            Repeater::make('content.slides')
                ->label(__('admin.labels.slides'))
                ->schema([
                    TextInput::make('heading_ar')->label(__('admin.labels.heading_ar')),
                    TextInput::make('heading_en')->label(__('admin.labels.heading_en')),
                    Textarea::make('subheading_ar')->label(__('admin.labels.subheading_ar')),
                    Textarea::make('subheading_en')->label(__('admin.labels.subheading_en')),
                    TextInput::make('button_text_ar')->label(__('admin.labels.button_text_ar')),
                    TextInput::make('button_text_en')->label(__('admin.labels.button_text_en')),
                    TextInput::make('button_url')->label(__('admin.labels.button_url')),
                    FileUpload::make('background_image')
                        ->disk('s3')
                        ->directory('sections/landing')
                        ->image(),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
        ];
    }

    protected static function landingLeadFormFields(): array
    {
        return [
            TextInput::make('content.heading_ar')->label(__('admin.labels.heading_ar')),
            TextInput::make('content.heading_en')->label(__('admin.labels.heading_en')),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            Select::make('content.type_id')
                ->label(__('admin.labels.type'))
                ->options(fn () => \App\Models\ServiceRequestType::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslatedName()])
                )
                ->searchable()
                ->nullable(),
        ];
    }

    protected static function landingV2BrandsFields(): array
    {
        return [
            FileUpload::make('content.logo')
                ->label(__('admin.labels.landing_v2_center_logo'))
                ->helperText(__('admin.helpers.landing_v2_center_logo'))
                ->disk('s3')
                ->directory('sections/landing-v2')
                ->image()
                ->columnSpanFull(),
            Repeater::make('content.brands')
                ->label(__('admin.labels.landing_v2_brands'))
                ->helperText(__('admin.helpers.landing_v2_brands'))
                ->schema([
                    FileUpload::make('image')
                        ->label(__('admin.labels.logo'))
                        ->disk('s3')
                        ->directory('sections/landing-v2')
                        ->image()
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('alt')->label(__('admin.labels.landing_v2_alt')),
                    TextInput::make('caption_ar')->label(__('admin.labels.landing_v2_caption_ar')),
                    TextInput::make('caption_en')->label(__('admin.labels.landing_v2_caption_en')),
                ])
                ->columns(3)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
        ];
    }

    protected static function landingV2HeroFields(): array
    {
        return [
            TextInput::make('content.heading_ar')
                ->label(__('admin.labels.heading_ar'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.heading_en')
                ->label(__('admin.labels.heading_en'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            Textarea::make('content.subheading_ar')->label(__('admin.labels.subheading_ar')),
            Textarea::make('content.subheading_en')->label(__('admin.labels.subheading_en')),
            Repeater::make('content.facts')
                ->label(__('admin.labels.landing_v2_facts'))
                ->schema([
                    TextInput::make('value_ar')->label(__('admin.labels.landing_v2_value_ar'))->required(),
                    TextInput::make('value_en')->label(__('admin.labels.landing_v2_value_en')),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar')),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                ])
                ->columns(2)
                ->maxItems(3)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            TextInput::make('content.price_ar')->label(__('admin.labels.landing_v2_price_ar')),
            TextInput::make('content.price_en')->label(__('admin.labels.landing_v2_price_en')),
            TextInput::make('content.form_title_ar')->label(__('admin.labels.landing_v2_form_title_ar')),
            TextInput::make('content.form_title_en')->label(__('admin.labels.landing_v2_form_title_en')),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            Select::make('content.type_id')
                ->label(__('admin.labels.type'))
                ->options(fn () => \App\Models\ServiceRequestType::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslatedName()])
                )
                ->searchable()
                ->nullable(),
            Toggle::make('content.show_next_batch')
                ->label(__('admin.labels.landing_v2_show_next_batch'))
                ->helperText(__('admin.helpers.landing_v2_show_next_batch'))
                ->default(true),
            FileUpload::make('content.mark_image')
                ->label(__('admin.labels.landing_v2_mark_image'))
                ->helperText(__('admin.helpers.landing_v2_mark_image'))
                ->disk('s3')
                ->directory('sections/landing-v2')
                ->image()
                ->columnSpanFull(),
        ];
    }

    protected static function landingV2ClientsFields(): array
    {
        return [
            TextInput::make('content.title_ar')
                ->label(__('admin.labels.title_ar'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.title_en')
                ->label(__('admin.labels.title_en'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            FileUpload::make('content.logos')
                ->label(__('admin.labels.logos'))
                ->disk('s3')
                ->directory('sections/landing-v2/clients')
                ->image()
                ->multiple()
                ->reorderable()
                ->columnSpanFull(),
        ];
    }

    protected static function landingV3HeroFields(): array
    {
        return [
            TextInput::make('content.heading_ar')
                ->label(__('admin.labels.heading_ar'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.heading_en')
                ->label(__('admin.labels.heading_en'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            Textarea::make('content.subheading_ar')->label(__('admin.labels.subheading_ar')),
            Textarea::make('content.subheading_en')->label(__('admin.labels.subheading_en')),
            FileUpload::make('content.logo')
                ->label(__('admin.labels.landing_v3_heading_logo'))
                ->helperText(__('admin.helpers.landing_v3_heading_logo'))
                ->disk('s3')
                ->directory('sections/landing-v2')
                ->image()
                ->columnSpanFull(),
            Repeater::make('content.facts')
                ->label(__('admin.labels.landing_v2_facts'))
                ->helperText(__('admin.helpers.landing_v3_fact_image'))
                ->schema([
                    TextInput::make('value_ar')->label(__('admin.labels.landing_v2_value_ar')),
                    TextInput::make('value_en')->label(__('admin.labels.landing_v2_value_en')),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar')),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                    FileUpload::make('image')
                        ->label(__('admin.labels.logo'))
                        ->disk('s3')
                        ->directory('sections/landing-v2')
                        ->image()
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->maxItems(3)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            Repeater::make('content.badges')
                ->label(__('admin.labels.landing_v3_badges'))
                ->schema([
                    FileUpload::make('image')
                        ->label(__('admin.labels.logo'))
                        ->disk('s3')
                        ->directory('sections/landing-v2')
                        ->image()
                        ->columnSpanFull(),
                    TextInput::make('name_ar')->label(__('admin.labels.name_ar'))->required(),
                    TextInput::make('name_en')->label(__('admin.labels.name_en')),
                    TextInput::make('detail_ar')->label(__('admin.labels.landing_v3_badge_detail_ar')),
                    TextInput::make('detail_en')->label(__('admin.labels.landing_v3_badge_detail_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            TextInput::make('content.price_ar')->label(__('admin.labels.landing_v2_price_ar')),
            TextInput::make('content.price_en')->label(__('admin.labels.landing_v2_price_en')),
            Toggle::make('content.show_currency_icon')
                ->label(__('admin.labels.landing_v3_show_currency_icon'))
                ->default(true),
            TextInput::make('content.price_note_ar')->label(__('admin.labels.landing_v3_price_note_ar')),
            TextInput::make('content.price_note_en')->label(__('admin.labels.landing_v3_price_note_en')),
            FileUpload::make('content.price_note_image')
                ->label(__('admin.labels.landing_v3_price_note_image'))
                ->disk('s3')
                ->directory('sections/landing-v2')
                ->image(),
            TextInput::make('content.form_title_ar')->label(__('admin.labels.landing_v2_form_title_ar')),
            TextInput::make('content.form_title_en')->label(__('admin.labels.landing_v2_form_title_en')),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            Select::make('content.type_id')
                ->label(__('admin.labels.type'))
                ->options(fn () => \App\Models\ServiceRequestType::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslatedName()])
                )
                ->searchable()
                ->nullable(),
            Toggle::make('content.show_next_batch')
                ->label(__('admin.labels.landing_v2_show_next_batch'))
                ->helperText(__('admin.helpers.landing_v2_show_next_batch'))
                ->default(true),
            FileUpload::make('content.mark_image')
                ->label(__('admin.labels.landing_v2_mark_image'))
                ->helperText(__('admin.helpers.landing_v2_mark_image'))
                ->disk('s3')
                ->directory('sections/landing-v2')
                ->image()
                ->columnSpanFull(),
        ];
    }

    protected static function landingV2BenefitsFields(): array
    {
        $itemsRepeater = fn (string $name, string $label) => Repeater::make($name)
            ->label($label)
            ->schema([
                TextInput::make('text_ar')
                    ->label(__('admin.labels.text_ar'))
                    ->helperText(__('admin.helpers.landing_v2_highlight_bold'))
                    ->required(),
                TextInput::make('text_en')->label(__('admin.labels.text_en')),
                TextInput::make('note_ar')->label(__('admin.labels.note_ar')),
                TextInput::make('note_en')->label(__('admin.labels.note_en')),
            ])
            ->columns(2)
            ->reorderable()
            ->collapsed()
            ->columnSpanFull();

        return [
            TextInput::make('content.title_ar')
                ->label(__('admin.labels.title_ar'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.title_en')
                ->label(__('admin.labels.title_en'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.during_title_ar')->label(__('admin.labels.landing_v2_during_title_ar')),
            TextInput::make('content.during_title_en')->label(__('admin.labels.landing_v2_during_title_en')),
            $itemsRepeater('content.during_items', __('admin.labels.landing_v2_during_items')),
            TextInput::make('content.after_title_ar')->label(__('admin.labels.landing_v2_after_title_ar')),
            TextInput::make('content.after_title_en')->label(__('admin.labels.landing_v2_after_title_en')),
            $itemsRepeater('content.after_items', __('admin.labels.landing_v2_after_items')),
            Repeater::make('content.stats')
                ->label(__('admin.labels.stats'))
                ->schema([
                    TextInput::make('value')->label(__('admin.labels.stat_value'))->required(),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar'))->required(),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                ])
                ->columns(3)
                ->maxItems(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            TextInput::make('content.cta_text_ar')->label(__('admin.labels.cta_text_ar')),
            TextInput::make('content.cta_text_en')->label(__('admin.labels.cta_text_en')),
        ];
    }

    protected static function landingCourseHeroFields(): array
    {
        return [
            Repeater::make('content.badges')
                ->label(__('admin.labels.landing_v3_badges'))
                ->schema([
                    FileUpload::make('image')
                        ->label(__('admin.labels.logo'))
                        ->disk('s3')
                        ->directory('sections/landing-course')
                        ->image()
                        ->columnSpanFull(),
                    TextInput::make('name_ar')->label(__('admin.labels.name_ar'))->required(),
                    TextInput::make('name_en')->label(__('admin.labels.name_en')),
                    TextInput::make('detail_ar')->label(__('admin.labels.landing_v3_badge_detail_ar')),
                    TextInput::make('detail_en')->label(__('admin.labels.landing_v3_badge_detail_en')),
                ])
                ->columns(2)
                ->maxItems(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            TextInput::make('content.heading_ar')
                ->label(__('admin.labels.heading_ar'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.heading_en')
                ->label(__('admin.labels.heading_en'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            Textarea::make('content.subheading_ar')->label(__('admin.labels.subheading_ar')),
            Textarea::make('content.subheading_en')->label(__('admin.labels.subheading_en')),
            FileUpload::make('content.logo')
                ->label(__('admin.labels.landing_v3_heading_logo'))
                ->helperText(__('admin.helpers.landing_v3_heading_logo'))
                ->disk('s3')
                ->directory('sections/landing-course')
                ->image()
                ->columnSpanFull(),
            TextInput::make('content.price_ar')->label(__('admin.labels.landing_v2_price_ar')),
            TextInput::make('content.price_en')->label(__('admin.labels.landing_v2_price_en')),
            TextInput::make('content.old_price_ar')
                ->label(__('admin.labels.landing_course_old_price_ar'))
                ->helperText(__('admin.helpers.landing_course_old_price')),
            TextInput::make('content.old_price_en')->label(__('admin.labels.landing_course_old_price_en')),
            Toggle::make('content.show_currency_icon')
                ->label(__('admin.labels.landing_v3_show_currency_icon'))
                ->default(true)
                ->columnSpanFull(),
            TextInput::make('content.installment_ar')
                ->label(__('admin.labels.landing_course_installment_ar'))
                ->helperText(__('admin.helpers.landing_course_installment')),
            TextInput::make('content.installment_en')->label(__('admin.labels.landing_course_installment_en')),
            TextInput::make('content.installment_badge_ar')->label(__('admin.labels.landing_course_installment_badge_ar')),
            TextInput::make('content.installment_badge_en')->label(__('admin.labels.landing_course_installment_badge_en')),
            TextInput::make('content.form_title_ar')->label(__('admin.labels.landing_v2_form_title_ar')),
            TextInput::make('content.form_title_en')->label(__('admin.labels.landing_v2_form_title_en')),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            Select::make('content.type_id')
                ->label(__('admin.labels.type'))
                ->options(fn () => \App\Models\ServiceRequestType::query()
                    ->active()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslatedName()])
                )
                ->searchable()
                ->nullable(),
            Toggle::make('content.show_next_batch')
                ->label(__('admin.labels.landing_v2_show_next_batch'))
                ->helperText(__('admin.helpers.landing_course_next_batch'))
                ->default(true),
            FileUpload::make('content.mark_image')
                ->label(__('admin.labels.landing_v2_mark_image'))
                ->helperText(__('admin.helpers.landing_v2_mark_image'))
                ->disk('s3')
                ->directory('sections/landing-course')
                ->image()
                ->columnSpanFull(),
        ];
    }

    protected static function landingCourseBenefitsFields(): array
    {
        $iconRepeater = fn (string $name, string $label, int $maxItems) => Repeater::make($name)
            ->label($label)
            ->schema([
                FileUpload::make('image')
                    ->label(__('admin.labels.icon'))
                    ->disk('s3')
                    ->directory('sections/landing-course')
                    ->image()
                    ->columnSpanFull(),
                TextInput::make('text_ar')->label(__('admin.labels.text_ar'))->required(),
                TextInput::make('text_en')->label(__('admin.labels.text_en')),
            ])
            ->columns(2)
            ->maxItems($maxItems)
            ->reorderable()
            ->collapsed()
            ->columnSpanFull();

        return [
            Repeater::make('content.facts')
                ->label(__('admin.labels.landing_v2_facts'))
                ->helperText(__('admin.helpers.landing_course_facts'))
                ->schema([
                    TextInput::make('value_ar')->label(__('admin.labels.landing_v2_value_ar'))->required(),
                    TextInput::make('value_en')->label(__('admin.labels.landing_v2_value_en')),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar')),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                ])
                ->columns(2)
                ->maxItems(3)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            TextInput::make('content.title_ar')
                ->label(__('admin.labels.title_ar'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            TextInput::make('content.title_en')
                ->label(__('admin.labels.title_en'))
                ->helperText(__('admin.helpers.landing_v2_highlight')),
            $iconRepeater('content.tools', __('admin.labels.landing_course_tools'), 6),
            TextInput::make('content.extras_title_ar')->label(__('admin.labels.landing_course_extras_title_ar')),
            TextInput::make('content.extras_title_en')->label(__('admin.labels.landing_course_extras_title_en')),
            $iconRepeater('content.extras', __('admin.labels.landing_course_extras'), 5),
            TextInput::make('content.stories_title_ar')->label(__('admin.labels.landing_course_stories_title_ar')),
            TextInput::make('content.stories_title_en')->label(__('admin.labels.landing_course_stories_title_en')),
            TextInput::make('content.stories_subtitle_ar')->label(__('admin.labels.landing_course_stories_subtitle_ar')),
            TextInput::make('content.stories_subtitle_en')->label(__('admin.labels.landing_course_stories_subtitle_en')),
            Repeater::make('content.stories')
                ->label(__('admin.labels.landing_course_stories'))
                ->schema([
                    TextInput::make('title_ar')->label(__('admin.labels.title_ar'))->required(),
                    TextInput::make('title_en')->label(__('admin.labels.title_en')),
                    Textarea::make('summary_ar')->label(__('admin.labels.landing_course_story_summary_ar')),
                    Textarea::make('summary_en')->label(__('admin.labels.landing_course_story_summary_en')),
                    Textarea::make('now_ar')->label(__('admin.labels.landing_course_story_now_ar')),
                    Textarea::make('now_en')->label(__('admin.labels.landing_course_story_now_en')),
                    Textarea::make('after_ar')->label(__('admin.labels.landing_course_story_after_ar')),
                    Textarea::make('after_en')->label(__('admin.labels.landing_course_story_after_en')),
                ])
                ->columns(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            Repeater::make('content.stats')
                ->label(__('admin.labels.stats'))
                ->schema([
                    TextInput::make('value')->label(__('admin.labels.stat_value'))->required(),
                    TextInput::make('label_ar')->label(__('admin.labels.label_ar'))->required(),
                    TextInput::make('label_en')->label(__('admin.labels.label_en')),
                ])
                ->columns(3)
                ->maxItems(2)
                ->reorderable()
                ->collapsed()
                ->columnSpanFull(),
            TextInput::make('content.cta_text_ar')->label(__('admin.labels.cta_text_ar')),
            TextInput::make('content.cta_text_en')->label(__('admin.labels.cta_text_en')),
        ];
    }

    protected static function courseProfileTabFields(): array
    {
        return [
            RichEditor::make('content.body_ar')
                ->label(__('admin.labels.body_ar'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('sections/course-profile')
                ->fileAttachmentsVisibility('private')
                ->columnSpanFull(),
            RichEditor::make('content.body_en')
                ->label(__('admin.labels.body_en'))
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('sections/course-profile')
                ->fileAttachmentsVisibility('private')
                ->columnSpanFull(),
            FileUpload::make('content.gallery')
                ->label(__('admin.labels.gallery_images'))
                ->disk('s3')
                ->directory('sections/course-profile')
                ->image()
                ->multiple()
                ->reorderable()
                ->columnSpanFull(),
        ];
    }
}
