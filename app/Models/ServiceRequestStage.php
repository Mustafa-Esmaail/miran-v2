<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequestStage extends Model
{
    protected $fillable = [
        'type_id',
        'name',
        'slug',
        'color',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
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

    public function type(): BelongsTo
    {
        return $this->belongsTo(ServiceRequestType::class, 'type_id');
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(ServiceRequestStatus::class, 'stage_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
