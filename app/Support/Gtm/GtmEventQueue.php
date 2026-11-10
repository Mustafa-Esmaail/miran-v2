<?php

namespace App\Support\Gtm;

/**
 * Holds dataLayer events in the session until the next full page render.
 *
 * Livewire actions that redirect (add to cart → /cart) lose any browser event
 * dispatched before the redirect, so they queue here and the layout flushes it.
 */
class GtmEventQueue
{
    private const SESSION_KEY = 'gtm_events';

    /**
     * @param  array<string, mixed>  $data
     */
    public static function push(string $event, array $data = []): void
    {
        session()->push(self::SESSION_KEY, ['event' => $event, 'data' => $data]);
    }

    /**
     * @return array<int, array{event: string, data: array<string, mixed>}>
     */
    public static function pull(): array
    {
        return session()->pull(self::SESSION_KEY, []);
    }
}
