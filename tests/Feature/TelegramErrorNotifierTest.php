<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class TelegramErrorNotifierTest extends TestCase
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

    public function test_error_log_is_sent_to_telegram(): void
    {
        Log::error('Payment gateway exploded');

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
                && $request['chat_id'] === '-100123'
                && str_contains($request['text'], 'ERROR')
                && str_contains($request['text'], 'Payment gateway exploded');
        });
    }

    public function test_errors_on_any_channel_are_sent(): void
    {
        Log::channel('filament_error')->critical('Filament action failed');

        Http::assertSent(fn (Request $request): bool => str_contains($request['text'], 'CRITICAL')
            && str_contains($request['text'], 'Filament action failed'));
    }

    public function test_exception_details_are_included(): void
    {
        report(new RuntimeException('Boom from service'));

        Http::assertSent(fn (Request $request): bool => str_contains($request['text'], 'Exception: RuntimeException')
            && str_contains($request['text'], 'Boom from service')
            && str_contains($request['text'], 'TelegramErrorNotifierTest.php:'));
    }

    public function test_logs_below_threshold_are_not_sent(): void
    {
        Log::warning('Just a warning');
        Log::info('Just info');

        Http::assertNothingSent();
    }

    public function test_threshold_is_configurable(): void
    {
        config(['services.telegram_errors.level' => 'warning']);

        Log::warning('Disk almost full');

        Http::assertSentCount(1);
    }

    public function test_repeated_error_is_sent_only_once_within_window(): void
    {
        Log::error('Same failure');
        Log::error('Same failure');
        Log::error('Different failure');

        Http::assertSentCount(2);
    }

    public function test_same_exception_logged_on_two_channels_is_sent_once(): void
    {
        $exception = new RuntimeException('Duplicated');

        Log::error($exception->getMessage(), ['exception' => $exception]);
        Log::channel('filament_error')->error('[SYS-ERROR-ABC123] '.$exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        Http::assertSentCount(1);
    }

    public function test_nothing_is_sent_when_disabled(): void
    {
        config(['services.telegram_errors.enabled' => false]);

        Log::error('Should stay local');

        Http::assertNothingSent();
    }

    public function test_nothing_is_sent_without_credentials(): void
    {
        config(['services.telegram_errors.bot_token' => null]);

        Log::error('No token configured');

        Http::assertNothingSent();
    }

    public function test_notifier_failure_does_not_break_logging(): void
    {
        config(['services.telegram_errors.dedup_store' => 'missing-store']);

        Log::error('Error while notifier is broken');

        Http::assertNothingSent();
    }

    public function test_failed_send_is_retried_on_next_occurrence(): void
    {
        Http::swap(new HttpFactory);

        Http::fake(['api.telegram.org/*' => Http::sequence()
            ->push(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)
            ->push(['ok' => true])]);

        Log::error('Flaky failure');
        Log::error('Flaky failure');
        Log::error('Flaky failure');

        Http::assertSentCount(2);
    }
}
