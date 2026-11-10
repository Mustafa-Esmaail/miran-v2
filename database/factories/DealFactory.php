<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'service_request_id' => null,
            'title' => fake()->catchPhrase(),
            'stage' => DealStage::Prospecting->value,
            'value' => fake()->numberBetween(1_000, 500_000) * 100,
            'probability' => fake()->numberBetween(0, 100),
            'expected_close_date' => fake()->dateTimeBetween('now', '+3 months'),
            'employee_id' => null,
            'lost_reason' => null,
            'notes' => null,
        ];
    }

    public function stage(DealStage $stage): static
    {
        return $this->state(fn (): array => ['stage' => $stage->value]);
    }
}
