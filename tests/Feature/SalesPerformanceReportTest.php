<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\SalesPerformanceReport;
use App\Models\Employee;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
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

    private function makeEmployee(): Employee
    {
        return Employee::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    private function makeSalesOrder(Employee $employee, CarbonInterface $createdAt, int $totalAmount = 1000): Order
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Paid,
            'referred_by_employee_id' => $employee->id,
            'subtotal_amount' => $totalAmount,
            'discount_amount' => 0,
            'total_amount' => $totalAmount,
        ]);
        $order->forceFill(['created_at' => $createdAt, 'source' => 'sales'])->saveQuietly();

        return $order;
    }

    public function test_page_requires_permission(): void
    {
        $this->actingAsAdminWithPermissions([]);

        $this->assertFalse(SalesPerformanceReport::canAccess());
    }

    public function test_it_opens_on_today_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        Livewire::test(SalesPerformanceReport::class)
            ->assertSet('filters.date_range', 'today')
            ->assertSet('filters.date_from', now()->toDateString())
            ->assertSet('filters.date_to', now()->toDateString())
            ->set('filters.date_range', 'this_month')
            ->assertSet('filters.date_from', now()->startOfMonth()->toDateString())
            ->assertSet('filters.date_to', now()->endOfMonth()->toDateString())
            ->set('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null);
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        Livewire::test(SalesPerformanceReport::class)
            ->set('filters.date_from', now()->subDays(3)->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_presets_scope_the_employee_sales_counts(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $todaysSeller = $this->makeEmployee();
        $lastMonthsSeller = $this->makeEmployee();

        $this->makeSalesOrder($todaysSeller, now());
        $this->makeSalesOrder($lastMonthsSeller, now()->subMonthNoOverflow()->startOfMonth()->addDays(2), 500);

        Livewire::test(SalesPerformanceReport::class)
            ->assertCanSeeTableRecords([$todaysSeller])
            ->assertCanNotSeeTableRecords([$lastMonthsSeller])
            ->set('filters.date_range', 'last_month')
            ->assertCanSeeTableRecords([$lastMonthsSeller])
            ->assertCanNotSeeTableRecords([$todaysSeller])
            ->set('filters.date_range', 'all')
            ->assertCanSeeTableRecords([$todaysSeller, $lastMonthsSeller]);
    }

    public function test_all_preset_exports_every_sale(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $employee = $this->makeEmployee();
        $this->makeSalesOrder($employee, now());
        $this->makeSalesOrder($employee, now()->subYears(2));

        $component = Livewire::test(SalesPerformanceReport::class)
            ->set('filters.date_range', 'all');

        $rows = (fn (): array => $this->exportRows())->call($component->instance());

        $this->assertSame($employee->name, $rows[0][0]);
        $this->assertSame(2, $rows[0][2]);
        $this->assertSame(2, end($rows)[2]);
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithPermissions(['sales_performance_report.viewAny']);

        $this->makeSalesOrder($this->makeEmployee(), now());

        Livewire::test(SalesPerformanceReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }
}
