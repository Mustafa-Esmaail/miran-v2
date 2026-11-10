<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CallQualityCriterion extends Model
{
    /** @use HasFactory<\Database\Factories\CallQualityCriterionFactory> */
    use HasFactory;

    use HasTranslations;

    protected $table = 'call_quality_criteria';

    /**
     * @var array<int, string>
     */
    public array $translatable = ['name'];

    protected $fillable = [
        'key',
        'name',
        'weight',
        'sort_order',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $criterion): void {
            if (blank($criterion->key)) {
                $criterion->key = self::nextKey();
            }
        });

        static::saved(function (self $criterion): void {
            if ($criterion->wasChanged(['weight', 'is_active']) || $criterion->wasRecentlyCreated) {
                CallEvaluation::recalculateAll();
            }
        });

        static::deleted(fn (): int => CallEvaluation::recalculateAll());
    }

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'sort_order' => 'int',
            'is_active' => 'bool',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The next free "c{n}" key, as the reference system names new criteria.
     */
    public static function nextKey(): string
    {
        $numbers = self::query()->pluck('key')
            ->map(fn (string $key): int => (int) preg_replace('/\D+/', '', $key))
            ->all();

        return 'c'.((empty($numbers) ? 0 : max($numbers)) + 1);
    }
}
