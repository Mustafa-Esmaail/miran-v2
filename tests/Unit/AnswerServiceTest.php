<?php

namespace Tests\Unit;

use App\Enums\QuestionAnswerStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseQuestion;
use App\Models\QuestionVote;
use App\Models\User;
use App\Services\AnswerService;
use App\Services\QuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AnswerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_pending_answers_and_allows_acceptance_by_owner(): void
    {
        Notification::fake();

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

        $questionService = $this->app->make(QuestionService::class);
        $answerService = $this->app->make(AnswerService::class);

        $question = $questionService->createQuestion($course, $user, 'Is there a certificate?', 'Need to know if I get one.');

        $answer = $answerService->createAnswer($question, $user, 'Yes, a certificate is provided.');

        $this->assertSame(QuestionAnswerStatus::Pending->value, $answer->status->value);
        $this->assertFalse($answer->is_accepted);

        $answerService->acceptAnswer($answer, $user);

        $this->assertTrue($answer->fresh()->is_accepted);
    }

    public function test_it_rejects_acceptance_from_non_owners(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
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

        $question = CourseQuestion::query()->create([
            'course_id' => $course->id,
            'user_id' => $owner->id,
            'title' => 'Will it be recorded?',
            'content' => 'Need a recording if I miss a session.',
        ]);

        $answer = $this->app->make(AnswerService::class)->createAnswer($question, $otherUser, 'Recordings are shared.');

        $this->expectException(\RuntimeException::class);

        $this->app->make(AnswerService::class)->acceptAnswer($answer, $otherUser);
    }

    public function test_it_allows_marking_answers_as_helpful(): void
    {
        Notification::fake();

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

        $question = CourseQuestion::query()->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => 'Are there live sessions?',
            'content' => 'Will there be live Q&A?',
        ]);

        $answer = $this->app->make(AnswerService::class)->createAnswer($question, $user, 'Yes, weekly live sessions.');

        $this->app->make(AnswerService::class)->toggleHelpful($answer, $user);

        $this->assertSame(1, QuestionVote::query()->where('answer_id', $answer->id)->count());
    }
}
