<?php

namespace App\Filament\Pages;

use App\Filament\Forms\TranslatableField;
use App\Services\Settings\SettingsService;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($settings->formData());
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('settings.view') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('settings_tabs')
                    ->tabs([

                        Tab::make(__('admin.settings.site'))
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                TranslatableField::localeSwitcher(),
                                TranslatableField::text('site_title', __('admin.fields.title')),
                                TextInput::make('site_url')
                                    ->label(__('admin.fields.site_url'))
                                    ->helperText(__('admin.settings.site_url_hint'))
                                    ->url()
                                    ->required()
                                    ->columnSpanFull(),
                                FileUpload::make('site_logo')
                                    ->disk('s3')->directory('settings')->visibility('private')->image(),
                                FileUpload::make('site_favicon')
                                    ->disk('s3')->directory('settings')->visibility('private')->image(),
                                Select::make('site_default_locale')
                                    ->options(['ar' => __('admin.options.ar'), 'en' => __('admin.options.en')])
                                    ->required(),
                                TextInput::make('site_currency')->default('SAR'),
                                TextInput::make('site_currency_symbol')->default('ر.س'),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.seo'))
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                TranslatableField::text('seo_meta_title', __('admin.fields.title')),
                                TranslatableField::rich('seo_meta_description', __('admin.fields.description')),
                                FileUpload::make('seo_og_image')
                                    ->disk('s3')->directory('settings')->visibility('private')->image(),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.topbar'))
                            ->icon('heroicon-o-bars-3')
                            ->schema([
                                Section::make(__('admin.settings.topbar'))
                                    ->schema([
                                        TranslatableField::text('topbar_left_text', __('admin.fields.topbar_left_text')),
                                        TranslatableField::text('topbar_center_text', __('admin.fields.topbar_center_text')),
                                        TranslatableField::text('topbar_right_text', __('admin.fields.topbar_right_text')),
                                    ])
                                    ->columns(3),

                                // Social links hidden — navbar.blade.php is unused; links render in footer only.
                                // Section::make(__('admin.settings.navbar'))
                                //     ->schema([
                                //         Repeater::make('navbar_social_links')
                                //             ->label(__('admin.fields.social_links'))
                                //             ->schema([
                                //                 TextInput::make('label_ar')->label(__('admin.fields.label_ar')),
                                //                 TextInput::make('label_en')->label(__('admin.fields.label_en')),
                                //                 TextInput::make('url')->label(__('admin.fields.url'))->required(),
                                //                 Select::make('icon')
                                //                     ->label(__('admin.fields.icon'))
                                //                     ->options([
                                //                         'linkedin'  => __('admin.options.linkedin'),
                                //                         'x'         => __('admin.options.x'),
                                //                         'instagram' => __('admin.options.instagram'),
                                //                         'tiktok'    => __('admin.options.tiktok'),
                                //                         'facebook'  => __('admin.options.facebook'),
                                //                         'youtube'   => __('admin.options.youtube'),
                                //                         'whatsapp'  => __('admin.options.whatsapp'),
                                //                     ])
                                //                     ->required(),
                                //             ])
                                //             ->columns(2)
                                //             ->reorderable()
                                //             ->collapsed(),
                                //     ]),
                            ]),

                        Tab::make(__('admin.settings.contact'))
                            ->icon('heroicon-o-phone')
                            ->schema([
                                TextInput::make('contact_email')->email(),
                                TextInput::make('contact_phone'),
                                TextInput::make('whatsapp_phone')
                                    ->helperText(__('admin.settings.whatsapp_phone_hint')),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.reports'))
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make(__('admin.settings.completion_thresholds'))
                                    ->description(__('admin.settings.completion_thresholds_hint'))
                                    ->schema([
                                        Repeater::make('report_completion_thresholds')
                                            ->label(__('admin.fields.completion_thresholds'))
                                            ->schema([
                                                TextInput::make('min')
                                                    ->label(__('admin.fields.min_percent'))
                                                    ->numeric()
                                                    ->required()
                                                    ->suffix('%'),
                                                ColorPicker::make('color')
                                                    ->label(__('admin.fields.badge_color'))
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->reorderable()
                                            ->collapsed()
                                            ->defaultItems(0),
                                    ]),
                            ]),

                        Tab::make(__('admin.settings.footer'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TranslatableField::text('footer_address_label', __('admin.fields.address_label')),
                                TranslatableField::rich('footer_address_value', __('admin.fields.address')),
                                TranslatableField::text('footer_hours', __('admin.fields.hours')),
                                TranslatableField::text('footer_contact_label', __('admin.fields.contact_label')),
                                TranslatableField::text('footer_email', __('admin.fields.email')),
                                TranslatableField::text('footer_whatsapp', __('admin.fields.whatsapp')),
                                TranslatableField::text('footer_policies_label', __('admin.fields.policies_label')),
                                Repeater::make('footer_policy_links')
                                    ->label(__('admin.fields.policy_links'))
                                    ->schema([
                                        TextInput::make('label_ar')->label(__('admin.fields.label_ar'))->required(),
                                        TextInput::make('label_en')->label(__('admin.fields.label_en')),
                                        TextInput::make('url')->label(__('admin.fields.url'))->required(),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsed(),
                                Repeater::make('footer_certification_cards')
                                    ->label(__('admin.fields.footer_certification_cards'))
                                    ->schema([
                                        TextInput::make('label_ar')->label(__('admin.fields.label_ar'))->required(),
                                        TextInput::make('label_en')->label(__('admin.fields.label_en')),
                                        FileUpload::make('logo')
                                            ->label(__('admin.fields.logo'))
                                            ->disk('s3')
                                            ->directory('settings/footer')
                                            ->visibility('private')
                                            ->image(),
                                    ])
                                    ->columns(2)
                                    ->reorderable()
                                    ->collapsed(),
                            ])
                            ->columns(2),

                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $settings = app(SettingsService::class);

        $settings->updateFromForm($this->form->getState());
        $this->form->fill($settings->formData());

        Notification::make()
            ->title(__('admin.settings.saved'))
            ->success()
            ->send();
    }
}
