<?php

namespace App\Filament\Pages;

use App\Services\Settings\MailSettingsApplier;
use App\Services\Settings\SettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Integrations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.integrations';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($settings->formData());
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.integrations');
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
                Tabs::make('integrations_tabs')
                    ->tabs([

                        Tab::make(__('admin.settings.mail'))
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Toggle::make('mail_smtp_enabled')
                                    ->label(__('admin.fields.mail_smtp_enabled'))
                                    ->columnSpanFull(),
                                TextInput::make('mail_smtp_host')
                                    ->label(__('admin.fields.mail_smtp_host')),
                                TextInput::make('mail_smtp_port')
                                    ->label(__('admin.fields.mail_smtp_port'))
                                    ->numeric(),
                                TextInput::make('mail_smtp_username')
                                    ->label(__('admin.fields.mail_smtp_username')),
                                TextInput::make('mail_smtp_password')
                                    ->label(__('admin.fields.mail_smtp_password'))
                                    ->password()
                                    ->revealable(),
                                Select::make('mail_smtp_encryption')
                                    ->label(__('admin.fields.mail_smtp_encryption'))
                                    ->options([
                                        '' => __('admin.options.none'),
                                        'tls' => 'TLS',
                                        'ssl' => 'SSL',
                                    ]),
                                TextInput::make('mail_from_address')
                                    ->label(__('admin.fields.mail_from_address'))
                                    ->email(),
                                TextInput::make('mail_from_name')
                                    ->label(__('admin.fields.mail_from_name')),
                                TextInput::make('mail_training_video_url')
                                    ->label(__('admin.fields.mail_training_video_url'))
                                    ->url(),
                                TextInput::make('mail_certificate_form_url')
                                    ->label(__('admin.fields.mail_certificate_form_url'))
                                    ->url(),
                                SchemaActions::make([
                                    Action::make('saveMail')
                                        ->label(__('admin.settings.save'))
                                        ->action('saveMail'),
                                ])->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.zoom'))
                            ->icon('heroicon-o-video-camera')
                            ->schema([
                                TextInput::make('zoom_account_id')
                                    ->label(__('admin.fields.zoom_account_id')),
                                TextInput::make('zoom_client_id')
                                    ->label(__('admin.fields.zoom_client_id')),
                                TextInput::make('zoom_client_secret')
                                    ->label(__('admin.fields.zoom_client_secret'))
                                    ->password()
                                    ->revealable(),
                                TextInput::make('zoom_webhook_secret')
                                    ->label(__('admin.fields.zoom_webhook_secret'))
                                    ->password()
                                    ->revealable(),
                                SchemaActions::make([
                                    Action::make('saveZoom')
                                        ->label(__('admin.settings.save'))
                                        ->action('saveZoom'),
                                ])->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.google_sheets'))
                            ->icon('heroicon-o-table-cells')
                            ->schema([
                                TextInput::make('google_sheets_credentials_path')
                                    ->label(__('admin.fields.google_sheets_credentials_path'))
                                    ->helperText(__('admin.helpers.google_sheets_credentials_path'))
                                    ->placeholder('/var/www/storage/app/google-service-account.json')
                                    ->columnSpanFull(),
                                Toggle::make('orders_sync_to_sheets')
                                    ->label(__('admin.labels.orders_sync_to_sheets'))
                                    ->live()
                                    ->columnSpanFull(),
                                TextInput::make('orders_sheet_id')
                                    ->label(__('admin.labels.orders_sheet_id'))
                                    ->helperText(__('admin.helpers.orders_sheet_id'))
                                    ->visible(fn ($get) => (bool) $get('orders_sync_to_sheets'))
                                    ->required(fn ($get) => (bool) $get('orders_sync_to_sheets'))
                                    ->columnSpanFull(),
                                SchemaActions::make([
                                    Action::make('saveGoogleSheets')
                                        ->label(__('admin.settings.save'))
                                        ->action('saveGoogleSheets'),
                                ])->columnSpanFull(),
                            ])
                            ->columns(1),

                        Tab::make(__('admin.settings.gtm'))
                            ->icon('heroicon-o-tag')
                            ->schema([
                                TextInput::make('gtm_container_id')
                                    ->label(__('admin.fields.gtm_container_id'))
                                    ->placeholder('GTM-XXXXXXX')
                                    ->columnSpanFull(),
                                SchemaActions::make([
                                    Action::make('saveGtm')
                                        ->label(__('admin.settings.save'))
                                        ->action('saveGtm'),
                                ])->columnSpanFull(),
                            ])
                            ->columns(1),

                        Tab::make(__('admin.settings.turnstile'))
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Toggle::make('turnstile_enabled')
                                    ->label(__('admin.labels.turnstile_enabled'))
                                    ->helperText(__('admin.helpers.turnstile_enabled'))
                                    ->live()
                                    ->columnSpanFull(),
                                TextInput::make('turnstile_site_key')
                                    ->label(__('admin.labels.turnstile_site_key'))
                                    ->helperText(__('admin.helpers.turnstile_site_key'))
                                    ->visible(fn ($get) => (bool) $get('turnstile_enabled'))
                                    ->required(fn ($get) => (bool) $get('turnstile_enabled')),
                                TextInput::make('turnstile_secret_key')
                                    ->label(__('admin.labels.turnstile_secret_key'))
                                    ->helperText(__('admin.helpers.turnstile_secret_key'))
                                    ->password()
                                    ->revealable()
                                    ->visible(fn ($get) => (bool) $get('turnstile_enabled'))
                                    ->required(fn ($get) => (bool) $get('turnstile_enabled')),
                                SchemaActions::make([
                                    Action::make('saveTurnstile')
                                        ->label(__('admin.settings.save'))
                                        ->action('saveTurnstile'),
                                ])->columnSpanFull(),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.nelc'))
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Toggle::make('nelc_enabled')
                                    ->label(__('admin.fields.nelc_enabled'))
                                    ->columnSpanFull(),
                                TextInput::make('nelc_lrs_endpoint')
                                    ->label(__('admin.fields.nelc_lrs_endpoint'))
                                    ->url()
                                    ->columnSpanFull(),
                                TextInput::make('nelc_lrs_username')
                                    ->label(__('admin.fields.nelc_lrs_username')),
                                TextInput::make('nelc_lrs_password')
                                    ->label(__('admin.fields.nelc_lrs_password'))
                                    ->password()
                                    ->revealable(),
                                TextInput::make('nelc_platform_ar')
                                    ->label(__('admin.fields.nelc_platform_ar')),
                                TextInput::make('nelc_platform_en')
                                    ->label(__('admin.fields.nelc_platform_en')),
                                SchemaActions::make([
                                    Action::make('saveNelc')
                                        ->label(__('admin.settings.save'))
                                        ->action('saveNelc'),
                                ])->columnSpanFull(),
                            ])
                            ->columns(2),

                    ]),
            ])
            ->statePath('data');
    }

    public function saveMail(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);
        $mailSettingsApplier = app(MailSettingsApplier::class);

        $settings->updateFromForm(array_intersect_key($data, array_flip([
            'mail_smtp_enabled', 'mail_smtp_host', 'mail_smtp_port',
            'mail_smtp_username', 'mail_smtp_password', 'mail_smtp_encryption',
            'mail_from_address', 'mail_from_name',
            'mail_training_video_url', 'mail_certificate_form_url',
        ])));
        $mailSettingsApplier->apply();

        Notification::make()->title(__('admin.settings.saved'))->success()->send();
    }

    public function saveZoom(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        $settings->updateFromForm(array_intersect_key($data, array_flip([
            'zoom_account_id', 'zoom_client_id', 'zoom_client_secret', 'zoom_webhook_secret',
        ])));

        Notification::make()->title(__('admin.settings.saved'))->success()->send();
    }

    public function saveGoogleSheets(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        $settings->updateFromForm(array_intersect_key($data, array_flip([
            'google_sheets_credentials_path', 'orders_sync_to_sheets', 'orders_sheet_id',
        ])));

        Notification::make()->title(__('admin.settings.saved'))->success()->send();
    }

    public function saveGtm(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        $settings->updateFromForm(array_intersect_key($data, array_flip([
            'gtm_container_id',
        ])));

        Notification::make()->title(__('admin.settings.saved'))->success()->send();
    }

    public function saveNelc(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        $settings->updateFromForm(array_intersect_key($data, array_flip([
            'nelc_enabled', 'nelc_lrs_endpoint', 'nelc_lrs_username', 'nelc_lrs_password',
            'nelc_platform_ar', 'nelc_platform_en',
        ])));

        Notification::make()->title(__('admin.settings.saved'))->success()->send();
    }

    public function saveTurnstile(): void
    {
        abort_unless(auth('admin')->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        $settings->updateFromForm(array_intersect_key($data, array_flip([
            'turnstile_enabled', 'turnstile_site_key', 'turnstile_secret_key',
        ])));

        Notification::make()->title(__('admin.settings.saved'))->success()->send();
    }
}
