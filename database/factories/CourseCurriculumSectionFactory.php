<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseCurriculumSection>
 */
class CourseCurriculumSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => [
                'en' => fake()->words(2, true),
                'ar' => fake()->words(2, true),
            ],
            'description' => [
                'en' => fake()->sentence(),
                'ar' => fake()->sentence(),
            ],
            'sort_order' => 0,
        ];
    }
}
