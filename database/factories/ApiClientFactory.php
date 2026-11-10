<?php

namespace Database\Factories;

use App\Models\ApiClient;
use App\Services\Api\ApiKeyService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiClient>
 */
class ApiClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = app(ApiKeyService::class)->generate();

        return [
            'name' => $this->faker->company(),
            'key_prefix' => $key['key_prefix'],
            'key_hash' => $key['key_hash'],
            'abilities' => ApiClient::ABILITIES,
            'allowed_ips' => null,
            'rate_limit_per_minute' => 60,
        ];
    }

    /**
     * Use a known plain key so tests can authenticate with it.
     */
    public function withKey(string $plainKey): static
    {
        return $this->state(fn (): array => [
            'key_hash' => app(ApiKeyService::class)->hash($plainKey),
            'key_prefix' => substr($plainKey, 0, 12),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()->subMinute()]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }
}
