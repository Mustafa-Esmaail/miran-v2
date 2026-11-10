<?php

namespace Tests\Feature;

use App\Enums\QuizQuestionType;
use App\Livewire\Dashboard\CoursePlayer\Quiz as DashboardCoursePlayerQuiz;
use App\Models\Category;
use App\Models\ContentProgress;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardCoursePlayerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_session_and_quiz_pages_render_from_day_cards_flow(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $day = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $day->id,
            'title' => ['en' => 'Lesson page'],
            'slug' => 'lesson-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson body</p>',
            'sort_order' => 1,
        ]);

        $session = CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $day->id,
            'title' => 'Session page',
            'content' => '<p>Session body</p>',
            'start_at' => now()->addDay(),
            'duration_minutes' => 90,
            'join_url' => 'https://example.com/join-session',
            'attachments' => ['course-intros/session-handout.pdf'],
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $day->id,
            'title' => 'Quiz page',
            'passing_score' => 1,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'What is correct?',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        $option = QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Correct answer',
            'is_correct' => true,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Wrong answer',
            'is_correct' => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.lesson', [$course->slug, $group->id, $lesson->id]))
            ->assertOk()
            ->assertSee('Lesson page')
            ->assertSee('Lesson body', false)
            ->assertSee('Day 1');

        $this->actingAs($user)
            ->get(route('dashboard.course-player.session', [$course->slug, $group->id, $session->id]))
            ->assertOk()
            ->assertSee('Session page')
            ->assertSee('Session body', false)
            ->assertSee(__('web.course_player.session_statuses.upcoming'))
            ->assertSee(__('web.course_player.join_session'))
            ->assertSee($session->join_url ?? '', false);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.quiz', [$course->slug, $group->id, $quiz->id]))
            ->assertOk()
            ->assertSee('Quiz page')
            ->assertSee('What is correct?');

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayerQuiz::class, ['course_slug' => $course->slug, 'group' => $group->id, 'quiz' => $quiz->id])
            ->set('quizAnswers', [$question->id => $option->id])
            ->call('submitQuiz')
            ->assertHasNoErrors()
            ->assertSet('quizResults.score', 1);

        $this->assertSame(1, QuizAttempt::query()->count());
        $this->assertSame(1, QuizAttempt::query()->firstOrFail()->score);
    }

    public function test_lesson_page_nav_lists_all_days_and_their_content(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $firstDay = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 2'],
            'sort_order' => 2,
        ]);

        $firstLesson = Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $firstDay->id,
            'title' => ['en' => 'Lesson A'],
            'slug' => 'lesson-a-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson A body</p>',
            'sort_order' => 1,
        ]);

        Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => CourseCurriculumSection::query()
                ->where('course_group_id', $group->id)
                ->where('sort_order', 2)
                ->value('id'),
            'title' => ['en' => 'Lesson B'],
            'slug' => 'lesson-b-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson B body</p>',
            'sort_order' => 1,
        ]);

        Quiz::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $firstDay->id,
            'title' => 'Day 1 gate quiz',
            'passing_score' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.lesson', [$course->slug, $group->id, $firstLesson->id]))
            ->assertOk()
            ->assertSee('Day 1')
            ->assertSee('Lesson A')
            ->assertSee('Day 2')
            ->assertSee('Lesson B')
            ->assertSee(__('web.course_player.state_locked'));
    }

    public function test_day_page_shows_quiz_without_day_on_first_day(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $firstDay = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 2'],
            'sort_order' => 2,
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'title' => 'General quiz',
            'passing_score' => 1,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'Question',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Option',
            'is_correct' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.day', [$course->slug, $group->id, $firstDay->id]))
            ->assertOk()
            ->assertSee('General quiz');
    }

    public function test_second_day_redirects_to_first_accessible_day_when_previous_day_is_incomplete(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $firstDay = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $firstDay->id,
            'title' => ['en' => 'Locked prerequisite'],
            'slug' => 'prerequisite-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Prerequisite</p>',
            'sort_order' => 1,
        ]);

        $secondDay = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 2'],
            'sort_order' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.day', [$course->slug, $group->id, $secondDay->id]))
            ->assertRedirect(route('dashboard.course-player.day', [$course->slug, $group->id, $firstDay->id]))
            ->assertSessionHas('status', __('web.course_player.locked_day_message'));
    }

    public function test_second_day_unlocks_after_previous_day_requirements_are_completed(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $firstDay = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $firstDay->id,
            'title' => ['en' => 'Lesson 1'],
            'slug' => 'lesson-1-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson 1</p>',
            'sort_order' => 1,
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $firstDay->id,
            'title' => 'Day 1 quiz',
            'passing_score' => 1,
        ]);

        $secondDay = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 2'],
            'sort_order' => 2,
        ]);

        ContentProgress::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'contentable_type' => Lesson::class,
            'contentable_id' => $lesson->id,
        ]);

        QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'score' => 1,
            'answers' => [],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.day', [$course->slug, $group->id, $secondDay->id]))
            ->assertOk()
            ->assertSee('Day 2');
    }

    public function test_quiz_is_locked_until_day_lessons_and_sessions_are_completed(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $day = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $day->id,
            'title' => ['en' => 'Lesson before quiz'],
            'slug' => 'lesson-before-quiz-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Lesson body</p>',
            'sort_order' => 1,
        ]);

        $session = CourseGroupSession::query()->create([
            'course_group_id' => $group->id,
            'course_curriculum_section_id' => $day->id,
            'title' => 'Session before quiz',
            'start_at' => now()->addDay(),
            'duration_minutes' => 60,
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $day->id,
            'title' => 'Locked quiz',
            'passing_score' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.day', [$course->slug, $group->id, $day->id]))
            ->assertOk()
            ->assertSee('Locked quiz')
            ->assertSee(__('web.course_player.locked_quiz_hint'));

        $this->actingAs($user)
            ->get(route('dashboard.course-player.quiz', [$course->slug, $group->id, $quiz->id]))
            ->assertRedirect(route('dashboard.course-player.day', [$course->slug, $group->id, $day->id]))
            ->assertSessionHas('status', __('web.course_player.locked_quiz_message'));

        ContentProgress::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'contentable_type' => Lesson::class,
            'contentable_id' => $lesson->id,
        ]);

        ContentProgress::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'contentable_type' => CourseGroupSession::class,
            'contentable_id' => $session->id,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.quiz', [$course->slug, $group->id, $quiz->id]))
            ->assertOk()
            ->assertSee('Locked quiz');
    }

    /**
     * @return array{course: Course, group: CourseGroup}
     */
    private function createEnrollmentFor(User $user): array
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'order-'.Str::uuid(),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course group',
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'price_amount' => 10000,
            'quantity' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        return ['course' => $course, 'group' => $group];
    }
}
