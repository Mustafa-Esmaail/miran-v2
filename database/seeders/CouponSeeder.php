<?php

namespace Database\Seeders;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * code10 => code15 pairs. Each pair applies the same discount value to
     * every course and bundle (no CouponTarget rows means unrestricted —
     * see PricingService::itemMatchesCoupon()).
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private array $codePairs = [
        ['NRT06', 'DRT06'],
        ['NRT10', 'DRT10'],
        ['NRC07', 'DRC07'],
        ['NRT04', 'DRT04'],
        ['NRC23', 'DRC23'],
        ['NRT07', 'DRT07'],
        ['NRT03', 'DRT03'],
        ['NRC34', 'DRC34'],
        ['NRC06', 'DRC06'],
        ['NRC08', 'DRC08'],
        ['NRC31', 'DRC31'],
        ['NRC03', 'DRC03'],
        ['NRC29', 'DRC29'],
        ['NRC30', 'DRC30'],
        ['NRG08', 'DRG08'],
    ];

    public function run(): void
    {
        $created = 0;

        foreach ($this->codePairs as [$code10, $code15]) {
            $this->createCoupon($code10, 10);
            $this->createCoupon($code15, 15);
            $created += 2;
        }

        $this->command->info("Created/updated {$created} coupons (10% and 15%, all courses and bundles)");

        $updated = $this->updateMinItemsCountForExistingPercentCoupons();
        $this->command->info("Updated min_items_count on {$updated} existing 10%/15% percent coupons");
    }

    /**
     * Backfill min_items_count on every existing 10%/15% percent coupon,
     * including ones created outside this seeder (e.g. via the admin panel).
     */
    private function updateMinItemsCountForExistingPercentCoupons(): int
    {
        $updated = Coupon::query()
            ->where('type', CouponType::Percent)
            ->where('value', 10)
            ->update(['min_items_count' => 2]);

        $updated += Coupon::query()
            ->where('type', CouponType::Percent)
            ->where('value', 15)
            ->update(['min_items_count' => 3]);

        return $updated;
    }

    private function createCoupon(string $code, int $percent): void
    {
        Coupon::query()->updateOrCreate(
            ['code' => $code],
            [
                'title' => [
                    'en' => "{$percent}% Discount",
                    'ar' => "خصم {$percent}%",
                ],
                'type' => CouponType::Percent,
                'applies_to' => CouponAppliesTo::Both,
                'value' => $percent,
                'starts_at' => null,
                'ends_at' => null,
                'usage_limit' => null,
                'per_user_limit' => null,
                'min_order_amount' => null,
                'min_items_count' => match ($percent) {
                    10 => 2,
                    15 => 3,
                    default => null,
                },
                'is_active' => true,
            ]
        );
    }
}
