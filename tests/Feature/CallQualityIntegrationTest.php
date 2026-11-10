<?php

namespace Tests\Feature;

use App\Enums\CrmActivityType;
use App\Filament\Admin\Resources\CallEvaluations\CallEvaluationResource;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewB2cServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\CrmActivitiesRelationManager;
use App\Filament\Widgets\KpiStatsWidget;
use App\Models\CallEvaluation;
use App\Models\CrmActivity;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\KpiDashboardService;
use App\Support\CallQuality\CallQualityLevels;
use App\Support\Crm\LeadTimeline;
use Carbon\CarbonImmutable;
use Database\Seeders\CallQualitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CallQualityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const OCTOBER = ['date_from' => '2026-10-01', 'date_to' => '2026-10-31', 'source' => 'sales'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CallQualitySeeder::class);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsAdminWith(array $permissions): User
    {
        $admin = User::factory()->create(['name' => 'Quality Evaluator']);
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function callActivity(ServiceRequest $lead, string $subject, ?string $createdAt = null): CrmActivity
    {
        return CrmActivity::factory()->create([
            'entity_type' => ServiceRequest::class,
            'entity_id' => $lead->id,
            'type' => CrmActivityType::Call->value,
            'subject' => $subject,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function evaluate(ServiceRequest $lead, ?CrmActivity $activity, int $score, User $evaluator, ?string $callDate = null): CallEvaluation
    {
        return CallEvaluation::factory()->scoredAll($score)->create([
            'service_request_id' => $lead->id,
            'crm_activity_id' => $activity?->id,
            'evaluator_id' => $evaluator->id,
            'employee_id' => Employee::factory()->create()->id,
            'call_phone' => $lead->phone,
            'call_date' => $callDate ?? now()->toDateString(),
        ]);
    }

    public function test_lead_view_offers_evaluate_call_with_the_create_permission_and_links_the_lead(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'call_evaluations.create']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertActionVisible('evaluate_call')
            ->assertActionHasUrl('evaluate_call', CallEvaluationResource::getUrl('create', ['service_request' => $lead->getKey()]));
    }

    public function test_evaluate_call_is_hidden_without_permission_or_without_a_phone(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'b2c_service_requests.viewAny', 'b2c_service_requests.view']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertActionHidden('evaluate_call');

        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'call_evaluations.create']);
        $withoutPhone = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '']);

        Livewire::test(ViewServiceRequest::class, ['record' => $withoutPhone->getKey()])
            ->assertActionHidden('evaluate_call');
    }

    public function test_evaluate_call_is_on_every_lead_view_page(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'b2c_service_requests.viewAny', 'b2c_service_requests.view', 'call_evaluations.create']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);

        Livewire::test(ViewB2cServiceRequest::class, ['record' => $lead->getKey()])
            ->assertActionVisible('evaluate_call');
    }

    public function test_activities_relation_manager_offers_evaluate_only_for_unrated_call_activities(): void
    {
        $evaluator = $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'call_evaluations.viewAny', 'call_evaluations.view', 'call_evaluations.create']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);

        $unrated = $this->callActivity($lead, 'Unrated call');
        $rated = $this->callActivity($lead, 'Rated call');
        $email = CrmActivity::factory()->create([
            'entity_type' => ServiceRequest::class,
            'entity_id' => $lead->id,
            'type' => CrmActivityType::Email->value,
        ]);
        $evaluation = $this->evaluate($lead, $rated, 5, $evaluator);

        Livewire::test(CrmActivitiesRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewServiceRequest::class])
            ->assertOk()
            ->assertTableActionVisible('evaluate_call', $unrated)
            ->assertTableActionHasUrl('evaluate_call', CallEvaluationResource::getUrl('create', [
                'service_request' => $lead->getKey(),
                'crm_activity' => $unrated->getKey(),
            ]), $unrated)
            ->assertTableActionHidden('evaluate_call', $rated)
            ->assertTableActionHidden('evaluate_call', $email)
            ->assertTableColumnStateSet('call_evaluation', __('admin.call_quality.lead.rated_badge', [
                'percent' => '100%',
                'level' => CallQualityLevels::label('excellent'),
            ]), $rated)
            ->assertTableColumnStateSet('call_evaluation', null, $unrated)
            ->assertSee(CallEvaluationResource::getUrl('view', ['record' => $evaluation]), false);
    }

    public function test_activities_relation_manager_hides_evaluate_without_the_create_permission_and_the_badge_without_view_any(): void
    {
        $other = User::factory()->create();
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);
        $unrated = $this->callActivity($lead, 'Unrated call');
        $rated = $this->callActivity($lead, 'Rated call');
        $this->evaluate($lead, $rated, 5, $other);

        Livewire::test(CrmActivitiesRelationManager::class, ['ownerRecord' => $lead, 'pageClass' => ViewServiceRequest::class])
            ->assertOk()
            ->assertTableActionHidden('evaluate_call', $unrated)
            ->assertTableColumnHidden('call_evaluation');
    }

    public function test_timeline_includes_the_evaluation_entry_and_the_activity_badge(): void
    {
        $viewer = $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'call_evaluations.viewAny', 'call_evaluations.view']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);
        $activity = $this->callActivity($lead, 'Evaluated call with the lead', now()->subDays(2)->toDateTimeString());
        $evaluation = $this->evaluate($lead, $activity, 5, $viewer, now()->subDays(2)->toDateString());
        $evaluation->forceFill(['discussed_at' => now(), 'created_at' => now()->addMinute()])->save();

        $entries = LeadTimeline::for($lead, $viewer);
        $evaluationEntry = $entries->firstWhere('source', LeadTimeline::SOURCE_CALL_EVALUATION);
        $activityEntry = $entries->firstWhere('source', LeadTimeline::SOURCE_CRM_ACTIVITY);
        $rating = __('admin.call_quality.lead.rated_badge', ['percent' => '100%', 'level' => CallQualityLevels::label('excellent')]);
        $viewUrl = CallEvaluationResource::getUrl('view', ['record' => $evaluation]);

        $this->assertNotNull($evaluationEntry);
        $this->assertSame(__('admin.call_quality.lead.timeline_title', ['percent' => '100%']), $evaluationEntry['title']);
        $this->assertSame(CallQualityLevels::label('excellent'), $evaluationEntry['rating']['label']);
        $this->assertSame([__('admin.call_quality.lead.state_discussed')], $evaluationEntry['badges']);
        $this->assertSame('Quality Evaluator', $evaluationEntry['actor']);
        $this->assertSame($viewUrl, $evaluationEntry['url']);
        $this->assertSame($rating, $activityEntry['rating']['label']);
        $this->assertSame($viewUrl, $activityEntry['url']);
        $this->assertSame(LeadTimeline::SOURCE_CALL_EVALUATION, $entries->first()['source']);

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertOk()
            ->assertSeeInOrder([
                __('admin.call_quality.lead.timeline_title', ['percent' => '100%']),
                'Evaluated call with the lead',
            ])
            ->assertSee($rating)
            ->assertSee($viewUrl, false);
    }

    public function test_timeline_leaves_out_evaluations_without_view_any(): void
    {
        $other = User::factory()->create();
        $viewer = $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);
        $activity = $this->callActivity($lead, 'Evaluated call with the lead');
        $this->evaluate($lead, $activity, 5, $other);

        $entries = LeadTimeline::for($lead, $viewer);

        $this->assertNull($entries->firstWhere('source', LeadTimeline::SOURCE_CALL_EVALUATION));
        $this->assertNull($entries->firstWhere('source', LeadTimeline::SOURCE_CRM_ACTIVITY)['rating']);
    }

    public function test_kpi_shows_the_average_call_quality_for_the_range(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00'));
        $viewer = $this->actingAsAdminWith(['crm_dashboard.viewAny', 'call_evaluations.viewAny']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);

        $this->evaluate($lead, null, 5, $viewer, '2026-10-05');
        $this->evaluate($lead, null, 3, $viewer, '2026-10-20');
        $this->evaluate($lead, null, 1, $viewer, '2026-09-30');

        $callQuality = app(KpiDashboardService::class)->callQuality(self::OCTOBER);

        $this->assertSame(80.0, $callQuality['average']);
        $this->assertSame(2, $callQuality['count']);
        $this->assertSame('very_good', $callQuality['level']);

        Livewire::test(KpiStatsWidget::class, ['pageFilters' => self::OCTOBER])
            ->assertOk()
            ->assertSee(__('admin.kpi_dashboard.stats.call_quality'))
            ->assertSee('80.0%')
            ->assertSee(CallQualityLevels::label('very_good'));
    }

    public function test_kpi_shows_a_dash_when_there_are_no_evaluations_in_the_range(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00'));
        $this->actingAsAdminWith(['crm_dashboard.viewAny', 'call_evaluations.viewAny']);

        $callQuality = app(KpiDashboardService::class)->callQuality(self::OCTOBER);

        $this->assertNull($callQuality['average']);
        $this->assertSame(0, $callQuality['count']);

        Livewire::test(KpiStatsWidget::class, ['pageFilters' => self::OCTOBER])
            ->assertOk()
            ->assertSee(__('admin.kpi_dashboard.stats.call_quality_none'));
    }

    public function test_kpi_tile_is_hidden_without_call_evaluations_view_any(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-15 12:00:00'));
        $viewer = $this->actingAsAdminWith(['crm_dashboard.viewAny']);
        $lead = ServiceRequest::factory()->create(['source' => 'form', 'phone' => '0551234567']);
        $this->evaluate($lead, null, 5, $viewer, '2026-10-05');

        $this->assertNull(app(KpiDashboardService::class)->callQuality(self::OCTOBER));

        Livewire::test(KpiStatsWidget::class, ['pageFilters' => self::OCTOBER])
            ->assertOk()
            ->assertDontSee(__('admin.kpi_dashboard.stats.call_quality'))
            ->assertDontSee('data-kpi="call-quality"', false);
    }
}
