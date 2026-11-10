<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestSource;
use App\Filament\Pages\ServiceRequestStatusReport;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceRequestStatusReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithReportAccess(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');
        Permission::query()->firstOrCreate(['name' => 'service_request_status_report.viewAny', 'guard_name' => 'admin']);
        $admin->givePermissionTo('service_request_status_report.viewAny');

        return $admin;
    }

    public function test_it_denies_access_without_permission(): void
    {
        $this->actingAs(User::factory()->create(), 'admin');

        $this->assertFalse(ServiceRequestStatusReport::canAccess());
    }

    public function test_it_opens_on_all_time_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(ServiceRequestStatusReport::class)
            ->assertOk()
            ->assertSet('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->set('filters.date_range', 'today')
            ->assertSet('filters.date_from', now()->toDateString())
            ->assertSet('filters.date_to', now()->toDateString());
    }

    public function test_editing_a_date_by_hand_switches_the_preset_to_custom(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(ServiceRequestStatusReport::class)
            ->set('filters.date_to', now()->toDateString())
            ->assertSet('filters.date_range', 'custom');
    }

    public function test_last_month_preset_excludes_this_months_requests(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'status' => 'new']);
        ServiceRequest::factory()
            ->create(['source' => ServiceRequestSource::Lead, 'status' => 'new'])
            ->forceFill(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(2)])
            ->save();

        $allRows = $this->callBuildRows(Livewire::test(ServiceRequestStatusReport::class)->instance());
        $this->assertSame(2, $allRows->last()['total']);

        $page = Livewire::test(ServiceRequestStatusReport::class)
            ->set('filters.date_range', 'last_month')
            ->instance();

        $this->assertSame(1, $this->callBuildRows($page)->last()['total']);
    }

    public function test_it_exports_csv(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'status' => 'new']);

        Livewire::test(ServiceRequestStatusReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithReportAccess();

        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'status' => 'new']);

        Livewire::test(ServiceRequestStatusReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    private function callBuildRows(ServiceRequestStatusReport $page): Collection
    {
        return (new ReflectionMethod(ServiceRequestStatusReport::class, 'buildRows'))->invoke($page);
    }
}
