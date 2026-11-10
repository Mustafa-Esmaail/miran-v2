<?php

namespace Tests\Feature;

use App\Enums\OrderItemSeatStatus;
use App\Filament\Admin\Resources\CourseGroups\CourseGroupResource;
use App\Filament\Admin\Resources\Courses\CourseResource;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Orders\RelationManagers\CrmActivitiesRelationManager;
use App\Filament\Admin\Resources\Orders\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Admin\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\Orders\RelationManagers\OrderAttributionsRelationManager;
use App\Filament\Admin\Resources\Orders\RelationManagers\OrderItemSeatsRelationManager;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CrmActivity;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\OrderItem;
use App\Models\OrderItemSeat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderResourceNewRelationManagersTest extends TestCase
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

    private function makeFullOrder(): Order
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'rm-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Clickable Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'rm-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Clickable Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'customer_name' => 'Jane Doe',
            'customer_phone' => '0501234567',
            'order_no' => 'ORD-RM-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Clickable Group',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'course_group',
                'course_group_id' => $group->id,
                'course_id' => $course->id,
            ],
            'quantity' => 1,
        ]);

        OrderItemSeat::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'purchaser_user_id' => $user->id,
            'status' => OrderItemSeatStatus::Unassigned,
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        OrderAttribution::query()->create([
            'order_id' => $order->id,
            'sales_user_id' => $user->id,
            'attribution_type' => 'manual',
            'credited_amount' => 500,
            'created_at' => now(),
        ]);

        CrmActivity::query()->create([
            'entity_type' => Order::class,
            'entity_id' => $order->id,
            'type' => 'note',
            'subject' => 'Order placed via referral',
            'created_at' => now(),
        ]);

        return $order->fresh();
    }

    public function test_items_relation_manager_links_title_and_group_to_their_resources(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeFullOrder();
        $course = Course::query()->where('slug', 'rm-course')->firstOrFail();
        $group = CourseGroup::query()->where('course_id', $course->id)->firstOrFail();

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->assertSee(CourseResource::getUrl('edit', ['record' => $course->id]), escape: false)
            ->assertSee(CourseGroupResource::getUrl('edit', ['record' => $group->id]), escape: false);
    }

    public function test_seat_assignments_relation_manager_renders(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeFullOrder();

        Livewire::test(OrderItemSeatsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->assertSee('Jane Doe');
    }

    public function test_enrollments_relation_manager_renders(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeFullOrder();

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->assertSee('Clickable Course');
    }

    public function test_attribution_relation_manager_renders(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeFullOrder();

        Livewire::test(OrderAttributionsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->assertSee('Jane Doe');
    }

    public function test_crm_activities_relation_manager_renders(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeFullOrder();

        Livewire::test(CrmActivitiesRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->assertSee('Order placed via referral');
    }
}
