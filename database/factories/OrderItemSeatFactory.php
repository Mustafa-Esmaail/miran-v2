<?php

namespace Database\Factories;

use App\Enums\OrderItemSeatStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItemSeat>
 */
class OrderItemSeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => ['en' => $this->faker->words(2, true)],
            'slug' => $this->faker->unique()->slug,
            'is_active' => true,
        ]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $this->faker->sentence(3)],
            'summary' => ['en' => $this->faker->sentence(6)],
            'description' => ['en' => $this->faker->paragraph],
            'slug' => $this->faker->unique()->slug,
            'status' => 'published',
        ]);
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $this->faker->words(2, true)],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'order_no' => 'ORD-'.$this->faker->unique()->numerify('########'),
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

        return [
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'purchaser_user_id' => $user->id,
            'invited_user_id' => null,
            'invited_phone' => null,
            'status' => OrderItemSeatStatus::Unassigned,
        ];
    }
}
