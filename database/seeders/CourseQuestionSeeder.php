<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseQuestionSeeder extends Seeder
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

        $questions = [
            [
                'title' => 'How long does the course take?',
                'content' => 'I want to understand the expected weekly pace.',
            ],
            [
                'title' => 'Are there any prerequisites?',
                'content' => 'Should I complete any other course before this one?',
            ],
            [
                'title' => 'Is there a certificate after completion?',
                'content' => 'Please confirm if a certificate is issued.',
            ],
        ];

        foreach ($questions as $question) {
            CourseQuestion::query()->create([
                'course_id' => $course->id,
                'user_id' => $user->id,
                'title' => $question['title'],
                'content' => $question['content'],
            ]);
        }
    }
}
