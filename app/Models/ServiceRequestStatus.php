<?php

namespace App\Models;

use App\Enums\ServiceRequestStatusOutcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequestStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'color',
        'outcome',
        'stage_id',
        'is_default',
        'is_spam',
        'requires_date',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'outcome' => ServiceRequestStatusOutcome::class,
            'is_default' => 'boolean',
            'is_spam' => 'boolean',
            'requires_date' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $status): void {
            if ($status->is_default) {
                static::query()->where('id', '!=', $status->id ?? 0)->update(['is_default' => false]);
            }

            if ($status->is_spam) {
                static::query()->where('id', '!=', $status->id ?? 0)->update(['is_spam' => false]);
            }
        });
    }

    public function getTranslatedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $name = $this->name;

        if (is_array($name)) {
            return $name[$locale] ?? $name[array_key_first($name)] ?? '';
        }

        return (string) $name;
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestStage::class, 'stage_id');
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'status', 'slug');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, ?int $typeId): Builder
    {
        return $query->where(function (Builder $q) use ($typeId): void {
            $q->whereNull('stage_id');

            if ($typeId) {
                $q->orWhereHas('stage', fn (Builder $sq) => $sq->where('type_id', $typeId));
            }
        });
    }

    public static function default(): ?self
    {
        return static::query()->active()->where('is_default', true)->first();
    }

    public static function spamBucket(): ?self
    {
        return static::query()->active()->where('is_spam', true)->first();
    }
}
