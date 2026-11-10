<?php

namespace App\Services\Api;

use App\Models\ApiClient;
use Illuminate\Support\Str;

class ApiKeyService
{
    private const PREFIX = 'mlms_';

    private const PREFIX_DISPLAY_LENGTH = 12;

    /**
     * Keys carry 48 random characters, so a plain SHA-256 is safe to store
     * and look up by — a slow hash (bcrypt) is only needed for low-entropy secrets.
     */
    public function hash(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    /**
     * @return array{plain: string, key_hash: string, key_prefix: string}
     */
    public function generate(): array
    {
        $plain = self::PREFIX.Str::random(48);

        return [
            'plain' => $plain,
            'key_hash' => $this->hash($plain),
            'key_prefix' => Str::substr($plain, 0, self::PREFIX_DISPLAY_LENGTH),
        ];
    }

    public function findByPlainKey(string $plainKey): ?ApiClient
    {
        return ApiClient::query()->where('key_hash', $this->hash($plainKey))->first();
    }

    /**
     * Replace the client's key; the old key stops working immediately.
     *
     * @return string the new plain key, to be shown to the admin once
     */
    public function rotate(ApiClient $client): string
    {
        $key = $this->generate();

        $client->forceFill([
            'key_hash' => $key['key_hash'],
            'key_prefix' => $key['key_prefix'],
        ])->save();

        return $key['plain'];
    }

    public function revoke(ApiClient $client): void
    {
        $client->forceFill(['revoked_at' => now()])->save();
    }
}
