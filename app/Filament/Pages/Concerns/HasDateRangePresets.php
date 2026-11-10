<?php

namespace App\Filament\Pages\Concerns;

use App\Enums\DateRangePreset;
use Carbon\CarbonImmutable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Shared date filter for report pages: a preset select (today … this year,
 * all, custom) that fills the page's existing date_from / date_to pickers.
 * Editing a picker by hand switches the preset to "custom".
 *
 * Used inside a page's filtersForm(); the page reads the range back with
 * reportDateRange().
 */
trait HasDateRangePresets
{
    /**
     * The preset a page opens with. Override per page.
     */
    protected function defaultDateRangePreset(): DateRangePreset
    {
        return DateRangePreset::ThisMonth;
    }

    /**
     * The presets a page offers, in order. Override to drop ones that make no
     * sense for the page (e.g. "all" next to a monthly target).
     *
     * @return array<int, DateRangePreset>
     */
    protected function availableDateRangePresets(): array
    {
        return DateRangePreset::cases();
    }

    /**
     * The preset select followed by the from / until pickers.
     *
     * @return array<int, Select|DatePicker>
     */
    protected function dateRangeFields(): array
    {
        $default = $this->defaultDateRangePreset();
        [$defaultFrom, $defaultTo] = $default->bounds();

        return [
            Select::make('date_range')
                ->label(__('admin.date_presets.label'))
                ->options(DateRangePreset::options($this->availableDateRangePresets()))
                ->default($default->value)
                ->selectablePlaceholder(false)
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    $preset = DateRangePreset::tryFrom((string) $state);

                    if ($preset === null || $preset === DateRangePreset::Custom) {
                        return;
                    }

                    [$from, $to] = $preset->bounds();

                    $set('date_from', $from?->toDateString());
                    $set('date_to', $to?->toDateString());
                }),
            DatePicker::make('date_from')
                ->label(__('admin.labels.from'))
                ->default($defaultFrom?->toDateString())
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('date_range', DateRangePreset::Custom->value)),
            DatePicker::make('date_to')
                ->label(__('admin.labels.until'))
                ->default($defaultTo?->toDateString())
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('date_range', DateRangePreset::Custom->value)),
        ];
    }

    /**
     * The selected range as [start of from-day, end of to-day]; either side is
     * null when that picker is empty (e.g. the "all" preset).
     *
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    protected function reportDateRange(): array
    {
        $filters = $this->filters ?? [];

        $from = filled($filters['date_from'] ?? null) ? CarbonImmutable::parse($filters['date_from'])->startOfDay() : null;
        $to = filled($filters['date_to'] ?? null) ? CarbonImmutable::parse($filters['date_to'])->endOfDay() : null;

        return [$from, $to];
    }
}
