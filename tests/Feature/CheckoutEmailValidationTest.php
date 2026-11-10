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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutEmailValidationTest extends TestCase
{
    use RefreshDatabase;

    private function addGroupToCart(User $user): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'email-validation-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Paid Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'email-validation-course',
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
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidEmails(): array
    {
        return [
            'missing tld' => ['hyma10808@gmail'],
            'missing domain' => ['hyma10808@'],
            'missing at sign' => ['hyma10808gmail.com'],
        ];
    }

    #[DataProvider('invalidEmails')]
    public function test_checkout_rejects_invalid_email(string $email): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addGroupToCart($user);

        Livewire::test(CheckoutForm::class)
            ->set('email', $email)
            ->set('paymentMethod', 'bank_transfer')
            ->call('placeOrder')
            ->assertHasErrors(['email']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_accepts_email_with_tld(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->addGroupToCart($user);

        Livewire::test(CheckoutForm::class)
            ->set('email', 'hyma10808@gmail.com')
            ->set('paymentMethod', 'bank_transfer')
            ->call('placeOrder')
            ->assertHasNoErrors(['email']);
    }
}
