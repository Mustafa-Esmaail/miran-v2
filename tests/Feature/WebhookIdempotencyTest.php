<?php

namespace Tests\Feature;

use App\DTOs\WebhookPayloadDto;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_idempotency_does_not_duplicate_enrollments(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat3', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Course'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course3',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Course'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => \App\Models\CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => $course->getTranslation('title', app()->getLocale()) ?? 'Course',
            'price_amount' => 10000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'course_group_id' => $group->id,
                'course_id' => $course->id,
            ],
            'quantity' => 1,
        ]);

        $payload = new WebhookPayloadDto('tamara', 'ref-1', 'paid', [
            'order_id' => $order->id,
            'amount' => 10000,
        ]);

        $service = $this->app->make(PaymentService::class);
        $service->handleWebhook($payload);
        $service->handleWebhook($payload);

        $this->assertSame(1, \App\Models\Enrollment::query()->count());
    }
}
