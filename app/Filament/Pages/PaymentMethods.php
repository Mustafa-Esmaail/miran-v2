<?php

namespace App\Filament\Pages;

use App\Services\Settings\SettingsService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class PaymentMethods extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.payment-methods';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($settings->formData());
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.payment_methods');
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
                Tabs::make('payment_methods_tabs')
                    ->tabs([

                        Tab::make(__('admin.settings.moyasar'))
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Toggle::make('payments_enable_moyasar')
                                    ->label(__('admin.fields.enable_moyasar'))
                                    ->columnSpanFull(),
                                FileUpload::make('payments_moyasar_logo')
                                    ->label(__('admin.fields.payments_moyasar_logo'))
                                    ->disk('s3')
                                    ->directory('settings/payments')
                                    ->visibility('private')
                                    ->image(),
                                // ->columnSpanFull(),
                                Select::make('payments_moyasar_mode')
                                    ->label(__('admin.fields.payments_moyasar_mode'))
                                    ->options([
                                        'test' => __('admin.options.test'),
                                        'live' => __('admin.options.live'),
                                    ])
                                    ->default('test'),
                                TextInput::make('payments_moyasar_publishable_key')
                                    ->label(__('admin.fields.payments_moyasar_publishable_key')),
                                TextInput::make('payments_moyasar_secret_key')
                                    ->label(__('admin.fields.payments_moyasar_secret_key'))
                                    ->password()
                                    ->revealable(),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.tamara'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Toggle::make('payments_enable_tamara')
                                    ->label(__('admin.fields.enable_tamara'))
                                    ->columnSpanFull(),
                                FileUpload::make('payments_tamara_logo')
                                    ->label(__('admin.fields.payments_tamara_logo'))
                                    ->disk('s3')
                                    ->directory('settings/payments')
                                    ->visibility('private')
                                    ->image(),
                                // ->columnSpanFull(),
                                Select::make('payments_tamara_mode')
                                    ->label(__('admin.fields.payments_tamara_mode'))
                                    ->options([
                                        'test' => __('admin.options.test'),
                                        'live' => __('admin.options.live'),
                                    ])
                                    ->default('test'),
                                TextInput::make('payments_tamara_api_key')
                                    ->label(__('admin.fields.payments_tamara_api_key'))
                                    ->password()
                                    ->revealable(),
                                TextInput::make('payments_tamara_init_api_key')
                                    ->label(__('admin.fields.payments_tamara_init_api_key'))
                                    ->password()
                                    ->revealable(),
                                Select::make('payments_tamara_payment_type')
                                    ->label(__('admin.fields.payments_tamara_payment_type'))
                                    ->options([
                                        ''                   => __('admin.options.auto'),
                                        'PAY_BY_LATER'       => __('admin.options.pay_by_later'),
                                        'PAY_BY_INSTALMENTS' => __('admin.options.pay_by_instalments'),
                                    ])
                                    ->default('PAY_BY_INSTALMENTS'),
                                TextInput::make('payments_tamara_instalments')
                                    ->label(__('admin.fields.payments_tamara_instalments'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(4)
                                    ->visible(fn(callable $get): bool => $get('payments_tamara_payment_type') === 'PAY_BY_INSTALMENTS'),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.tabby'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Toggle::make('payments_enable_tabby')
                                    ->label(__('admin.fields.enable_tabby'))
                                    ->columnSpanFull(),
                                FileUpload::make('payments_tabby_logo')
                                    ->label(__('admin.fields.payments_tabby_logo'))
                                    ->disk('s3')
                                    ->directory('settings/payments')
                                    ->visibility('private')
                                    ->image(),
                                // ->columnSpanFull(),,
                                TextInput::make('payments_tabby_base_url')
                                    ->label(__('admin.fields.payments_tabby_base_url')),
                                TextInput::make('payments_tabby_public_key')
                                    ->label(__('admin.fields.payments_tabby_public_key')),
                                TextInput::make('payments_tabby_secret_key')
                                    ->label(__('admin.fields.payments_tabby_secret_key'))
                                    ->password()
                                    ->revealable(),
                                TextInput::make('payments_tabby_webhook_secret')
                                    ->label(__('admin.fields.payments_tabby_webhook_secret'))
                                    ->password()
                                    ->revealable(),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.bank_transfer'))
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                Toggle::make('payments_enable_bank_transfer')
                                    ->label(__('admin.fields.enable_bank_transfer'))
                                    ->columnSpanFull(),
                                FileUpload::make('payments_bank_transfer_logo')
                                    ->label(__('admin.fields.payments_bank_transfer_logo'))
                                    ->disk('s3')
                                    ->directory('settings/payments')
                                    ->visibility('private')
                                    ->image(),
                                // ->columnSpanFull(),
                                TextInput::make('bank_name')
                                    ->label(__('admin.fields.bank_name')),
                                TextInput::make('bank_account_number')
                                    ->label(__('admin.fields.bank_account_number')),
                                TextInput::make('bank_iban')
                                    ->label(__('admin.fields.bank_iban')),
                            ])
                            ->columns(2),

                        Tab::make(__('admin.settings.cash'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Toggle::make('payments_enable_cash')
                                    ->label(__('admin.fields.enable_cash'))
                                    ->columnSpanFull(),
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
