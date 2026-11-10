<?php

namespace Tests\Feature\Api\V1;

use App\Models\ApiClient;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiClientAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'mlms_test_key_0123456789abcdefghijklmnopqrstuvwxyzAB';

    public function test_request_without_a_key_is_rejected(): void
    {
        $this->getJson('/api/v1/orders')
            ->assertUnauthorized()
            ->assertJson(['code' => 'missing_api_key']);
    }

    public function test_request_with_an_unknown_key_is_rejected(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create();

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY.'x'])
            ->assertUnauthorized()
            ->assertJson(['code' => 'invalid_api_key']);
    }

    public function test_rejection_is_json_even_without_an_accept_header(): void
    {
        $this->get('/api/v1/orders')
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson(['code' => 'missing_api_key']);
    }

    public function test_unauthenticated_lookup_of_a_missing_order_does_not_reveal_it_exists(): void
    {
        Order::factory()->create(['order_no' => 'ORD-EXISTS']);

        $this->getJson('/api/v1/orders/ORD-EXISTS')->assertUnauthorized();
        $this->getJson('/api/v1/orders/ORD-MISSING')->assertUnauthorized();
    }

    public function test_revoked_key_is_rejected(): void
    {
        ApiClient::factory()->withKey(self::KEY)->revoked()->create();

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])
            ->assertUnauthorized()
            ->assertJson(['code' => 'api_key_revoked']);
    }

    public function test_expired_key_is_rejected(): void
    {
        ApiClient::factory()->withKey(self::KEY)->expired()->create();

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])
            ->assertUnauthorized()
            ->assertJson(['code' => 'api_key_expired']);
    }

    public function test_key_with_a_future_expiry_is_accepted(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create(['expires_at' => now()->addDay()]);

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertOk();
    }

    public function test_valid_key_is_accepted_via_header_or_bearer_token(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create();

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertOk();
        $this->getJson('/api/v1/orders', ['Authorization' => 'Bearer '.self::KEY])->assertOk();
    }

    public function test_request_from_an_ip_outside_the_allowlist_is_forbidden(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create(['allowed_ips' => ['203.0.113.10']]);

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])
            ->assertForbidden()
            ->assertJson(['code' => 'ip_not_allowed']);
    }

    public function test_request_from_an_allowlisted_cidr_range_is_accepted(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create(['allowed_ips' => ['127.0.0.0/8']]);

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertOk();
    }

    public function test_client_without_the_orders_ability_is_forbidden(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create(['abilities' => []]);

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])
            ->assertForbidden()
            ->assertJson(['code' => 'ability_denied']);
    }

    public function test_successful_request_records_last_usage(): void
    {
        $client = ApiClient::factory()->withKey(self::KEY)->create();

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertOk();

        $client->refresh();
        $this->assertNotNull($client->last_used_at);
        $this->assertSame('127.0.0.1', $client->last_used_ip);
    }

    public function test_requests_over_the_client_rate_limit_are_throttled(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create(['rate_limit_per_minute' => 2]);

        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertOk();
        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertOk();
        $this->getJson('/api/v1/orders', ['X-API-Key' => self::KEY])->assertTooManyRequests();
    }

    public function test_write_methods_are_not_available(): void
    {
        ApiClient::factory()->withKey(self::KEY)->create();

        $this->postJson('/api/v1/orders', [], ['X-API-Key' => self::KEY])->assertMethodNotAllowed();
        $this->deleteJson('/api/v1/orders/ORD-1', [], ['X-API-Key' => self::KEY])->assertMethodNotAllowed();
    }

    public function test_only_the_hash_of_the_key_is_stored(): void
    {
        $client = ApiClient::factory()->withKey(self::KEY)->create();

        $this->assertSame(hash('sha256', self::KEY), $client->key_hash);
        $this->assertDatabaseMissing('api_clients', ['key_hash' => self::KEY]);
        $this->assertArrayNotHasKey('key_hash', $client->toArray());
    }
}
