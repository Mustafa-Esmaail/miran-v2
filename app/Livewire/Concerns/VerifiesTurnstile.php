<?php

namespace App\Livewire\Concerns;

use App\Rules\TurnstileRule;
use App\Services\Settings\SettingsService;
use Illuminate\Validation\ValidationException;

trait VerifiesTurnstile
{
    public string $turnstileToken = '';

    protected function turnstileWidgetConfig(): array
    {
        $config = app(SettingsService::class)->turnstileConfig();

        return [
            'enabled' => $config['enabled'] && trim($config['site_key']) !== '',
            'site_key' => $config['site_key'],
        ];
    }

    protected function turnstileRules(): array
    {
        return [
            'turnstileToken' => [new TurnstileRule],
        ];
    }

    protected function validateWithTurnstile(array $rules): array
    {
        try {
            return $this->validate(array_merge($rules, $this->turnstileRules()));
        } catch (ValidationException $exception) {
            $this->dispatch('turnstile-reset');

            throw $exception;
        }
    }
}
