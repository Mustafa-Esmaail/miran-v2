<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutDuplicatePurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    private function enroll(int $userId, Course $course, CourseGroup $group): void
    {
        $order = Order::query()->create([
            'user_id' => $userId,
            'order_no' => 'ORD-EXISTING-'.$group->id,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 10000,
            'discount_amount' => 0,
            'total_amount' => 10000,
        ]);

        $orderItem = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Group',
            'price_amount' => 10000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'course_group_id' => $group->id,
                'course_id' => $course->id,
            ],
            'quantity' => 1,
        ]);

        Enrollment::create([
            'user_id' => $userId,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);
    }

    public function test_same_group_repurchase_shows_invite_panel_and_succeeds_after_gifting(): void
    {
        $user = \App\Models\User::factory()->create(['phone' => '0551234567']);
        $this->actingAs($user);

        $category = Category::query()->create(['name' => ['en' => 'Category'], 'slug' => 'dup-same-cat', 'is_active' => true]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'dup-same-course',
            'status' => 'published',
            'price' => 10000,
        ]);
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $this->enroll($user->id, $course, $group);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);
        $itemId = \App\Models\CartItem::query()->where('cart_id', \App\Models\Cart::query()->where('user_id', $user->id)->value('id'))->value('id');

        $component = Livewire::test(CheckoutForm::class)
            ->call('placeOrder')
            ->assertSet('showDuplicateInvitePanel', true)
            ->assertSet('duplicateItemIds', [$itemId])
            ->assertSet('showDuplicateModal', false)
            ->assertSet('differentGroupDuplicateItemIds', []);

        $this->assertDatabaseCount('orders', 1);

        $component
            ->set("inviteEmails.{$itemId}", 'friend@example.com')
            ->set("invitePhones.{$itemId}", '0551234567')
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_different_group_repurchase_shows_confirm_back_popup_and_succeeds_after_confirm(): void
    {
        $user = \App\Models\User::factory()->create(['phone' => '0551234567']);
        $this->actingAs($user);

        $category = Category::query()->create(['name' => ['en' => 'Category'], 'slug' => 'dup-diff-cat', 'is_active' => true]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'dup-diff-course',
            'status' => 'published',
            'price' => 10000,
        ]);
        $enrolledGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
        $otherGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group B'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $this->enroll($user->id, $course, $enrolledGroup);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $otherGroup->id, 1);
        $itemId = \App\Models\CartItem::query()->where('cart_id', \App\Models\Cart::query()->where('user_id', $user->id)->value('id'))->value('id');

        $component = Livewire::test(CheckoutForm::class)
            ->call('placeOrder')
            ->assertSet('showDuplicateModal', true)
            ->assertSet('differentGroupDuplicateItemIds', [$itemId])
            ->assertSet('showDuplicateInvitePanel', false)
            ->assertSet('duplicateItemIds', []);

        $this->assertDatabaseCount('orders', 1);

        $component->call('confirmDuplicateAndContinue');

        $this->assertDatabaseCount('orders', 2);
        $component->assertSet('showDuplicateModal', false);
    }

    public function test_closing_the_different_group_popup_leaves_cart_untouched(): void
    {
        $user = \App\Models\User::factory()->create(['phone' => '0551234567']);
        $this->actingAs($user);

        $category = Category::query()->create(['name' => ['en' => 'Category'], 'slug' => 'dup-back-cat', 'is_active' => true]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'dup-back-course',
            'status' => 'published',
            'price' => 10000,
        ]);
        $enrolledGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group A'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
        $otherGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group B'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);

        $this->enroll($user->id, $course, $enrolledGroup);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $otherGroup->id, 1);

        Livewire::test(CheckoutForm::class)
            ->call('placeOrder')
            ->assertSet('showDuplicateModal', true)
            ->call('closeDuplicateModal')
            ->assertSet('showDuplicateModal', false)
            ->assertSet('differentGroupDuplicateItemIds', []);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 1);
    }
}
