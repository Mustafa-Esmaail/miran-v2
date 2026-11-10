<?php

namespace Tests\Unit;

use App\Enums\DateRangePreset;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DateRangePresetTest extends TestCase
{
    /**
     * @return array<string, array{DateRangePreset, ?string, ?string}>
     */
    public static function presets(): array
    {
        return [
            'today' => [DateRangePreset::Today, '2026-03-15', '2026-03-15'],
            'yesterday' => [DateRangePreset::Yesterday, '2026-03-14', '2026-03-14'],
            'last 7 days includes today' => [DateRangePreset::Last7Days, '2026-03-09', '2026-03-15'],
            'last 30 days includes today' => [DateRangePreset::Last30Days, '2026-02-14', '2026-03-15'],
            'this month' => [DateRangePreset::ThisMonth, '2026-03-01', '2026-03-31'],
            'last month' => [DateRangePreset::LastMonth, '2026-02-01', '2026-02-28'],
            'this quarter' => [DateRangePreset::ThisQuarter, '2026-01-01', '2026-03-31'],
            'this year' => [DateRangePreset::ThisYear, '2026-01-01', '2026-12-31'],
            'all has no bounds' => [DateRangePreset::All, null, null],
            'custom has no bounds' => [DateRangePreset::Custom, null, null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('presets')]
    public function test_bounds(DateRangePreset $preset, ?string $from, ?string $to): void
    {
        [$actualFrom, $actualTo] = $preset->bounds(CarbonImmutable::parse('2026-03-15 17:45'));

        $this->assertSame($from, $actualFrom?->toDateString());
        $this->assertSame($to, $actualTo?->toDateString());
    }

    public function test_last_month_does_not_overflow_from_the_31st(): void
    {
        [$from, $to] = DateRangePreset::LastMonth->bounds(CarbonImmutable::parse('2026-03-31'));

        $this->assertSame('2026-02-01', $from->toDateString());
        $this->assertSame('2026-02-28', $to->toDateString());
    }

    public function test_options_can_be_limited_and_reordered(): void
    {
        $options = DateRangePreset::options([DateRangePreset::ThisYear, DateRangePreset::Today]);

        $this->assertSame(['this_year', 'today'], array_keys($options));
    }

    public function test_options_cover_every_case_with_a_translated_label(): void
    {
        $options = DateRangePreset::options();

        $this->assertSame(array_column(DateRangePreset::cases(), 'value'), array_keys($options));

        foreach ($options as $value => $label) {
            $this->assertNotSame('admin.date_presets.'.$value, $label);
        }
    }
}
