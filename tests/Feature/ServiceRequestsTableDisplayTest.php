<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestsTableDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdminWithServiceRequestsAccess(): User
    {
        $admin = User::factory()->create();
        $role = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $permission = Permission::query()->firstOrCreate(['name' => 'service_requests.viewAny', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    public function test_status_column_shows_the_stage_name_alongside_a_stage_linked_status(): void
    {
        $this->actingAsAdminWithServiceRequestsAccess();

        $status = ServiceRequestStatus::query()->where('slug', 'no_answer_2')->first();
        $this->assertNotNull($status, 'Expected the seeded Talabat stage data to be present.');
        $this->assertNotNull($status->stage);

        $record = ServiceRequest::factory()->create(['status' => 'no_answer_2']);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertSee($status->stage->getTranslatedName().' ('.$status->getTranslatedName().')');

        $record->delete();
    }

    public function test_status_column_shows_just_the_status_name_when_it_has_no_stage(): void
    {
        $this->actingAsAdminWithServiceRequestsAccess();

        $status = ServiceRequestStatus::query()->where('slug', 'new')->first();
        $this->assertNotNull($status);
        $this->assertNull($status->stage_id);

        ServiceRequest::factory()->create(['status' => 'new']);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertSee($status->getTranslatedName());
    }

    public function test_name_email_and_sheet_sync_columns_are_hidden_by_default(): void
    {
        $this->actingAsAdminWithServiceRequestsAccess();

        $record = ServiceRequest::factory()->create([
            'name' => 'Should Not Be Visible By Default',
            'email' => 'hidden-by-default@example.com',
        ]);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertDontSee($record->name)
            ->assertDontSee($record->email);
    }
}
