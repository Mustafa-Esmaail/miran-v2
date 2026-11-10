<?php

namespace Tests\Feature;

use App\Livewire\Cart\CartPanel;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CartCouponRevalidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(string $title, int $price): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $title],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'published',
            'price' => $price,
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $title.' Group'],
            'status' => 'open',
            'group_price_amount' => $price,
        ]);
    }

    public function test_removing_an_item_clears_a_coupon_that_no_longer_meets_min_items_count(): void
    {
        $user = User::factory()->create();
        $groupA = $this->makeGroup('Course A', 1000);
        $groupB = $this->makeGroup('Course B', 2000);

        Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'SAVE10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'min_items_count' => 2,
            'is_active' => true,
        ]);

        $service = app(CartService::class);
        $itemA = $service->addItem($user->id, CourseGroup::class, $groupA->id);
        $service->addItem($user->id, CourseGroup::class, $groupB->id);

        $reason = $service->applyCoupon($user->id, 'SAVE10');
        $this->assertNull($reason);

        $cart = Cart::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($cart->coupon_id);

        $service->removeItem($user->id, $itemA->id);

        $cart->refresh();
        $this->assertNull($cart->coupon_id);
        $this->assertSame('min_items_count', $service->consumeCouponRemovalReason($user->id));
        $this->assertNull($service->consumeCouponRemovalReason($user->id));
    }

    public function test_cart_panel_flashes_a_toast_when_removing_an_item_invalidates_the_coupon(): void
    {
        $user = User::factory()->create();
        $groupA = $this->makeGroup('Course A', 1000);
        $groupB = $this->makeGroup('Course B', 2000);

        Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'SAVE10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'min_items_count' => 2,
            'is_active' => true,
        ]);

        $service = app(CartService::class);
        $itemA = $service->addItem($user->id, CourseGroup::class, $groupA->id);
        $service->addItem($user->id, CourseGroup::class, $groupB->id);
        $service->applyCoupon($user->id, 'SAVE10');

        $this->actingAs($user);

        Livewire::test(CartPanel::class)
            ->call('removeItem', $itemA->id)
            ->assertDispatched('toast', message: __('web.cart.coupon_removed_cart_changed'));

        $cart = Cart::query()->where('user_id', $user->id)->first();
        $this->assertNull($cart->coupon_id);
    }
}
