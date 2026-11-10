<?php

namespace Tests\Feature;

use App\Enums\OrderItemSeatStatus;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemSeat;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EditOrderItemActionTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(bool $withPaidPermission = true): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach (['orders.viewAny', 'orders.view', 'order_items.update'] as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo(['orders.viewAny', 'orders.view', 'order_items.update']);

        if ($withPaidPermission) {
            Permission::query()->firstOrCreate(['name' => 'order_items.update_paid', 'guard_name' => 'admin']);
            $admin->givePermissionTo('order_items.update_paid');
        }

        return $admin;
    }

    private function makeCategory(string $slug): Category
    {
        return Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeCourse(Category $category, string $slug, string $title): Course
    {
        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $title],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);
    }

    private function makeGroup(Course $course, string $title, int $price = 1000): CourseGroup
    {
        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => $title],
            'group_price_amount' => $price,
            'status' => 'open',
        ]);
    }

    private function makeBundle(Category $category, string $slug, string $title, int $price = 1000): Bundle
    {
        return Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => $title],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function makePaidOrder(User $student): Order
    {
        return Order::query()->create([
            'user_id' => $student->id,
            'customer_name' => $student->name,
            'customer_phone' => '0501234567',
            'order_no' => 'ORD-EDIT-'.random_int(100000, 999999),
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ]);
    }

    public function test_admin_can_swap_course_group_item_and_enrollment_is_updated_in_place(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-1');
        $courseA = $this->makeCourse($category, 'edit-item-course-a', 'Course A');
        $groupA = $this->makeGroup($courseA, 'Group A', 1000);
        $courseB = $this->makeCourse($category, 'edit-item-course-b', 'Course B');
        $groupB = $this->makeGroup($courseB, 'Group B', 1500);

        $order = $this->makePaidOrder($student);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Course A',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => ['type_label' => 'course_group', 'course_group_id' => $groupA->id, 'course_id' => $courseA->id],
            'quantity' => 1,
        ]);

        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseA->id,
            'course_group_id' => $groupA->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);
        $enrollmentId = $enrollment->id;

        $test = Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make('edit_item')->table($item), data: [
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $groupB->id,
                'price_amount' => 1500,
                'change_reason' => 'Customer purchased the wrong course group.',
            ]);
        $test->assertHasNoFormErrors();

        $item->refresh();
        $this->assertSame($groupB->id, $item->purchasable_id);
        $this->assertSame(1500, $item->price_amount);

        $order->refresh();
        $this->assertSame(1500, $order->subtotal_amount);
        $this->assertSame(1500, $order->total_amount);

        $this->assertSame(1, Enrollment::query()->where('order_item_id', $item->id)->count());

        $updated = Enrollment::query()->find($enrollmentId);
        $this->assertNotNull($updated);
        $this->assertSame($courseB->id, $updated->course_id);
        $this->assertSame($groupB->id, $updated->course_group_id);
        $this->assertSame('active', $updated->status->value);

        $log = $order->activityLogs()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Customer purchased the wrong course group.', $log->notes);
        $this->assertArrayHasKey('purchasable_id', $log->diff);
        $this->assertSame($groupB->id, $log->diff['purchasable_id']['new']);
    }

    public function test_bundle_item_group_change_only_updates_that_courses_enrollment(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-2');
        $courseX = $this->makeCourse($category, 'edit-item-course-x', 'Course X');
        $groupX = $this->makeGroup($courseX, 'Group X', 500);
        $courseY = $this->makeCourse($category, 'edit-item-course-y', 'Course Y');
        $groupY1 = $this->makeGroup($courseY, 'Group Y1', 500);
        $groupY2 = $this->makeGroup($courseY, 'Group Y2', 500);

        $bundle = $this->makeBundle($category, 'edit-item-bundle-1', 'Bundle 1', 1000);
        $bundle->courses()->attach([$courseX->id, $courseY->id]);

        $order = $this->makePaidOrder($student);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Bundle 1',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'bundle',
                'bundle_id' => $bundle->id,
                'bundle_course_count' => 2,
                'selected_course_groups' => [$courseX->id => $groupX->id, $courseY->id => $groupY1->id],
                'selected_course_group_ids' => [$groupX->id, $groupY1->id],
            ],
            'quantity' => 1,
        ]);

        $enrollmentX = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseX->id,
            'course_group_id' => $groupX->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);
        $enrollmentY = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseY->id,
            'course_group_id' => $groupY1->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->callAction(TestAction::make('edit_item')->table($item), data: [
                'purchasable_type' => Bundle::class,
                'purchasable_id' => $bundle->id,
                'price_amount' => 1000,
                'bundle_course_groups' => [$courseX->id => $groupX->id, $courseY->id => $groupY2->id],
                'change_reason' => 'Moved the student to a different Course Y group.',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(2, Enrollment::query()->where('order_item_id', $item->id)->count());

        $enrollmentX->refresh();
        $this->assertSame($groupX->id, $enrollmentX->course_group_id);

        $enrollmentY->refresh();
        $this->assertSame($groupY2->id, $enrollmentY->course_group_id);
    }

    public function test_bundle_item_swap_to_bundle_with_extra_course_creates_new_enrollment(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-3');
        $courseX = $this->makeCourse($category, 'edit-item-course-x2', 'Course X');
        $groupX = $this->makeGroup($courseX, 'Group X', 500);
        $courseY = $this->makeCourse($category, 'edit-item-course-y2', 'Course Y');
        $groupY = $this->makeGroup($courseY, 'Group Y', 500);
        $courseZ = $this->makeCourse($category, 'edit-item-course-z2', 'Course Z');
        $groupZ = $this->makeGroup($courseZ, 'Group Z', 500);

        $bundleSmall = $this->makeBundle($category, 'edit-item-bundle-small', 'Small Bundle', 1000);
        $bundleSmall->courses()->attach([$courseX->id, $courseY->id]);

        $bundleBig = $this->makeBundle($category, 'edit-item-bundle-big', 'Big Bundle', 1500);
        $bundleBig->courses()->attach([$courseX->id, $courseY->id, $courseZ->id]);

        $order = $this->makePaidOrder($student);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundleSmall->id,
            'title_snapshot' => 'Small Bundle',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'type_label' => 'bundle',
                'bundle_id' => $bundleSmall->id,
                'bundle_course_count' => 2,
                'selected_course_groups' => [$courseX->id => $groupX->id, $courseY->id => $groupY->id],
                'selected_course_group_ids' => [$groupX->id, $groupY->id],
            ],
            'quantity' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseX->id,
            'course_group_id' => $groupX->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);
        Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseY->id,
            'course_group_id' => $groupY->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->callAction(TestAction::make('edit_item')->table($item), data: [
                'purchasable_type' => Bundle::class,
                'purchasable_id' => $bundleBig->id,
                'price_amount' => 1500,
                'bundle_course_groups' => [
                    $courseX->id => $groupX->id,
                    $courseY->id => $groupY->id,
                    $courseZ->id => $groupZ->id,
                ],
                'change_reason' => 'Upgraded to the bigger bundle.',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(3, Enrollment::query()->where('order_item_id', $item->id)->count());
        $this->assertTrue(Enrollment::query()->where('order_item_id', $item->id)->where('course_group_id', $groupZ->id)->exists());
    }

    public function test_item_with_multiple_seats_cannot_be_edited(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $invitee = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-4');
        $course = $this->makeCourse($category, 'edit-item-course-seats', 'Course Seats');
        $group = $this->makeGroup($course, 'Group Seats', 1000);

        $order = $this->makePaidOrder($student);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course Seats',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 2,
        ]);

        OrderItemSeat::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'purchaser_user_id' => $student->id,
            'invited_user_id' => $student->id,
            'status' => OrderItemSeatStatus::Enrolled,
        ]);

        OrderItemSeat::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'purchaser_user_id' => $student->id,
            'invited_user_id' => $invitee->id,
            'status' => OrderItemSeatStatus::Enrolled,
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertActionHidden(TestAction::make('edit_item')->table($item));
    }

    public function test_item_with_a_single_purchaser_seat_can_be_edited_and_seat_is_synced(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-single-seat');
        $courseA = $this->makeCourse($category, 'edit-item-single-seat-course-a', 'Course A');
        $groupA = $this->makeGroup($courseA, 'Group A', 1000);
        $courseB = $this->makeCourse($category, 'edit-item-single-seat-course-b', 'Course B');
        $groupB = $this->makeGroup($courseB, 'Group B', 1300);

        $order = $this->makePaidOrder($student);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Course A',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => ['type_label' => 'course_group', 'course_group_id' => $groupA->id, 'course_id' => $courseA->id],
            'quantity' => 1,
        ]);

        $seat = OrderItemSeat::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'course_id' => $courseA->id,
            'course_group_id' => $groupA->id,
            'purchaser_user_id' => $student->id,
            'invited_user_id' => $student->id,
            'status' => OrderItemSeatStatus::Enrolled,
        ]);

        $enrollment = Enrollment::query()->create([
            'user_id' => $student->id,
            'course_id' => $courseA->id,
            'course_group_id' => $groupA->id,
            'order_item_id' => $item->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertActionVisible(TestAction::make('edit_item')->table($item))
            ->callAction(TestAction::make('edit_item')->table($item), data: [
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $groupB->id,
                'price_amount' => 1300,
                'change_reason' => 'Customer purchased the wrong course group.',
            ])
            ->assertHasNoFormErrors();

        $item->refresh();
        $this->assertSame($groupB->id, $item->purchasable_id);

        $seat->refresh();
        $this->assertSame($courseB->id, $seat->course_id);
        $this->assertSame($groupB->id, $seat->course_group_id);

        $enrollment->refresh();
        $this->assertSame($courseB->id, $enrollment->course_id);
        $this->assertSame($groupB->id, $enrollment->course_group_id);
    }

    public function test_non_paid_order_item_cannot_be_edited(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-5');
        $course = $this->makeCourse($category, 'edit-item-course-pending', 'Course Pending');
        $group = $this->makeGroup($course, 'Group Pending', 1000);

        $order = $this->makePaidOrder($student);
        $order->update(['status' => 'pending']);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course Pending',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order->fresh(),
            'pageClass' => ViewOrder::class,
        ])
            ->assertActionHidden(TestAction::make('edit_item')->table($item));
    }

    public function test_user_without_paid_permission_cannot_edit_item_on_paid_order(): void
    {
        $this->actingAsAdmin(withPaidPermission: false);

        $student = User::factory()->create();
        $category = $this->makeCategory('edit-item-cat-6');
        $course = $this->makeCourse($category, 'edit-item-course-noperm', 'Course No Permission');
        $group = $this->makeGroup($course, 'Group No Permission', 1000);

        $order = $this->makePaidOrder($student);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course No Permission',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertActionHidden(TestAction::make('edit_item')->table($item));
    }

    public function test_admin_can_add_item_to_paid_order_and_creates_enrollment(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('add-item-cat-1');
        $course = $this->makeCourse($category, 'add-item-course-a', 'Course A');
        $group = $this->makeGroup($course, 'Group A', 1200);

        $order = $this->makePaidOrder($student);

        $test = Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make('add_item')->table(), data: [
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $group->id,
                'price_amount' => 1200,
                'quantity' => 1,
                'change_reason' => 'Customer purchased an additional course.',
            ]);
        $test->assertHasNoFormErrors();

        $order->refresh();
        $this->assertSame(1, $order->items()->count());

        $item = $order->items()->first();
        $this->assertSame($group->id, $item->purchasable_id);
        $this->assertSame(1200, $item->price_amount);
        $this->assertSame(1200, $order->subtotal_amount);
        $this->assertSame(1200, $order->total_amount);

        $this->assertTrue(
            Enrollment::query()
                ->where('user_id', $student->id)
                ->where('course_group_id', $group->id)
                ->where('order_item_id', $item->id)
                ->exists()
        );

        $log = $order->activityLogs()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Customer purchased an additional course.', $log->notes);
        $this->assertArrayHasKey('purchasable_id', $log->diff);
        $this->assertSame($group->id, $log->diff['purchasable_id']['new']);
    }

    public function test_user_without_paid_permission_cannot_add_item_to_paid_order(): void
    {
        $this->actingAsAdmin(withPaidPermission: false);

        $student = User::factory()->create();
        $order = $this->makePaidOrder($student);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->assertActionHidden(TestAction::make('add_item')->table());
    }

    public function test_add_item_hidden_on_non_paid_order(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $order = $this->makePaidOrder($student);
        $order->update(['status' => 'pending']);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order->fresh(),
            'pageClass' => ViewOrder::class,
        ])
            ->assertActionHidden(TestAction::make('add_item')->table());
    }

    public function test_adding_a_duplicate_item_shows_validation_error(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('add-item-cat-dup');
        $course = $this->makeCourse($category, 'add-item-course-dup', 'Course Dup');
        $group = $this->makeGroup($course, 'Group Dup', 1000);

        $order = $this->makePaidOrder($student);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Course Dup',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->callAction(TestAction::make('add_item')->table(), data: [
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $group->id,
                'price_amount' => 1000,
                'quantity' => 1,
                'change_reason' => 'Trying to add the same group again.',
            ])
            ->assertHasFormErrors(['purchasable_id']);

        $this->assertSame(1, $order->items()->count());
    }

    public function test_adding_item_with_quantity_above_one_creates_seats(): void
    {
        $this->actingAsAdmin();

        $student = User::factory()->create();
        $category = $this->makeCategory('add-item-cat-seats');
        $course = $this->makeCourse($category, 'add-item-course-seats', 'Course Seats');
        $group = $this->makeGroup($course, 'Group Seats', 1000);

        $order = $this->makePaidOrder($student);

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
            ->callAction(TestAction::make('add_item')->table(), data: [
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $group->id,
                'price_amount' => 1000,
                'quantity' => 3,
                'change_reason' => 'Bought 3 seats for the team.',
            ])
            ->assertHasNoFormErrors();

        $item = $order->items()->firstOrFail();
        $this->assertSame(3, $item->quantity);
        $this->assertSame(3, $item->seats()->count());

        $reservedSeat = $item->seats()->where('invited_user_id', $student->id)->first();
        $this->assertNotNull($reservedSeat);
        $this->assertSame(OrderItemSeatStatus::Enrolled->value, $reservedSeat->status->value);

        $this->assertSame(2, $item->seats()->where('status', OrderItemSeatStatus::Unassigned)->count());

        $this->assertTrue(
            Enrollment::query()
                ->where('user_id', $student->id)
                ->where('course_group_id', $group->id)
                ->where('order_item_id', $item->id)
                ->exists()
        );
    }
}
