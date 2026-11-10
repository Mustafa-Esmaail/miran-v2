<?php

namespace Tests\Feature;

use App\Livewire\Cart\CartPanel;
use App\Livewire\Courses\CourseShow;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GuestCartTest extends TestCase
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

    public function test_guest_adding_a_second_item_does_not_remove_the_first(): void
    {
        $groupA = $this->makeGroup('Course A', 1000);
        $groupB = $this->makeGroup('Course B', 2000);

        Livewire::test(CourseShow::class, ['slug' => $groupA->course->slug])
            ->set('courseGroupId', (string) $groupA->id)
            ->call('addCourseToCart');

        Livewire::test(CourseShow::class, ['slug' => $groupB->course->slug])
            ->set('courseGroupId', (string) $groupB->id)
            ->call('addCourseToCart');

        $this->assertDatabaseHas('cart_items', ['purchasable_type' => CourseGroup::class, 'purchasable_id' => $groupA->id]);
        $this->assertDatabaseHas('cart_items', ['purchasable_type' => CourseGroup::class, 'purchasable_id' => $groupB->id]);

        $cart = Cart::query()->whereNotNull('session_id')->first();
        $this->assertNotNull($cart);
        $this->assertCount(2, $cart->items);
    }

    public function test_guest_coupon_still_applies_after_adding_a_second_item(): void
    {
        $groupA = $this->makeGroup('Course A', 1000);
        $groupB = $this->makeGroup('Course B', 2000);

        Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'SAVE10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);

        Livewire::test(CourseShow::class, ['slug' => $groupA->course->slug])
            ->set('courseGroupId', (string) $groupA->id)
            ->call('addCourseToCart');

        Livewire::test(CartPanel::class)
            ->set('couponCode', 'SAVE10')
            ->call('applyCoupon');

        Livewire::test(CourseShow::class, ['slug' => $groupB->course->slug])
            ->set('courseGroupId', (string) $groupB->id)
            ->call('addCourseToCart');

        $cart = Cart::query()->whereNotNull('session_id')->first();
        $this->assertNotNull($cart->coupon_id);
        $this->assertCount(2, $cart->items);
    }

    public function test_guest_cart_merges_into_new_account_on_registration(): void
    {
        $group = $this->makeGroup('Course A', 1000);

        Coupon::query()->create([
            'title' => ['en' => 'Test'],
            'code' => 'SAVE10',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);

        Livewire::test(CourseShow::class, ['slug' => $group->course->slug])
            ->set('courseGroupId', (string) $group->id)
            ->call('addCourseToCart');

        Livewire::test(CartPanel::class)
            ->set('couponCode', 'SAVE10')
            ->call('applyCoupon');

        $guestCart = Cart::query()->whereNotNull('session_id')->firstOrFail();
        $this->assertCount(1, $guestCart->items);
        $this->assertNotNull($guestCart->coupon_id);

        $user = User::factory()->create();

        app(\App\Services\CartService::class)->mergeGuestCartIntoUser($guestCart->session_id, $user->id);

        $userCart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertCount(1, $userCart->items);
        $this->assertNotNull($userCart->coupon_id);
        $this->assertDatabaseMissing('carts', ['id' => $guestCart->id]);
    }
}
