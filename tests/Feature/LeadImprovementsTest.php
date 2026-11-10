<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Orders\Pages\CreateOrder;
use App\Filament\Admin\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Employee;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\Team;
use App\Models\User;
use App\Services\ServiceRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LeadImprovementsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $permissions
     */
    private function actingAsAdminWith(array $permissions): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function makeStatus(string $slug, ?string $outcome): ServiceRequestStatus
    {
        return ServiceRequestStatus::query()->create([
            'name' => ['en' => ucfirst($slug), 'ar' => $slug],
            'slug' => $slug,
            'outcome' => $outcome,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function courseGroup(int $price = 1200): CourseGroup
    {
        $category = Category::query()->create(['name' => ['en' => 'Cat'], 'slug' => 'lead-cat-'.fake()->unique()->numberBetween(1, 99999), 'is_active' => true]);
        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Lead Course', 'ar' => 'Lead Course'],
            'summary' => ['en' => 'S'],
            'description' => ['en' => 'D'],
            'slug' => 'lead-course-'.fake()->unique()->numberBetween(1, 99999),
            'status' => 'published',
            'price' => $price,
        ]);

        return CourseGroup::query()->create(['course_id' => $course->id, 'title' => ['en' => 'G1'], 'group_price_amount' => $price, 'status' => 'open']);
    }

    public function test_follow_up_scopes_split_open_leads_by_status_date(): void
    {
        $this->makeStatus('callback', 'open');
        $this->makeStatus('won_deal', 'won');

        $overdue = ServiceRequest::factory()->create(['status' => 'callback', 'status_date' => now()->subDays(2)]);
        $dueToday = ServiceRequest::factory()->create(['status' => 'callback', 'status_date' => now()->setTime(18, 0)]);
        $upcoming = ServiceRequest::factory()->create(['status' => 'callback', 'status_date' => now()->addDays(3)]);
        $closedPast = ServiceRequest::factory()->create(['status' => 'won_deal', 'status_date' => now()->subDays(2)]);
        ServiceRequest::factory()->create(['status' => 'callback', 'status_date' => null]);

        $this->assertSame([$overdue->id], ServiceRequest::query()->followUpOverdue()->pluck('id')->all());
        $this->assertSame([$dueToday->id], ServiceRequest::query()->followUpDueToday()->pluck('id')->all());
        $this->assertSame([$upcoming->id], ServiceRequest::query()->followUpUpcoming()->pluck('id')->all());
        $this->assertNotContains($closedPast->id, ServiceRequest::query()->followUpOpen()->pluck('id')->all());
    }

    public function test_follow_up_table_filter_shows_only_overdue_leads(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view']);
        $this->makeStatus('callback', 'open');

        $overdue = ServiceRequest::factory()->create(['status' => 'callback', 'status_date' => now()->subDay()]);
        $upcoming = ServiceRequest::factory()->create(['status' => 'callback', 'status_date' => now()->addDays(2)]);

        Livewire::test(ListServiceRequests::class)
            ->filterTable('follow_up', 'overdue')
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$upcoming]);
    }

    public function test_possible_duplicates_match_any_stored_phone_format(): void
    {
        $local = ServiceRequest::factory()->create(['phone' => '0551234567']);
        $international = ServiceRequest::factory()->create(['phone' => '966551234567']);
        $other = ServiceRequest::factory()->create(['phone' => '0559999999']);

        $found = ServiceRequestService::findPossibleDuplicates('+966 55 123 4567')->pluck('id');

        $this->assertEqualsCanonicalizing([$local->id, $international->id], $found->all());
        $this->assertNotContains($other->id, $found->all());
        $this->assertTrue(ServiceRequestService::findPossibleDuplicates('')->isEmpty());
        $this->assertEqualsCanonicalizing(
            [$local->id, $international->id],
            ServiceRequestService::findPossibleDuplicates('00966 55 123 4567')->pluck('id')->all(),
        );
    }

    public function test_normalize_phone_handles_the_00_international_prefix(): void
    {
        $this->assertSame('966551234567', ServiceRequestService::normalizePhone('00966551234567'));
        $this->assertSame('966551234567', ServiceRequestService::normalizePhone('0551234567'));
        $this->assertSame('966551234567', ServiceRequestService::normalizePhone('+966 55 123 4567'));
    }

    public function test_create_form_warns_about_an_existing_lead_with_the_same_phone(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.create']);
        ServiceRequest::factory()->create(['phone' => '0551234567', 'name' => 'Existing Lead Name']);

        Livewire::test(CreateServiceRequest::class)
            ->set('data.phone', '966551234567')
            ->assertSee(__('admin.labels.possible_duplicates'))
            ->assertSee('Existing Lead Name')
            ->set('data.phone', '0500000000')
            ->assertDontSee('Existing Lead Name');
    }

    public function test_create_order_from_lead_prefills_and_links_the_lead(): void
    {
        $this->actingAsAdminWith(['orders.viewAny', 'orders.create', 'service_requests.update']);
        $customer = User::factory()->create();
        $group = $this->courseGroup(1200);
        $lead = ServiceRequest::factory()->create(['user_id' => $customer->id, 'course_group_id' => $group->id]);

        Livewire::withQueryParams(['service_request' => $lead->id])
            ->test(CreateOrder::class)
            ->assertSet('data.user_id', $customer->id)
            ->assertSet('data.purchasable_type', CourseGroup::class)
            ->assertSet('data.purchasable_id', $group->id)
            ->assertSet('data.price_amount', 1200)
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::query()->sole();
        $lead->refresh();

        $this->assertSame($order->id, $lead->converted_order_id);
        $this->assertNotNull($lead->converted_at);
    }

    public function test_create_order_ignores_a_lead_the_user_cannot_update(): void
    {
        $this->actingAsAdminWith(['orders.viewAny', 'orders.create']);
        $lead = ServiceRequest::factory()->create(['user_id' => User::factory()->create()->id]);

        Livewire::withQueryParams(['service_request' => $lead->id])
            ->test(CreateOrder::class)
            ->assertSet('data.user_id', null);
    }

    public function test_link_existing_order_action_converts_the_lead(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'service_requests.update']);
        $customer = User::factory()->create();
        $lead = ServiceRequest::factory()->create(['user_id' => $customer->id, 'source' => 'form']);
        $order = Order::factory()->create(['user_id' => $customer->id]);

        Livewire::test(ViewServiceRequest::class, ['record' => $lead->getKey()])
            ->assertActionVisible('link_order_to_lead')
            ->callAction('link_order_to_lead', data: ['order_id' => $order->id])
            ->assertHasNoActionErrors();

        $this->assertSame($order->id, $lead->refresh()->converted_order_id);
    }

    public function test_conversion_actions_are_hidden_for_complaints_converted_leads_and_without_permission(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'service_requests.update', 'orders.create']);

        $complaint = ServiceRequest::factory()->create(['source' => 'complaint']);
        $converted = ServiceRequest::factory()->create(['source' => 'form', 'converted_order_id' => Order::factory()->create()->id]);

        Livewire::test(ViewServiceRequest::class, ['record' => $complaint->getKey()])
            ->assertActionHidden('create_order_from_lead')
            ->assertActionHidden('link_order_to_lead');

        Livewire::test(ViewServiceRequest::class, ['record' => $converted->getKey()])
            ->assertActionHidden('create_order_from_lead')
            ->assertActionHidden('link_order_to_lead');

        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view']);
        $open = ServiceRequest::factory()->create(['source' => 'form']);

        Livewire::test(ViewServiceRequest::class, ['record' => $open->getKey()])
            ->assertActionHidden('create_order_from_lead')
            ->assertActionHidden('link_order_to_lead');
    }

    public function test_reassign_row_action_moves_the_lead_and_is_gated(): void
    {
        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view', 'service_requests.assign']);

        $team = Team::factory()->create();
        $agent = User::factory()->create();
        Employee::factory()->create(['user_id' => $agent->id, 'team_id' => $team->id, 'is_active' => true]);
        $lead = ServiceRequest::factory()->create(['assigned_to' => null]);

        Livewire::test(ListServiceRequests::class)
            ->callTableAction('reassign', $lead, data: ['team_id' => $team->id, 'assigned_to' => $agent->id])
            ->assertHasNoTableActionErrors();

        $lead->refresh();
        $this->assertSame($team->id, $lead->team_id);
        $this->assertSame($agent->id, $lead->assigned_to);

        $this->actingAsAdminWith(['service_requests.viewAny', 'service_requests.view']);

        Livewire::test(ListServiceRequests::class)
            ->assertTableActionHidden('reassign', $lead);
    }
}
