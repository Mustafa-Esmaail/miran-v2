<?php

namespace Database\Factories;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrmActivity>
 */
class CrmActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entity_type' => Deal::class,
            'entity_id' => Deal::factory(),
            'type' => fake()->randomElement(CrmActivityType::cases())->value,
            'subject' => fake()->sentence(4),
            'body' => fake()->optional()->paragraph(),
            'outcome' => fake()->randomElement(CrmActivityOutcome::cases())->value,
            'scheduled_at' => null,
            'completed_at' => now(),
            'employee_id' => null,
            'created_at' => now(),
        ];
    }
}
