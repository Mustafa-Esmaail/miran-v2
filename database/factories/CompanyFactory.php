<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'industry' => fake()->randomElement(['education', 'healthcare', 'retail', 'government']),
            'size' => fake()->randomElement(['micro', 'small', 'medium', 'large', 'enterprise']),
            'website' => fake()->url(),
            'tax_id' => fake()->numerify('3##########0003'),
            'address' => fake()->address(),
            'account_manager_id' => null,
            'notes' => null,
        ];
    }
}
