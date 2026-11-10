<?php

namespace Database\Seeders;

use App\Models\CourseQuestion;
use App\Models\QuestionAnswer;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionAnswerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $question = CourseQuestion::query()->inRandomOrder()->first();
        $user = User::query()->inRandomOrder()->first();

        if (! $question || ! $user) {
            return;
        }

        $answers = [
            'The course runs for 6 weeks with 3 hours per week.',
            'No prerequisites are required, but basic familiarity helps.',
            'Yes, a certificate is issued after completion.',
        ];

        foreach ($answers as $content) {
            QuestionAnswer::query()->create([
                'question_id' => $question->id,
                'user_id' => $user->id,
                'content' => $content,
                'status' => 'approved',
                'is_accepted' => false,
            ]);
        }
    }
}
