<?php

namespace Tests\Feature;

use App\Exports\ReportArrayExport;
use App\Filament\Pages\MarketingSourceReport;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MarketingSourceReportTest extends TestCase
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

    private function makeServiceRequest(?string $marketingSource): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'source' => 'form',
            'marketing_source' => $marketingSource,
        ]);
    }

    private function makeOrder(string $orderNo, string $status, ?string $marketingSource): Order
    {
        return Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => $orderNo,
            'status' => $status,
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'source' => 'store',
            'marketing_source' => $marketingSource,
        ]);
    }

    public function test_it_denies_access_without_permission(): void
    {
        User::factory()->create();

        $this->assertFalse(MarketingSourceReport::canAccess());
    }

    public function test_it_groups_requests_and_paid_orders_by_marketing_source(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $this->makeServiceRequest('facebook_ads');
        $this->makeServiceRequest('facebook_ads');
        $this->makeServiceRequest('facebook_ads');
        $this->makeOrder('ORD-MS-1', 'paid', 'facebook_ads');

        $this->makeServiceRequest('snapchat_ads');
        $this->makeOrder('ORD-MS-2', 'pending', 'snapchat_ads');

        $totals = Livewire::test(MarketingSourceReport::class)->instance()->getReportTotals();

        $this->assertSame(4, $totals['requests_total']);
        $this->assertSame(1, $totals['paid_total']);

        Livewire::test(MarketingSourceReport::class)
            ->assertOk()
            ->assertSee('facebook_ads')
            ->assertSee('snapchat_ads');
    }

    public function test_it_folds_null_and_empty_marketing_source_into_unknown_bucket(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $this->makeServiceRequest(null);
        $this->makeServiceRequest('');
        $this->makeOrder('ORD-MS-UNKNOWN', 'paid', null);

        Livewire::test(MarketingSourceReport::class)
            ->assertOk()
            ->assertSee(__('admin.labels.unknown'));

        $totals = Livewire::test(MarketingSourceReport::class)->instance()->getReportTotals();

        $this->assertSame(1, $totals['sources_count']);
        $this->assertSame(2, $totals['requests_total']);
        $this->assertSame(1, $totals['paid_total']);
    }

    public function test_it_excludes_admin_source_orders_from_paid_counts(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $this->makeServiceRequest('google_ads');
        Order::query()->create([
            'user_id' => User::factory()->create()->id,
            'order_no' => 'ORD-MS-ADMIN',
            'status' => 'paid',
            'currency' => 'SAR',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'total_amount' => 100,
            'source' => 'admin',
            'marketing_source' => 'google_ads',
        ]);

        $totals = Livewire::test(MarketingSourceReport::class)->instance()->getReportTotals();

        $this->assertSame(1, $totals['requests_total']);
        $this->assertSame(0, $totals['paid_total']);
    }

    public function test_it_exports_csv_and_xlsx_with_a_totals_row(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $this->makeServiceRequest('facebook_ads');
        $this->makeServiceRequest('facebook_ads');
        $this->makeOrder('ORD-MS-EXP', 'paid', 'facebook_ads');

        Livewire::test(MarketingSourceReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();

        Excel::fake();
        Excel::matchByRegex();

        Livewire::test(MarketingSourceReport::class)->callAction('export_xlsx');

        Excel::assertDownloaded('/^miran-marketing-source-report-\d{8}-\d{6}\.xlsx$/', function (ReportArrayExport $export): bool {
            $rows = $export->array();

            return count($export->headings()) === 4
                && $rows[0] === ['facebook_ads', 2, 1, 50.0]
                && end($rows) === [__('admin.labels.total'), 2, 1, 50.0];
        });
    }

    public function test_date_filter_scopes_requests_and_paid_orders(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $recentRequest = $this->makeServiceRequest('tiktok_ads');
        $oldRequest = $this->makeServiceRequest('tiktok_ads');
        $oldRequest->forceFill(['created_at' => now()->subDays(30)])->save();

        $recentOrder = $this->makeOrder('ORD-MS-RECENT', 'paid', 'tiktok_ads');
        $oldOrder = $this->makeOrder('ORD-MS-OLD', 'paid', 'tiktok_ads');
        $oldOrder->forceFill(['created_at' => now()->subDays(30)])->save();

        $unfiltered = Livewire::test(MarketingSourceReport::class)
            ->set('filters.date_from', null)
            ->set('filters.date_to', null)
            ->instance()
            ->getReportTotals();
        $this->assertSame(2, $unfiltered['requests_total']);
        $this->assertSame(2, $unfiltered['paid_total']);

        $filtered = Livewire::test(MarketingSourceReport::class)
            ->set('filters.date_from', now()->subDays(2)->toDateString())
            ->instance()
            ->getReportTotals();
        $this->assertSame(1, $filtered['requests_total']);
        $this->assertSame(1, $filtered['paid_total']);
    }

    public function test_it_opens_on_this_month_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        Livewire::test(MarketingSourceReport::class)
            ->assertSet('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString())
            ->set('filters.date_range', 'yesterday')
            ->assertSet('filters.date_from', now()->subDay()->toDateString())
            ->assertSet('filters.date_to', now()->subDay()->toDateString())
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null);
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        Livewire::test(MarketingSourceReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_last_month_preset_excludes_this_months_requests(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $this->makeServiceRequest('google_ads');
        $lastMonth = $this->makeServiceRequest('google_ads');
        $lastMonth->forceFill(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(2)])->save();

        $totals = Livewire::test(MarketingSourceReport::class)
            ->set('filters.date_range', 'last_month')
            ->instance()
            ->getReportTotals();

        $this->assertSame(1, $totals['requests_total']);
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['marketing_source_report.viewAny']);

        $this->makeServiceRequest('facebook_ads');

        Livewire::test(MarketingSourceReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }
}
