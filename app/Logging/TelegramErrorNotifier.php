<?php

namespace App\Logging;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Monolog\Level;
use Throwable;

/**
 * Forwards every log entry at or above the configured level (from any channel)
 * to a Telegram chat, de-duplicating repeats of the same error within a time window.
 */
class TelegramErrorNotifier
{
    private const MAX_MESSAGE_LENGTH = 3500;

    private static bool $sending = false;

    public function handle(MessageLogged $event): void
    {
        if (self::$sending || ! $this->isEnabled()) {
            return;
        }

        self::$sending = true;

        try {
            if (! $this->meetsLevel($event->level)) {
                return;
            }

            $config = config('services.telegram_errors');

            $dedupCache = Cache::store($config['dedup_store']);
            $dedupKey = 'telegram-error-log:'.sha1($this->fingerprint($event));

            if (! $dedupCache->add($dedupKey, true, max(1, (int) $config['dedup_seconds']))) {
                return;
            }

            try {
                $response = Http::timeout(max(1, (int) $config['timeout']))
                    ->connectTimeout(3)
                    ->asForm()
                    ->post('https://api.telegram.org/bot'.$config['bot_token'].'/sendMessage', array_filter([
                        'chat_id' => $config['chat_id'],
                        'message_thread_id' => $config['topic_id'] ?: null,
                        'text' => $this->formatMessage($event),
                        'disable_web_page_preview' => 'true',
                    ]));
            } catch (Throwable $exception) {
                $dedupCache->forget($dedupKey);

                throw $exception;
            }

            if ($response->failed()) {
                $dedupCache->forget($dedupKey);

                error_log('Telegram error notification failed: HTTP '.$response->status().' '.$response->body());
            }
        } catch (Throwable $exception) {
            error_log('Telegram error notification failed: '.$exception->getMessage());
        } finally {
            self::$sending = false;
        }
    }

    private function isEnabled(): bool
    {
        return (bool) config('services.telegram_errors.enabled')
            && filled(config('services.telegram_errors.bot_token'))
            && filled(config('services.telegram_errors.chat_id'));
    }

    private function meetsLevel(string $level): bool
    {
        $threshold = Level::fromName((string) config('services.telegram_errors.level', 'error'));

        return $threshold->includes(Level::fromName($level));
    }

    /**
     * The same exception reported by several channels (e.g. the default stack and
     * filament_error) shares class/file/line, so it only produces one message.
     */
    private function fingerprint(MessageLogged $event): string
    {
        $exception = $event->context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            return get_class($exception).'|'.$exception->getFile().'|'.$exception->getLine();
        }

        if (is_string($exception) && isset($event->context['file'], $event->context['line'])) {
            return $exception.'|'.$event->context['file'].'|'.$event->context['line'];
        }

        return $event->level.'|'.$event->message;
    }

    private function formatMessage(MessageLogged $event): string
    {
        $exception = $event->context['exception'] ?? null;

        $lines = [
            '🚨 '.Str::upper($event->level).' · '.config('app.name').' ('.app()->environment().')',
            '',
            Str::limit($event->message, 1500),
            '',
        ];

        if ($exception instanceof Throwable) {
            $lines[] = 'Exception: '.get_class($exception);
            $lines[] = 'File: '.$this->relativePath($exception->getFile()).':'.$exception->getLine();
        } elseif (is_string($exception)) {
            $lines[] = 'Exception: '.$exception;

            if (isset($event->context['file'], $event->context['line'])) {
                $lines[] = 'File: '.$this->relativePath((string) $event->context['file']).':'.$event->context['line'];
            }
        }

        $lines[] = 'Source: '.$this->source();

        array_push($lines, ...$this->clientLines());

        $userId = $this->userId();

        if ($userId !== null) {
            $lines[] = 'User: #'.$userId;
        }

        $lines[] = 'Time: '.now()->toDateTimeString();

        return Str::limit(implode("\n", $lines), self::MAX_MESSAGE_LENGTH);
    }

    private function source(): string
    {
        if (app()->runningInConsole()) {
            return 'console: '.implode(' ', array_slice($_SERVER['argv'] ?? ['artisan'], 0, 4));
        }

        return request()->method().' '.request()->fullUrl();
    }

    /**
     * Who sent the request: IP, any proxy-forwarded IPs, User-Agent and Referer.
     *
     * @return list<string>
     */
    private function clientLines(): array
    {
        if (app()->runningInConsole()) {
            return [];
        }

        try {
            $request = request();

            $forwarded = array_filter([
                'CF' => $request->header('CF-Connecting-IP'),
                'XFF' => $request->header('X-Forwarded-For'),
                'X-Real-IP' => $request->header('X-Real-IP'),
            ]);

            $lines = ['IP: '.($request->ip() ?? 'unknown')];

            foreach ($forwarded as $label => $value) {
                $lines[] = 'Forwarded ('.$label.'): '.Str::limit((string) $value, 200);
            }

            $lines[] = 'User-Agent: '.Str::limit((string) ($request->userAgent() ?: '(empty)'), 300);

            if ($referer = $request->header('Referer')) {
                $lines[] = 'Referer: '.Str::limit((string) $referer, 300);
            }

            return $lines;
        } catch (Throwable) {
            return [];
        }
    }

    private function userId(): int|string|null
    {
        try {
            return auth()->id();
        } catch (Throwable) {
            return null;
        }
    }

    private function relativePath(string $path): string
    {
        return Str::after($path, base_path().DIRECTORY_SEPARATOR);
    }
}
