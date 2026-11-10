<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->numberBetween(10000, 50000);
        $discount = $this->faker->numberBetween(0, 2000);
        $total = max($subtotal - $discount, 0);

        return [
            'user_id' => User::factory(),
            'order_no' => 'ORDER-'.$this->faker->unique()->numberBetween(1000, 9999),
            'status' => OrderStatus::Pending,
            'currency' => 'SAR',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $total,
        ];
    }
}
