<?php

namespace Tests\Feature;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Enums\OrderSource;
use App\Exports\ReportArrayExport;
use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Filament\Admin\Resources\Orders\Pages\CreateOrder;
use App\Filament\Pages\OrderLineItemsReport;
use App\Filament\Pages\SalesPerformanceReport;
use App\Filament\Pages\StoreOrganicOrdersReport;
use App\Filament\Widgets\OrdersReportStatsWidget;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderSourceReportsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $slug): Category
    {
        return Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    private function makeCourseGroup(Category $category, string $slug, int $price = 1000): CourseGroup
    {
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => ucfirst($slug)],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => $slug,
            'status' => 'published',
        ]);

        return CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => ucfirst($slug).' Group'],
            'group_price_amount' => $price,
            'status' => 'open',
        ]);
    }

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

    public function test_filtered_for_report_scope_filters_by_source_and_status(): void
    {
        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SRC-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'source' => 'store',
        ]);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SRC-2',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'source' => 'sales',
        ]);

        $storePaid = Order::query()->filteredForReport(null, null, null, 'store', 'paid')->get();
        $this->assertSame(['ORD-SRC-1'], $storePaid->pluck('order_no')->all());

        $sales = Order::query()->filteredForReport(null, null, null, 'sales')->get();
        $this->assertSame(['ORD-SRC-2'], $sales->pluck('order_no')->all());
    }

    public function test_filtered_for_report_scope_excludes_admin_source_orders(): void
    {
        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SRC-ADMIN',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'source' => 'admin',
        ]);

        $all = Order::query()->filteredForReport(null, null, null)->get();
        $this->assertFalse($all->contains('order_no', 'ORD-SRC-ADMIN'));

        $explicitlyAdmin = Order::query()->filteredForReport(null, null, null, 'admin')->get();
        $this->assertCount(0, $explicitlyAdmin);
    }

    public function test_orders_report_stats_widget_excludes_admin_source_orders_from_totals(): void
    {
        $this->actingAsAdminWithPermissions(['orders_report.viewAny']);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-STAT-ADMIN',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 7000,
            'discount_amount' => 0,
            'total_amount' => 7000,
            'source' => 'admin',
        ]);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-STAT-STORE-3',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'source' => 'store',
        ]);

        Livewire::test(OrdersReportStatsWidget::class)
            ->assertOk()
            ->assertSee('SAR 100.00')
            ->assertDontSee('SAR 7,000.00')
            ->assertDontSee('SAR 7,100.00');
    }

    public function test_checkout_without_referral_creates_site_source_order(): void
    {
        $user = User::factory()->create();

        $category = $this->makeCategory('checkout-store-category');
        $group = $this->makeCourseGroup($category, 'checkout-store-course', 0);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        $order = app(CheckoutService::class)->createOrderFromCart($user->id);

        $this->assertNotNull($order);
        $this->assertSame(OrderSource::Site->value, $order->source->value);
    }

    public function test_checkout_with_unmatched_source_param_creates_store_source_order(): void
    {
        $user = User::factory()->create();

        session(['marketing_source' => 'SNPC_DT_Excel_IMG_PUR_15OFF']);

        $category = $this->makeCategory('checkout-unmatched-source-category');
        $group = $this->makeCourseGroup($category, 'checkout-unmatched-source-course', 0);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        $order = app(CheckoutService::class)->createOrderFromCart($user->id);

        $this->assertNotNull($order);
        $this->assertSame(OrderSource::Store->value, $order->source->value);
    }

    public function test_checkout_with_referral_employee_creates_sales_source_order(): void
    {
        $user = User::factory()->create();

        $employee = Employee::factory()->create(['is_active' => true]);
        session(['referral_employee_id' => $employee->id]);

        $category = $this->makeCategory('checkout-sales-category');
        $group = $this->makeCourseGroup($category, 'checkout-sales-course', 0);

        app(CartService::class)->addItem($user->id, CourseGroup::class, $group->id, 1);

        $order = app(CheckoutService::class)->createOrderFromCart($user->id);

        $this->assertNotNull($order);
        $this->assertSame(OrderSource::Sales->value, $order->source->value);
        $this->assertSame($employee->id, $order->referred_by_employee_id);
    }

    public function test_admin_created_order_gets_admin_source(): void
    {
        $this->actingAsAdminWithPermissions(['orders.viewAny', 'orders.view', 'orders.create']);

        $category = $this->makeCategory('admin-create-category');
        $group = $this->makeCourseGroup($category, 'admin-create-course', 500);
        $customer = User::factory()->create();

        Livewire::test(CreateOrder::class)
            ->fillForm([
                'user_id' => $customer->id,
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $group->id,
                'quantity' => 1,
                'price_amount' => 500,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame(OrderSource::Admin->value, $order->source->value);
    }

    public function test_sales_performance_report_counts_only_paid_referred_orders(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $employee = Employee::factory()->create(['name' => 'Sales Star']);

        foreach (['ORD-SP-1', 'ORD-SP-2'] as $orderNo) {
            Order::query()->create([
                'user_id' => User::factory()->create()->id,
                'order_no' => $orderNo,
                'status' => 'paid',
                'currency' => 'SAR',
                'subtotal_amount' => 100,
                'discount_amount' => 0,
                'total_amount' => 100,
                'referred_by_employee_id' => $employee->id,
                'source' => 'sales',
            ]);
        }

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SP-3',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'referred_by_employee_id' => $employee->id,
            'source' => 'sales',
        ]);

        $records = Livewire::test(SalesPerformanceReport::class)->instance()->getTableRecords();
        $record = $records->firstWhere('id', $employee->id);

        $this->assertNotNull($record);
        $this->assertSame(2, (int) $record->completed_orders_count);
    }

    public function test_sales_performance_report_exports_csv_and_xlsx(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $employee = Employee::factory()->create(['name' => 'Export Star']);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SP-EXP',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'referred_by_employee_id' => $employee->id,
            'source' => 'sales',
        ]);

        Livewire::test(SalesPerformanceReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Excel::fake();
        Excel::matchByRegex();

        Livewire::test(SalesPerformanceReport::class)->callAction('export_xlsx');

        Excel::assertDownloaded('/^miran-sales-performance-report-\d{8}-\d{6}\.xlsx$/', function (ReportArrayExport $export): bool {
            $rows = $export->array();

            return count($rows) === 2
                && $rows[0][0] === 'Export Star'
                && $rows[0][2] === 1
                && $rows[0][3] === 100.0
                && $rows[1][0] === __('admin.labels.total');
        });
    }

    public function test_sales_performance_report_date_filter_scopes_completed_orders_count(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $employee = Employee::factory()->create(['name' => 'Date Filter Star']);

        $recentOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-DATE-RECENT',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'referred_by_employee_id' => $employee->id,
            'source' => 'sales',
        ]);

        $oldOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-DATE-OLD',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'referred_by_employee_id' => $employee->id,
            'source' => 'sales',
        ]);
        $oldOrder->forceFill(['created_at' => now()->subDays(30)])->save();

        $recordsUnfiltered = Livewire::test(SalesPerformanceReport::class)->instance()->getTableRecords();
        $unfilteredRecord = $recordsUnfiltered->firstWhere('id', $employee->id);
        $this->assertSame(2, (int) $unfilteredRecord->completed_orders_count);

        $recordsFiltered = Livewire::test(SalesPerformanceReport::class)
            ->set('filters.date_from', now()->subDays(2)->toDateString())
            ->instance()
            ->getTableRecords();
        $filteredRecord = $recordsFiltered->firstWhere('id', $employee->id);

        $this->assertNotNull($filteredRecord);
        $this->assertSame(1, (int) $filteredRecord->completed_orders_count);
        $this->assertSame(100, (int) $filteredRecord->completed_orders_total);
    }

    public function test_order_line_items_report_flags_bundle_multi_item_and_single_item_rows(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $category = $this->makeCategory('line-items-category');

        $coupon = Coupon::query()->create([
            'title' => ['en' => 'Test Coupon'],
            'code' => 'TESTCODE',
            'type' => CouponType::Fixed,
            'applies_to' => CouponAppliesTo::Both,
            'value' => 10,
            'is_active' => true,
        ]);

        // Single-item order
        $singleGroup = $this->makeCourseGroup($category, 'single-item-course');
        $singleOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-LI-SINGLE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $singleOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $singleGroup->id,
            'title_snapshot' => 'Single Item Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        // Multi-item cart order (2 course groups)
        $multiGroupA = $this->makeCourseGroup($category, 'multi-item-course-a');
        $multiGroupB = $this->makeCourseGroup($category, 'multi-item-course-b');
        $multiOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-LI-MULTI',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 2000,
            'discount_amount' => 0,
            'total_amount' => 2000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $multiOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $multiGroupA->id,
            'title_snapshot' => 'Multi Item Course A',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);
        OrderItem::query()->create([
            'order_id' => $multiOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $multiGroupB->id,
            'title_snapshot' => 'Multi Item Course B',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        // Bundle order with a coupon applied
        $bundle = Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Line Items Bundle'],
            'slug' => 'line-items-bundle',
            'price' => 500,
            'is_active' => true,
        ]);
        $bundleOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-LI-BUNDLE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 500,
            'discount_amount' => 10,
            'total_amount' => 490,
            'source' => 'store',
            'coupon_id' => $coupon->id,
        ]);
        OrderItem::query()->create([
            'order_id' => $bundleOrder->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Line Items Bundle',
            'price_amount' => 500,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $records = Livewire::test(OrderLineItemsReport::class)->set('filters.source', null)->instance()->getTableRecords();

        $single = $records->firstWhere('title_snapshot', 'Single Item Course');
        $multiA = $records->firstWhere('title_snapshot', 'Multi Item Course A');
        $bundleRow = $records->firstWhere('title_snapshot', 'Line Items Bundle');

        $this->assertSame(1, (int) $single->sibling_items_count);
        $this->assertSame(2, (int) $multiA->sibling_items_count);
        $this->assertSame(Bundle::class, $bundleRow->purchasable_type);
        $this->assertSame('TESTCODE', $bundleRow->order->coupon->code);
    }

    public function test_order_line_items_report_keeps_multi_item_cart_rows_adjacent_despite_differing_popularity(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $category = $this->makeCategory('grouping-category');

        // Inflate "High" course's popularity above "Mid", and "Mid" above "Low",
        // so a pure popularity sort would interleave Order B's row between Order A's two rows.
        $highGroup = $this->makeCourseGroup($category, 'grouping-high-course');
        foreach (['ORD-GROUP-FILLER-1', 'ORD-GROUP-FILLER-2'] as $orderNo) {
            $fillerOrder = Order::query()->create([
                'user_id' => User::factory()->create()->id,
                'order_no' => $orderNo,
                'status' => 'paid',
                'currency' => 'SAR',
                'subtotal_amount' => 1000,
                'discount_amount' => 0,
                'total_amount' => 1000,
                'source' => 'store',
            ]);
            OrderItem::query()->create([
                'order_id' => $fillerOrder->id,
                'purchasable_type' => CourseGroup::class,
                'purchasable_id' => $highGroup->id,
                'title_snapshot' => 'Grouping High Course',
                'price_amount' => 1000,
                'currency' => 'SAR',
                'metadata_snapshot' => [],
                'quantity' => 1,
            ]);
        }

        $midGroup = $this->makeCourseGroup($category, 'grouping-mid-course');
        $fillerMidOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-GROUP-FILLER-MID',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $fillerMidOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $midGroup->id,
            'title_snapshot' => 'Grouping Mid Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $lowGroup = $this->makeCourseGroup($category, 'grouping-low-course');

        // Order A: one "High" item + one "Low" item (a multi-item cart spanning very different popularity).
        $orderA = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-GROUP-A',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 2000,
            'discount_amount' => 0,
            'total_amount' => 2000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $orderA->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $highGroup->id,
            'title_snapshot' => 'Grouping High Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);
        OrderItem::query()->create([
            'order_id' => $orderA->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $lowGroup->id,
            'title_snapshot' => 'Grouping Low Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        // Order B: a single "Mid" popularity item, which would sort between High and Low without grouping.
        $orderB = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-GROUP-B',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $orderB->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $midGroup->id,
            'title_snapshot' => 'Grouping Mid Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $records = Livewire::test(OrderLineItemsReport::class)->set('filters.source', null)->instance()->getTableRecords()->values();
        $orderIds = $records->pluck('order_id')->all();

        $orderAPositions = array_keys($orderIds, $orderA->id);
        $this->assertCount(2, $orderAPositions);
        $this->assertSame(
            $orderAPositions[0] + 1,
            $orderAPositions[1],
            'Order A\'s two line items must render back-to-back, with no other order\'s row in between.'
        );
    }

    public function test_store_organic_orders_report_only_shows_store_source_orders(): void
    {
        $this->actingAsAdminWithPermissions(['store_organic_orders_report.viewAny']);

        $category = $this->makeCategory('organic-category');

        $storeGroup = $this->makeCourseGroup($category, 'organic-store-course');
        $storeOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ORGANIC-STORE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $storeOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $storeGroup->id,
            'title_snapshot' => 'Organic Store Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $salesGroup = $this->makeCourseGroup($category, 'organic-sales-course');
        $salesOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ORGANIC-SALES',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'sales',
        ]);
        OrderItem::query()->create([
            'order_id' => $salesOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $salesGroup->id,
            'title_snapshot' => 'Organic Sales Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $siteGroup = $this->makeCourseGroup($category, 'organic-site-course');
        $siteOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ORGANIC-SITE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'site',
        ]);
        OrderItem::query()->create([
            'order_id' => $siteOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $siteGroup->id,
            'title_snapshot' => 'Organic Site Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $records = Livewire::test(StoreOrganicOrdersReport::class)->instance()->getTableRecords();

        $this->assertTrue($records->contains('title_snapshot', 'Organic Store Course'));
        $this->assertFalse($records->contains('title_snapshot', 'Organic Site Course'));
        $this->assertFalse($records->contains('title_snapshot', 'Organic Sales Course'));
    }

    public function test_order_line_items_report_row_links_to_order_view_page(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $category = $this->makeCategory('row-link-category');
        $group = $this->makeCourseGroup($category, 'row-link-course');
        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ROWLINK',
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
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Row Link Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->assertOk()
            ->assertSee(OrderResource::getUrl('view', ['record' => $order->id]), escape: false);
    }

    public function test_sales_performance_report_row_links_to_employee_edit_page(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $employee = Employee::factory()->create(['name' => 'Row Link Employee']);
        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-EMP-ROWLINK',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'referred_by_employee_id' => $employee->id,
            'source' => 'sales',
        ]);

        Livewire::test(SalesPerformanceReport::class)
            ->assertOk()
            ->assertSee(EmployeeResource::getUrl('edit', ['record' => $employee->id]), escape: false);
    }

    public function test_order_line_items_report_shows_employee_name_next_to_sales_source_badge(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $employee = Employee::factory()->create(['name' => 'Badge Employee']);
        $category = $this->makeCategory('badge-category');
        $group = $this->makeCourseGroup($category, 'badge-course');
        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-BADGE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'sales',
            'referred_by_employee_id' => $employee->id,
        ]);
        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Badge Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Livewire::test(OrderLineItemsReport::class)
            ->assertOk()
            ->assertSee('Badge Employee');
    }

    public function test_orders_report_stats_widget_splits_store_and_sales_paid_income(): void
    {
        $this->actingAsAdminWithPermissions(['orders_report.viewAny']);

        foreach ([['ORD-STAT-STORE-1', 1200], ['ORD-STAT-STORE-2', 1300]] as [$orderNo, $amount]) {
            Order::query()->create([
                'user_id' => User::factory()->create()->id,
                'order_no' => $orderNo,
                'status' => 'paid',
                'currency' => 'SAR',
                'subtotal_amount' => $amount,
                'discount_amount' => 0,
                'total_amount' => $amount,
                'source' => 'store',
            ]);
        }

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-STAT-SALES-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 900,
            'discount_amount' => 0,
            'total_amount' => 900,
            'source' => 'sales',
        ]);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-STAT-PENDING',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 5000,
            'discount_amount' => 0,
            'total_amount' => 5000,
            'source' => 'store',
        ]);

        Livewire::test(OrdersReportStatsWidget::class)
            ->assertOk()
            ->assertSee('SAR 2,500.00')
            ->assertSee('SAR 900.00');
    }

    public function test_orders_report_stats_widget_splits_site_paid_income(): void
    {
        $this->actingAsAdminWithPermissions(['orders_report.viewAny']);

        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-STAT-SITE-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1500,
            'discount_amount' => 0,
            'total_amount' => 1500,
            'source' => 'site',
        ]);

        Livewire::test(OrdersReportStatsWidget::class)
            ->assertOk()
            ->assertSee('SAR 1,500.00');
    }

    public function test_orders_report_items_sold_stat_counts_cart_items_and_expands_bundle_courses(): void
    {
        $this->actingAsAdminWithPermissions(['orders_report.viewAny']);

        $category = $this->makeCategory('items-sold-category');

        // Multi-item cart: 2 separate courses in one order = 2 items sold.
        $cartGroupA = $this->makeCourseGroup($category, 'items-sold-course-a');
        $cartGroupB = $this->makeCourseGroup($category, 'items-sold-course-b');
        $cartOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ITEMS-CART',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 2000,
            'discount_amount' => 0,
            'total_amount' => 2000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $cartOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $cartGroupA->id,
            'title_snapshot' => 'Items Sold Course A',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);
        OrderItem::query()->create([
            'order_id' => $cartOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $cartGroupB->id,
            'title_snapshot' => 'Items Sold Course B',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        // Bundle with 3 courses, quantity 2 = 6 items sold.
        $bundle = Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Items Sold Bundle'],
            'slug' => 'items-sold-bundle',
            'price' => 500,
            'is_active' => true,
        ]);
        $bundleOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ITEMS-BUNDLE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $bundleOrder->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Items Sold Bundle',
            'price_amount' => 500,
            'currency' => 'SAR',
            'metadata_snapshot' => ['bundle_course_count' => 3],
            'quantity' => 2,
        ]);

        // Pending order's items must not count.
        $pendingGroup = $this->makeCourseGroup($category, 'items-sold-pending-course');
        $pendingOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-ITEMS-PENDING',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => 'store',
        ]);
        OrderItem::query()->create([
            'order_id' => $pendingOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $pendingGroup->id,
            'title_snapshot' => 'Items Sold Pending Course',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        // 2 (cart) + 2*3 (bundle) = 8 paid items sold; the pending order's item is excluded.
        Livewire::test(OrdersReportStatsWidget::class)
            ->assertOk()
            ->assertSee('8');
    }

    public function test_order_line_items_report_splits_bundle_into_separate_rows(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $category = $this->makeCategory('bundle-split-category');

        $groupA = $this->makeCourseGroup($category, 'course-a-split', 12000);
        $groupB = $this->makeCourseGroup($category, 'course-b-split', 15000);

        $bundle = Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Split Bundle'],
            'slug' => 'split-bundle',
            'price' => 500,
            'is_active' => true,
        ]);
        $bundle->courses()->sync([$groupA->course_id, $groupB->course_id]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SPLIT-BUNDLE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
            'source' => 'store',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Split Bundle',
            'price_amount' => 500,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'selected_course_group_ids' => [$groupA->id, $groupB->id],
            ],
            'quantity' => 1,
        ]);

        Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->assertOk()
            ->assertSee('Split Bundle — Course-a-split')
            ->assertSee('Split Bundle — Course-b-split')
            ->assertSee('Course-a-split Group')
            ->assertSee('Course-b-split Group')
            ->assertSee('250.00 SAR');
    }

    public function test_store_organic_orders_report_splits_bundle_into_separate_rows(): void
    {
        $this->actingAsAdminWithPermissions(['store_organic_orders_report.viewAny']);

        $category = $this->makeCategory('organic-bundle-split-category');

        $groupA = $this->makeCourseGroup($category, 'organic-course-a-split', 12000);
        $groupB = $this->makeCourseGroup($category, 'organic-course-b-split', 15000);

        $bundle = Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Split Organic Bundle'],
            'slug' => 'split-organic-bundle',
            'price' => 600,
            'is_active' => true,
        ]);
        $bundle->courses()->sync([$groupA->course_id, $groupB->course_id]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-SPLIT-ORGANIC-BUNDLE',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 600,
            'discount_amount' => 0,
            'total_amount' => 600,
            'source' => 'store',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Split Organic Bundle',
            'price_amount' => 600,
            'currency' => 'SAR',
            'metadata_snapshot' => [
                'selected_course_group_ids' => [$groupA->id, $groupB->id],
            ],
            'quantity' => 1,
        ]);

        Livewire::test(StoreOrganicOrdersReport::class)
            ->assertOk()
            ->assertSee('Split Organic Bundle — Organic-course-a-split')
            ->assertSee('Split Organic Bundle — Organic-course-b-split')
            ->assertSee('Organic-course-a-split Group')
            ->assertSee('Organic-course-b-split Group')
            ->assertSee('300.00 SAR');
    }

    private function makeLineItemOrder(string $orderNo, string $source, string $title): Order
    {
        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => $orderNo,
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'source' => $source,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $this->makeCourseGroup($this->makeCategory(strtolower($orderNo).'-category'), strtolower($orderNo).'-course')->id,
            'title_snapshot' => $title,
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        return $order;
    }

    public function test_order_line_items_report_opens_on_today_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        Livewire::test(OrderLineItemsReport::class)
            ->assertSet('filters.date_range', 'today')
            ->assertSet('filters.date_from', now()->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->assertSet('filters.source', 'sales')
            ->assertSet('filters.status', 'paid')
            ->set('filters.date_range', 'this_year')
            ->assertSet('filters.date_from', now()->startOfYear()->toDateString())
            ->assertSet('filters.date_to', now()->endOfYear()->toDateString())
            ->set('filters.date_to', now()->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_order_line_items_report_yesterday_preset_scopes_rows(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $this->makeLineItemOrder('ORD-LI-TODAY', 'store', 'Today Course');
        $yesterdayOrder = $this->makeLineItemOrder('ORD-LI-YESTERDAY', 'store', 'Yesterday Course');
        $yesterdayOrder->forceFill(['created_at' => now()->subDay()])->save();

        $todayRecords = Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->instance()
            ->getTableRecords();

        $this->assertTrue($todayRecords->contains('title_snapshot', 'Today Course'));
        $this->assertFalse($todayRecords->contains('title_snapshot', 'Yesterday Course'));

        $yesterdayRecords = Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->set('filters.date_range', 'yesterday')
            ->instance()
            ->getTableRecords();

        $this->assertFalse($yesterdayRecords->contains('title_snapshot', 'Today Course'));
        $this->assertTrue($yesterdayRecords->contains('title_snapshot', 'Yesterday Course'));
    }

    public function test_order_line_items_report_exports_csv_xlsx_and_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['order_line_items_report.viewAny']);

        $this->makeLineItemOrder('ORD-LI-EXPORT', 'store', 'Export Course');

        Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->callAction('export_pdf')
            ->assertFileDownloaded();

        Excel::fake();
        Excel::matchByRegex();

        Livewire::test(OrderLineItemsReport::class)
            ->set('filters.source', null)
            ->callAction('export_xlsx');

        Excel::assertDownloaded('/^miran-order-line-items-report-\d{8}-\d{6}\.xlsx$/', function (ReportArrayExport $export): bool {
            $rows = $export->array();

            return count($export->headings()) === 10
                && count($rows) === 1
                && $rows[0][0] === 'ORD-LI-EXPORT'
                && $rows[0][1] === 'Export Course'
                && $rows[0][8] === 1000.0;
        });
    }

    public function test_store_organic_orders_report_exports_only_store_rows(): void
    {
        $this->actingAsAdminWithPermissions(['store_organic_orders_report.viewAny']);

        $this->makeLineItemOrder('ORD-ORG-EXP-STORE', 'store', 'Organic Export Store');
        $this->makeLineItemOrder('ORD-ORG-EXP-SALES', 'sales', 'Organic Export Sales');

        Livewire::test(StoreOrganicOrdersReport::class)
            ->assertSet('filters.date_range', 'today')
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Livewire::test(StoreOrganicOrdersReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();

        Excel::fake();
        Excel::matchByRegex();

        Livewire::test(StoreOrganicOrdersReport::class)->callAction('export_xlsx');

        Excel::assertDownloaded('/^miran-store-organic-orders-report-\d{8}-\d{6}\.xlsx$/', function (ReportArrayExport $export): bool {
            $orderNumbers = array_column($export->array(), 0);

            return $orderNumbers === ['ORD-ORG-EXP-STORE'];
        });
    }
}
