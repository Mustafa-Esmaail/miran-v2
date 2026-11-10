<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Support\S3Url;
use Filament\Support\Colors\Color;

class SettingsService
{
    /**
     * Colors stored before the completion-threshold color field became a free
     * hex ColorPicker. Kept so settings saved under the old named palette
     * (blue/sky/green/amber/red) keep resolving to the same visual color.
     */
    private const COMPLETION_LEGACY_HEX_COLORS = [
        'blue' => '#3b82f6',
        'sky' => '#0ea5e9',
        'green' => '#22c55e',
        'amber' => '#f59e0b',
        'red' => '#ef4444',
    ];

    private const DEFAULT_COMPLETION_THRESHOLDS = [
        ['min' => 18, 'color' => '#3b82f6'],
        ['min' => 17, 'color' => '#0ea5e9'],
        ['min' => 16, 'color' => '#22c55e'],
        ['min' => 14, 'color' => '#f59e0b'],
        ['min' => 0, 'color' => '#ef4444'],
    ];

    private const FIELD_MAP = [
        'site_title' => ['key' => 'site.title', 'group' => 'site', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 1],
        'site_url' => ['key' => 'site.url', 'group' => 'site', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 2],
        'site_logo' => ['key' => 'site.logo', 'group' => 'site', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 2],
        'site_favicon' => ['key' => 'site.favicon', 'group' => 'site', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 3],
        'site_default_locale' => ['key' => 'site.default_locale', 'group' => 'site', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 4],
        'site_currency' => ['key' => 'site.currency', 'group' => 'site', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 5],
        'site_currency_symbol' => ['key' => 'site.currency_symbol', 'group' => 'site', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 6],
        'site_enable_payments' => ['key' => 'site.enable_payments', 'group' => 'site', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 7],
        'seo_meta_title' => ['key' => 'seo.meta_title', 'group' => 'seo', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 10],
        'seo_meta_description' => ['key' => 'seo.meta_description', 'group' => 'seo', 'type' => 'text', 'is_translatable' => true, 'sort_order' => 11],
        'seo_og_image' => ['key' => 'seo.og_image', 'group' => 'seo', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 12],
        'topbar_left_text' => ['key' => 'topbar.left_text', 'group' => 'topbar', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 13],
        'topbar_center_text' => ['key' => 'topbar.center_text', 'group' => 'topbar', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 14],
        'topbar_right_text' => ['key' => 'topbar.right_text', 'group' => 'topbar', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 15],
        'navbar_social_links' => ['key' => 'navbar.social_links', 'group' => 'navbar', 'type' => 'json', 'is_translatable' => false, 'sort_order' => 19],
        'payments_enable_tamara' => ['key' => 'payments.enable_tamara', 'group' => 'payments', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 20],
        'payments_enable_tabby' => ['key' => 'payments.enable_tabby', 'group' => 'payments', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 21],
        'payments_enable_bank_transfer' => ['key' => 'payments.enable_bank_transfer', 'group' => 'payments', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 22],
        'payments_enable_cash' => ['key' => 'payments.enable_cash', 'group' => 'payments', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 23],
        'payments_tamara_mode' => ['key' => 'payments.tamara.mode', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 23],
        'payments_tamara_api_key' => ['key' => 'payments.tamara.api_key', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 24],
        'payments_tamara_init_api_key' => ['key' => 'payments.tamara.init_api_key', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 25],
        'payments_tamara_payment_type' => ['key' => 'payments.tamara.payment_type', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 26],
        'payments_tamara_instalments' => ['key' => 'payments.tamara.instalments', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 27],
        'payments_tabby_base_url' => ['key' => 'payments.tabby.base_url', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 28],
        'payments_tabby_public_key' => ['key' => 'payments.tabby.public_key', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 29],
        'payments_tabby_secret_key' => ['key' => 'payments.tabby.secret_key', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 30],
        'payments_tabby_webhook_secret' => ['key' => 'payments.tabby.webhook_secret', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 31],
        'payments_logo_visa' => ['key' => 'payments.logo_visa', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 32],
        'payments_logo_mastercard' => ['key' => 'payments.logo_mastercard', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 33],
        'payments_logo_mada' => ['key' => 'payments.logo_mada', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 34],
        'payments_logo_apple_pay' => ['key' => 'payments.logo_apple_pay', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 35],
        'payments_enable_moyasar' => ['key' => 'payments.enable_moyasar', 'group' => 'payments', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 36],
        'payments_moyasar_mode' => ['key' => 'payments.moyasar.mode', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 37],
        'payments_moyasar_publishable_key' => ['key' => 'payments.moyasar.publishable_key', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 38],
        'payments_moyasar_secret_key' => ['key' => 'payments.moyasar.secret_key', 'group' => 'payments', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 39],
        'payments_moyasar_logo' => ['key' => 'payments.moyasar.logo', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 40],
        'payments_tamara_logo' => ['key' => 'payments.tamara.logo', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 41],
        'payments_tabby_logo' => ['key' => 'payments.tabby.logo', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 42],
        'payments_bank_transfer_logo' => ['key' => 'payments.bank_transfer.logo', 'group' => 'payments', 'type' => 'image', 'is_translatable' => false, 'sort_order' => 43],
        'bank_name' => ['key' => 'bank.name', 'group' => 'bank', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 40],
        'bank_account_number' => ['key' => 'bank.account_number', 'group' => 'bank', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 41],
        'bank_iban' => ['key' => 'bank.iban', 'group' => 'bank', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 42],
        'contact_email' => ['key' => 'contact.email', 'group' => 'contact', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 50],
        'contact_phone' => ['key' => 'contact.phone', 'group' => 'contact', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 51],
        'whatsapp_phone' => ['key' => 'contact.whatsapp_phone', 'group' => 'contact', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 52],
        'mail_smtp_enabled' => ['key' => 'mail.smtp.enabled', 'group' => 'mail', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 60],
        'mail_smtp_host' => ['key' => 'mail.smtp.host', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 61],
        'mail_smtp_port' => ['key' => 'mail.smtp.port', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 62],
        'mail_smtp_username' => ['key' => 'mail.smtp.username', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 63],
        'mail_smtp_password' => ['key' => 'mail.smtp.password', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 64],
        'mail_smtp_encryption' => ['key' => 'mail.smtp.encryption', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 65],
        'mail_from_address' => ['key' => 'mail.from.address', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 66],
        'mail_from_name' => ['key' => 'mail.from.name', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 67],
        'mail_training_video_url' => ['key' => 'mail.training_video_url', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 68],
        'mail_certificate_form_url' => ['key' => 'mail.certificate_form_url', 'group' => 'mail', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 69],
        'google_sheets_credentials_path' => ['key' => 'google_sheets.credentials_path', 'group' => 'google_sheets', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 68],
        'orders_sync_to_sheets' => ['key' => 'orders.sync_to_sheets', 'group' => 'google_sheets', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 74],
        'orders_sheet_id' => ['key' => 'orders.sheet_id', 'group' => 'google_sheets', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 75],
        'gtm_container_id' => ['key' => 'gtm.container_id', 'group' => 'gtm', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 69],
        'zoom_account_id' => ['key' => 'zoom.account_id', 'group' => 'zoom', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 70],
        'zoom_client_id' => ['key' => 'zoom.client_id', 'group' => 'zoom', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 71],
        'zoom_client_secret' => ['key' => 'zoom.client_secret', 'group' => 'zoom', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 72],
        'zoom_webhook_secret' => ['key' => 'zoom.webhook_secret', 'group' => 'zoom', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 73],
        'turnstile_enabled' => ['key' => 'turnstile.enabled', 'group' => 'turnstile', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 76],
        'turnstile_site_key' => ['key' => 'turnstile.site_key', 'group' => 'turnstile', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 77],
        'turnstile_secret_key' => ['key' => 'turnstile.secret_key', 'group' => 'turnstile', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 78],
        'nelc_enabled' => ['key' => 'nelc.enabled', 'group' => 'nelc', 'type' => 'boolean', 'is_translatable' => false, 'sort_order' => 80],
        'nelc_lrs_endpoint' => ['key' => 'nelc.lrs_endpoint', 'group' => 'nelc', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 81],
        'nelc_lrs_username' => ['key' => 'nelc.lrs_username', 'group' => 'nelc', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 82],
        'nelc_lrs_password' => ['key' => 'nelc.lrs_password', 'group' => 'nelc', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 83],
        'nelc_platform_ar' => ['key' => 'nelc.platform_ar', 'group' => 'nelc', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 84],
        'nelc_platform_en' => ['key' => 'nelc.platform_en', 'group' => 'nelc', 'type' => 'string', 'is_translatable' => false, 'sort_order' => 85],
        // 'footer_address_label' => ['key' => 'footer.address_label', 'group' => 'footer', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 150],
        'footer_address_value' => ['key' => 'footer.address_value', 'group' => 'footer', 'type' => 'text', 'is_translatable' => true, 'sort_order' => 151],
        'footer_hours' => ['key' => 'footer.hours', 'group' => 'footer', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 152],
        'footer_contact_label' => ['key' => 'footer.contact_label', 'group' => 'footer', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 153],
        'footer_email' => ['key' => 'footer.email', 'group' => 'footer', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 154],
        'footer_whatsapp' => ['key' => 'footer.whatsapp', 'group' => 'footer', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 155],
        'footer_policies_label' => ['key' => 'footer.policies_label', 'group' => 'footer', 'type' => 'string', 'is_translatable' => true, 'sort_order' => 156],
        'footer_policy_links' => ['key' => 'footer.policy_links', 'group' => 'footer', 'type' => 'json', 'is_translatable' => false, 'sort_order' => 157],
        'footer_certification_cards' => ['key' => 'footer.certification_cards', 'group' => 'footer', 'type' => 'json', 'is_translatable' => false, 'sort_order' => 158],
        'report_completion_thresholds' => ['key' => 'reports.completion_thresholds', 'group' => 'reports', 'type' => 'json', 'is_translatable' => false, 'sort_order' => 160],
    ];

