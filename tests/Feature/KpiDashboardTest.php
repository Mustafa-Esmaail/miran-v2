<?php

namespace Tests\Feature;

use App\Enums\EmployeeRole;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\CrmDashboard2;
use App\Filament\Widgets\KpiStatsWidget;
use App\Filament\Widgets\KpiTargetPaceWidget;
use App\Filament\Widgets\KpiTopCoursesWidget;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\SalesTarget;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\KpiDashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const OCTOBER = ['date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'source' => 'sales'];

    protected function setUp(): void
    {
        parent::setUp();

        // Thursday, October 15th 2026.
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00'));

        $this->actingAs(User::factory()->create(), 'admin');
    }

    public function test_page_requires_the_crm_dashboard_permission(): void
    {
        Livewire::test(CrmDashboard2::class)->assertForbidden();
    }

    public function test_page_and_widgets_render(): void
    {
        $user = User::factory()->create();
        Permission::query()->firstOrCreate(['name' => 'crm_dashboard.viewAny', 'guard_name' => 'admin']);
        $user->givePermissionTo('crm_dashboard.viewAny');
        $this->actingAs($user, 'admin');

        $this->createOrder(1500, 'sales');

        Livewire::test(CrmDashboard2::class)
            ->assertOk()
            ->assertSee(__('admin.navigation.kpi_dashboard'))
            ->assertSee(__('admin.kpi_dashboard.scope_all'));
        Livewire::test(KpiTargetPaceWidget::class, ['pageFilters' => self::OCTOBER])->assertOk();
        Livewire::test(KpiStatsWidget::class, ['pageFilters' => self::OCTOBER])->assertOk()->assertSee('1,500');
        Livewire::test(KpiTopCoursesWidget::class, ['pageFilters' => self::OCTOBER])->assertOk();
    }

    public function test_source_defaults_to_sales_and_null_means_all_sources(): void
    {
        $service = app(KpiDashboardService::class);

        $this->createOrder(1000, 'sales');
        $this->createOrder(400, 'site');
        $this->createOrder(9999, 'sales', 'pending');

        $this->assertSame('sales', $service->source([]));
        $this->assertSame(1000, $service->kpis(['date_from' => '2026-10-01', 'date_to' => '2026-10-31'])['revenue']);
        $this->assertSame(1400, $service->kpis(['date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'source' => null])['revenue']);
        $this->assertSame(400, $service->kpis([...self::OCTOBER, 'source' => 'site'])['revenue']);
    }

    public function test_target_pace_uses_sunday_to_thursday_working_days(): void
    {
        $employee = Employee::factory()->create();

        SalesTarget::query()->create([
            'employee_id' => $employee->id,
            'period_type' => 'monthly',
            'period' => '2026-10',
            'target_revenue' => 10000,
            'target_leads' => 0,
        ]);

        $this->createOrder(2500, 'sales');

        $pace = app(KpiDashboardService::class)->targetPace(self::OCTOBER);

        $this->assertSame(10000, $pace['target']);
        $this->assertSame(2500, $pace['achieved']);
        $this->assertSame(25.0, $pace['percent']);
        $this->assertSame(7500, $pace['remaining']);
        // Oct 15 (Thu) + 18–22 + 25–29 = 11 working days left out of 21 in October.
        $this->assertSame(11, $pace['remaining_working_days']);
        $this->assertSame(682, $pace['required_daily']);
        $this->assertSame(48, $pace['expected_percent']);
    }

    public function test_target_pace_has_no_days_left_for_a_past_period(): void
    {
        $pace = app(KpiDashboardService::class)->targetPace(['date_from' => '2026-09-01', 'date_to' => '2026-09-30']);

        $this->assertSame(0, $pace['target']);
        $this->assertSame(0, $pace['remaining_working_days']);
        $this->assertSame(100, $pace['expected_percent']);
    }

    public function test_kpis_compare_with_previous_period_and_last_year_and_count_leads(): void
    {
        $this->createOrder(3000, 'sales');
        $this->createOrder(1000, 'sales', 'paid', '2026-09-20');
        $this->createOrder(2000, 'sales', 'paid', '2025-10-10');

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'converted_at' => now()]);
        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead]);
        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Complaint]);

        $kpis = app(KpiDashboardService::class)->kpis(self::OCTOBER);

        $this->assertSame(3000, $kpis['revenue']);
        $this->assertSame(1, $kpis['orders']);
        $this->assertSame(1000, $kpis['previous_revenue']);
        $this->assertSame(200.0, $kpis['revenue_change']);
        $this->assertSame(2000, $kpis['last_year_revenue']);
        $this->assertSame(50.0, $kpis['yoy_growth']);
        $this->assertSame(2, $kpis['leads']);
        $this->assertSame(50.0, $kpis['conversion_rate']);
    }

    public function test_daily_sales_fill_every_day_of_the_range(): void
    {
        $this->createOrder(700, 'sales');

        $daily = app(KpiDashboardService::class)->dailySales(self::OCTOBER);

        $this->assertCount(31, $daily['labels']);
        $this->assertSame(700, $daily['data'][14]);
        $this->assertSame(0, $daily['data'][0]);
    }

    public function test_payment_methods_group_revenue_by_provider(): void
    {
        $tamaraOrder = $this->createOrder(800, 'sales');
        Payment::query()->create([
            'order_id' => $tamaraOrder->id,
            'provider' => 'tamara',
            'provider_reference' => 'REF-KPI-1',
            'status' => 'paid',
            'amount' => 800,
        ]);
        $this->createOrder(300, 'sales');

        $methods = app(KpiDashboardService::class)->paymentMethods(self::OCTOBER);

        $this->assertSame([
            __('admin.kpi_dashboard.payment_methods.tamara') => 800,
            __('admin.kpi_dashboard.payment_methods.unknown') => 300,
        ], $methods);
    }

    public function test_top_sellers_and_top_courses_rank_by_revenue(): void
    {
        $small = Employee::factory()->create(['name' => 'Small Seller']);
        $big = Employee::factory()->create(['name' => 'Big Seller']);

        $category = Category::query()->create(['name' => ['en' => 'Cat'], 'slug' => 'kpi-cat', 'is_active' => true]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'KPI Course', 'ar' => 'KPI Course'],
            'summary' => ['en' => 'S'],
            'description' => ['en' => 'D'],
            'slug' => 'kpi-course',
            'status' => 'published',
        ]);
        $groupA = CourseGroup::query()->create(['course_id' => $course->id, 'title' => ['en' => 'A'], 'group_price_amount' => 500, 'status' => 'open']);
        $groupB = CourseGroup::query()->create(['course_id' => $course->id, 'title' => ['en' => 'B'], 'group_price_amount' => 500, 'status' => 'open']);

        $this->addItem($this->createOrder(500, 'sales', employee: $small), $groupA, 500);
        $this->addItem($this->createOrder(1500, 'sales', employee: $big), $groupB, 1500);

        $service = app(KpiDashboardService::class);
        $sellers = $service->topSellers(self::OCTOBER);
        $courses = $service->topCourses(self::OCTOBER);

        $this->assertSame(['Big Seller', 'Small Seller'], $sellers->pluck('name')->all());
        $this->assertCount(1, $courses);
        $this->assertSame('KPI Course', $courses[0]['title']);
        $this->assertSame(2000, $courses[0]['total']);
        $this->assertSame(2, $courses[0]['orders_count']);
    }

    public function test_monthly_trend_covers_twelve_months_against_previous_year(): void
    {
        $this->createOrder(1200, 'sales');
        $this->createOrder(900, 'sales', 'paid', '2025-10-05');

        $trend = app(KpiDashboardService::class)->monthlyTrend(self::OCTOBER);

        $this->assertCount(12, $trend['labels']);
        $this->assertSame(1200, $trend['current'][11]);
        $this->assertSame(900, $trend['previous'][11]);
        $this->assertSame(0, $trend['current'][0]);
    }

    public function test_sales_person_only_sees_their_own_sales(): void
    {
        $user = User::factory()->create();
        $me = Employee::factory()->create(['user_id' => $user->id, 'role' => EmployeeRole::SalesPerson]);
        $colleague = Employee::factory()->create();

        $this->createOrder(600, 'sales', employee: $me);
        $this->createOrder(4000, 'sales', employee: $colleague);

        $this->actingAs($user, 'admin');

        $service = app(KpiDashboardService::class);

        $this->assertSame(600, $service->kpis(self::OCTOBER)['revenue']);
        $this->assertSame($me->name, $service->scopeLabel());
    }

    /**
     * OrderObserver derives the source from the referrer: a sales order always has a referring employee.
     */
    public function test_page_uses_the_shared_presets_without_all_time_or_single_days(): void
    {
        $this->actingAsDashboardViewer();

        $component = Livewire::test(CrmDashboard2::class)
            ->assertSet('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', '2026-10-01')
            ->assertSet('filters.date_to', '2026-10-31')
            ->set('filters.date_range', 'last_30_days')
            ->assertSet('filters.date_from', '2026-09-16')
            ->assertSet('filters.date_to', '2026-10-15')
            ->set('filters.date_range', 'this_quarter')
            ->assertSet('filters.date_from', '2026-10-01')
            ->assertSet('filters.date_to', '2026-12-31');

        $options = $component->instance()->getSchema('filtersForm')->getComponent('date_range')->getOptions();

        $this->assertSame(
            ['this_month', 'last_month', 'last_30_days', 'this_quarter', 'this_year', 'custom'],
            array_keys($options),
        );
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsDashboardViewer();

        Livewire::test(CrmDashboard2::class)
            ->set('filters.date_from', '2026-10-05')
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_kpi_dashboard_is_the_crm_home_and_the_pipeline_comes_second(): void
    {
        $this->assertLessThan(
            \App\Filament\Pages\CrmDashboard::getNavigationSort(),
            CrmDashboard2::getNavigationSort(),
        );
        $this->assertSame(__('admin.navigation.kpi_dashboard'), CrmDashboard2::getNavigationLabel());
        $this->assertSame('CRM Dashboard', __('admin.navigation.kpi_dashboard', [], 'en'));
        $this->assertSame('Pipeline Dashboard', __('admin.navigation.crm_dashboard', [], 'en'));
    }

    private function actingAsDashboardViewer(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'admin');
        Permission::query()->firstOrCreate(['name' => 'crm_dashboard.viewAny', 'guard_name' => 'admin']);
        $user->givePermissionTo('crm_dashboard.viewAny');

        return $user;
    }

    private function createOrder(int $amount, string $source, string $status = 'paid', ?string $createdAt = null, ?Employee $employee = null): Order
    {
        if ($source === 'sales') {
            $employee ??= Employee::factory()->create();
        }

        $order = Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-KPI-'.fake()->unique()->numberBetween(1000, 999999),
            'status' => $status,
            'source' => $source,
            'currency' => 'SAR',
            'subtotal_amount' => $amount,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'referred_by_employee_id' => $employee?->id,
        ]);

        if ($createdAt) {
            $order->forceFill(['created_at' => CarbonImmutable::parse($createdAt.' 10:00:00')])->saveQuietly();
        }

        return $order;
    }

    private function addItem(Order $order, CourseGroup $group, int $price): void
    {
        OrderItem::query()->create([
            'order_id' => $order->id,
            'purchasable_type' => CourseGroup::class,
            'purchasable_id' => $group->id,
            'title_snapshot' => 'Item',
            'price_amount' => $price,
            'currency' => 'SAR',
            'metadata_snapshot' => [],
            'quantity' => 1,
        ]);
    }
}
