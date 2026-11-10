<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Filament\Pages\OrdersReport;
use App\Filament\Widgets\OrdersReportStatsWidget;
use App\Filament\Widgets\OrdersReportTopEmployeesWidget;
use App\Filament\Widgets\OrdersReportTopItemsWidget;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrdersReportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_report_page_renders_stats_chart_and_widgets_for_admin(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $employee = Employee::factory()->create(['name' => 'Top Seller']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'orders-report-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Report Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'orders-report-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Report Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $bundle = Bundle::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Report Bundle'],
            'slug' => 'orders-report-bundle',
            'price' => 500,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-REPORT-1',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'referred_by_employee_id' => $employee->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Report Group',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => Bundle::class,
            'purchasable_id' => $bundle->id,
            'title_snapshot' => 'Report Bundle',
            'price_amount' => 500,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'tamara',
            'provider_reference' => 'REF-REPORT-1',
            'status' => 'paid',
            'amount' => 1000,
        ]);

        Livewire::test(OrdersReport::class)
            ->assertOk()
            ->assertSee('Top Seller')
            ->assertSee('Report Group')
            ->assertSee('Report Bundle');
    }

    public function test_top_items_and_top_employees_widgets_exclude_unpaid_orders(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $paidEmployee = Employee::factory()->create(['name' => 'Paid Referrer']);
        $unpaidEmployee = Employee::factory()->create(['name' => 'Unpaid Referrer']);

        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => 'orders-report-category-2',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Paid Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'orders-report-paid-course',
            'status' => 'published',
        ]);

        $paidGroup = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Paid Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $unpaidCourse = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Unpaid Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'orders-report-unpaid-course',
            'status' => 'published',
        ]);

        $unpaidGroup = CourseGroup::query()->create([
            'course_id' => $unpaidCourse->id,
            'title' => ['en' => 'Unpaid Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $paidOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-REPORT-PAID',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'referred_by_employee_id' => $paidEmployee->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $paidOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $paidGroup->id,
            'title_snapshot' => 'Paid Group',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $pendingOrder = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-REPORT-PENDING',
            'status' => 'pending',
            'currency' => 'SAR',
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
            'referred_by_employee_id' => $unpaidEmployee->id,
        ]);

        OrderItem::query()->create([
            'order_id' => $pendingOrder->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $unpaidGroup->id,
            'title_snapshot' => 'Unpaid Group',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);

        $topItems = Livewire::test(OrdersReportTopItemsWidget::class)->instance()->getTopItems();
        $topEmployees = Livewire::test(OrdersReportTopEmployeesWidget::class)->instance()->getTopEmployees();

        $this->assertTrue($topItems->contains(fn (array $row): bool => str_contains($row['title'], 'Paid Group')));
        $this->assertFalse($topItems->contains(fn (array $row): bool => str_contains($row['title'], 'Unpaid Group')));

        $this->assertTrue($topEmployees->contains(fn (array $row): bool => $row['name'] === 'Paid Referrer'));
        $this->assertFalse($topEmployees->contains(fn (array $row): bool => $row['name'] === 'Unpaid Referrer'));
    }

    public function test_stats_widget_reports_lead_totals_b2c_leads_and_conversion_rate(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $category = Category::query()->create([
            'name' => ['en' => 'Lead Category'],
            'slug' => 'orders-report-lead-category',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Lead Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'orders-report-lead-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['en' => 'Lead Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-REPORT-LEAD-1',
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
            'title_snapshot' => 'Lead Group',
            'price_amount' => 1000,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 2,
        ]);

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Complaint]);
        ServiceRequest::factory()->create(['source' => ServiceRequestSource::CorporateTraining]);
        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'converted_order_id' => $order->id,
        ]);

        // Outside the default date_from=today() filter — must not be counted.
        $oldLead = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead]);
        $oldLead->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();

        $component = Livewire::test(OrdersReportStatsWidget::class, [
            'pageFilters' => ['date_from' => now()->toDateString()],
        ]);

        $getStats = new \ReflectionMethod(OrdersReportStatsWidget::class, 'getStats');
        $stats = $getStats->invoke($component->instance());

        $totalLeadsStat = $stats[8]->getValue();
        $b2cLeadsStat = $stats[9]->getValue();
        $leadToPaidRateStat = $stats[10]->getValue();

        // 3 leads in range: 1 complaint + 1 corporate training (both excluded
        // from "B2C") + 1 plain lead (B2C, converted to the paid order).
        $this->assertSame('3', $totalLeadsStat);
        $this->assertSame('1', $b2cLeadsStat);
        // 2 items sold (paid order) / 1 B2C lead = 200%.
        $this->assertSame('200.0%', $leadToPaidRateStat);
    }

    public function test_it_opens_on_today_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsReportAdmin();

        Livewire::test(OrdersReport::class)
            ->assertSet('filters.date_range', 'today')
            ->assertSet('filters.date_from', now()->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->assertSet('filters.status', 'paid')
            ->set('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString())
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->assertOk();
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsReportAdmin();

        Livewire::test(OrdersReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    private function actingAsReportAdmin(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');
        Permission::query()->firstOrCreate(['name' => 'orders_report.viewAny', 'guard_name' => 'admin']);
        $admin->givePermissionTo('orders_report.viewAny');

        return $admin;
    }
}
