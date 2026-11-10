<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ['en' => $this->faker->sentence(4)],
            'body' => ['en' => $this->faker->paragraph()],
            'status' => 'published',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ];
    }
}
