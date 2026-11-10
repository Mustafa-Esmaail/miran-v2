<?php

namespace Tests\Feature;

use App\Enums\QuizQuestionType;
use App\Livewire\DashboardCoursePlayer;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardQuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_attempt_quiz_once_and_score_is_saved(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'title' => 'Quiz 1',
            'passing_score' => 2,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'Question 1',
            'type' => QuizQuestionType::Mcq,
            'points' => 2,
        ]);

        $correctOption = QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Correct',
            'is_correct' => true,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Wrong',
            'is_correct' => false,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayer::class, ['course_slug' => $course->slug])
            ->set('quizAnswers', [$question->id => $correctOption->id])
            ->call('submitQuiz', $quiz->id)
            ->assertHasNoErrors();

        $this->assertSame(1, QuizAttempt::query()->count());
        $this->assertSame(2, QuizAttempt::query()->first()->score);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayer::class, ['course_slug' => $course->slug])
            ->set('quizAnswers', [$question->id => $correctOption->id])
            ->call('submitQuiz', $quiz->id)
            ->assertSet('quizError', __('web.quizzes.attempted_message'));
    }

    public function test_submit_incomplete_quiz_sets_error_and_does_not_throw(): void
    {
        $user = User::factory()->create();
        $course = $this->createCourse();
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'title' => 'Incomplete Quiz',
            'passing_score' => 1,
        ]);

        QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'Question 1',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayer::class, ['course_slug' => $course->slug])
            ->call('submitQuiz', $quiz->id)
            ->assertSet('quizError', __('web.quizzes.not_available'));
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => Str::slug(Str::random(12)),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => Str::slug(Str::random(12)),
            'status' => 'published',
        ]);
    }
}
