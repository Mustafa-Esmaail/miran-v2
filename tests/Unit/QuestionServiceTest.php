<?php

namespace Tests\Unit;

use App\Enums\QuestionAnswerStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\QuestionAnswer;
use App\Models\User;
use App\Services\QuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_questions_and_counts_approved_answers(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course',
        ]);

        $service = $this->app->make(QuestionService::class);

        $question = $service->createQuestion($course, $user, 'How long is the course?', 'Need details about duration.');

        QuestionAnswer::query()->create([
            'question_id' => $question->id,
            'user_id' => $user->id,
            'content' => 'It is 6 weeks long.',
            'status' => QuestionAnswerStatus::Approved,
            'is_accepted' => false,
        ]);
        QuestionAnswer::query()->create([
            'question_id' => $question->id,
            'user_id' => $user->id,
            'content' => 'Waiting for confirmation.',
            'status' => QuestionAnswerStatus::Pending,
            'is_accepted' => false,
        ]);

        $questions = $service->queryForCourse($course, $user)->get();

        $this->assertCount(1, $questions);
        $this->assertSame(1, (int) $questions->first()->approved_answers_count);
    }
}
