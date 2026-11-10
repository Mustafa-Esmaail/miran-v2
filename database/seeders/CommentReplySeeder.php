<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $comment = Comment::query()->inRandomOrder()->first();
        $user = User::query()->inRandomOrder()->first();

        if (! $comment || ! $user) {
            return;
        }

        $replies = [
            'Thanks for the feedback! We will add more examples.',
            'A summary PDF is planned for the next update.',
        ];

        foreach ($replies as $content) {
            CommentReply::query()->create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'content' => $content,
                'approved' => true,
                'hidden' => false,
            ]);
        }
    }
}
