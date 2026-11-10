<?php

namespace Database\Factories;

use App\Enums\ServiceRequestActivityType;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceRequestActivity>
 */
class ServiceRequestActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'type' => ServiceRequestActivityType::Created,
            'note' => $this->faker->sentence(),
            'created_at' => now(),
        ];
    }
}
