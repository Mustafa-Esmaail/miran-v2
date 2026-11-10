<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Orders\Pages\EditOrder;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Orders\RelationManagers\ActivityLogsRelationManager;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderResourceEditPriceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithOrderPermissions(): User
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        foreach (['viewAny', 'view', 'update'] as $action) {
            Permission::query()->create(['name' => "orders.{$action}", 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo(['orders.viewAny', 'orders.view', 'orders.update']);

        return $admin;
    }

    private function makeOrder(): Order
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'order-price-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'order-price-course',
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
            'order_no' => 'ORD-PRICE-1',
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

        return $order->fresh();
    }

    private function makeCoupon(): Coupon
    {
        return Coupon::query()->create([
            'title' => ['en' => 'Ten Percent Off'],
            'code' => 'TENOFF',
            'type' => 'percent',
            'applies_to' => 'both',
            'value' => 10,
            'is_active' => true,
        ]);
    }

    public function test_price_fields_are_not_editable(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrder();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertFormFieldDoesNotExist('subtotal_amount')
            ->assertFormFieldDoesNotExist('discount_amount')
            ->assertFormFieldDoesNotExist('total_amount')
            ->assertFormFieldExists('coupon_id');
    }

    public function test_changing_coupon_without_notes_fails_validation(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrder();
        $coupon = $this->makeCoupon();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->set('data.coupon_id', $coupon->id)
            ->set('data.notes', null)
            ->call('save')
            ->assertHasFormErrors(['notes' => 'required']);

        $this->assertNull($order->fresh()->coupon_id);
    }

    public function test_changing_coupon_with_notes_recalculates_pricing_and_logs_activity(): void
    {
        $admin = $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrder();
        $coupon = $this->makeCoupon();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->set('data.coupon_id', $coupon->id)
            ->set('data.notes', 'Applied loyalty coupon per customer request.')
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $order->fresh();
        $this->assertSame($coupon->id, $fresh->coupon_id);
        $this->assertSame(100, $fresh->discount_amount);
        $this->assertSame(1000, $fresh->subtotal_amount);
        $this->assertSame(900, $fresh->total_amount);
        $this->assertSame('Applied loyalty coupon per customer request.', $fresh->notes);

        $log = $fresh->activityLogs()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Applied loyalty coupon per customer request.', $log->notes);
        $this->assertArrayHasKey('coupon_id', $log->diff);
        $this->assertSame($coupon->id, $log->diff['coupon_id']['new']);
        $this->assertArrayHasKey('total_amount', $log->diff);
        $this->assertSame(900, $log->diff['total_amount']['new']);
    }

    public function test_unrelated_edit_without_coupon_change_does_not_require_notes(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrder();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->set('data.status', 'refunded')
            ->set('data.notes', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $order->fresh();
        $this->assertSame('refunded', $fresh->status->value);

        $log = $fresh->activityLogs()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('status', $log->diff);
    }

    public function test_activity_logs_relation_manager_renders_logged_changes(): void
    {
        $this->actingAsAdminWithOrderPermissions();
        $order = $this->makeOrder();
        $coupon = $this->makeCoupon();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->set('data.coupon_id', $coupon->id)
            ->set('data.notes', 'Applied loyalty coupon per customer request.')
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(ActivityLogsRelationManager::class, [
            'ownerRecord' => $order->fresh(),
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->assertSee('Applied loyalty coupon per customer request.')
            ->assertSee('coupon_id');
    }
}
