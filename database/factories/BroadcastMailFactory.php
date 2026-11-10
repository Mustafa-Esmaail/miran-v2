<?php

namespace Database\Factories;

use App\Models\BroadcastMail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BroadcastMail>
 */
class BroadcastMailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => $this->faker->sentence(4),
            'body' => '<p>'.$this->faker->paragraph().'</p>',
            'status' => BroadcastMail::STATUS_QUEUED,
        ];
    }
}
