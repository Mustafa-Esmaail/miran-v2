<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionVote>
 */
class QuestionVoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'answer_id' => \App\Models\QuestionAnswer::factory(),
            'user_id' => \App\Models\User::factory(),
            'is_helpful' => true,
        ];
    }
}
