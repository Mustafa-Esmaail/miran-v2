<?php

namespace Tests\Feature;

use App\Filament\Pages\CoursePerformanceReport;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CoursePerformanceReportTest extends TestCase
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

    public function test_course_performance_report_sums_paid_order_amounts_per_course_and_excludes_unpaid_orders(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'course-performance-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Revenue Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-revenue-course',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Revenue Course Group A'],
            'group_price_amount' => 700,
            'status' => 'open',
        ]);

        $groupB = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Revenue Course Group B'],
            'group_price_amount' => 300,
            'status' => 'open',
        ]);

        $paidOrderA = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-CPR-PAID-A',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 700,
            'discount_amount' => 0,
            'total_amount' => 700,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $paidOrderA->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Revenue Course Group A',
            'price_amount' => 700,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $paidOrderB = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-CPR-PAID-B',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 300,
            'discount_amount' => 0,
            'total_amount' => 300,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $paidOrderB->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupB->id,
            'title_snapshot' => 'Revenue Course Group B',
            'price_amount' => 300,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $pendingOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-CPR-PENDING',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 999,
            'discount_amount' => 0,
            'total_amount' => 999,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $pendingOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Revenue Course Group A',
            'price_amount' => 999,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $component = Livewire::test(CoursePerformanceReport::class);
        $records = $component->instance()->getTableRecords();

        $courseRow = $records->firstWhere('title', 'Revenue Course');

        $this->assertNotNull($courseRow);
        $this->assertSame(1000, (int) $courseRow['paid_orders_total_amount']);

        $totals = $component->instance()->getReportTotals();

        $this->assertSame(1000, (int) $totals['paid_total_amount']);
    }

    public function test_report_totals_paid_total_counts_each_order_once_even_when_it_spans_multiple_courses(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'course-performance-multi-category',
            'is_active' => true,
        ]);

        $courseA = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Multi Course A'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-multi-a',
            'status' => 'published',
        ]);

        $courseB = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Multi Course B'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-multi-b',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $courseA->id,
            'title' => ['en' => 'Multi Group A'],
            'group_price_amount' => 400,
            'status' => 'open',
        ]);

        $groupB = CourseGroup::query()->create([
            'course_id' => $courseB->id,
            'title' => ['en' => 'Multi Group B'],
            'group_price_amount' => 600,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-CPR-MULTI',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Multi Group A',
            'price_amount' => 400,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupB->id,
            'title_snapshot' => 'Multi Group B',
            'price_amount' => 600,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $totals = Livewire::test(CoursePerformanceReport::class)->instance()->getReportTotals();

        $this->assertSame(1, $totals['paid_total']);
        $this->assertSame(1000, (int) $totals['paid_total_amount']);
    }

    public function test_course_row_paid_order_items_count_reflects_line_items_not_distinct_orders(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'course-performance-items-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Items Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-items-course',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Items Group A'],
            'group_price_amount' => 300,
            'status' => 'open',
        ]);

        $groupB = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Items Group B'],
            'group_price_amount' => 200,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-CPR-ITEMS',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
            'source' => 'store',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Items Group A',
            'price_amount' => 300,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupB->id,
            'title_snapshot' => 'Items Group B',
            'price_amount' => 200,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $records = Livewire::test(CoursePerformanceReport::class)->instance()->getTableRecords();
        $courseRow = $records->firstWhere('title', 'Items Course');

        $this->assertSame(2, $courseRow['paid_order_items_count']);
    }

    public function test_revenue_uses_order_total_amount_after_discount_split_across_courses(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'course-performance-discount-category',
            'is_active' => true,
        ]);

        $courseA = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Discount Course A'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-discount-a',
            'status' => 'published',
        ]);

        $courseB = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Discount Course B'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-discount-b',
            'status' => 'published',
        ]);

        $groupA = CourseGroup::query()->create([
            'course_id' => $courseA->id,
            'title' => ['en' => 'Discount Group A'],
            'group_price_amount' => 300,
            'status' => 'open',
        ]);

        $groupB = CourseGroup::query()->create([
            'course_id' => $courseB->id,
            'title' => ['en' => 'Discount Group B'],
            'group_price_amount' => 700,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-CPR-DISCOUNT',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1300,
            'discount_amount' => 520,
            'total_amount' => 780,
            'source' => 'store',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupA->id,
            'title_snapshot' => 'Discount Group A',
            'price_amount' => 300,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 2,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $groupB->id,
            'title_snapshot' => 'Discount Group B',
            'price_amount' => 700,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $component = Livewire::test(CoursePerformanceReport::class);
        $records = $component->instance()->getTableRecords();

        $this->assertSame(360, $records->firstWhere('title', 'Discount Course A')['paid_orders_total_amount']);
        $this->assertSame(420, $records->firstWhere('title', 'Discount Course B')['paid_orders_total_amount']);
        $this->assertSame(780, $component->instance()->getReportTotals()['paid_total_amount']);
    }

    public function test_it_opens_on_today_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        Livewire::test(CoursePerformanceReport::class)
            ->assertSet('filters.date_range', 'today')
            ->assertSet('filters.date_from', now()->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->set('filters.date_range', 'last_7_days')
            ->assertSet('filters.date_from', now()->subDays(6)->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null);
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        Livewire::test(CoursePerformanceReport::class)
            ->set('filters.date_to', now()->subDay()->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_last_month_preset_reports_last_months_paid_orders_only(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $group = $this->makeCourseGroup('preset');

        $this->makePaidGroupOrder($group, 'ORD-CPR-PRESET-TODAY', 400, now());
        $this->makePaidGroupOrder($group, 'ORD-CPR-PRESET-LAST', 600, now()->subMonthNoOverflow()->startOfMonth()->addDays(2));

        $component = Livewire::test(CoursePerformanceReport::class);

        $this->assertSame(400, $component->instance()->getReportTotals()['paid_total_amount']);

        $lastMonthTotals = $component
            ->set('filters.date_range', 'last_month')
            ->instance()
            ->getReportTotals();

        $this->assertSame(1, $lastMonthTotals['paid_total']);
        $this->assertSame(600, $lastMonthTotals['paid_total_amount']);

        $allTotals = $component
            ->set('filters.date_range', 'all')
            ->instance()
            ->getReportTotals();

        $this->assertSame(2, $allTotals['paid_total']);
        $this->assertSame(1000, $allTotals['paid_total_amount']);
    }

    public function test_it_renders_the_shared_filter_card_and_totals_strip(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $this->makePaidGroupOrder($this->makeCourseGroup('strip'), 'ORD-CPR-STRIP', 1234, now());

        Livewire::test(CoursePerformanceReport::class)
            ->assertSeeHtml('fi-report-filters')
            ->assertSeeHtml('fi-report-totals')
            ->assertSee(__('admin.labels.total_courses'))
            ->assertSee('1,234.00');
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['course_performance_report.viewAny']);

        $this->makePaidGroupOrder($this->makeCourseGroup('pdf'), 'ORD-CPR-PDF', 500, now());

        Livewire::test(CoursePerformanceReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    private function makeCourseGroup(string $slug): CourseGroup
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'course-performance-'.$slug.'-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course '.$slug],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-performance-'.$slug.'-course',
            'status' => 'published',
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group '.$slug],
            'group_price_amount' => 500,
            'status' => 'open',
        ]);
    }

    private function makePaidGroupOrder(CourseGroup $group, string $orderNo, int $amount, CarbonInterface $createdAt): Order
    {
        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => $orderNo,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => $amount,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'source' => 'store',
        ]);
        $order->forceFill(['created_at' => $createdAt])->saveQuietly();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Group',
            'price_amount' => $amount,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        return $order;
    }
}
