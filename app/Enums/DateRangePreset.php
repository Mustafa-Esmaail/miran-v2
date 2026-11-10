<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum DateRangePreset: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisQuarter = 'this_quarter';
    case ThisYear = 'this_year';
    case All = 'all';
    case Custom = 'custom';

    public function label(): string
    {
        return __('admin.date_presets.'.$this->value);
    }

    /**
     * The inclusive [from, to] dates this preset covers, relative to $today.
     * All and Custom have no fixed bounds and return [null, null].
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public function bounds(?CarbonInterface $today = null): array
    {
        $today = CarbonImmutable::instance($today ?? now())->startOfDay();

        return match ($this) {
            self::Today => [$today, $today],
            self::Yesterday => [$today->subDay(), $today->subDay()],
            self::Last7Days => [$today->subDays(6), $today],
            self::Last30Days => [$today->subDays(29), $today],
            self::ThisMonth => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
            self::LastMonth => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            self::ThisQuarter => [$today->startOfQuarter(), $today->endOfQuarter()->startOfDay()],
            self::ThisYear => [$today->startOfYear(), $today->endOfYear()->startOfDay()],
            self::All, self::Custom => [null, null],
        };
    }

    /**
     * @param  array<int, self>|null  $presets  Limit and order the options; all cases when null.
     * @return array<string, string>
     */
    public static function options(?array $presets = null): array
    {
        return collect($presets ?? self::cases())
            ->mapWithKeys(fn (self $preset): array => [$preset->value => $preset->label()])
            ->all();
    }
}
