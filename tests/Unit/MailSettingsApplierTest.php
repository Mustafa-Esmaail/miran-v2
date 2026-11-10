<?php

namespace Tests\Unit;

use App\Services\Settings\MailSettingsApplier;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class MailSettingsApplierTest extends TestCase
{
    public function test_it_applies_smtp_settings(): void
    {
        Config::set('mail.default', 'log');
        Config::set('mail.mailers.smtp.host', '127.0.0.1');
        Config::set('mail.mailers.smtp.port', 2525);
        Config::set('mail.mailers.smtp.username', null);
        Config::set('mail.mailers.smtp.password', null);
        Config::set('mail.mailers.smtp.scheme', null);
        Config::set('mail.from.address', 'hello@example.com');
        Config::set('mail.from.name', 'Example');

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('getValue')->with('mail.smtp.enabled', false)->andReturn(true);
        $settings->shouldReceive('getValue')->with('mail.smtp.host', '127.0.0.1')->andReturn('smtp.example.com');
        $settings->shouldReceive('getValue')->with('mail.smtp.port', 2525)->andReturn(587);
        $settings->shouldReceive('getValue')->with('mail.smtp.username', null)->andReturn('mailer');
        $settings->shouldReceive('getValue')->with('mail.smtp.password', null)->andReturn('secret');
        $settings->shouldReceive('getValue')->with('mail.smtp.encryption', null)->andReturn('tls');
        $settings->shouldReceive('getValue')->with('mail.from.address', 'hello@example.com')->andReturn('noreply@example.com');
        $settings->shouldReceive('getValue')->with('mail.from.name', 'Example')->andReturn('Miran');

        app()->instance(SettingsService::class, $settings);

        $applier = app(MailSettingsApplier::class);
        $applier->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('mailer', config('mail.mailers.smtp.username'));
        $this->assertSame('secret', config('mail.mailers.smtp.password'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertSame('noreply@example.com', config('mail.from.address'));
        $this->assertSame('Miran', config('mail.from.name'));
    }

    public function test_it_falls_back_to_existing_mail_config_when_setting_values_are_missing(): void
    {
        Config::set('mail.default', 'log');
        Config::set('mail.mailers.smtp.host', 'smtp.default.local');
        Config::set('mail.mailers.smtp.port', 2525);
        Config::set('mail.mailers.smtp.username', 'default-user');
        Config::set('mail.mailers.smtp.password', 'default-password');
        Config::set('mail.mailers.smtp.scheme', 'tls');
        Config::set('mail.from.address', 'noreply@default.local');
        Config::set('mail.from.name', 'Default App');

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('getValue')->with('mail.smtp.enabled', false)->andReturn(true);
        $settings->shouldReceive('getValue')->with('mail.smtp.host', 'smtp.default.local')->andReturn('smtp.default.local');
        $settings->shouldReceive('getValue')->with('mail.smtp.port', 2525)->andReturn(2525);
        $settings->shouldReceive('getValue')->with('mail.smtp.username', 'default-user')->andReturn('default-user');
        $settings->shouldReceive('getValue')->with('mail.smtp.password', 'default-password')->andReturn('default-password');
        $settings->shouldReceive('getValue')->with('mail.smtp.encryption', '')->andReturn('tls');
        $settings->shouldReceive('getValue')->with('mail.from.address', 'noreply@default.local')->andReturn('noreply@default.local');
        $settings->shouldReceive('getValue')->with('mail.from.name', 'Default App')->andReturn('Default App');

        app()->instance(SettingsService::class, $settings);

        app(MailSettingsApplier::class)->apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.default.local', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('default-user', config('mail.mailers.smtp.username'));
        $this->assertSame('default-password', config('mail.mailers.smtp.password'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertSame('noreply@default.local', config('mail.from.address'));
        $this->assertSame('Default App', config('mail.from.name'));
    }

    public function test_it_keeps_default_mailer_when_smtp_is_disabled(): void
    {
        Config::set('mail.default', 'log');
        Config::set('mail.mailers.smtp.host', '127.0.0.1');
        Config::set('mail.mailers.smtp.port', 2525);
        Config::set('mail.mailers.smtp.username', null);
        Config::set('mail.mailers.smtp.password', null);
        Config::set('mail.mailers.smtp.scheme', null);
        Config::set('mail.from.address', 'hello@example.com');
        Config::set('mail.from.name', 'Example');

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('getValue')->with('mail.smtp.enabled', false)->andReturn(false);
        $settings->shouldReceive('getValue')->with('mail.from.address', 'hello@example.com')->andReturn('noreply@example.com');
        $settings->shouldReceive('getValue')->with('mail.from.name', 'Example')->andReturn('Miran');

        app()->instance(SettingsService::class, $settings);

        app(MailSettingsApplier::class)->apply();

        $this->assertSame('log', config('mail.default'));
        $this->assertSame('127.0.0.1', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('noreply@example.com', config('mail.from.address'));
        $this->assertSame('Miran', config('mail.from.name'));
    }
}
