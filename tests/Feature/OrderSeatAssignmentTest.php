<?php

namespace Tests\Feature;

use App\Enums\OrderItemSeatStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\OrderApprovalService;
use App\Services\OrderSeatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderSeatAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'admin');
        Role::findOrCreate('student', 'web');
    }

    public function test_checkout_flow_a_creates_invited_enrollments_on_payment(): void
    {
        $user = User::factory()->create();
        $group = $this->createCourseGroup();
        $cartItem = $this->createCartItem($user, $group, 3);

        $checkout = $this->app->make(CheckoutService::class);
        $order = $checkout->createOrderFromCart($user->id, [
            $cartItem->id => ['5550001', '5550002'],
        ]);

        $this->assertNotNull($order);
        $this->assertSame(3, $order->items()->first()->seats()->count());
        $this->assertSame(3, $order->items()->first()->seats()->where('status', OrderItemSeatStatus::Invited)->count());
        $this->assertSame(0, $order->items()->first()->seats()->where('status', OrderItemSeatStatus::Unassigned)->count());

        $this->app->make(OrderApprovalService::class)->approve($order);
        $order->refresh();

        $this->assertSame(2, Enrollment::query()->where('is_invited', true)->count());
        $this->assertSame(1, Enrollment::query()->where('user_id', $user->id)->where('is_invited', false)->count());
        $this->assertSame(3, $order->items()->first()->seats()->where('status', OrderItemSeatStatus::Enrolled)->count());
    }

    public function test_checkout_flow_b_allows_assigning_seats_after_payment(): void
    {
        $user = User::factory()->create();
        $group = $this->createCourseGroup();
        $cartItem = $this->createCartItem($user, $group, 2);

        $checkout = $this->app->make(CheckoutService::class);
        $order = $checkout->createOrderFromCart($user->id);

        $this->assertNotNull($order);
        $this->app->make(OrderApprovalService::class)->approve($order);
        $order->refresh();

        $this->assertSame(1, Enrollment::query()->where('user_id', $user->id)->where('is_invited', false)->count());

        $orderItem = $order->items()->first();
        $this->app->make(OrderSeatService::class)->assignSeatToPhone($orderItem, '5550003', 'Invited User', 'invitee@example.com');

        $invited = User::query()->where('phone', '5550003')->first();
        $this->assertNotNull($invited);
        $this->assertSame(1, Enrollment::query()->where('user_id', $invited->id)->where('is_invited', true)->count());
        $this->assertSame(0, $orderItem->seats()->where('status', OrderItemSeatStatus::Unassigned)->count());
        $this->assertSame(1, Enrollment::query()->where('user_id', $user->id)->where('is_invited', false)->count());
    }

    public function test_quantity_one_assigns_seat_to_purchaser(): void
    {
        $user = User::factory()->create();
        $group = $this->createCourseGroup();
        $this->createCartItem($user, $group, 1);

        $checkout = $this->app->make(CheckoutService::class);
        $order = $checkout->createOrderFromCart($user->id);

        $this->assertNotNull($order);
        $orderItem = $order->items()->first();
        $this->assertSame(1, $orderItem->seats()->count());
        $this->assertSame(1, $orderItem->seats()->where('status', OrderItemSeatStatus::Invited)->count());

        $this->app->make(OrderApprovalService::class)->approve($order);
        $order->refresh();

        $this->assertSame(1, Enrollment::query()->where('user_id', $user->id)->where('is_invited', false)->count());
        $this->assertSame(0, Enrollment::query()->where('is_invited', true)->count());
        $this->assertSame(1, $orderItem->seats()->where('status', OrderItemSeatStatus::Enrolled)->count());
    }

    public function test_cannot_reduce_quantity_below_assigned_seats(): void
    {
        $user = User::factory()->create();
        $group = $this->createCourseGroup();
        $cartItem = $this->createCartItem($user, $group, 3);

        $checkout = $this->app->make(CheckoutService::class);
        $order = $checkout->createOrderFromCart($user->id, [
            $cartItem->id => ['5550004', '5550005'],
        ]);

        $orderItem = $order->items()->first();

        $this->expectException(RuntimeException::class);
        $this->app->make(OrderSeatService::class)->updateOrderItemQuantity($orderItem, 2);
    }

    public function test_quantity_two_keeps_one_purchaser_and_one_available_when_no_invites(): void
    {
        $user = User::factory()->create();
        $group = $this->createCourseGroup();
        $this->createCartItem($user, $group, 2);

        $checkout = $this->app->make(CheckoutService::class);
        $order = $checkout->createOrderFromCart($user->id);

        $this->assertNotNull($order);
        $orderItem = $order->items()->first();

        $this->assertSame(2, $orderItem->seats()->count());
        $this->assertSame(1, $orderItem->seats()->where('status', OrderItemSeatStatus::Invited)->count());
        $this->assertSame(1, $orderItem->seats()->where('status', OrderItemSeatStatus::Unassigned)->count());
    }

    public function test_assigning_with_existing_email_does_not_create_duplicate_user(): void
    {
        $user = User::factory()->create(['email' => 'student1@example.com', 'phone' => '5000001']);
        $buyer = User::factory()->create();
        $group = $this->createCourseGroup();
        $this->createCartItem($buyer, $group, 2);

        $checkout = $this->app->make(CheckoutService::class);
        $order = $checkout->createOrderFromCart($buyer->id);

        $this->assertNotNull($order);
        $orderItem = $order->items()->first();

        $this->app->make(OrderSeatService::class)->assignSeatToPhone(
            $orderItem,
            '5000002',
            'Student One',
            'student1@example.com'
        );

        $this->assertSame(1, User::query()->where('email', 'student1@example.com')->count());
        $this->assertTrue(
            $orderItem->seats()->where('invited_user_id', $user->id)->exists()
        );
    }

    private function createCourseGroup(): CourseGroup
    {
        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'cat-'.$this->randomSlug(),
            'is_active' => true,
        ]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-'.$this->randomSlug(),
            'status' => 'published',
        ]);

        return CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
    }

    private function createCartItem(User $user, CourseGroup $group, int $quantity): CartItem
    {
        $cart = Cart::create(['user_id' => $user->id]);

        return CartItem::create([
            'cart_id' => $cart->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 10000,
            'quantity' => $quantity,
        ]);
    }

    private function randomSlug(): string
    {
        return (string) random_int(1000, 9999);
    }
}
