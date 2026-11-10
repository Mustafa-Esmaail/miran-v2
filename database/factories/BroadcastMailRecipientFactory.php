<?php

namespace Database\Factories;

use App\Models\BroadcastMail;
use App\Models\BroadcastMailRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BroadcastMailRecipient>
 */
class BroadcastMailRecipientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'broadcast_mail_id' => BroadcastMail::factory(),
            'user_id' => User::factory(),
            'status' => BroadcastMailRecipient::STATUS_PENDING,
        ];
    }
}
