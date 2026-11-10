<?php

namespace Tests\Feature;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UnauthenticatedExceptionHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'logging.default' => 'null',
            'logging.channels.filament_error' => ['driver' => 'monolog', 'handler' => \Monolog\Handler\NullHandler::class],
            'services.telegram_errors' => [
                'enabled' => true,
                'bot_token' => 'test-token',
                'chat_id' => '-100123',
                'topic_id' => null,
                'level' => 'error',
                'dedup_seconds' => 300,
                'dedup_store' => 'array',
                'timeout' => 5,
            ],
        ]);

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    public function test_guest_visiting_protected_page_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    public function test_guest_json_request_receives_unauthenticated_response(): void
    {
        $this->getJson(route('dashboard'))
            ->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_authentication_exception_is_not_rendered_as_system_error(): void
    {
        Route::middleware('web')->get('/dashboard/_test-unauthenticated', function (): never {
            throw new AuthenticationException;
        });

        $this->get('/dashboard/_test-unauthenticated')
            ->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    public function test_unexpected_exceptions_are_still_reported_to_telegram(): void
    {
        Route::middleware('web')->get('/dashboard/_test-boom', function (): never {
            throw new \RuntimeException('Boom');
        });

        $this->get('/dashboard/_test-boom')
            ->assertStatus(500);

        Http::assertSentCount(1);
    }
}
