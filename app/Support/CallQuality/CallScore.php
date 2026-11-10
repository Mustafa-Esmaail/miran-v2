<?php

namespace App\Support\CallQuality;

use App\Models\CallQualityCriterion;
use Illuminate\Support\Collection;

/**
 * The reference system's call score: each scored criterion contributes
 * (score / 5) × weight, divided by the sum of the weights that were scored.
 * Unscored criteria are left out of both sides, so weights need not add to 100.
 */
class CallScore
{
    public const MAX_SCORE = 5;

    /**
     * @param  array<string, int|string|null>  $scores  keyed by criterion key, values 1–5
     * @param  Collection<int, CallQualityCriterion>|null  $criteria  defaults to the active criteria
     * @return array{percent: ?float, points: int, max_points: int, scored: int, level: ?string}
     */
    public static function calculate(array $scores, ?Collection $criteria = null): array
    {
        $criteria ??= CallQualityCriterion::query()->active()->ordered()->get();

        $points = 0;
        $scored = 0;
        $weightSum = 0.0;
        $weighted = 0.0;

        foreach ($criteria as $criterion) {
            $value = (int) ($scores[$criterion->key] ?? 0);

            if ($value < 1 || $value > self::MAX_SCORE) {
                continue;
            }

            $weight = (float) $criterion->weight;
            $points += $value;
            $scored++;
            $weightSum += $weight;
            $weighted += $value / self::MAX_SCORE * $weight;
        }

        $percent = $weightSum > 0 ? round($weighted / $weightSum * 100, 2) : null;

        return [
            'percent' => $percent,
            'points' => $points,
            'max_points' => $scored * self::MAX_SCORE,
            'scored' => $scored,
            'level' => $scored > 0 ? CallQualityLevels::levelFor($percent) : null,
        ];
    }
}
