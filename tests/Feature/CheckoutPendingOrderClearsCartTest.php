<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutForm;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutPendingOrderClearsCartTest extends TestCase
{
    use RefreshDatabase;

    private function makeGroup(): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'pending-order-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Paid Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'pending-order-course',
            'status' => 'published',
            'price' => 1350,
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Paid Group'],
            'group_price_amount' => 1350,
            'status' => 'open',
        ]);
    }

    public function test_bank_transfer_checkout_clears_the_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $group = $this->makeGroup();

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('paymentMethod', 'bank_transfer')
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cash_checkout_clears_the_cart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $group = $this->makeGroup();

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        Livewire::test(CheckoutForm::class)
            ->set('paymentMethod', 'cash')
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }
}
