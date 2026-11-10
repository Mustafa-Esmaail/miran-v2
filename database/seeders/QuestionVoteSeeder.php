<?php

namespace Database\Seeders;

use App\Models\CourseQuestion;
use App\Models\QuestionAnswer;
use App\Models\QuestionVote;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionVoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->inRandomOrder()->first();
        if (! $user) {
            return;
        }

        $question = CourseQuestion::query()->inRandomOrder()->first();
        if ($question) {
            QuestionVote::query()->firstOrCreate([
                'question_id' => $question->id,
                'user_id' => $user->id,
            ], [
                'is_helpful' => true,
            ]);
        }

        $answer = QuestionAnswer::query()->inRandomOrder()->first();
        if ($answer) {
            QuestionVote::query()->firstOrCreate([
                'answer_id' => $answer->id,
                'user_id' => $user->id,
            ], [
                'is_helpful' => true,
            ]);
        }
    }
}
