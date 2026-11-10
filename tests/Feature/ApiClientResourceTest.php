<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\ApiClients\ApiClientResource;
use App\Filament\Admin\Resources\ApiClients\Pages\CreateApiClient;
use App\Filament\Admin\Resources\ApiClients\Pages\ListApiClients;
use App\Models\ApiClient;
use App\Models\User;
use Database\Seeders\ApiClientPermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiClientResourceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsApiClientManager(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        $this->seed(ApiClientPermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * The plain key is only ever passed to the "show key" modal.
     */
    private function plainKeyFromModal(Testable $component): string
    {
        $component->assertActionMounted('showApiKey');

        $mounted = collect($component->get('mountedActions'))->firstWhere('name', 'showApiKey');

        return (string) ($mounted['arguments']['key'] ?? '');
    }

    public function test_seeder_grants_permissions_to_admin_roles_only(): void
    {
        $admin = Role::query()->create(['name' => 'admin', 'guard_name' => 'admin']);
        $superAdmin = Role::query()->create(['name' => 'super-admin', 'guard_name' => 'admin']);
        $sales = Role::query()->create(['name' => 'sales', 'guard_name' => 'admin']);

        $this->seed(ApiClientPermissionSeeder::class);

        $this->assertSame(5, Permission::query()->where('name', 'like', 'api_clients.%')->count());
        $this->assertTrue($admin->hasPermissionTo('api_clients.create'));
        $this->assertTrue($superAdmin->hasPermissionTo('api_clients.update'));
        $this->assertFalse($sales->fresh()->hasPermissionTo('api_clients.viewAny'));
    }

    public function test_admin_without_permission_cannot_open_the_resource(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create(), 'admin');

        $this->get(ApiClientResource::getUrl('index'))->assertForbidden();
    }

    public function test_creating_a_client_shows_the_key_once_and_stores_only_its_hash(): void
    {
        $admin = $this->actingAsApiClientManager();

        $component = Livewire::test(CreateApiClient::class)
            ->fillForm([
                'name' => 'ERP',
                'abilities' => [ApiClient::ABILITY_ORDERS],
                'rate_limit_per_minute' => 60,
                'allowed_ips' => ['127.0.0.1'],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNoRedirect();

        $client = ApiClient::query()->sole();
        $plainKey = $this->plainKeyFromModal($component);

        $this->assertNotSame('', $plainKey);
        $this->assertSame(hash('sha256', $plainKey), $client->key_hash);
        $this->assertStringStartsWith($client->key_prefix, $plainKey);
        $this->assertSame($admin->id, $client->created_by);

        $this->getJson('/api/v1/orders', ['X-API-Key' => $plainKey])->assertOk();

        $component->callMountedAction()
            ->assertRedirect(ApiClientResource::getUrl('index'));
    }

    public function test_allowed_ips_must_be_valid_ips_or_cidr_ranges(): void
    {
        $this->actingAsApiClientManager();

        Livewire::test(CreateApiClient::class)
            ->fillForm([
                'name' => 'ERP',
                'abilities' => [ApiClient::ABILITY_ORDERS],
                'rate_limit_per_minute' => 60,
                'allowed_ips' => ['10.0.0.0/8', 'not-an-ip'],
            ])
            ->call('create')
            ->assertHasFormErrors(['allowed_ips.1']);

        $this->assertDatabaseCount('api_clients', 0);
    }

    public function test_rotating_a_key_invalidates_the_old_one(): void
    {
        $this->actingAsApiClientManager();
        $oldKey = 'mlms_old_key_0123456789abcdefghijklmnopqrstuvwxyzABCD';
        $client = ApiClient::factory()->withKey($oldKey)->create();

        $component = Livewire::test(ListApiClients::class)->callTableAction('rotate_key', $client);

        $newKey = $this->plainKeyFromModal($component);
        $this->assertNotSame('', $newKey);
        $this->assertSame(hash('sha256', $newKey), $client->fresh()->key_hash);

        $this->getJson('/api/v1/orders', ['X-API-Key' => $oldKey])->assertUnauthorized();
        $this->getJson('/api/v1/orders', ['X-API-Key' => $newKey])->assertOk();
    }

    public function test_revoking_a_key_blocks_api_access(): void
    {
        $this->actingAsApiClientManager();
        $key = 'mlms_revoke_key_0123456789abcdefghijklmnopqrstuvwxyz';
        $client = ApiClient::factory()->withKey($key)->create();

        Livewire::test(ListApiClients::class)->callTableAction('revoke', $client);

        $this->assertNotNull($client->fresh()->revoked_at);
        $this->getJson('/api/v1/orders', ['X-API-Key' => $key])
            ->assertUnauthorized()
            ->assertJson(['code' => 'api_key_revoked']);
    }
}
