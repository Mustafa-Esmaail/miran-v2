<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\Companies\Pages\EditCompany;
use App\Filament\Admin\Resources\Deals\Pages\CreateDeal;
use App\Filament\Admin\Resources\Deals\Pages\EditDeal;
use App\Filament\Admin\Resources\Deals\RelationManagers\DealsRelationManager;
use App\Filament\Admin\Resources\Deals\RelationManagers\QuotesRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\B2bServiceRequestResource;
use App\Filament\Admin\Resources\ServiceRequests\Pages\EditB2bServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\ActivitiesRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\CrmActivitiesRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\CrmTasksRelationManager;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DealAndQuoteFormSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithCrmPermissions(): User
    {
        $admin = User::factory()->create();

        $this->actingAs($admin, 'admin');

        $permissions = [];
        foreach (['deals', 'quotes', 'companies'] as $resource) {
            foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $action) {
                Permission::query()->create(['name' => "{$resource}.{$action}", 'guard_name' => 'admin']);
                $permissions[] = "{$resource}.{$action}";
            }
        }
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function makeCompany(): Company
    {
        return Company::query()->create(['name' => 'Acme Corp']);
    }

    public function test_deal_create_page_groups_fields_into_sections_and_saves(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $company = $this->makeCompany();

        Livewire::test(CreateDeal::class)
            ->assertOk()
            ->assertSee(__('admin.sections.details'))
            ->assertSee(__('admin.sections.status'))
            ->fillForm([
                'title' => 'Sectioned Deal',
                'company_id' => $company->id,
                'stage' => DealStage::Prospecting->value,
                'probability' => 40,
                'value' => 5000,
                'notes' => 'Deal notes',
                'lost_reason' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('deals', [
            'title' => 'Sectioned Deal',
            'company_id' => $company->id,
            'probability' => 40,
            'notes' => 'Deal notes',
        ]);
    }

    public function test_deal_create_page_still_requires_title_and_company(): void
    {
        $this->actingAsAdminWithCrmPermissions();

        Livewire::test(CreateDeal::class)
            ->fillForm(['title' => null, 'company_id' => null])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'company_id' => 'required']);
    }

    public function test_deals_relation_manager_creates_deal_from_sectioned_form(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $company = $this->makeCompany();

        Livewire::test(DealsRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => EditCompany::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'Company Deal',
                'stage' => DealStage::Prospecting->value,
                'probability' => 25,
                'value' => 1000,
                'notes' => 'From relation manager',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('deals', [
            'title' => 'Company Deal',
            'company_id' => $company->id,
            'notes' => 'From relation manager',
        ]);
    }

    public function test_quotes_relation_manager_creates_quote_from_sectioned_form(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $deal = Deal::query()->create([
            'company_id' => $this->makeCompany()->id,
            'title' => 'Quoted Deal',
            'stage' => DealStage::Prospecting->value,
        ]);

        Livewire::test(QuotesRelationManager::class, [
            'ownerRecord' => $deal,
            'pageClass' => EditDeal::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make('create')->table(), data: [
                'status' => QuoteStatus::Draft->value,
                'notes' => 'Quote notes',
                'items' => [],
                'subtotal' => 900,
                'discount' => 100,
                'total' => 800,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quotes', [
            'deal_id' => $deal->id,
            'notes' => 'Quote notes',
            'total' => 800,
        ]);
    }

    public function test_b2b_resource_shows_history_tasks_deals_and_quotes_but_not_crm_activities(): void
    {
        $relations = B2bServiceRequestResource::getRelations();

        $this->assertSame([
            ActivitiesRelationManager::class,
            CrmTasksRelationManager::class,
            DealsRelationManager::class,
            QuotesRelationManager::class,
        ], $relations);
        $this->assertNotContains(CrmActivitiesRelationManager::class, $relations);
    }

    public function test_deals_tab_on_b2b_request_requires_company_and_links_deal_to_request(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $company = $this->makeCompany();
        $request = $this->makeB2bRequest();

        $component = Livewire::test(DealsRelationManager::class, [
            'ownerRecord' => $request,
            'pageClass' => EditB2bServiceRequest::class,
        ])->assertOk();

        $component
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'No Company Deal',
                'company_id' => null,
            ])
            ->assertHasFormErrors(['company_id' => 'required']);

        $component
            ->callAction(TestAction::make('create')->table(), data: [
                'title' => 'B2B Deal',
                'company_id' => $company->id,
                'stage' => DealStage::Prospecting->value,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('deals', [
            'title' => 'B2B Deal',
            'company_id' => $company->id,
            'service_request_id' => $request->id,
        ]);
    }

    public function test_quotes_tab_on_b2b_request_lists_quotes_across_its_deals_only(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $request = $this->makeB2bRequest();
        $ownQuote = Quote::query()->create([
            'deal_id' => $this->makeDeal($request)->id,
            'status' => QuoteStatus::Draft->value,
            'quote_no' => 'QT-OWN',
        ]);
        $otherQuote = Quote::query()->create([
            'deal_id' => $this->makeDeal($this->makeB2bRequest())->id,
            'status' => QuoteStatus::Draft->value,
            'quote_no' => 'QT-OTHER',
        ]);

        Livewire::test(QuotesRelationManager::class, [
            'ownerRecord' => $request,
            'pageClass' => EditB2bServiceRequest::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$ownQuote])
            ->assertCanNotSeeTableRecords([$otherQuote]);
    }

    public function test_quotes_tab_on_b2b_request_creates_quote_on_selected_deal(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $request = $this->makeB2bRequest();
        $deal = $this->makeDeal($request);

        Livewire::test(QuotesRelationManager::class, [
            'ownerRecord' => $request,
            'pageClass' => EditB2bServiceRequest::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'deal_id' => $deal->id,
                'status' => QuoteStatus::Draft->value,
                'notes' => 'B2B quote',
                'total' => 500,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quotes', [
            'deal_id' => $deal->id,
            'notes' => 'B2B quote',
            'total' => 500,
        ]);
    }

    public function test_quotes_tab_on_b2b_request_rejects_missing_or_foreign_deal(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $request = $this->makeB2bRequest();
        $this->makeDeal($request);
        $foreignDeal = $this->makeDeal($this->makeB2bRequest());

        $component = Livewire::test(QuotesRelationManager::class, [
            'ownerRecord' => $request,
            'pageClass' => EditB2bServiceRequest::class,
        ]);

        $component
            ->callAction(TestAction::make('create')->table(), data: [
                'deal_id' => null,
                'status' => QuoteStatus::Draft->value,
            ])
            ->assertHasFormErrors(['deal_id' => 'required']);

        $component
            ->callAction(TestAction::make('create')->table(), data: [
                'deal_id' => $foreignDeal->id,
                'status' => QuoteStatus::Draft->value,
            ])
            ->assertHasFormErrors(['deal_id']);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_quotes_tab_on_b2b_request_edits_quote(): void
    {
        $this->actingAsAdminWithCrmPermissions();
        $request = $this->makeB2bRequest();
        $quote = Quote::query()->create([
            'deal_id' => $this->makeDeal($request)->id,
            'status' => QuoteStatus::Draft->value,
            'total' => 100,
        ]);

        Livewire::test(QuotesRelationManager::class, [
            'ownerRecord' => $request,
            'pageClass' => EditB2bServiceRequest::class,
        ])
            ->callAction(TestAction::make('edit')->table($quote), data: [
                'total' => 750,
                'notes' => 'Edited',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'total' => 750,
            'notes' => 'Edited',
        ]);
    }

    private function makeB2bRequest(): ServiceRequest
    {
        return ServiceRequest::factory()->create(['source' => ServiceRequestSource::CorporateTraining]);
    }

    private function makeDeal(ServiceRequest $request): Deal
    {
        return Deal::query()->create([
            'company_id' => $this->makeCompany()->id,
            'service_request_id' => $request->id,
            'title' => 'Deal for #'.$request->id,
            'stage' => DealStage::Prospecting->value,
        ]);
    }
}
