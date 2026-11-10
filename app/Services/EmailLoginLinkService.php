<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Builds signed, expiring links placed in emails so a student can open a dashboard
 * page directly without typing their password. Expired or tampered links fall back
 * to the regular login page (see EmailLoginController).
 */
class EmailLoginLinkService
{
    public const VALID_FOR_DAYS = 3;

    /**
     * @param  string  $redirectUrl  Absolute or relative URL of the dashboard page to open after login.
     */
    public function urlFor(User $user, string $redirectUrl): string
    {
        return url(URL::temporarySignedRoute(
            'login.email',
            now()->addDays(self::VALID_FOR_DAYS),
            ['user' => $user->getKey(), 'redirect' => $this->safeRedirectPath($redirectUrl)],
            absolute: false,
        ));
    }

    /**
     * Only student dashboard paths on this site are allowed; anything else falls back to the dashboard home.
     */
    public function safeRedirectPath(?string $redirectUrl): string
    {
        $fallback = route('dashboard', absolute: false);

        if (blank($redirectUrl)) {
            return $fallback;
        }

        $path = parse_url($redirectUrl, PHP_URL_PATH);
        $query = parse_url($redirectUrl, PHP_URL_QUERY);

        if (! is_string($path) || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return $fallback;
        }

        if ($path !== '/dashboard' && ! str_starts_with($path, '/dashboard/')) {
            return $fallback;
        }

        return $query ? $path.'?'.$query : $path;
    }
}
