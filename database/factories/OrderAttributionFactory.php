<?php

namespace Database\Factories;

use App\Enums\OrderAttributionType;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderAttribution>
 */
class OrderAttributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'service_request_id' => ServiceRequest::factory(),
            'sales_user_id' => User::factory(),
            'attribution_type' => OrderAttributionType::Lead,
            'credited_amount' => $this->faker->numberBetween(10000, 50000),
            'created_at' => now(),
        ];
    }
}
