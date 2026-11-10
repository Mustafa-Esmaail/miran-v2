<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Repositories\Contracts\SettingsRepositoryInterface;
use App\Services\Settings\SettingsService;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $requestCache = new \ReflectionProperty(SettingsService::class, 'requestCache');
        $requestCache->setAccessible(true);
        $requestCache->setValue(null);
    }

    protected function tearDown(): void
    {
        $requestCache = new \ReflectionProperty(SettingsService::class, 'requestCache');
        $requestCache->setAccessible(true);
        $requestCache->setValue(null);

        parent::tearDown();
    }

    public function test_all_uses_request_cache(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([
            'site.title' => ['value' => 'Miran'],
        ]);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $service = new SettingsService($repo);

        $this->assertSame('Miran', $service->getValue('site.title'));
        $this->assertSame('Miran', $service->getValue('site.title'));
    }

    public function test_payment_method_logos_are_read_from_settings_values(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([
            'payments.logo_visa' => ['value' => 'https://cdn.example.com/visa.png'],
            'payments.logo_mastercard' => ['value' => 'https://cdn.example.com/mastercard.png'],
            'payments.logo_mada' => ['value' => null],
            'payments.logo_apple_pay' => ['value' => 'https://cdn.example.com/apple-pay.png'],
        ]);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $service = new SettingsService($repo);

        $logos = $service->paymentMethodLogos();

        $this->assertCount(4, $logos);
        $this->assertSame('VISA', $logos[0]['label']);
        $this->assertSame('https://cdn.example.com/visa.png', $logos[0]['logo']);
        $this->assertSame('MasterCard', $logos[1]['label']);
        $this->assertSame('https://cdn.example.com/mastercard.png', $logos[1]['logo']);
        $this->assertSame('mada', $logos[2]['label']);
        $this->assertNull($logos[2]['logo']);
        $this->assertSame('Apple Pay', $logos[3]['label']);
        $this->assertSame('https://cdn.example.com/apple-pay.png', $logos[3]['logo']);
    }

    public function test_payment_provider_config_and_readiness_are_read_from_settings(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([
            'payments.enable_tamara' => ['value' => true],
            'payments.tamara.mode' => ['value' => 'test'],
            'payments.tamara.api_key' => ['value' => 'tamara_api_key'],
            'payments.tamara.init_api_key' => ['value' => 'tamara_init_key'],
            'payments.tamara.payment_type' => ['value' => 'PAY_BY_LATER'],
            'payments.tamara.instalments' => ['value' => 0],
        ]);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $service = new SettingsService($repo);

        $config = $service->paymentProviderConfig('tamara');

        $this->assertTrue($service->paymentProviderIsReady('tamara'));
        $this->assertSame('test', $config['mode']);
        $this->assertSame('tamara_api_key', $config['api_key']);
        $this->assertSame('tamara_init_key', $config['init_api_key']);
        $this->assertSame('PAY_BY_LATER', $config['payment_type']);
        $this->assertSame(0, $config['instalments']);
    }

    public function test_form_data_has_mail_defaults_when_settings_are_missing(): void
    {
        Cache::shouldReceive('remember')->once()->andReturn([]);

        config()->set('mail.mailers.smtp.host', 'smtp.default.local');
        config()->set('mail.mailers.smtp.port', 2525);
        config()->set('mail.mailers.smtp.username', 'default-user');
        config()->set('mail.mailers.smtp.password', 'default-pass');
        config()->set('mail.mailers.smtp.scheme', 'tls');
        config()->set('mail.from.address', 'noreply@default.local');
        config()->set('mail.from.name', 'Default App');

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $service = new SettingsService($repo);

        $data = $service->formData();

        $this->assertSame('smtp.default.local', $data['mail_smtp_host']);
        $this->assertSame('2525', $data['mail_smtp_port']);
        $this->assertSame('default-user', $data['mail_smtp_username']);
        $this->assertSame('default-pass', $data['mail_smtp_password']);
        $this->assertSame('tls', $data['mail_smtp_encryption']);
        $this->assertSame('PAY_BY_INSTALMENTS', $data['payments_tamara_payment_type']);
        $this->assertSame('4', $data['payments_tamara_instalments']);
        $this->assertSame('noreply@default.local', $data['mail_from_address']);
        $this->assertSame('Default App', $data['mail_from_name']);
        $this->assertSame('https://drive.google.com/file/d/1G65bq0ZPudDTTG24_fAs2FKfWmccHvdS/view?usp=drive_link', $data['mail_training_video_url']);
        $this->assertSame('https://forms.gle/oTsTjJ8Ebqk9xJgz6', $data['mail_certificate_form_url']);
    }

    public function test_completion_color_for_filament_uses_configured_thresholds(): void
    {
        $setting = new Setting([
            'key' => 'reports.completion_thresholds',
            'value' => [
                ['min' => 18, 'color' => 'blue'],
                ['min' => 17, 'color' => 'sky'],
                ['min' => 16, 'color' => 'green'],
                ['min' => 14, 'color' => 'amber'],
                ['min' => 0, 'color' => 'red'],
            ],
        ]);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect([$setting]));

        $service = new SettingsService($repo);

        $this->assertSame(Color::hex('#3b82f6'), $service->completionColorForFilament(20.0));
        $this->assertSame(Color::hex('#0ea5e9'), $service->completionColorForFilament(17.5));
        $this->assertSame(Color::hex('#22c55e'), $service->completionColorForFilament(16.0));
        $this->assertSame(Color::hex('#f59e0b'), $service->completionColorForFilament(14.0));
        $this->assertSame(Color::hex('#ef4444'), $service->completionColorForFilament(5.0));
        $this->assertSame('gray', $service->completionColorForFilament(null));
    }

    public function test_completion_color_for_filament_supports_arbitrary_hex_colors(): void
    {
        $setting = new Setting([
            'key' => 'reports.completion_thresholds',
            'value' => [
                ['min' => 50, 'color' => '#7c3aed'],
                ['min' => 0, 'color' => '#111827'],
            ],
        ]);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect([$setting]));

        $service = new SettingsService($repo);

        $this->assertSame(Color::hex('#7c3aed'), $service->completionColorForFilament(75.0));
        $this->assertSame(Color::hex('#111827'), $service->completionColorForFilament(10.0));
    }

    public function test_completion_badge_style_uses_configured_colors(): void
    {
        $setting = new Setting([
            'key' => 'reports.completion_thresholds',
            'value' => [
                ['min' => 50, 'color' => '#ffffff'],
                ['min' => 0, 'color' => '#000000'],
            ],
        ]);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect([$setting]));

        $service = new SettingsService($repo);

        $this->assertSame('background-color:#ffffff;color:#111827;', $service->completionBadgeStyle(75.0));
        $this->assertSame('background-color:#000000;color:#ffffff;', $service->completionBadgeStyle(10.0));
        $this->assertSame('background-color:#f3f4f6;color:#6b7280;', $service->completionBadgeStyle(null));
    }

    public function test_completion_thresholds_fall_back_to_defaults_when_unset(): void
    {
        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect());

        $service = new SettingsService($repo);

        $thresholds = $service->completionThresholds();

        $this->assertSame(18.0, $thresholds[0]['min']);
        $this->assertSame('#3b82f6', $thresholds[0]['color']);
        $this->assertSame(0.0, $thresholds[count($thresholds) - 1]['min']);
        $this->assertSame('#ef4444', $thresholds[count($thresholds) - 1]['color']);
    }

    public function test_site_url_prefers_configured_setting_over_app_url(): void
    {
        $setting = new Setting(['key' => 'site.url', 'value' => 'https://custom.example.com/']);

        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect([$setting]));

        config()->set('app.url', 'http://127.0.0.1');

        $service = new SettingsService($repo);

        $this->assertSame('https://custom.example.com', $service->siteUrl());
    }

    public function test_site_url_falls_back_to_app_url_when_unset(): void
    {
        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect());

        config()->set('app.url', 'https://themiran.net');

        $service = new SettingsService($repo);

        $this->assertSame('https://themiran.net', $service->siteUrl());
    }

    public function test_whatsapp_phone_prefers_dedicated_number_and_strips_non_digits(): void
    {
        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect([
            new Setting(['key' => 'contact.phone', 'value' => '+966 11 000 0000']),
            new Setting(['key' => 'contact.whatsapp_phone', 'value' => '+966 50 123 4567']),
        ]));

        $service = new SettingsService($repo);

        $this->assertSame('966501234567', $service->whatsappPhone());
    }

    public function test_whatsapp_phone_falls_back_to_contact_phone_when_unset(): void
    {
        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect([
            new Setting(['key' => 'contact.phone', 'value' => '+966 11 000 0000']),
        ]));

        $service = new SettingsService($repo);

        $this->assertSame('966110000000', $service->whatsappPhone());
    }

    public function test_whatsapp_phone_is_empty_when_no_number_configured(): void
    {
        $repo = Mockery::mock(SettingsRepositoryInterface::class);
        $repo->shouldReceive('all')->once()->andReturn(collect());

        $service = new SettingsService($repo);

        $this->assertSame('', $service->whatsappPhone());
    }
}
