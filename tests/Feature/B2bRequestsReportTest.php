<?php

namespace Tests\Feature;

use App\Enums\CrmActivityType;
use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestActivityType;
use App\Enums\ServiceRequestSource;
use App\Filament\Pages\B2bRequestsReport;
use App\Filament\Widgets\B2bDealStatsWidget;
use App\Filament\Widgets\B2bRequestStatsWidget;
use App\Models\Company;
use App\Models\CrmActivity;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestActivity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class B2bRequestsReportTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithReportAccess(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'b2b_requests_report.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function createAgent(string $name, array $attributes = []): Employee
    {
        $user = User::factory()->create(['name' => $name]);

        return Employee::factory()->create(array_merge(['user_id' => $user->id, 'name' => $name], $attributes));
    }

    public function test_page_is_forbidden_without_permission(): void
    {
        $this->actingAs(User::factory()->create(), 'admin');

        $this->assertFalse(B2bRequestsReport::canAccess());
    }

    public function test_page_renders_with_stats_widget(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(B2bRequestsReport::class)
            ->assertOk()
            ->assertSee(__('admin.dashboard_widgets.stats.b2b_activities'));

        Livewire::test(B2bRequestStatsWidget::class)
            ->assertOk()
            ->assertSee(__('admin.dashboard_widgets.stats.b2b_total'))
            ->assertSee(__('admin.dashboard_widgets.stats.b2b_success_rate'));
    }

    public function test_per_employee_row_counts_only_non_spam_b2b_requests_by_outcome(): void
    {
        $this->actingAsAdminWithReportAccess();

        $agent = $this->createAgent('B2B Agent');

        foreach (['payment_completed', 'price_not_suitable', 'wrong_number', 'no_answer_1', 'spam'] as $status) {
            ServiceRequest::factory()->create([
                'source' => ServiceRequestSource::CorporateTraining,
                'status' => $status,
                'assigned_to' => $agent->user_id,
            ]);
        }

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'payment_completed',
            'assigned_to' => $agent->user_id,
        ]);

        $page = Livewire::test(B2bRequestsReport::class, ['filters' => ['department_id' => null]])->assertOk();

        $row = $this->callBuildRows($page->instance())->firstWhere('employee', 'B2B Agent');

        $this->assertNotNull($row);
        $this->assertSame(4, $row['total']);
        $this->assertSame(1, $row['succeeded']);
        $this->assertSame(2, $row['failed']);
        $this->assertSame(1, $row['open']);
        $this->assertSame(33.3, $row['success_rate']);
    }

    public function test_success_rate_is_null_when_nothing_is_closed(): void
    {
        $this->actingAsAdminWithReportAccess();

        $agent = $this->createAgent('Open Only Agent');

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::CorporateTraining,
            'status' => 'no_answer_1',
            'assigned_to' => $agent->user_id,
        ]);

        $page = Livewire::test(B2bRequestsReport::class, ['filters' => ['department_id' => null]])->assertOk();

        $row = $this->callBuildRows($page->instance())->firstWhere('employee', 'Open Only Agent');

        $this->assertSame(1, $row['open']);
        $this->assertNull($row['success_rate']);
    }

    public function test_activities_combine_request_history_and_crm_activities_on_b2b_requests_only(): void
    {
        $this->actingAsAdminWithReportAccess();

        $agent = $this->createAgent('Active Agent');

        $b2bRequest = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::CorporateTraining,
            'status' => 'no_answer_1',
            'assigned_to' => $agent->user_id,
        ]);

        $b2cRequest = ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::Lead,
            'status' => 'no_answer_1',
            'assigned_to' => $agent->user_id,
        ]);

        foreach ([$b2bRequest, $b2cRequest] as $request) {
            ServiceRequestActivity::query()->create([
                'service_request_id' => $request->id,
                'actor_user_id' => $agent->user_id,
                'type' => ServiceRequestActivityType::NoteAdded,
                'created_at' => now(),
            ]);
        }

        foreach ([CrmActivityType::Call, CrmActivityType::Meeting] as $type) {
            CrmActivity::query()->create([
                'entity_type' => ServiceRequest::class,
                'entity_id' => $b2bRequest->id,
                'type' => $type,
                'subject' => 'Follow up',
                'employee_id' => $agent->id,
            ]);
        }

        $page = Livewire::test(B2bRequestsReport::class, ['filters' => ['department_id' => null]])->assertOk();

        $row = $this->callBuildRows($page->instance())->firstWhere('employee', 'Active Agent');

        $this->assertSame(3, $row['activities']);
    }

    public function test_date_filter_limits_counted_requests(): void
    {
        $this->actingAsAdminWithReportAccess();

        $agent = $this->createAgent('Dated Agent');

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::CorporateTraining,
            'status' => 'payment_completed',
            'assigned_to' => $agent->user_id,
            'created_at' => now()->subMonths(2),
        ]);

        ServiceRequest::factory()->create([
            'source' => ServiceRequestSource::CorporateTraining,
            'status' => 'payment_completed',
            'assigned_to' => $agent->user_id,
            'created_at' => now(),
        ]);

        $page = Livewire::test(B2bRequestsReport::class, ['filters' => [
            'department_id' => null,
            'date_from' => now()->subWeek()->toDateString(),
        ]])->assertOk();

        $row = $this->callBuildRows($page->instance())->firstWhere('employee', 'Dated Agent');

        $this->assertSame(1, $row['total']);
    }

    public function test_department_filter_defaults_to_operations_and_narrows_rows(): void
    {
        $this->actingAsAdminWithReportAccess();

        $operations = Department::factory()->create(['code' => 'DEPT-OPS']);
        $sales = Department::factory()->create(['name' => 'Sales Dept For Test']);

        $this->createAgent('Ops Employee', [
            'department_id' => $operations->id,
            'team_id' => Team::factory()->create(['department_id' => $operations->id])->id,
        ]);
        $this->createAgent('Sales Employee', [
            'department_id' => $sales->id,
            'team_id' => Team::factory()->create(['department_id' => $sales->id])->id,
        ]);

        $page = Livewire::test(B2bRequestsReport::class)
            ->assertOk()
            ->assertSet('filters.department_id', $operations->id);

        $rows = $this->callBuildRows($page->instance());

        $this->assertTrue($rows->contains('employee', 'Ops Employee'));
        $this->assertFalse($rows->contains('employee', 'Sales Employee'));
    }

    public function test_status_filter_options_are_scoped_to_the_requests_type_without_spam(): void
    {
        $this->actingAsAdminWithReportAccess();

        $page = Livewire::test(B2bRequestsReport::class)->assertOk();

        $method = new ReflectionMethod(B2bRequestsReport::class, 'b2bStatusOptions');
        $options = $method->invoke($page->instance());

        $this->assertArrayHasKey('payment_completed', $options);
        $this->assertArrayHasKey('new', $options);
        $this->assertArrayNotHasKey('spam', $options);
        $this->assertArrayNotHasKey('complaint_late', $options);
    }

    public function test_request_stats_split_total_into_new_open_successful_and_cancelled(): void
    {
        $this->actingAsAdminWithReportAccess();

        foreach (['new', 'new', 'no_answer_1', 'payment_completed', 'price_not_suitable', 'spam'] as $status) {
            $this->createB2bRequest(['status' => $status]);
        }
        ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead, 'status' => 'new']);

        $stats = $this->statValues(B2bRequestStatsWidget::class, []);

        $this->assertSame(5, $stats[__('admin.dashboard_widgets.stats.b2b_total')]);
        $this->assertSame(2, $stats[__('admin.dashboard_widgets.stats.b2b_new')]);
        $this->assertSame(1, $stats[__('admin.dashboard_widgets.stats.b2b_open')]);
        $this->assertSame(1, $stats[__('admin.dashboard_widgets.stats.b2b_succeeded')]);
        $this->assertSame(1, $stats[__('admin.dashboard_widgets.stats.b2b_cancelled')]);
        $this->assertSame('50%', $stats[__('admin.dashboard_widgets.stats.b2b_success_rate')]);
    }

    public function test_deal_stats_count_deals_by_main_status_and_ratios(): void
    {
        $this->actingAsAdminWithReportAccess();

        $withDeals = $this->createB2bRequest();
        $this->createB2bRequest();
        $company = Company::query()->create(['name' => 'Acme']);

        $open = $this->createDeal($withDeals, $company, DealStage::Proposal);
        $this->createDeal($withDeals, $company, DealStage::ClosedWon);
        $this->createDeal($withDeals, $company, DealStage::ClosedLost);
        $this->createQuote($open);
        $this->createQuote($open);
        $this->createQuote($open);

        $b2cRequest = ServiceRequest::factory()->create(['source' => ServiceRequestSource::Lead]);
        $this->createQuote($this->createDeal($b2cRequest, $company, DealStage::ClosedWon));

        $stats = $this->statValues(B2bDealStatsWidget::class, []);

        $this->assertSame(3, $stats[__('admin.dashboard_widgets.stats.b2b_deals_total')]);
        $this->assertSame(1, $stats[__('admin.dashboard_widgets.stats.b2b_deals_open')]);
        $this->assertSame(1, $stats[__('admin.dashboard_widgets.stats.b2b_deals_won')]);
        $this->assertSame(1, $stats[__('admin.dashboard_widgets.stats.b2b_deals_lost')]);
        $this->assertSame('1.5', $stats[__('admin.dashboard_widgets.stats.b2b_deals_per_request')]);
        $this->assertSame(3, $stats[__('admin.dashboard_widgets.stats.b2b_quotes_total')]);
        $this->assertSame('1.0', $stats[__('admin.dashboard_widgets.stats.b2b_quotes_per_deal')]);
    }

    public function test_deal_stats_ratios_are_zero_when_there_is_nothing_to_divide_by(): void
    {
        $this->actingAsAdminWithReportAccess();

        $stats = $this->statValues(B2bDealStatsWidget::class, []);

        $this->assertSame(0, $stats[__('admin.dashboard_widgets.stats.b2b_deals_total')]);
        $this->assertSame('0', $stats[__('admin.dashboard_widgets.stats.b2b_deals_per_request')]);
        $this->assertSame('0', $stats[__('admin.dashboard_widgets.stats.b2b_quotes_per_deal')]);
    }

    public function test_company_filter_uses_companies_table_through_deals(): void
    {
        $this->actingAsAdminWithReportAccess();

        $acme = Company::query()->create(['name' => 'Acme']);
        $globex = Company::query()->create(['name' => 'Globex']);

        $acmeRequest = $this->createB2bRequest(['company_name' => 'Globex']);
        $this->createDeal($acmeRequest, $acme, DealStage::ClosedWon);
        $this->createDeal($this->createB2bRequest(['company_name' => 'Acme']), $globex, DealStage::Proposal);
        $this->createB2bRequest(['company_name' => 'Acme']);

        $filters = ['company_id' => $acme->id];

        $requestStats = $this->statValues(B2bRequestStatsWidget::class, $filters);
        $dealStats = $this->statValues(B2bDealStatsWidget::class, $filters);

        $this->assertSame(1, $requestStats[__('admin.dashboard_widgets.stats.b2b_total')]);
        $this->assertSame(1, $dealStats[__('admin.dashboard_widgets.stats.b2b_deals_total')]);
        $this->assertSame(1, $dealStats[__('admin.dashboard_widgets.stats.b2b_deals_won')]);
    }

    public function test_team_table_credits_deals_and_quotes_to_the_deal_owner(): void
    {
        $this->actingAsAdminWithReportAccess();

        $assignee = $this->createAgent('Request Assignee');
        $dealOwner = $this->createAgent('Deal Owner');
        $company = Company::query()->create(['name' => 'Acme']);

        $request = $this->createB2bRequest(['assigned_to' => $assignee->user_id]);
        $won = $this->createDeal($request, $company, DealStage::ClosedWon, $dealOwner);
        $this->createDeal($request, $company, DealStage::Proposal, $dealOwner);
        $this->createQuote($won);

        $page = Livewire::test(B2bRequestsReport::class, ['filters' => ['department_id' => null, 'team_id' => null]])->assertOk();
        $rows = $this->callBuildRows($page->instance());

        $ownerRow = $rows->firstWhere('employee', 'Deal Owner');
        $assigneeRow = $rows->firstWhere('employee', 'Request Assignee');

        $this->assertSame(2, $ownerRow['deals']);
        $this->assertSame(1, $ownerRow['deals_won']);
        $this->assertSame(1, $ownerRow['quotes']);
        $this->assertSame(0, $ownerRow['total']);

        $this->assertSame(0, $assigneeRow['deals']);
        $this->assertSame(1, $assigneeRow['total']);
    }

    public function test_team_filter_defaults_to_the_companies_team_when_it_exists(): void
    {
        $this->actingAsAdminWithReportAccess();

        $operations = Department::factory()->create(['code' => 'DEPT-OPS']);
        $companiesTeam = Team::factory()->create([
            'department_id' => $operations->id,
            'name' => B2bRequestsReport::DEFAULT_TEAM_NAME,
        ]);

        Livewire::test(B2bRequestsReport::class)
            ->assertOk()
            ->assertSet('filters.department_id', $operations->id)
            ->assertSet('filters.team_id', $companiesTeam->id);
    }

    public function test_team_filter_defaults_to_all_when_the_companies_team_is_missing(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(B2bRequestsReport::class)
            ->assertOk()
            ->assertSet('filters.team_id', null);
    }

    public function test_it_opens_on_all_time_and_presets_fill_the_date_pickers(): void
    {
        $this->actingAsAdminWithReportAccess();

        Livewire::test(B2bRequestsReport::class)
            ->assertSet('filters.date_range', 'all')
            ->assertSet('filters.date_from', null)
            ->assertSet('filters.date_to', null)
            ->set('filters.date_range', 'yesterday')
            ->assertSet('filters.date_from', now()->subDay()->toDateString())
            ->assertSet('filters.date_to', now()->subDay()->toDateString());
    }

    public function test_it_exports_csv(): void
    {
        $this->actingAsAdminWithReportAccess();

        $this->createB2bRequest(['assigned_to' => $this->createAgent('Export Agent')->user_id]);

        Livewire::test(B2bRequestsReport::class)
            ->callAction('export_csv')
            ->assertFileDownloaded();
    }

    public function test_it_exports_a_pdf(): void
    {
        $this->actingAsAdminWithReportAccess();

        $this->createB2bRequest(['assigned_to' => $this->createAgent('Export Agent')->user_id]);

        Livewire::test(B2bRequestsReport::class)
            ->callAction('export_pdf')
            ->assertFileDownloaded();
    }

    private function createB2bRequest(array $attributes = []): ServiceRequest
    {
        return ServiceRequest::factory()->create(array_merge([
            'source' => ServiceRequestSource::CorporateTraining,
            'status' => 'no_answer_1',
        ], $attributes));
    }

    private function createDeal(ServiceRequest $request, Company $company, DealStage $stage, ?Employee $owner = null): Deal
    {
        return Deal::query()->create([
            'company_id' => $company->id,
            'service_request_id' => $request->id,
            'title' => 'Deal '.$stage->value,
            'stage' => $stage,
            'employee_id' => $owner?->id,
        ]);
    }

    private function createQuote(Deal $deal): Quote
    {
        return Quote::query()->create([
            'deal_id' => $deal->id,
            'status' => QuoteStatus::Draft,
        ]);
    }

    /**
     * @param  class-string  $widget
     * @param  array<string, mixed>  $filters
     * @return array<string, int|string>
     */
    private function statValues(string $widget, array $filters): array
    {
        $instance = Livewire::test($widget, ['pageFilters' => $filters])->assertOk()->instance();
        $method = new ReflectionMethod($widget, 'getStats');

        return collect($method->invoke($instance))
            ->mapWithKeys(fn ($stat): array => [(string) $stat->getLabel() => $stat->getValue()])
            ->all();
    }

    private function callBuildRows(B2bRequestsReport $page): Collection
    {
        $method = new ReflectionMethod(B2bRequestsReport::class, 'buildRows');

        return $method->invoke($page);
    }
}
