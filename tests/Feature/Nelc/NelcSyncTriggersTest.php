<?php

namespace Tests\Feature\Nelc;

use App\Enums\CertificateStatus;
use App\Enums\QuizQuestionType;
use App\Jobs\Nelc\SyncAttemptedJob;
use App\Jobs\Nelc\SyncEarnedJob;
use App\Jobs\Nelc\SyncInitializedJob;
use App\Jobs\Nelc\SyncRatedJob;
use App\Jobs\Nelc\SyncRegisteredJob;
use App\Livewire\Dashboard\CoursePlayer\Quiz as DashboardCoursePlayerQuiz;
use App\Livewire\DashboardCertificateForm;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\CourseReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NelcSyncTriggersTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_certificate_request_submission_dispatches_registered_job_but_not_a_resubmit(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        Queue::fake();

        Livewire::actingAs($user)
            ->test(DashboardCertificateForm::class)
            ->set('idNumber', '1234567890')
            ->set('firstNameAr', 'أحمد')
            ->set('secondNameAr', 'محمد')
            ->set('thirdNameAr', 'علي')
            ->set('lastNameAr', 'سالم')
            ->set('birthDate', '1995-05-15')
            ->set('gender', 'male')
            ->set('nationality', 'Saudi')
            ->set('qualification', 'Bachelor')
            ->set('email', 'student@example.com')
            ->set('phone', '+966500000000')
            ->set('firstNameEn', 'Ahmed')
            ->set('secondNameEn', 'Mohamed')
            ->set('thirdNameEn', 'Ali')
            ->set('lastNameEn', 'Salem')
            ->call('submit');

        Queue::assertPushed(SyncRegisteredJob::class, 1);

        Livewire::actingAs($user)
            ->test(DashboardCertificateForm::class)
            ->set('courseGroupId', $group->id)
            ->set('qualification', 'Master')
            ->call('submit');

        Queue::assertPushed(SyncRegisteredJob::class, 1);
    }

    public function test_first_lesson_view_dispatches_initialized_job_only_once(): void
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
            'title' => ['en' => 'Lesson 1'],
            'slug' => 'lesson-'.Str::uuid(),
            'type' => 'text',
            'content' => '<p>Body</p>',
            'sort_order' => 1,
        ]);

        Queue::fake();

        $this->actingAs($user)
            ->get(route('dashboard.course-player.lesson', [$course->slug, $group->id, $lesson->id]))
            ->assertOk();

        Queue::assertPushed(SyncInitializedJob::class, 1);

        $this->actingAs($user)
            ->get(route('dashboard.course-player.lesson', [$course->slug, $group->id, $lesson->id]))
            ->assertOk();

        Queue::assertPushed(SyncInitializedJob::class, 1);
    }

    public function test_quiz_submission_dispatches_attempted_job(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $day = CourseCurriculumSection::query()->create([
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'title' => ['en' => 'Day 1'],
            'sort_order' => 1,
        ]);

        $quiz = Quiz::query()->create([
            'course_id' => $course->id,
            'course_curriculum_section_id' => $day->id,
            'title' => 'Quiz 1',
            'passing_score' => 1,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question' => 'Q1',
            'type' => QuizQuestionType::Mcq,
            'points' => 1,
        ]);

        $option = QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Correct',
            'is_correct' => true,
        ]);

        QuizOption::query()->create([
            'quiz_question_id' => $question->id,
            'label' => 'Wrong',
            'is_correct' => false,
        ]);

        Queue::fake();

        Livewire::actingAs($user)
            ->test(DashboardCoursePlayerQuiz::class, ['course_slug' => $course->slug, 'group' => $group->id, 'quiz' => $quiz->id])
            ->set('quizAnswers', [$question->id => $option->id])
            ->call('submitQuiz')
            ->assertHasNoErrors();

        Queue::assertPushed(SyncAttemptedJob::class, 1);
    }

    public function test_certificate_approval_dispatches_earned_job_only_on_approval(): void
    {
        $user = User::factory()->create();
        ['course' => $course, 'group' => $group] = $this->createEnrollmentFor($user);

        $certificate = Certificate::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => CertificateStatus::Pending,
        ]);

        Queue::fake();

        $certificate->update(['file_path' => 'certificates/example.pdf']);
        Queue::assertNotPushed(SyncEarnedJob::class);

        $certificate->update(['status' => CertificateStatus::Approved]);
        Queue::assertPushed(SyncEarnedJob::class, 1);
    }

    public function test_course_review_submission_dispatches_rated_job(): void
    {
        $user = User::factory()->create();
        ['course' => $course] = $this->createEnrollmentFor($user);

        Queue::fake();

        app(CourseReviewService::class)->addReview($course, $user, 5, 'Great course');

        Queue::assertPushed(SyncRatedJob::class, 1);
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
