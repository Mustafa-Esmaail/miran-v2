<?php

namespace App\Rules;

use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TurnstileRule implements ValidationRule
{
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $config = app(SettingsService::class)->turnstileConfig();

        if (! $config['enabled'] || trim($config['secret_key']) === '') {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail(__('web.home.lead_turnstile_failed'));

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->retry(2, 200)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $config['secret_key'],
                    'response' => $value,
                    'remoteip' => request()->ip(),
                    'idempotency_key' => (string) Str::uuid(),
                ]);
        } catch (ConnectionException) {
            $fail(__('web.home.lead_turnstile_failed'));

            return;
        }

        if (! $response->successful() || ! $response->json('success')) {
            $fail(__('web.home.lead_turnstile_failed'));
        }
    }
}
