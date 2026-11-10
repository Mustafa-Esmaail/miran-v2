<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestCallButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_column_links_to_a_normalized_tel_href(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin');

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        Permission::query()->create(['name' => 'service_requests.viewAny', 'guard_name' => 'admin']);
        $admin->assignRole($role);
        $admin->givePermissionTo('service_requests.viewAny');

        ServiceRequest::factory()->create(['phone' => '0501234567']);

        Livewire::test(ListServiceRequests::class)
            ->assertOk()
            ->assertSee('tel:966501234567', false);
    }
}
