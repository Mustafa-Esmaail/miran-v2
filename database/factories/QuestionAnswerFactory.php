<?php

namespace Database\Factories;

use App\Enums\QuestionAnswerStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionAnswer>
 */
class QuestionAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => \App\Models\CourseQuestion::factory(),
            'user_id' => \App\Models\User::factory(),
            'content' => $this->faker->paragraph(),
            'status' => QuestionAnswerStatus::Approved,
            'is_accepted' => false,
        ];
    }
}
