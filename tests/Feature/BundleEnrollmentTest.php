<?php

namespace Tests\Feature;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_purchase_creates_enrollments_for_selected_groups(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'bundle-category',
            'is_active' => true,
        ]);
        $courseA = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course A'],
            'summary' => ['en' => 'Course A'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-a',
            'status' => 'published',
        ]);
        $courseB = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course B'],
            'summary' => ['en' => 'Course B'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-b',
            'status' => 'published',
        ]);
        $groupA = CourseGroup::create([
            'course_id' => $courseA->id,
            'title' => ['en' => 'Course A Group'],
            'group_price_amount' => 12000,
            'enrollment_deadline_at' => now()->addDays(5),
            'status' => 'open',
        ]);
        $groupA2 = CourseGroup::create([
            'course_id' => $courseA->id,
            'title' => ['en' => 'Course A Group 2'],
            'group_price_amount' => 12000,
            'enrollment_deadline_at' => now()->addDays(5),
            'status' => 'open',
        ]);
        $groupB = CourseGroup::create([
            'course_id' => $courseB->id,
            'title' => ['en' => 'Course B Group'],
            'group_price_amount' => 15000,
            'enrollment_deadline_at' => now()->addDays(5),
            'status' => 'open',
        ]);
        $groupB2 = CourseGroup::create([
            'course_id' => $courseB->id,
            'title' => ['en' => 'Course B Group 2'],
            'group_price_amount' => 15000,
            'enrollment_deadline_at' => now()->addDays(5),
            'status' => 'open',
        ]);

        $bundle = Bundle::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Bundle'],
            'summary' => ['en' => 'Bundle summary'],
            'description' => ['en' => 'Bundle description'],
            'slug' => 'bundle',
            'price' => 25000,
            'is_active' => true,
        ]);
        $bundle->courses()->sync([$courseA->id, $courseB->id]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_no' => 'ORD-BUNDLE-1',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 25000,
            'discount_amount' => 0,
            'total_amount' => 25000,
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Bundle',
            'price_amount' => 25000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'selected_course_groups' => [
                    $courseA->id => $groupA2->id,
                    $courseB->id => $groupB2->id,
                ],
                'selected_course_group_ids' => [$groupA2->id, $groupB2->id],
            ],
            'quantity' => 1,
        ]);

        $service = $this->app->make(EnrollmentService::class);
        $service->activateForOrder($order);

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_group_id' => $groupA2->id,
            'order_item_id' => $item->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'course_group_id' => $groupB2->id,
            'order_item_id' => $item->id,
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $user->id,
            'course_group_id' => $groupA->id,
            'order_item_id' => $item->id,
        ]);
        $this->assertDatabaseMissing('enrollments', [
            'user_id' => $user->id,
            'course_group_id' => $groupB->id,
            'order_item_id' => $item->id,
        ]);
        $this->assertSame(2, Enrollment::query()->count());
    }
}
