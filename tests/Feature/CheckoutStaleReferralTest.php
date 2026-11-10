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

class CheckoutStaleReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_succeeds_when_session_references_a_deleted_employee(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'stale-referral-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Free Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'stale-referral-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Free Group'],
            'group_price_amount' => 0,
            'status' => 'open',
        ]);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        session(['referral_employee_id' => 999999]);

        Livewire::test(CheckoutForm::class)
            ->assertSet('paymentMethod', 'free')
            ->call('placeOrder');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', ['referred_by_employee_id' => null]);
        $this->assertNull(session('referral_employee_id'));
    }
}
