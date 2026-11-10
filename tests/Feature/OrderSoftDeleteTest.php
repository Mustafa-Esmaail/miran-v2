<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Orders\Pages\ListOrders;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWithOrderPermissions(User $user): void
    {
        $this->actingAs($user, 'admin');

        foreach (['viewAny', 'view', 'delete'] as $action) {
            Permission::query()->firstOrCreate(['name' => "orders.{$action}", 'guard_name' => 'admin']);
        }
        $user->givePermissionTo(['orders.viewAny', 'orders.view', 'orders.delete']);
    }

    private function makeOrder(string $orderNo): Order
    {
        return Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'customer_name' => 'Customer',
            'customer_phone' => '0501234567',
            'order_no' => $orderNo,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
    }

    public function test_deleting_an_order_soft_deletes_it(): void
    {
        $order = $this->makeOrder('ORD-SOFT-1');

        $order->delete();

        $this->assertSoftDeleted($order);
        $this->assertNotNull($order->fresh()->deleted_at);
    }

    public function test_deleting_an_enrollment_soft_deletes_it(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => ['ar' => 'Category'], 'slug' => 'cat-soft-delete', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course-soft-delete',
            'status' => 'published',
        ]);
        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
        $order = $this->makeOrder('ORD-ENROLLMENT-1');
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course',
            'price_amount' => 10000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'course_group_id' => $group->id,
                'course_id' => $course->id,
            ],
            'quantity' => 1,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $orderItem->id,
            'status' => 'active',
        ]);

        $enrollment->delete();

        $this->assertSoftDeleted($enrollment);
        $this->assertNotNull($enrollment->fresh()->deleted_at);
    }

    public function test_trashed_orders_are_hidden_from_the_default_listing(): void
    {
        $activeOrder = $this->makeOrder('ORD-ACTIVE-1');
        $trashedOrder = $this->makeOrder('ORD-TRASHED-1');
        $trashedOrder->delete();

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $this->actingAsWithOrderPermissions($admin);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertSee($activeOrder->order_no)
            ->assertDontSee($trashedOrder->order_no);
    }

    public function test_view_order_page_shows_deleted_by_info_for_a_trashed_order(): void
    {
        $order = $this->makeOrder('ORD-VIEW-TRASHED-1');
        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $this->actingAsWithOrderPermissions($admin);

        $order->delete();

        Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee($admin->name);
    }

    public function test_only_admin_roles_can_see_the_trashed_filter(): void
    {
        $regularUser = User::factory()->create();
        $this->actingAsWithOrderPermissions($regularUser);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertTableFilterHidden('trashed');

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));
        $this->actingAsWithOrderPermissions($admin);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertTableFilterVisible('trashed');

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']));
        $this->actingAsWithOrderPermissions($superAdmin);

        Livewire::test(ListOrders::class)
            ->assertOk()
            ->assertTableFilterVisible('trashed');
    }
}
