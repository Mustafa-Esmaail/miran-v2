<?php

namespace App\Services\Settings;

class MailSettingsApplier
{
    public function __construct(private readonly SettingsService $settings) {}

    public function apply(): void
    {
        $smtpEnabled = (bool) $this->settings->getValue('mail.smtp.enabled', false);

        config([
            'mail.from.address' => (string) $this->settings->getValue('mail.from.address', config('mail.from.address')),
            'mail.from.name' => (string) $this->settings->getValue('mail.from.name', config('mail.from.name')),
        ]);

        if (! $smtpEnabled) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => (string) $this->settings->getValue('mail.smtp.host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => (int) $this->settings->getValue('mail.smtp.port', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => (string) $this->settings->getValue('mail.smtp.username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => (string) $this->settings->getValue('mail.smtp.password', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.scheme' => $this->resolveSmtpScheme(),
        ]);
    }

    private function resolveSmtpScheme(): ?string
    {
        $encryption = strtolower(trim((string) $this->settings->getValue('mail.smtp.encryption', '')));

        if ($encryption === 'ssl') {
            return 'smtps';
        }

        if ($encryption === 'tls') {
            return 'smtp';
        }

        $currentScheme = strtolower(trim((string) config('mail.mailers.smtp.scheme')));
        if (in_array($currentScheme, ['smtp', 'smtps'], true)) {
            return $currentScheme;
        }

        return null;
    }
}
