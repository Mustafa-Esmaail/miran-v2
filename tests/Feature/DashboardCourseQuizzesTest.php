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

class DashboardCourseQuizzesTest extends TestCase
{
    use RefreshDatabase;

    public function test_quizzes_tab_receives_quiz_data(): void
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
            'passing_score' => 70,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'Question 1',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);
        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Option A',
            'is_correct' => true,
        ]);

        QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 80,
            'answers' => [],
        ]);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayer::class, ['course_slug' => $course->slug])
            ->assertViewHas('quizzes', function ($quizzes) {
                $first = $quizzes->first();

                return $first
                    && $first['questions_count'] === 1
                    && $first['status'] === 'passed'
                    && $first['score'] === 80;
            });
    }

    public function test_quizzes_without_options_are_not_shown(): void
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
            'question' => 'Question without options',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayer::class, ['course_slug' => $course->slug])
            ->assertViewHas('quizzes', function ($quizzes) use ($quiz) {
                return ! $quizzes->contains(fn (array $item) => $item['id'] === $quiz->id);
            });
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
