<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'type',
        'title',
        'subtitle',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'sort_order' => 'int',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function sectionFields(): HasMany
    {
        return $this->hasMany(SectionField::class)->orderBy('sort_order');
    }

    public function field(string $key): ?array
    {
        $field = $this->relationLoaded('sectionFields')
            ? $this->sectionFields->firstWhere('key', $key)
            : $this->sectionFields()->where('key', $key)->first();

        return $field?->value;
    }

    public function fieldValue(string $key, mixed $default = null): mixed
    {
        $value = $this->field($key);

        if ($value === null) {
            return $default;
        }

        if (array_key_exists('value', $value)) {
            $raw = $value['value'];

            return $raw === null ? $default : $raw;
        }

        return $value;
    }

    public function fieldText(string $key, string $locale, string $fallback = ''): string
    {
        $value = $this->field($key);

        if (! is_array($value)) {
            return $fallback;
        }

        if (array_key_exists('value', $value) && ! is_array($value['value'])) {
            $raw = $value['value'];
            if ($raw === null) {
                return $fallback;
            }

            return is_string($raw) ? $raw : (string) $raw;
        }

        $text = $value[$locale] ?? $value['en'] ?? $value['ar'] ?? null;

        if ($text === null) {
            return $fallback;
        }

        return is_string($text) ? $text : (string) $text;
    }

    public function setField(string $key, array $value, string $type = 'json', int $sortOrder = 0, ?string $group = null): void
    {
        $this->sectionFields()->updateOrCreate(
            ['key' => $key],
            [
                'type' => $type,
                'value' => $value,
                'sort_order' => $sortOrder,
                'group' => $group,
            ]
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
