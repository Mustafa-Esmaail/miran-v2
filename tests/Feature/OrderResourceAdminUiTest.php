<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Customers\CustomerResource;
use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderResourceAdminUiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithOrderPermissions(): User
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        foreach (['viewAny', 'view'] as $action) {
            Permission::query()->create(['name' => "orders.{$action}", 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo(['orders.viewAny', 'orders.view']);

        return $admin;
    }

    private function makeOrderWithPayment(): Order
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'phone' => '0559999999']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'order-ui-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'order-ui-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Jane Doe',
            'customer_phone' => '0501234567',
            'order_no' => 'ORD-UI-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Group',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'bank_transfer',
            'provider_reference' => 'REF-1',
            'status' => 'paid',
            'amount' => 1000,
        ]);

        return $order->fresh();
    }

    public function test_orders_list_renders_phone_and_payment_method_columns(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrderWithPayment();

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee($order->customer_phone)
            ->assertSee('Bank Transfer');
    }

    public function test_orders_list_shows_bank_transfer_even_without_a_payment_row(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrderWithPayment();
        $order->payments()->delete();

        \App\Models\BankTransferReceipt::query()->create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'receipt_path' => 'bank-receipts/test.pdf',
            'status' => 'submitted',
        ]);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee('Bank Transfer');
    }

    public function test_customer_name_column_links_to_customer_resource_edit_page(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrderWithPayment();

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee(CustomerResource::getUrl('edit', ['record' => $order->user_id]), escape: false);
    }

    public function test_order_view_page_shows_customer_and_checkout_contact_details(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrderWithPayment();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee($order->user->email)
            ->assertSee($order->user->phone)
            ->assertSee($order->customer_name)
            ->assertSee($order->customer_phone);
    }
}
