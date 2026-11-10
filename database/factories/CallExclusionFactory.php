<?php

namespace Database\Factories;

use App\Models\CallExclusion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallExclusion>
 */
class CallExclusionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => fake()->unique()->numerify('05########'),
            'reason' => CallExclusion::REASON_FOLLOW_UP,
        ];
    }
}
