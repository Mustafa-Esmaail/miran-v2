<?php

namespace Tests\Feature;

use App\Enums\DealStage;
use App\Filament\Admin\Resources\Deals\Pages\ListDeals;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DealKanbanTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithDealAccess(): User
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        Permission::query()->firstOrCreate(['name' => 'deals.viewAny', 'guard_name' => 'admin']);
        $admin->givePermissionTo('deals.viewAny');

        return $admin;
    }

    public function test_column_header_shows_the_summed_value_of_its_deals(): void
    {
        $this->actingAsAdminWithDealAccess();

        Deal::factory()->stage(DealStage::Proposal)->create(['value' => 1_234_500]);
        Deal::factory()->stage(DealStage::Proposal)->create(['value' => 2_000_000]);
        Deal::factory()->stage(DealStage::Negotiation)->create(['value' => 7_000_000]);

        Livewire::test(ListDeals::class)
            ->assertOk()
            ->assertSee('SAR 32,345')
            ->assertSee('SAR 70,000')
            ->assertSeeHtml('data-column-total="'.DealStage::Proposal->value.'"');
    }

    public function test_column_totals_follow_the_active_filters(): void
    {
        $this->actingAsAdminWithDealAccess();

        $firstEmployee = Employee::factory()->create();
        $secondEmployee = Employee::factory()->create();

        Deal::factory()->stage(DealStage::Qualification)->create(['value' => 1_111_100, 'employee_id' => $firstEmployee->id]);
        Deal::factory()->stage(DealStage::Qualification)->create(['value' => 2_222_200, 'employee_id' => $firstEmployee->id]);
        Deal::factory()->stage(DealStage::Qualification)->create(['value' => 4_444_400, 'employee_id' => $secondEmployee->id]);

        $component = Livewire::test(ListDeals::class)
            ->assertSee('SAR 77,777');

        $component
            ->set('employeeId', (string) $firstEmployee->id)
            ->assertSee('SAR 33,333')
            ->assertDontSee('SAR 77,777')
            ->assertDontSee('SAR 44,444');
    }

    public function test_empty_columns_show_a_zero_total(): void
    {
        $this->actingAsAdminWithDealAccess();

        Livewire::test(ListDeals::class)
            ->assertOk()
            ->assertSee('SAR 0');
    }
}
