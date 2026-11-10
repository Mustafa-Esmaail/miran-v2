<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewCourseGroupContentNotification;
use App\Notifications\PaymentSuccessNotification;
use App\Services\EmailLoginLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailLoginLinkTest extends TestCase
{
    use RefreshDatabase;

    private const TARGET_PATH = '/dashboard/courses/test-course/5/sessions/893';

    private function student(string $status = User::STATUS_ACTIVE): User
    {
        return User::factory()->create(['status' => $status]);
    }

    private function linkFor(User $user, string $target = self::TARGET_PATH): string
    {
        return app(EmailLoginLinkService::class)->urlFor($user, $target);
    }

    public function test_valid_link_logs_the_student_in_and_opens_the_target_page(): void
    {
        $student = $this->student();

        $this->get($this->linkFor($student))
            ->assertRedirect(self::TARGET_PATH);

        $this->assertAuthenticatedAs($student, 'web');
    }

    public function test_link_still_works_before_three_days_pass(): void
    {
        $student = $this->student();
        $link = $this->linkFor($student);

        $this->travel(EmailLoginLinkService::VALID_FOR_DAYS)->days();
        $this->travel(-1)->minutes();

        $this->get($link)->assertRedirect(self::TARGET_PATH);

        $this->assertAuthenticatedAs($student, 'web');
    }

    public function test_expired_link_redirects_to_login_and_remembers_the_target_page(): void
    {
        $student = $this->student();
        $link = $this->linkFor($student);

        $this->travel(EmailLoginLinkService::VALID_FOR_DAYS)->days();
        $this->travel(1)->minutes();

        $this->get($link)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.login_link_expired')])
            ->assertSessionHas('url.intended', url(self::TARGET_PATH));

        $this->assertGuest('web');
    }

    public function test_student_returns_to_target_page_after_logging_in_from_expired_link(): void
    {
        $student = User::factory()->create(['status' => User::STATUS_ACTIVE, 'password' => 'secret-pass']);
        $link = $this->linkFor($student);

        $this->travel(EmailLoginLinkService::VALID_FOR_DAYS + 1)->days();

        $this->get($link)->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $student->email, 'password' => 'secret-pass'])
            ->assertRedirect(url(self::TARGET_PATH));
    }

    public function test_tampered_link_does_not_log_in(): void
    {
        $student = $this->student();
        $link = str_replace(urlencode(self::TARGET_PATH), urlencode('/dashboard/orders'), $this->linkFor($student));

        $this->get($link)->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_link_for_another_user_id_does_not_log_in(): void
    {
        $student = $this->student();
        $other = $this->student();
        $link = str_replace('/login/email/'.$student->id, '/login/email/'.$other->id, $this->linkFor($student));

        $this->get($link)->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_suspended_student_is_sent_to_login(): void
    {
        $student = $this->student(User::STATUS_SUSPENDED);

        $this->get($this->linkFor($student))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.account_inactive')]);

        $this->assertGuest('web');
    }

    public function test_link_for_deleted_user_record_is_sent_to_login(): void
    {
        $student = $this->student();
        $link = $this->linkFor($student);
        $student->forceDelete();

        $this->get($link)->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_already_logged_in_user_is_sent_to_target_without_switching_account(): void
    {
        $student = $this->student();
        $currentUser = $this->student();

        $this->actingAs($currentUser, 'web')
            ->get($this->linkFor($student))
            ->assertRedirect(self::TARGET_PATH);

        $this->assertAuthenticatedAs($currentUser, 'web');
    }

    public function test_redirect_outside_the_dashboard_falls_back_to_dashboard_home(): void
    {
        $student = $this->student();

        foreach (['https://evil.example/steal', '//evil.example/dashboard', '/admin/users', '/login'] as $target) {
            $this->get($this->linkFor($student, $target))
                ->assertRedirect(route('dashboard', absolute: false));

            auth('web')->logout();
        }
    }

    public function test_hand_signed_link_with_external_redirect_is_sanitized(): void
    {
        $student = $this->student();
        $link = url(URL::temporarySignedRoute('login.email', now()->addDay(), [
            'user' => $student->id,
            'redirect' => 'https://evil.example',
        ], absolute: false));

        $this->get($link)->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_content_email_button_uses_login_link(): void
    {
        $student = $this->student();

        $course = (new Course)->forceFill(['slug' => 'test-course', 'title' => ['ar' => 'Course']]);
        $group = (new CourseGroup)->forceFill(['id' => 5, 'title' => ['ar' => 'Group']])->setRelation('course', $course);
        $session = (new CourseGroupSession)->forceFill(['id' => 893, 'title' => 'Session'])->setRelation('courseGroup', $group);

        $actionUrl = (new NewCourseGroupContentNotification($session))->toMail($student)->actionUrl;

        $this->assertStringStartsWith(url('/login/email/'.$student->id), $actionUrl);
        $this->assertStringContainsString('signature=', $actionUrl);
        $this->assertStringContainsString(urlencode('/dashboard/courses/test-course/5'), $actionUrl);

        $this->get($actionUrl)->assertRedirect('/dashboard/courses/test-course/5');
        $this->assertAuthenticatedAs($student, 'web');
    }

    public function test_payment_success_email_button_uses_login_link(): void
    {
        $student = $this->student();

        $order = (new Order)->forceFill([
            'order_no' => 'ORD-1001',
            'user_id' => $student->id,
            'currency' => 'SAR',
            'total_amount' => 500,
        ])->setRelation('items', collect());

        $actionUrl = (new PaymentSuccessNotification($order))->toMail($student)->viewData['actionUrl'];

        $this->assertStringStartsWith(url('/login/email/'.$student->id), $actionUrl);
        $this->assertStringContainsString('signature=', $actionUrl);

        $this->get($actionUrl)->assertRedirect('/dashboard/orders/ORD-1001');
        $this->assertAuthenticatedAs($student, 'web');
    }
}
