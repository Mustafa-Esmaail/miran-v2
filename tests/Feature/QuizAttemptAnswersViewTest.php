<?php

namespace Tests\Feature;

use App\Enums\QuizQuestionType;
use App\Models\Category;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuizAttemptAnswersViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_attempt_answers_as_readable_review(): void
    {
        $quiz = $this->createQuiz();
        $user = User::factory()->create();

        $firstQuestion = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'What is correct?',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        $firstCorrectOption = QuizOption::query()->create([
            'quiz_question_id' => $firstQuestion->id,
            'label' => 'Correct option',
            'is_correct' => true,
        ]);

        $secondQuestion = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'What is wrong?',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $secondQuestion->id,
            'label' => 'Second correct option',
            'is_correct' => true,
        ]);

        $secondWrongOption = QuizOption::query()->create([
            'quiz_question_id' => $secondQuestion->id,
            'label' => 'Wrong option',
            'is_correct' => false,
        ]);

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 1,
            'answers' => [
                $firstQuestion->id => $firstCorrectOption->id,
                $secondQuestion->id => $secondWrongOption->id,
            ],
        ]);

        $html = (string) view('filament.quiz-attempt-answers', ['record' => $attempt])->render();
        $normalizedHtml = preg_replace('/\s+/', ' ', $html);

        $this->assertStringContainsString('What is correct?', $html);
        $this->assertStringContainsString('What is wrong?', $html);
        $this->assertStringContainsString('Selected answer', $html);
        $this->assertStringContainsString('Correct option', $html);
        $this->assertStringContainsString('Wrong option', $html);
        $this->assertStringContainsString('Incorrect', $html);
        $this->assertStringContainsString('1 / 2', $normalizedHtml);
        $this->assertStringContainsString('Raw answers', $html);
    }

    public function test_it_renders_answers_without_matching_questions_separately(): void
    {
        $quiz = $this->createQuiz();
        $user = User::factory()->create();

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'Known question',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        $correctOption = QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Known option',
            'is_correct' => true,
        ]);

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 1,
            'answers' => [
                $question->id => $correctOption->id,
                999 => 321,
            ],
        ]);

        $html = (string) view('filament.quiz-attempt-answers', ['record' => $attempt])->render();
        $normalizedHtml = preg_replace('/\s+/', ' ', $html);

        $this->assertStringContainsString('Answers without matching questions', $html);
        $this->assertStringContainsString('Question #999', $normalizedHtml);
        $this->assertStringContainsString('321', $html);
    }

    private function createQuiz(): Quiz
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => Str::slug(Str::random(12)),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => Str::slug(Str::random(12)),
            'status' => 'published',
        ]);

        return Quiz::query()->create([
            'course_id' => $course->id,
            'title' => 'Readable quiz',
            'passing_score' => 1,
        ]);
    }
}
