<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallQualityCriterion>
 */
class CallQualityCriterionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'key' => 'c'.fake()->unique()->numberBetween(100, 99999),
            'name' => ['en' => $name, 'ar' => $name],
            'weight' => 20,
            'sort_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }

    public function weight(float $weight): static
    {
        return $this->state(fn (): array => ['weight' => $weight]);
    }
}
