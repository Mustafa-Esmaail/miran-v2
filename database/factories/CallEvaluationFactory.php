<?php

namespace Database\Factories;

use App\Models\CallQualityCriterion;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallEvaluation>
 */
class CallEvaluationFactory extends Factory
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
            'evaluator_id' => User::factory(),
            'call_phone' => fake()->numerify('05########'),
            'call_date' => now()->toDateString(),
            'scores' => fn (): array => CallQualityCriterion::query()->active()->pluck('key')
                ->mapWithKeys(fn (string $key): array => [$key => fake()->numberBetween(3, 5)])
                ->all(),
            'notes' => [],
        ];
    }

    /**
     * Every active criterion scored with the same value (1–5).
     */
    public function scoredAll(int $score): static
    {
        return $this->state(fn (): array => [
            'scores' => CallQualityCriterion::query()->active()->pluck('key')
                ->mapWithKeys(fn (string $key): array => [$key => $score])
                ->all(),
        ]);
    }

    public function discussed(): static
    {
        return $this->state(fn (): array => ['discussed_at' => now(), 'discussed_by' => User::factory()]);
    }

    public function approved(): static
    {
        return $this->discussed()->state(fn (): array => ['approved_at' => now(), 'approved_by' => User::factory()]);
    }
}
