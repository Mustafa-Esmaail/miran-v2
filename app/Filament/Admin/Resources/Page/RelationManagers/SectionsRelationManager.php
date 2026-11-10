<?php

namespace App\Filament\Admin\Resources\Page\RelationManagers;

use App\Filament\Admin\Resources\Sections\SectionResource;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.page_sections');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('admin')->user()?->can('sections.viewAny') ?? false;
    }

    public function canCreate(): bool
    {
        return auth('admin')->user()?->can('sections.create') ?? false;
    }

    public function canEdit(Model $record): bool
    {
        return auth('admin')->user()?->can('sections.update') ?? false;
    }

    public function canDelete(Model $record): bool
    {
        return auth('admin')->user()?->can('sections.delete') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.section'))
                ->schema([
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
                            'why' => __('admin.options.why_choose_us'),
                            'corporate' => __('admin.options.corporate_cta'),
                            'testimonials' => __('admin.options.testimonials'),
                            'stats' => __('admin.options.stats'),
                            'lead_form' => __('admin.options.lead_form'),
                            'complaints' => __('admin.options.complaints'),
                            'features' => __('admin.options.features'),
                            'faq' => __('admin.options.faq'),
                            'cta' => __('admin.options.cta'),
                            'action_cards' => __('admin.options.action_cards'),
                            'policy_text' => __('admin.options.policy_text'),
                            'video' => __('admin.options.video'),
                            'map' => __('admin.options.map'),
                        ])
                        ->required()
                        ->live()
                        ->helperText(__('admin.helpers.controls_which_fields_appear_below')),
                    TextInput::make('title'),
                    TextInput::make('subtitle'),
                    $this->typeFields(),
                ])
                ->columns(3),
            Section::make(__('admin.sections.settings'))
                ->schema([
                    Toggle::make('is_active')->label(__('admin.labels.active'))->default(true),
                    TextInput::make('sort_order')->numeric()->default(0)->helperText(__('admin.helpers.lower_numbers_appear_first')),
                ])
                ->columns(3),
        ])->columns(1);
    }

    protected function typeFields()
    {
        return Group::make()
            ->schema(function (Get $get) {
                return match ($get('type')) {
                    'hero' => $this->heroFields(),
                    'lead_form' => $this->leadFormFields(),
                    'features' => $this->featuresFields(),
                    'testimonials' => $this->testimonialsFields(),
                    'faq' => $this->faqFields(),
                    'cta' => $this->ctaFields(),
                    'about' => $this->aboutFields(),
                    'how_it_works' => $this->howItWorksFields(),
                    'trainer_quotes' => $this->trainerQuotesFields(),
                    'testimonials_strip' => $this->testimonialsStripFields(),
                    'cta_banner' => $this->ctaBannerFields(),
                    'cta_counter' => $this->ctaCounterFields(),
                    'categories' => $this->categoriesFields(),
                    'partners' => $this->partnersFields(),
                    'why' => $this->whyFields(),
                    'corporate' => $this->corporateFields(),
                    'stats' => $this->statsFields(),
                    'complaints' => $this->complaintsFields(),
                    'action_cards' => $this->actionCardsFields(),
                    'policy_text' => $this->policyTextFields(),
                    'video' => $this->videoFields(),
                    'map' => $this->mapFields(),
                    default => [],
                };
            })
            ->columnSpanFull();
    }

    protected function mapFields(): array
    {
        return [
            Textarea::make('content.address_ar')->label(__('admin.labels.address').' (AR)'),
            Textarea::make('content.address_en')->label(__('admin.labels.address').' (EN)'),
            TextInput::make('content.map_url')->label(__('admin.labels.url'))->placeholder(__('admin.placeholders.https')),
        ];
    }

    protected function heroFields(): array
    {
        return [
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
            TextInput::make('content.search_placeholder_ar')->label(__('admin.labels.search_placeholder_ar')),
            TextInput::make('content.search_placeholder_en')->label(__('admin.labels.search_placeholder_en')),
            TextInput::make('content.category_placeholder_ar')->label(__('admin.labels.category_placeholder_ar')),
            TextInput::make('content.category_placeholder_en')->label(__('admin.labels.category_placeholder_en')),
            TextInput::make('content.search_cta_ar')->label(__('admin.labels.search_cta_ar')),
            TextInput::make('content.search_cta_en')->label(__('admin.labels.search_cta_en')),
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
        ];
    }

    protected function leadFormFields(): array
    {
        return [
            TextInput::make('content.heading_ar')->label(__('admin.labels.heading_ar')),
            TextInput::make('content.heading_en')->label(__('admin.labels.heading_en')),
            Textarea::make('content.description_ar')->label(__('admin.labels.description_ar')),
            Textarea::make('content.description_en')->label(__('admin.labels.description_en')),
            TextInput::make('content.submit_text_ar')->label(__('admin.labels.submit_text_ar')),
            TextInput::make('content.submit_text_en')->label(__('admin.labels.submit_text_en')),
            TextInput::make('content.note_ar')->label(__('admin.labels.note_ar')),
            TextInput::make('content.note_en')->label(__('admin.labels.note_en')),
            TextInput::make('content.success_message_ar')->label(__('admin.labels.success_message_ar')),
            TextInput::make('content.success_message_en')->label(__('admin.labels.success_message_en')),
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

    protected function featuresFields(): array
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

    protected function testimonialsFields(): array
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

    protected function faqFields(): array
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

    protected function ctaFields(): array
    {
        return [
            TextInput::make('content.text_ar')->label(__('admin.labels.text_ar')),
            TextInput::make('content.text_en')->label(__('admin.labels.text_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
            FileUpload::make('content.image')
                ->disk('s3')
                ->directory('sections/cta')
                ->image(),
        ];
    }

    protected function aboutFields(): array
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

    protected function howItWorksFields(): array
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

    protected function trainerQuotesFields(): array
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

    protected function testimonialsStripFields(): array
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

    protected function ctaBannerFields(): array
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

    protected function ctaCounterFields(): array
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

    protected function partnersFields(): array
    {
        return [
            FileUpload::make('content.logos')
                ->label(__('admin.labels.logos'))
                ->disk('s3')
                ->directory('sections/partners')
                ->image()
                ->multiple()
                ->reorderable(),
        ];
    }

    protected function categoriesFields(): array
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

    protected function whyFields(): array
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

    protected function corporateFields(): array
    {
        return [
            TextInput::make('content.tag_ar')->label(__('admin.labels.tag_ar')),
            TextInput::make('content.tag_en')->label(__('admin.labels.tag_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
        ];
    }

    protected function statsFields(): array
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

    protected function complaintsFields(): array
    {
        return [
            TextInput::make('content.tag_ar')->label(__('admin.labels.tag_ar')),
            TextInput::make('content.tag_en')->label(__('admin.labels.tag_en')),
            TextInput::make('content.button_text_ar')->label(__('admin.labels.button_text_ar')),
            TextInput::make('content.button_text_en')->label(__('admin.labels.button_text_en')),
            TextInput::make('content.button_url')->label(__('admin.labels.button_url')),
        ];
    }

    protected function actionCardsFields(): array
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

    protected function policyTextFields(): array
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

    protected function videoFields(): array
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

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable()->limit(40),
                IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create'))
                    ->modal(false)
                    ->url(fn () => SectionResource::getUrl('create', ['page_id' => $this->getOwnerRecord()->id])),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => SectionResource::getUrl('edit', ['record' => $record])),
                \Filament\Actions\DeleteAction::make()->label(__('admin.actions.delete')),
            ])
            ->emptyStateHeading(__('admin.empty_states.page_sections.heading'))
            ->emptyStateDescription(__('admin.empty_states.page_sections.description'));
    }
}
