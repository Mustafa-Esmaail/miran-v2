<?php

namespace Database\Factories;

use App\Enums\SalesTargetPeriodType;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesTarget>
 */
class SalesTargetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_type' => SalesTargetPeriodType::Monthly->value,
            'period' => now()->format('Y-m'),
            'target_revenue' => fake()->numberBetween(10_000, 200_000) * 100,
            'target_leads' => fake()->numberBetween(5, 60),
            'notes' => null,
        ];
    }
}
