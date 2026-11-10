<?php

namespace Tests\Unit\Nelc;

use App\Enums\NelcSyncStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\NelcSyncLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Nelc\NelcSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class NelcSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.nelc.enabled' => true,
            'services.nelc.lrs_endpoint' => 'https://lrs.example.com/statements',
            'services.nelc.lrs_username' => 'nelc-user',
            'services.nelc.lrs_password' => 'nelc-secret',
            'services.nelc.platform_ar' => 'منصة ميران',
            'services.nelc.platform_en' => 'Miran Platform',
        ]);
    }

    public function test_sync_rated_sends_statement_and_logs_success(): void
    {
        Http::fake([
            'lrs.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        ['course' => $course, 'user' => $user] = $this->createEnrollment();

        $review = CourseReview::query()->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'rating' => 5,
            'content' => 'Great course',
            'status' => 'approved',
        ]);

        app(NelcSyncService::class)->syncRated($review);

        $this->assertSame(1, NelcSyncLog::query()->count());

        $log = NelcSyncLog::query()->sole();
        $this->assertSame('Rated', $log->event_type);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame($course->id, $log->course_id);
        $this->assertSame(NelcSyncStatus::Sent, $log->status);
        $this->assertSame('http://id.tincanapi.com/verb/rated', $log->payload['verb']['id']);
        $this->assertSame(5, $log->payload['result']['score']['raw']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://lrs.example.com/statements'
                && $request->hasHeader('X-Experience-API-Version', '1.0.3');
        });
    }

    public function test_sync_rated_logs_failure_on_non_2xx_response(): void
    {
        Http::fake([
            'lrs.example.com/*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        ['course' => $course, 'user' => $user] = $this->createEnrollment();

        $review = CourseReview::query()->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'rating' => 3,
            'content' => 'Ok course',
            'status' => 'approved',
        ]);

        app(NelcSyncService::class)->syncRated($review);

        $log = NelcSyncLog::query()->sole();
        $this->assertSame(NelcSyncStatus::Failed, $log->status);
        $this->assertNull($log->synced_at);
    }

    public function test_sync_is_skipped_when_nelc_disabled(): void
    {
        config(['services.nelc.enabled' => false]);

        Http::fake();

        ['course' => $course, 'user' => $user] = $this->createEnrollment();

        $review = CourseReview::query()->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'rating' => 4,
            'content' => 'Fine',
            'status' => 'approved',
        ]);

        app(NelcSyncService::class)->syncRated($review);

        $this->assertSame(0, NelcSyncLog::query()->count());
        Http::assertNothingSent();
    }

    /**
     * @return array{course: Course, group: CourseGroup, user: User}
     */
    private function createEnrollment(): array
    {
        $user = User::factory()->create();

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

        return ['course' => $course, 'group' => $group, 'user' => $user];
    }
}