    public function __construct(private readonly SettingsRepositoryInterface $settings) {}

    private static ?array $requestCache = null;

    public function all(): array
    {
        if (self::$requestCache !== null) {
            return self::$requestCache;
        }

        self::$requestCache = $this->settings->all()
            ->keyBy('key')
            ->map(fn ($setting) => $setting->toArray())
            ->all();

        return self::$requestCache;
    }

    public function formData(): array
    {
        $defaults = [
            'mail_smtp_enabled' => false,
            'mail_smtp_host' => (string) config('mail.mailers.smtp.host'),
            'mail_smtp_port' => (string) config('mail.mailers.smtp.port'),
            'mail_smtp_username' => (string) (config('mail.mailers.smtp.username') ?? ''),
            'mail_smtp_password' => (string) (config('mail.mailers.smtp.password') ?? ''),
            'mail_smtp_encryption' => (string) (config('mail.mailers.smtp.scheme') ?? ''),
            'mail_from_address' => (string) config('mail.from.address'),
            'mail_from_name' => (string) config('mail.from.name'),
            'payments_tamara_payment_type' => (string) config('services.tamara.payment_type', ''),
            'payments_tamara_instalments' => (string) (config('services.tamara.instalments', 0) ?: ''),
            'mail_training_video_url' => 'https://drive.google.com/file/d/1G65bq0ZPudDTTG24_fAs2FKfWmccHvdS/view?usp=drive_link',
            'mail_certificate_form_url' => 'https://forms.gle/oTsTjJ8Ebqk9xJgz6',
            'zoom_account_id' => (string) config('services.zoom.account_id', ''),
            'zoom_client_id' => (string) config('services.zoom.client_id', ''),
            'zoom_client_secret' => (string) config('services.zoom.client_secret', ''),
            'zoom_webhook_secret' => (string) config('services.zoom.webhook_secret', ''),
            'turnstile_enabled' => false,
            'turnstile_site_key' => (string) config('services.turnstile.site_key', ''),
            'turnstile_secret_key' => (string) config('services.turnstile.secret_key', ''),
            'gtm_container_id' => 'GTM-55QMTJQ',
            'nelc_enabled' => (bool) config('services.nelc.enabled', false),
            'nelc_lrs_endpoint' => (string) config('services.nelc.lrs_endpoint', ''),
            'nelc_lrs_username' => (string) config('services.nelc.lrs_username', ''),
            'nelc_lrs_password' => (string) config('services.nelc.lrs_password', ''),
            'nelc_platform_ar' => (string) config('services.nelc.platform_ar', ''),
            'nelc_platform_en' => (string) config('services.nelc.platform_en', ''),
            'report_completion_thresholds' => self::DEFAULT_COMPLETION_THRESHOLDS,
            'site_url' => rtrim((string) config('app.url'), '/'),
        ];

        $data = [];
        foreach (self::FIELD_MAP as $field => $meta) {
            $key = $meta['key'];
            $value = $this->getValue($key, $defaults[$field] ?? null);

            if ($meta['is_translatable']) {
                $data[$field] = $this->normalizeTranslatable($value);
            } else {
                $data[$field] = $value;
            }
        }

        return $data;
    }

