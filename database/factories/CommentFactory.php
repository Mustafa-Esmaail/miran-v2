<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
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
            throw new RuntimeException('CommentFactory requires an existing course.');
        }

        return [
            'commentable_id' => $course->id,
            'commentable_type' => Course::class,
            'user_id' => \App\Models\User::factory(),
            'content' => $this->faker->paragraph(),
            'approved' => true,
            'hidden' => false,
        ];
    }
}
