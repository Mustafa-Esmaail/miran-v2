<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceRequestType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'sync_to_sheets',
        'sheet_id',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'sync_to_sheets' => 'boolean',
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

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'type_id');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ServiceRequestStage::class, 'type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
