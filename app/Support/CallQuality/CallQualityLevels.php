<?php

namespace App\Support\CallQuality;

use App\Models\Setting;
use App\Services\Settings\SettingsService;

/**
 * Performance levels for a call evaluation's score. Thresholds (minimum %) are
 * editable and stored in settings under "call_quality.levels"; the defaults are
 * the reference system's: ≥90 excellent, ≥80 very good, ≥70 good, ≥60 acceptable.
 */
class CallQualityLevels
{
    public const SETTING_KEY = 'call_quality.levels';

    private const CONTAINER_KEY = 'call_quality.levels.stored';

    /**
     * Highest first; the last level has no minimum.
     *
     * @var array<string, int>
     */
    public const DEFAULTS = [
        'excellent' => 90,
        'very_good' => 80,
        'good' => 70,
        'acceptable' => 60,
        'needs_improvement' => 0,
    ];

    /**
     * @return array<string, int>
     */
    public static function thresholds(): array
    {
        $stored = self::stored();

        $thresholds = [];
        foreach (self::DEFAULTS as $level => $default) {
            $thresholds[$level] = $level === 'needs_improvement'
                ? 0
                : (int) max(0, min(100, $stored[$level] ?? $default));
        }

        arsort($thresholds);

        return $thresholds;
    }

    public static function levelFor(?float $percent): ?string
    {
        if ($percent === null) {
            return null;
        }

        foreach (self::thresholds() as $level => $minimum) {
            if ($percent >= $minimum) {
                return $level;
            }
        }

        return 'needs_improvement';
    }

    public static function label(?string $level): string
    {
        return $level === null ? '—' : __('admin.call_quality.levels.'.$level);
    }

    public static function color(?string $level): string
    {
        return match ($level) {
            'excellent' => 'success',
            'very_good', 'good' => 'primary',
            'acceptable' => 'warning',
            'needs_improvement' => 'danger',
            default => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(array_keys(self::DEFAULTS))
            ->mapWithKeys(fn (string $level): array => [$level => self::label($level)])
            ->all();
    }

    /**
     * @param  array<string, int|float|string>  $thresholds
     */
    public static function save(array $thresholds): void
    {
        $clean = [];
        foreach (array_keys(self::DEFAULTS) as $level) {
            if ($level !== 'needs_improvement' && isset($thresholds[$level])) {
                $clean[$level] = (int) max(0, min(100, $thresholds[$level]));
            }
        }

        Setting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['group' => 'call_quality', 'type' => 'json', 'value' => $clean, 'is_translatable' => false, 'sort_order' => 1],
        );

        app()->forgetInstance(self::CONTAINER_KEY);
        app(SettingsService::class)->clearCache();
    }

    /**
     * The saved thresholds, read once per request/app instance. Kept in the
     * container (not a static) so long-running workers and tests never see a stale value.
     *
     * @return array<string, int|float|string>
     */
    private static function stored(): array
    {
        if (! app()->bound(self::CONTAINER_KEY)) {
            $value = Setting::query()->where('key', self::SETTING_KEY)->first()?->value;
            app()->instance(self::CONTAINER_KEY, is_array($value) ? $value : []);
        }

        return app(self::CONTAINER_KEY);
    }
}