    public function updateFromForm(array $data): void
    {
        $rows = [];

        foreach (self::FIELD_MAP as $field => $meta) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            $rows[] = [
                'key' => $meta['key'],
                'group' => $meta['group'],
                'type' => $meta['type'],
                'value' => $this->encodeValue($value, $meta['is_translatable']),
                'is_translatable' => $meta['is_translatable'],
                'sort_order' => $meta['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->settings->upsert($rows);
        self::$requestCache = null;
    }

    public function clearCache(): void
    {
        self::$requestCache = null;
    }

    public function siteTitle(string $locale): string
    {
        $title = $this->getTranslatable('site.title', $locale);

        return $title !== '' ? $title : config('app.name');
    }

    /**
     * The canonical site URL, configurable from Settings > Site so it doesn't
     * depend on the runtime APP_URL env value (e.g. inside queue workers).
     */
    public function siteUrl(): string
    {
        $url = trim((string) $this->getValue('site.url', config('app.url')));

        return $url !== '' ? rtrim($url, '/') : rtrim((string) config('app.url'), '/');
    }

    public function logoUrl(): ?string
    {
        $path = $this->getValue('site.logo');

        return $path ? S3Url::temporary($path) : null;
    }

    public function faviconUrl(): ?string
    {
        $path = $this->getValue('site.favicon');

        return $path ? S3Url::temporary($path) : null;
    }

    public function ogImageUrl(): ?string
    {
        $path = $this->getValue('seo.og_image');

        return $path ? S3Url::temporary($path) : null;
    }

    public function getValue(string $key, $default = null)
    {
        $settings = $this->all();

        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        $value = $settings[$key]['value'] ?? null;

        return $value ?? $default;
    }

    public function getTranslatable(string $key, string $locale, string $default = ''): string
    {
        $value = $this->getValue($key);

        if (is_array($value)) {
            return $value[$locale] ?? $value[array_key_first($value)] ?? $default;
        }

        if (is_string($value)) {
            return $value;
        }

        return $default;
    }

    public function defaultLocale(): string
    {
        return (string) $this->getValue('site.default_locale', 'ar');
    }

    public function contactPhone(): string
    {
        return trim((string) $this->getValue('contact.phone', ''));
    }

    /**
     * WhatsApp number for the floating contact button, digits only
     * (international format, no leading +), falling back to the general
     * contact phone when no dedicated WhatsApp number is configured.
     */
    public function whatsappPhone(): string
    {
        $whatsapp = trim((string) $this->getValue('contact.whatsapp_phone', ''));
        $number = $whatsapp !== '' ? $whatsapp : $this->contactPhone();

        return preg_replace('/\D/', '', $number) ?? '';
    }

    public function paymentsEnabled(): bool
    {
        return (bool) $this->getValue('site.enable_payments', true);
    }

    public function paymentMethodLogos(): array
    {
        $items = [
            ['label' => 'VISA', 'key' => 'payments.logo_visa'],
            ['label' => 'MasterCard', 'key' => 'payments.logo_mastercard'],
            ['label' => 'mada', 'key' => 'payments.logo_mada'],
            ['label' => 'Apple Pay', 'key' => 'payments.logo_apple_pay'],
        ];

        return collect($items)
            ->map(function (array $item): array {
                $path = $this->getValue($item['key']);
                $logo = is_string($path) ? S3Url::temporary($path) : null;

                return [
                    'label' => $item['label'],
                    'logo' => $logo,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Returns enabled payment providers with their logo URL and label.
     * Used by checkout and course-show pages to render payment method badges.
     *
     * @return array<int, array{key: string, label: string, logo: string|null}>
     */
    public function enabledPaymentMethods(): array
    {
        $methods = [
            [
                'key' => 'moyasar',
                'label' => 'مويسر',
                'enabled_key' => 'payments.enable_moyasar',
                'logo_key' => 'payments.moyasar.logo',
            ],
            [
                'key' => 'tamara',
                'label' => 'تمارا',
                'enabled_key' => 'payments.enable_tamara',
                'logo_key' => 'payments.tamara.logo',
            ],
            [
                'key' => 'tabby',
                'label' => 'تابي',
                'enabled_key' => 'payments.enable_tabby',
                'logo_key' => 'payments.tabby.logo',
            ],
            [
                'key' => 'bank_transfer',
                'label' => 'تحويل بنكي',
                'enabled_key' => 'payments.enable_bank_transfer',
                'logo_key' => 'payments.bank_transfer.logo',
            ],
        ];

        return collect($methods)
            ->filter(fn (array $m): bool => (bool) $this->getValue($m['enabled_key'], false))
            ->map(function (array $m): array {
                $path = $this->getValue($m['logo_key']);
                $logo = is_string($path) && $path !== '' ? S3Url::temporary($path) : null;

                return [
                    'key' => $m['key'],
                    'label' => $m['label'],
                    'logo' => $logo,
                ];
            })
            ->values()
            ->all();
    }

    public function paymentProviderIsReady(string $provider): bool
    {
        $provider = strtolower(trim($provider));
        if (! in_array($provider, ['tamara', 'tabby', 'moyasar'], true)) {
            return false;
        }

        $enabled = (bool) $this->getValue('payments.enable_'.$provider, false);
        if (! $enabled) {
            return false;
        }

        if ($provider === 'moyasar') {
            $secretKey = trim((string) $this->getValue('payments.moyasar.secret_key', ''));

            return $secretKey !== '';
        }

        if ($provider === 'tamara') {
            $apiKey = trim((string) $this->getValue('payments.tamara.api_key', config('services.tamara.secret_key', '')));
            $initApiKey = trim((string) $this->getValue('payments.tamara.init_api_key', config('services.tamara.init_api_key', '')));

            return $apiKey !== '' || $initApiKey !== '';
        }

        $secret = trim((string) $this->getValue('payments.'.$provider.'.secret_key', ''));

        return $secret !== '';
    }

    public function googleSheetsCredentialsPath(): string
    {
        $path = (string) $this->getValue('google_sheets.credentials_path', 'storage/app/miran-sheet-216bde447aac.json');

        if ($path !== '' && ! str_starts_with($path, '/') && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        return $path;
    }

    public function googleSheetsIsReady(): bool
    {
        $path = $this->googleSheetsCredentialsPath();

        return $path !== '' && file_exists($path);
    }

    public function ordersSyncToSheetsEnabled(): bool
    {
        return (bool) $this->getValue('orders.sync_to_sheets', false);
    }

    public function ordersSheetId(): string
    {
        return trim((string) $this->getValue('orders.sheet_id', ''));
    }

    public function zoomConfig(): array
    {
        return [
            'account_id' => (string) $this->getValue('zoom.account_id', config('services.zoom.account_id', '')),
            'client_id' => (string) $this->getValue('zoom.client_id', config('services.zoom.client_id', '')),
            'client_secret' => (string) $this->getValue('zoom.client_secret', config('services.zoom.client_secret', '')),
            'webhook_secret' => (string) $this->getValue('zoom.webhook_secret', config('services.zoom.webhook_secret', '')),
        ];
    }

    public function zoomIsReady(): bool
    {
        $config = $this->zoomConfig();

        return trim($config['account_id']) !== ''
            && trim($config['client_id']) !== ''
            && trim($config['client_secret']) !== '';
    }

    public function turnstileConfig(): array
    {
        return [
            'enabled' => (bool) $this->getValue('turnstile.enabled', false),
            'site_key' => (string) $this->getValue('turnstile.site_key', config('services.turnstile.site_key', '')),
            'secret_key' => (string) $this->getValue('turnstile.secret_key', config('services.turnstile.secret_key', '')),
        ];
    }

    public function turnstileIsReady(): bool
    {
        $config = $this->turnstileConfig();

        return $config['enabled']
            && trim($config['site_key']) !== ''
            && trim($config['secret_key']) !== '';
    }

    /**
     * @return array{enabled:bool,lrs_endpoint:string,lrs_username:string,lrs_password:string,platform_ar:string,platform_en:string}
     */
    public function nelcConfig(): array
    {
        return [
            'enabled' => (bool) $this->getValue('nelc.enabled', config('services.nelc.enabled', false)),
            'lrs_endpoint' => (string) $this->getValue('nelc.lrs_endpoint', config('services.nelc.lrs_endpoint', '')),
            'lrs_username' => (string) $this->getValue('nelc.lrs_username', config('services.nelc.lrs_username', '')),
            'lrs_password' => (string) $this->getValue('nelc.lrs_password', config('services.nelc.lrs_password', '')),
            'platform_ar' => (string) $this->getValue('nelc.platform_ar', config('services.nelc.platform_ar', '')),
            'platform_en' => (string) $this->getValue('nelc.platform_en', config('services.nelc.platform_en', '')),
        ];
    }

    /**
     * @return array{enabled:bool,mode?:string,api_key?:string,init_api_key?:string,payment_type?:string,instalments?:int,base_url:string,public_key:string,secret_key:string,webhook_secret:string}
     */
    public function paymentProviderConfig(string $provider): array
    {
        $provider = strtolower(trim($provider));

        if ($provider === 'moyasar') {
            return [
                'enabled' => (bool) $this->getValue('payments.enable_moyasar', false),
                'mode' => (string) $this->getValue('payments.moyasar.mode', 'test'),
                'publishable_key' => (string) $this->getValue('payments.moyasar.publishable_key', ''),
                'secret_key' => (string) $this->getValue('payments.moyasar.secret_key', ''),
                'base_url' => '',
                'public_key' => '',
                'webhook_secret' => '',
            ];
        }

        if ($provider === 'tamara') {
            return [
                'enabled' => (bool) $this->getValue('payments.enable_tamara', false),
                'mode' => (string) $this->getValue('payments.tamara.mode', 'test'),
                'api_key' => (string) $this->getValue('payments.tamara.api_key', config('services.tamara.secret_key', '')),
                'init_api_key' => (string) $this->getValue('payments.tamara.init_api_key', config('services.tamara.init_api_key', '')),
                'payment_type' => (string) $this->getValue('payments.tamara.payment_type', config('services.tamara.payment_type', '')),
                'instalments' => (int) $this->getValue('payments.tamara.instalments', config('services.tamara.instalments', 0)),
                'base_url' => '',
                'public_key' => '',
                'secret_key' => '',
                'webhook_secret' => '',
            ];
        }

        return [
            'enabled' => (bool) $this->getValue('payments.enable_'.$provider, false),
            'base_url' => (string) $this->getValue('payments.'.$provider.'.base_url', ''),
            'public_key' => (string) $this->getValue('payments.'.$provider.'.public_key', ''),
            'secret_key' => (string) $this->getValue('payments.'.$provider.'.secret_key', ''),
            'webhook_secret' => (string) $this->getValue('payments.'.$provider.'.webhook_secret', ''),
        ];
    }

    /**
     * @return array{min: float, color: string}
     */
    public function completionThresholds(): array
    {
        $thresholds = $this->getValue('reports.completion_thresholds', self::DEFAULT_COMPLETION_THRESHOLDS);

        if (! is_array($thresholds) || $thresholds === []) {
            $thresholds = self::DEFAULT_COMPLETION_THRESHOLDS;
        }

        return collect($thresholds)
            ->filter(fn ($tier): bool => is_array($tier) && isset($tier['min'], $tier['color']))
            ->map(fn (array $tier): array => ['min' => (float) $tier['min'], 'color' => (string) $tier['color']])
            ->sortByDesc('min')
            ->values()
            ->all();
    }

    private function matchCompletionTier(?float $percent): ?string
    {
        if ($percent === null) {
            return null;
        }

        foreach ($this->completionThresholds() as $tier) {
            if ($percent >= $tier['min']) {
                return $tier['color'];
            }
        }

        return null;
    }

    /**
     * Resolves a completion percentage to the Tailwind shade palette
     * Filament's table column ->color() accepts, based on the configurable
     * thresholds and the admin-picked hex color.
     *
     * @return array<int, string>|string
     */
    public function completionColorForFilament(?float $percent): array|string
    {
        $color = $this->matchCompletionTier($percent);

        return $color !== null ? Color::hex($this->resolveHexColor($color)) : 'gray';
    }

    /**
     * Resolves a completion percentage to an inline CSS style, for use
     * outside Filament table columns (e.g. a plain Blade footer badge).
     */
    public function completionBadgeStyle(?float $percent): string
    {
        $color = $this->matchCompletionTier($percent);

        if ($color === null) {
            return 'background-color:#f3f4f6;color:#6b7280;';
        }

        $hex = $this->resolveHexColor($color);
        $textColor = Color::isLight($hex) ? '#111827' : '#ffffff';

        return "background-color:{$hex};color:{$textColor};";
    }

    private function resolveHexColor(string $color): string
    {
        return self::COMPLETION_LEGACY_HEX_COLORS[$color] ?? $color;
    }

    private function normalizeTranslatable($value): array
    {
        if (is_array($value)) {
            return [
                'ar' => $value['ar'] ?? '',
                'en' => $value['en'] ?? '',
            ];
        }

        if (is_string($value)) {
            return [
                'ar' => $value,
                'en' => $value,
            ];
        }

        return ['ar' => '', 'en' => ''];
    }

    private function encodeValue($value, bool $translatable): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($translatable) {
            $value = $this->normalizeTranslatable($value);
        }

        return json_encode($value);
    }
}
