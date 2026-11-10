<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommentReply>
 */
class CommentReplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'comment_id' => \App\Models\Comment::factory(),
            'parent_reply_id' => null,
            'user_id' => \App\Models\User::factory(),
            'content' => $this->faker->sentence(),
            'approved' => true,
            'hidden' => false,
        ];
    }
}
