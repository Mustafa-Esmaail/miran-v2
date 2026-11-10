<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\CartService;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutFreeOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_total_checkout_uses_free_method_and_skips_gateway(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'free-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Free Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'free-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Free Group'],
            'group_price_amount' => 0,
            'status' => 'open',
        ]);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        $component = Livewire::test(CheckoutForm::class)
            ->assertSet('paymentMethod', 'free')
            ->call('placeOrder');

        $order = Order::query()->firstOrFail();
        $component->assertRedirect(route('dashboard.orders.show', ['order_no' => $order->order_no]));

        $payment = Payment::query()->firstOrFail();

        $this->assertSame('paid', $order->status->value);
        $this->assertSame('free', $payment->provider);
        $this->assertSame('paid', $payment->status->value);
        $this->assertSame(1, Enrollment::query()->count());
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_course_marked_as_free_uses_free_checkout_even_when_group_has_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'free-flag-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Flag Free Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'flag-free-course',
            'status' => 'published',
            'price' => 250,
            'is_free' => true,
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Paid Group'],
            'group_price_amount' => 15000,
            'status' => 'open',
        ]);

        $cart = Cart::query()->create(['user_id' => $user->id]);

        CartItem::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'price_amount' => 0,
            'quantity' => 1,
        ]);

        $component = Livewire::test(CheckoutForm::class)
            ->assertSet('paymentMethod', 'free')
            ->call('placeOrder');

        $order = Order::query()->latest('id')->firstOrFail();
        $payment = Payment::query()->latest('id')->firstOrFail();

        $component->assertRedirect(route('dashboard.orders.show', ['order_no' => $order->order_no]));

        $this->assertSame(0, (int) $order->total_amount);
        $this->assertSame('free', $payment->provider);
        $this->assertSame('paid', $payment->status->value);
    }

    public function test_failed_tamara_checkout_keeps_cart_items(): void
    {
        Http::fake([
            'https://api-sandbox.tamara.co/checkout' => Http::response([
                'message' => 'Your order information is invalid',
                'errors' => [
                    ['error_code' => 'payment_type_unsupported'],
                ],
            ], 400),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('paymentsEnabled')->andReturn(true);
        $settings->shouldReceive('paymentProviderIsReady')->with('tamara')->andReturn(true);
        $settings->shouldReceive('paymentProviderIsReady')->with('tabby')->andReturn(false);
        $settings->shouldReceive('getValue')->with('payments.enable_bank_transfer', true)->andReturn(true);
        $settings->shouldReceive('paymentMethodLogos')->andReturn([]);
        $settings->shouldReceive('paymentProviderConfig')->with('tamara')->andReturn([
            'enabled' => true,
            'mode' => 'test',
            'api_key' => 'tamara-api-key',
            'init_api_key' => 'tamara-init-key',
            'payment_type' => '',
            'instalments' => 0,
            'base_url' => '',
            'public_key' => '',
            'secret_key' => '',
            'webhook_secret' => '',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = User::factory()->create([
            'phone' => '0551234567',
        ]);
        $this->actingAs($user);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'tamara-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Paid Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'paid-course',
            'status' => 'published',
            'price' => 1350,
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Paid Group'],
            'group_price_amount' => 1350,
            'status' => 'open',
        ]);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('paymentMethod', 'tamara')
            ->call('placeOrder')
            ->assertHasErrors('paymentMethod');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 1);
    }
}
