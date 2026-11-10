<?php

namespace Database\Factories;

use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CrmTask>
 */
class CrmTaskFactory extends Factory
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
            'title' => fake()->sentence(3),
            'due_at' => now()->addDay(),
            'priority' => CrmTaskPriority::Normal->value,
            'status' => CrmTaskStatus::Todo->value,
            'employee_id' => null,
            'notes' => null,
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => ['due_at' => now()->subDay()]);
    }

    public function done(): static
    {
        return $this->state(fn (): array => ['status' => CrmTaskStatus::Done->value]);
    }
}
