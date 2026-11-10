<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $course = Course::query()->inRandomOrder()->first();
        $user = User::query()->inRandomOrder()->first();

        if (! $course || ! $user) {
            return;
        }

        $comments = [
            'Great lesson, very clear explanations.',
            'I would love more practical examples here.',
            'Is there a downloadable summary for this lesson?',
        ];

        foreach ($comments as $content) {
            Comment::query()->create([
                'commentable_id' => $course->id,
                'commentable_type' => Course::class,
                'user_id' => $user->id,
                'content' => $content,
                'approved' => true,
                'hidden' => false,
            ]);
        }
    }
}
