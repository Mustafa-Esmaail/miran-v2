<?php

namespace App\Http\Middleware;

use Closure;

class SetAdminSessionCookie
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle($request, Closure $next)
    {
        // Separate admin session cookie so admin and web CSRF tokens don't collide.
        // Path must stay '/' so Livewire's /livewire/update route sends the cookie.
        config(['session.cookie' => config('session.admin_cookie')]);

        return $next($request);
    }
}
