<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\EmployeeDailyOrderReport;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeDailyOrderReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    /**
     * @return array{0: CourseGroup, 1: CourseGroup}
     */
    private function twoCourseGroups(): array
    {
        $category = Category::create(['name' => ['en' => 'Cat'], 'slug' => 'cat', 'is_active' => true]);
        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Course'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => 'published',
        ]);

        return [
            CourseGroup::create(['course_id' => $course->id, 'title' => ['en' => 'G1'], 'group_price_amount' => 1000, 'enrollment_deadline_at' => now()->addDays(5), 'status' => 'open']),
            CourseGroup::create(['course_id' => $course->id, 'title' => ['en' => 'G2'], 'group_price_amount' => 1000, 'enrollment_deadline_at' => now()->addDays(5), 'status' => 'open']),
        ];
    }

    private function makeOrder(Carbon $day, OrderStatus $status, ?Employee $employee, string $source = 'sales'): Order
    {
        $order = Order::factory()->create([
            'status' => $status,
            'referred_by_employee_id' => $employee?->id,
        ]);
        $order->forceFill(['created_at' => $day, 'source' => $source])->saveQuietly();

        return $order;
    }

    private function addGroupItem(Order $order, CourseGroup $group): void
    {
        OrderItem::create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Item',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'quantity' => 1,
        ]);
    }

    public function test_it_counts_paid_orders_and_line_items_per_employee_per_day(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        $employeeA = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        $employeeB = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        [$groupA, $groupB] = $this->twoCourseGroups();

        $bundle = Bundle::create([
            'category_id' => $groupA->course->category_id,
            'title' => ['en' => 'Bundle'],
            'summary' => ['en' => 'Bundle'],
            'description' => ['en' => 'Bundle'],
            'slug' => 'bundle',
            'price' => 2000,
            'is_active' => true,
        ]);

        $day1 = Carbon::parse('2026-08-03');
        $day2 = Carbon::parse('2026-08-04');

        $orderWithBundle = $this->makeOrder($day1, OrderStatus::Paid, $employeeA);
        $this->addGroupItem($orderWithBundle, $groupA);
        OrderItem::create([
            'order_id' => $orderWithBundle->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Bundle',
            'price_amount' => 2000,
            'currency' => 'SAR',
            'quantity' => 1,
            'metadata_snapshot' => ['selected_course_group_ids' => [$groupA->id, $groupB->id]],
        ]);

        $simpleOrder = $this->makeOrder($day1, OrderStatus::Paid, $employeeB);
        $this->addGroupItem($simpleOrder, $groupB);

        $unassigned = $this->makeOrder($day1, OrderStatus::Paid, null);
        $this->addGroupItem($unassigned, $groupA);

        $pending = $this->makeOrder($day1, OrderStatus::Pending, $employeeA);
        $this->addGroupItem($pending, $groupA);

        $adminSource = $this->makeOrder($day1, OrderStatus::Paid, $employeeA, 'admin');
        $this->addGroupItem($adminSource, $groupA);

        $secondDay = $this->makeOrder($day2, OrderStatus::Paid, $employeeB);
        $this->addGroupItem($secondDay, $groupA);

        $rows = Livewire::test(EmployeeDailyOrderReport::class)
            ->set('filters.date_from', $day1->toDateString())
            ->set('filters.date_to', $day2->toDateString())
            ->instance()
            ->getTableRecords();

        $row1 = $rows->firstWhere('day', $day1->toDateString().' ('.$day1->format('D').')');
        $row2 = $rows->firstWhere('day', $day2->toDateString().' ('.$day2->format('D').')');
        $sumRow = $rows->firstWhere('day', __('admin.labels.sum_per_employee'));

        $this->assertSame('1 (3)', $row1['emp_'.$employeeA->id]);
        $this->assertSame('1 (1)', $row1['emp_'.$employeeB->id]);
        $this->assertSame('2 (4)', $row1['sum']);
        $this->assertSame('1 (1)', $row1['not_assigned']);

        $this->assertSame('0 (0)', $row2['emp_'.$employeeA->id]);
        $this->assertSame('1 (1)', $row2['emp_'.$employeeB->id]);

        $this->assertSame('1 (3)', $sumRow['emp_'.$employeeA->id]);
        $this->assertSame('2 (2)', $sumRow['emp_'.$employeeB->id]);
        $this->assertSame('3 (5)', $sumRow['sum']);
    }

    public function test_exports_follow_the_active_filters(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        [$group] = $this->twoCourseGroups();
        $day = Carbon::parse('2026-08-03');

        $order = $this->makeOrder($day, OrderStatus::Paid, $employee);
        $this->addGroupItem($order, $group);

        $component = Livewire::test(EmployeeDailyOrderReport::class)
            ->set('filters.date_from', $day->toDateString())
            ->set('filters.date_to', $day->toDateString());

        $exported = (fn (): array => [$this->exportHeadings(), $this->exportRows()])->call($component->instance());

        $this->assertSame($employee->employee_code, $exported[0][1]);
        $this->assertCount(3, $exported[1]);
        $this->assertSame('1 (1)', $exported[1][0][1]);

        $component->callAction('export_csv')->assertFileDownloaded();
        $component->callAction('export_xlsx')->assertFileDownloaded();
    }

    public function test_it_opens_on_the_last_seven_days_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        $component = Livewire::test(EmployeeDailyOrderReport::class)
            ->assertSet('filters.date_range', 'last_7_days')
            ->assertSet('filters.date_from', now()->subDays(6)->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->assertDontSeeHtml('filters.period');

        $this->assertCount(7 + 2, $component->instance()->getTableRecords());

        $component
            ->set('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString())
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null);
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        Livewire::test(EmployeeDailyOrderReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_all_preset_lists_days_from_the_first_paid_order_until_today(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        [$group] = $this->twoCourseGroups();

        $firstDay = now()->subDays(20)->startOfDay();
        $oldOrder = $this->makeOrder($firstDay->copy()->addHours(10), OrderStatus::Paid, $employee);
        $this->addGroupItem($oldOrder, $group);
        $todaysOrder = $this->makeOrder(now()->startOfDay()->addHour(), OrderStatus::Paid, $employee);
        $this->addGroupItem($todaysOrder, $group);

        $rows = Livewire::test(EmployeeDailyOrderReport::class)
            ->set('filters.date_range', 'all')
            ->instance()
            ->getTableRecords();

        $this->assertCount(21 + 2, $rows);
        $this->assertSame($firstDay->toDateString().' ('.$firstDay->format('D').')', $rows->first()['day']);
        $this->assertSame('2 (2)', $rows->firstWhere('day', __('admin.labels.sum_per_employee'))['emp_'.$employee->id]);
    }

    public function test_all_preset_without_orders_lists_only_today(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        $rows = Livewire::test(EmployeeDailyOrderReport::class)
            ->set('filters.date_range', 'all')
            ->instance()
            ->getTableRecords();

        $this->assertCount(1 + 2, $rows);
        $this->assertSame(now()->toDateString().' ('.now()->format('D').')', $rows->first()['day']);
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['employee_daily_order_report.viewAny']);

        $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);
        [$group] = $this->twoCourseGroups();
        $order = $this->makeOrder(now(), OrderStatus::Paid, $employee);
        $this->addGroupItem($order, $group);

        Livewire::test(EmployeeDailyOrderReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    public function test_page_requires_permission(): void
    {
        $this->actingAsAdminWithPermissions([]);

        $this->assertFalse(EmployeeDailyOrderReport::canAccess());
    }
}
