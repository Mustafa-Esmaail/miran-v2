<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supported = ['en', 'ar'];
        $routeLang = $this->sanitizeLocale($request->route('lang'));
        $userLocale = $this->sanitizeLocale($request->user('web')?->locale);
        $sessionLocale = $this->sanitizeLocale($request->session()->get('app_locale'));
        $defaultLocale = $this->sanitizeLocale(app(SettingsService::class)->defaultLocale());

        if ($routeLang && in_array($routeLang, $supported, true)) {
            app()->setLocale($routeLang);
            $request->session()->put('app_locale', $routeLang);
        } elseif ($userLocale && in_array($userLocale, $supported, true)) {
            app()->setLocale($userLocale);
            $request->session()->put('app_locale', $userLocale);
        } else {
            if ($sessionLocale && in_array($sessionLocale, $supported, true)) {
                app()->setLocale($sessionLocale);
            } elseif ($defaultLocale && in_array($defaultLocale, $supported, true)) {
                app()->setLocale($defaultLocale);
                $request->session()->put('app_locale', $defaultLocale);
            }
        }

        return $next($request);
    }

    private function sanitizeLocale(?string $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if ($clean === false) {
            return null;
        }

        $clean = trim($clean);

        return $clean !== '' ? $clean : null;
    }
}
