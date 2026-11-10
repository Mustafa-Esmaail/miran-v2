<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Filament\Admin\Resources\Bundles\Pages\ListBundles;
use App\Filament\Admin\Resources\CourseGroups\Pages\ListCourseGroups;
use App\Filament\Admin\Resources\Courses\Pages\ListCourses;
use App\Models\Bundle;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\WishlistItem;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeletionSafeguardsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $permissions = [
            'courses.viewAny', 'courses.view', 'courses.delete',
            'course_groups.viewAny', 'course_groups.view', 'course_groups.delete',
            'bundles.viewAny', 'bundles.view', 'bundles.delete',
            'users.viewAny', 'users.view', 'users.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);
        $admin->assignRole(Role::findOrCreate('admin', 'admin'));

        return $admin;
    }

    private function makeCourse(string $slug): Course
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'safeguard-category'],
            ['name' => ['en' => 'Category'], 'is_active' => true],
        );

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course '.$slug],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);
    }

    private function makeGroup(Course $course): CourseGroup
    {
        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'status' => 'open',
        ]);
    }

    private function makeBundle(string $slug): Bundle
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'safeguard-category'],
            ['name' => ['en' => 'Category'], 'is_active' => true],
        );

        return Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Bundle'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'price' => 100,
            'is_active' => true,
        ]);
    }

    private function makeOrderItemFor(User $customer, Bundle|CourseGroup $purchasable): OrderItem
    {
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_no' => 'ORD-SAFE-'.random_int(100000, 999999),
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
        ]);

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => $purchasable::class,
            'purchasable_id' => $purchasable->id,
            'title_snapshot' => 'Item',
            'price_amount' => 100,
            'currency' => 'SAR',
            'quantity' => 1,
        ]);
    }

    private function addToCart(User $customer, Bundle|CourseGroup $purchasable): CartItem
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $customer->id]);

        return CartItem::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $purchasable::class,
            'purchasable_id' => $purchasable->id,
            'price_amount' => 100,
            'quantity' => 1,
        ]);
    }

    public function test_course_with_groups_cannot_be_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $course = $this->makeCourse('safe-course-with-group');
        $this->makeGroup($course);

        $this->assertFalse($admin->can('delete', $course));

        Livewire::test(ListCourses::class)
            ->selectTableRecords([$course->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertNotSoftDeleted($course);
    }

    public function test_unused_course_is_soft_deleted_and_can_be_restored(): void
    {
        $this->actingAsAdmin();
        $course = $this->makeCourse('safe-course-unused');
        $wishlist = WishlistItem::query()->create([
            'user_id' => User::factory()->create()->id,
            'wishable_type' => Course::class,
            'wishable_id' => $course->id,
        ]);

        Livewire::test(ListCourses::class)
            ->selectTableRecords([$course->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertSoftDeleted($course);
        $this->assertModelMissing($wishlist);

        Livewire::test(ListCourses::class)
            ->filterTable('trashed', false)
            ->callAction(TestAction::make(RestoreAction::class)->table($course));

        $this->assertNotSoftDeleted($course);
    }

    public function test_group_with_soft_deleted_enrollment_cannot_be_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $group = $this->makeGroup($this->makeCourse('safe-group-enrolled'));
        $enrollment = Enrollment::query()->create([
            'user_id' => User::factory()->create()->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => EnrollmentStatus::Active,
        ]);
        $enrollment->delete();

        $this->assertFalse($admin->can('delete', $group));

        Livewire::test(ListCourseGroups::class)
            ->selectTableRecords([$group->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertNotSoftDeleted($group);
    }

    public function test_group_with_order_items_cannot_be_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $group = $this->makeGroup($this->makeCourse('safe-group-ordered'));
        $this->makeOrderItemFor(User::factory()->create(), $group);

        $this->assertFalse($admin->can('delete', $group));
    }

    public function test_unused_group_is_soft_deleted_and_removed_from_carts(): void
    {
        $this->actingAsAdmin();
        $group = $this->makeGroup($this->makeCourse('safe-group-unused'));
        $cartItem = $this->addToCart(User::factory()->create(), $group);

        Livewire::test(ListCourseGroups::class)
            ->selectTableRecords([$group->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertSoftDeleted($group);
        $this->assertModelMissing($cartItem);
    }

    public function test_group_cannot_be_restored_while_its_course_is_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $course = $this->makeCourse('safe-group-orphan');
        $group = $this->makeGroup($course);
        $group->delete();
        $course->delete();

        $this->assertFalse($admin->can('restore', $group->fresh()));

        $course->restore();

        $this->assertTrue($admin->can('restore', $group->fresh()));
    }

    public function test_bundle_with_orders_cannot_be_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $bundle = $this->makeBundle('safe-bundle-ordered');
        $this->makeOrderItemFor(User::factory()->create(), $bundle);

        $this->assertFalse($admin->can('delete', $bundle));

        Livewire::test(ListBundles::class)
            ->selectTableRecords([$bundle->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertModelExists($bundle);
    }

    public function test_unused_bundle_delete_removes_cart_and_wishlist_rows(): void
    {
        $this->actingAsAdmin();
        $bundle = $this->makeBundle('safe-bundle-unused');
        $customer = User::factory()->create();
        $cartItem = $this->addToCart($customer, $bundle);
        $wishlist = WishlistItem::query()->create([
            'user_id' => $customer->id,
            'wishable_type' => Bundle::class,
            'wishable_id' => $bundle->id,
        ]);

        Livewire::test(ListBundles::class)
            ->selectTableRecords([$bundle->id])
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertModelMissing($bundle);
        $this->assertModelMissing($cartItem);
        $this->assertModelMissing($wishlist);
    }

    public function test_user_with_history_cannot_be_deleted(): void
    {
        $admin = $this->actingAsAdmin();
        $group = $this->makeGroup($this->makeCourse('safe-user-history'));

        $withOrder = User::factory()->create();
        $this->makeOrderItemFor($withOrder, $group);

        $withEnrollment = User::factory()->create();
        Enrollment::query()->create([
            'user_id' => $withEnrollment->id,
            'course_id' => $group->course_id,
            'course_group_id' => $group->id,
            'status' => EnrollmentStatus::Active,
        ]);

        $this->assertFalse($admin->can('delete', $withOrder));
        $this->assertFalse($admin->can('delete', $withEnrollment));
        $this->assertSame(__('admin.validation.user_in_use'), Gate::forUser($admin)->inspect('delete', $withOrder)->message());
    }

    public function test_user_without_history_can_be_deleted(): void
    {
        $admin = $this->actingAsAdmin();

        $this->assertTrue($admin->can('delete', User::factory()->create()));
    }
}
