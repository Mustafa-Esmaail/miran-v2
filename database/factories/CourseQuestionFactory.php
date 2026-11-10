<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseQuestion>
 */
class CourseQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $course = Course::query()->inRandomOrder()->first();
        if (! $course) {
            throw new RuntimeException('CourseQuestionFactory requires an existing course.');
        }

        return [
            'course_id' => $course->id,
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->paragraph(),
        ];
    }
}
