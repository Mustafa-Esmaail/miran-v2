<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureUtm
{
    public const KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    /**
     * Stores the ad click's UTM parameters in the session so a later lead
     * submission (a Livewire request without the landing query string) can
     * still be attributed. A new click carrying any UTM replaces the whole set.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $utm = [];

        foreach (self::KEYS as $key) {
            $value = $request->query($key);

            if (is_string($value) && trim($value) !== '') {
                $utm[$key] = mb_substr(trim($value), 0, 255);
            }
        }

        if ($utm !== []) {
            $request->session()->put('utm', $utm);
        }

        return $next($request);
    }
}
