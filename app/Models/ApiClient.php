<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * An external system allowed to call the read-only /api/v1 endpoints.
 * Only the SHA-256 hash of its key is stored; the plain key is shown once.
 */
class ApiClient extends Model
{
    /** @use HasFactory<\Database\Factories\ApiClientFactory> */
    use HasFactory;

    public const ABILITY_ORDERS = 'orders';

    /**
     * @var array<int, string>
     */
    public const ABILITIES = [
        self::ABILITY_ORDERS,
    ];

    protected $fillable = [
        'name',
        'key_prefix',
        'key_hash',
        'abilities',
        'allowed_ips',
        'rate_limit_per_minute',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'revoked_at',
        'created_by',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'allowed_ips' => 'array',
            'rate_limit_per_minute' => 'int',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    /**
     * An empty allowlist accepts any IP; entries may be single IPs or CIDR ranges.
     */
    public function allowsIp(?string $ip): bool
    {
        if (blank($this->allowed_ips)) {
            return true;
        }

        return $ip !== null && IpUtils::checkIp($ip, $this->allowed_ips);
    }
}
